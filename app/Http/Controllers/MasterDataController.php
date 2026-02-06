<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response; // Untuk Download Template CSV On-the-fly

use App\Exports\ProductTemplateExport; // <--- Tambahkan ini


// === IMPORT MODEL ===
use App\Models\Product;
use App\Models\Machine;
use App\Models\ProductionLine;
use App\Models\ProductRouting;
use App\Models\ActivityLog;

// === IMPORT EXCEL ===
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PartsImport; // Pastikan ini sesuai nama class import Anda

class MasterDataController extends Controller
{
    // 1. LIST DATA (INDEX)
    public function index(Request $request)
    {
        // Fitur Pencarian Sederhana
        $query = Product::with(['routings'])->withCount('routings');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('code_part', 'like', "%{$search}%")
                ->orWhere('part_name', 'like', "%{$search}%")
                ->orWhere('customer', 'like', "%{$search}%");
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('master_data.index', compact('products'));
    }

    // 2. FORM CREATE
    public function create()
    {
        $machines = Machine::with('productionLine')->orderBy('name')->get();
        $lines = ProductionLine::orderBy('name')->get();
        $plants = $lines->pluck('plant')->filter()->unique()->values();

        return view('master_data.form', compact('machines', 'plants', 'lines'));
    }

    // 3. FORM EDIT
    public function edit($id)
    {
        $product = Product::with('routings')->findOrFail($id);

        $machines = Machine::with('productionLine')->orderBy('name')->get();
        $lines = ProductionLine::orderBy('name')->get();
        $plants = $lines->pluck('plant')->filter()->unique()->values();

        return view('master_data.form', compact('product', 'machines', 'plants', 'lines'));
    }

    // 4. SIMPAN DATA (STORE/UPDATE)
    public function store(Request $request, $id = null)
    {
        // A. VALIDASI INPUT
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'code_part' => [
                'required',
                'string',
                'max:50',
                'min:3',
                // Validasi Unik (Ignore ID jika sedang Edit)
                \Illuminate\Validation\Rule::unique('products', 'code_part')->ignore($id)->whereNull('deleted_at')
            ],
            'part_number' => 'required|string',
            'part_name' => 'required|string',
            'category' => 'required|string',
            'customer' => 'required|string',

            // --- [VALIDASI TAMBAHAN BARU] ---
            'kode_box' => 'nullable|string|max:50',
            'kanban_type' => 'required|in:PRODUCTION,SUBCONT,FINISH_GOODS', // <--- PENTING: Validasi Tipe Kanban
            // --------------------------------

            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // Validasi Routings
            'routings' => 'nullable|array',
            'routings.*.machine_id' => 'required|exists:machines,id',
            'routings.*.production_line_id' => 'required|exists:production_lines,id',
            'routings.*.process_name' => 'required|string',
            'routings.*.pcs_per_hour' => 'required|numeric|min:1',
            'routings.*.manpower_ratio' => 'nullable|numeric|min:0.1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // B. HITUNG CYCLE TIME (BOTTLENECK)
            // Mencari kapasitas terendah (Min PCS/Hour) dari semua routing
            $bottleneckCT = 0;
            $minPcsPerHour = 999999;

            if ($request->has('routings') && count($request->routings) > 0) {
                foreach ($request->routings as $r) {
                    $val = (int) ($r['pcs_per_hour'] ?? 0);
                    if ($val > 0 && $val < $minPcsPerHour) {
                        $minPcsPerHour = $val;
                    }
                }
                // Jika ketemu nilai valid, hitung Cycle Time (3600 detik / output per jam)
                if ($minPcsPerHour < 999999) {
                    $bottleneckCT = 3600 / $minPcsPerHour;
                }
            }

            // Siapkan Data Header
            // $request->except(...) otomatis mengambil semua input form termasuk 'kode_box' dan 'kanban_type'
            $data = $request->except(['_token', 'routings', 'photo']);
            $data['cycle_time'] = $bottleneckCT;

            // C. UPLOAD FOTO (JIKA ADA)
            if ($request->hasFile('photo')) {
                // Hapus foto lama jika edit
                if ($id) {
                    $existingProduct = \App\Models\Product::find($id);
                    if ($existingProduct && $existingProduct->photo) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($existingProduct->photo);
                    }
                }
                $path = $request->file('photo')->store('products', 'public');
                $data['photo'] = $path;
            }

            // D. SIMPAN / UPDATE HEADER PRODUCT
            if ($id) {
                $product = \App\Models\Product::findOrFail($id);
                $product->update($data); // Update termasuk kanban_type & kode_box

                $logAction = 'UPDATE MASTER PART';
                $logDesc = "Memperbarui Master Part: {$product->code_part}";
            } else {
                $product = \App\Models\Product::create($data); // Create termasuk kanban_type & kode_box

                $logAction = 'CREATE MASTER PART';
                $logDesc = "Membuat Master Part Baru: {$product->code_part}";
            }

            // E. SIMPAN DETAIL ROUTING (REPLACE STRATEGY)
            // Hapus routing lama, lalu insert ulang yang baru (lebih aman daripada update satu2)
            \App\Models\ProductRouting::where('product_id', $product->id)->delete();

            if ($request->has('routings')) {
                foreach ($request->routings as $index => $route) {
                    \App\Models\ProductRouting::create([
                        'product_id' => $product->id,
                        'production_line_id' => $route['production_line_id'],
                        'machine_id' => $route['machine_id'],
                        'process_name' => $route['process_name'],
                        'plant' => $route['plant'] ?? null,
                        'pcs_per_hour' => $route['pcs_per_hour'],
                        'manpower_ratio' => $route['manpower_ratio'] ?? 1,
                        // Opsional: Simpan urutan berdasarkan index array
                        // 'seq'             => $index + 1, 
                    ]);
                }
            }

            // F. CATAT LOG AKTIVITAS
            // Pastikan Anda punya model ActivityLog
            if (class_exists(\App\Models\ActivityLog::class)) {
                \App\Models\ActivityLog::create([
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'user_name' => \Illuminate\Support\Facades\Auth::user()->name ?? 'System',
                    'action' => $logAction,
                    'description' => $logDesc
                ]);
            }

            DB::commit();
            return redirect()->route('master.index')->with('success', 'Data Part berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            // Log error untuk developer (opsional)
            // \Illuminate\Support\Facades\Log::error($e->getMessage());

            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
    // 5. DELETE (SOFT DELETE)
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $partInfo = "{$product->code_part} - {$product->part_name}";
            $product->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'DELETE MASTER PART',
                'description' => "Menghapus (Arsip) Master Part: {$partInfo}"
            ]);

            return back()->with('success', 'Part berhasil dihapus (diarsip).');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    // 6. DOWNLOAD TEMPLATE (Excel .xlsx)
    public function downloadTemplate()
    {
        // Download sebagai file Excel .xlsx agar kolom rapi otomatis
        return Excel::download(new ProductTemplateExport, 'template_part_routing.xlsx');
    }

    // 7. IMPORT EXCEL
    public function import(Request $request)
    {
        // 1. Validasi File
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            // 2. Cek apakah file terbaca
            // dd('File masuk controller', $request->file('file')); // <--- Uncomment ini kalau mau cek file masuk/tidak

            // 3. Proses Import
            Excel::import(new PartsImport, $request->file('file'));

            // Log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'IMPORT MASTER PART',
                'description' => "Import data dari: " . $request->file('file')->getClientOriginalName()
            ]);

            return back()->with('success', 'Data Part & Routing berhasil diimport!');

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Tangkap Error Validasi Excel spesifik
            $failures = $e->failures();
            dd($failures);

        } catch (\Exception $e) {
            // ==============================================
            // INI DEBUGGING NYA (HAPUS JIKA SUDAH FIX)
            // ==============================================
            dd([
                'STATUS' => 'ERROR TERJADI SAAT IMPORT',
                'PESAN' => $e->getMessage(),
                'BARIS' => $e->getLine(),
                'FILE' => $e->getFile(),
                'TRACE' => $e->getTraceAsString()
            ]);
        }
    }
}