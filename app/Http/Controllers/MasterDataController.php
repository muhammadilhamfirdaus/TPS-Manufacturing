<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

// === IMPORT MODEL ===
use App\Models\Product;
use App\Models\Machine;
use App\Models\ProductionLine;
use App\Models\ProductRouting;
use App\Models\ActivityLog;

// === IMPORT EXCEL ===
use App\Exports\ProductTemplateExport;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class MasterDataController extends Controller
{
    // 1. LIST DATA (INDEX)
    public function index()
    {
        $products = Product::with(['routings'])
            ->withCount('routings')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('master_data.index', compact('products'));
    }

    public function create()
    {
        // Ambil Machines beserta Line-nya
        $machines = Machine::with('productionLine')->orderBy('name')->get();
        
        // Ambil Lines (Pastikan kolom 'plant' terambil)
        $lines = ProductionLine::orderBy('name')->get(); 
        
        // Ambil Plants unik dari Lines
        $plants = $lines->pluck('plant')->filter()->unique()->values();

        return view('master_data.form', compact('machines', 'plants', 'lines'));
    }

    public function edit($id)
    {
        $product = Product::with('routings')->findOrFail($id);
        
        // SAMA SEPERTI CREATE
        $machines = Machine::with('productionLine')->orderBy('name')->get();
        $lines = ProductionLine::orderBy('name')->get(); 
        $plants = $lines->pluck('plant')->filter()->unique()->values();

        return view('master_data.form', compact('product', 'machines', 'plants', 'lines'));
    }

   // 4. SIMPAN DATA (STORE/UPDATE)
    public function store(Request $request, $id = null)
    {
        // ==========================================
        // 1. CEK VALIDASI (DEBUGGING MODE)
        // ==========================================
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            // Validasi Header Produk
            'code_part'   => [
                'required', 'string', 'max:50', 'min:3',
                Rule::unique('products', 'code_part')->ignore($id)->whereNull('deleted_at')
            ],
            'part_number' => 'required|string',
            'part_name'   => 'required|string',
            'category'    => 'required|string',
            'customer'    => 'required|string',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            
            // Validasi Array Routings
            'routings'    => 'nullable|array',
            
            // Validasi Detail Routing
            'routings.*.machine_id'         => 'required|exists:machines,id',
            'routings.*.production_line_id' => 'required|exists:production_lines,id',
            'routings.*.process_name'       => 'required|string',
            'routings.*.pcs_per_hour'       => 'required|numeric|min:1', 
            'routings.*.manpower_ratio'     => 'nullable|numeric|min:0.1', 
        ]);

        // JIKA VALIDASI GAGAL -> TAMPILKAN ERROR DI LAYAR (DD)
        if ($validator->fails()) {
            dd([
                'STATUS' => 'VALIDASI GAGAL',
                'PESAN ERROR' => $validator->errors()->all(),
                'INPUT DARI FORM' => $request->all()
            ]);
        }

        DB::beginTransaction();
        try {
            // 2. LOGIKA HITUNG CYCLE TIME
            $bottleneckCT = 0;
            $minPcsPerHour = 999999;

            if ($request->has('routings') && count($request->routings) > 0) {
                foreach ($request->routings as $r) {
                    $val = (int) ($r['pcs_per_hour'] ?? 0);
                    if ($val > 0 && $val < $minPcsPerHour) {
                        $minPcsPerHour = $val;
                    }
                }
                if ($minPcsPerHour < 999999) {
                    $bottleneckCT = 3600 / $minPcsPerHour;
                }
            }

            // Siapkan data Header
            $data = $request->except(['_token', 'routings', 'photo']);
            $data['cycle_time'] = $bottleneckCT;

            // 3. UPLOAD FOTO
            if ($request->hasFile('photo')) {
                if ($id) {
                    $existingProduct = Product::find($id);
                    if ($existingProduct && $existingProduct->photo) {
                        Storage::disk('public')->delete($existingProduct->photo);
                    }
                }
                $path = $request->file('photo')->store('products', 'public');
                $data['photo'] = $path;
            }

            // 4. SIMPAN HEADER
            if ($id) {
                $product = Product::findOrFail($id);
                $product->update($data);
                $logAction = 'UPDATE MASTER PART';
                $logDesc = "Memperbarui Master Part: {$product->code_part}";
            } else {
                $product = Product::create($data);
                $logAction = 'CREATE MASTER PART';
                $logDesc = "Membuat Master Part Baru: {$product->code_part}";
            }

            // 5. SIMPAN DETAIL ROUTING
            ProductRouting::where('product_id', $product->id)->delete();

            if ($request->has('routings')) {
                foreach ($request->routings as $index => $route) {
                    
                    // Debugging: Cek data sebelum masuk DB
                    // Jika error terjadi di loop tertentu, kita bisa lihat datanya
                    $dataRouting = [
                        'product_id'         => $product->id,
                        'production_line_id' => $route['production_line_id'],
                        'machine_id'         => $route['machine_id'],
                        'process_name'       => $route['process_name'],
                        'plant'              => $route['plant'] ?? null,
                        'pcs_per_hour'       => $route['pcs_per_hour'], 
                        'manpower_ratio'     => $route['manpower_ratio'] ?? 1,
                    ];

                    ProductRouting::create($dataRouting);
                }
            }

            // 6. CATAT LOG
            ActivityLog::create([
                'user_id'     => Auth::id(),
                'user_name'   => Auth::user()->name,
                'action'      => $logAction,
                'description' => $logDesc
            ]);

            DB::commit();
            return redirect()->route('master.index')->with('success', 'Data Part berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // ==========================================
            // JIKA ERROR DATABASE -> TAMPILKAN DI LAYAR
            // ==========================================
            dd([
                'STATUS' => 'ERROR DATABASE / SYSTEM',
                'PESAN ERROR' => $e->getMessage(),
                'BARIS ERROR' => $e->getLine(),
                'FILE ERROR' => $e->getFile(),
                'TRACE' => $e->getTraceAsString()
            ]);
            
            // return back()->withInput()->withErrors('Gagal menyimpan: ' . $e->getMessage());
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

    // 6. RESTORE
    public function restore($id)
    {
        $product = Product::withTrashed()->find($id);
        if ($product && $product->trashed()) {
            $product->restore();
            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'RESTORE MASTER PART',
                'description' => "Mengembalikan (Restore) Master Part: {$product->code_part}"
            ]);
            return back()->with('success', 'Part berhasil dikembalikan (Restore)!');
        }
        return back()->with('error', 'Data tidak ditemukan.');
    }

    // 7. EXPORT & IMPORT
    public function downloadTemplate()
    {
        return Excel::download(new ProductTemplateExport, 'master_part_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);
        try {
            Excel::import(new ProductsImport, $request->file('file'));
            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'IMPORT MASTER PART',
                'description' => "Import data Master Part dari file Excel: " . $request->file('file')->getClientOriginalName()
            ]);
            return back()->with('success', 'Import Sukses');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal Import: ' . $e->getMessage());
        }
    }
}