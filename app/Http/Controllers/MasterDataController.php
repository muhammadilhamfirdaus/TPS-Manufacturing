<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Machine;
use App\Models\ProductRouting;
use Illuminate\Support\Facades\DB;

// Import Excel Stuff
use App\Exports\ProductTemplateExport;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class MasterDataController extends Controller
{
    // 1. LIST DATA (INDEX)
    public function index()
    {
        $products = Product::withCount('routings') 
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('master_data.index', compact('products'));
    }

    // 2. FORM TAMBAH BARU
    public function create()
    {
        $machines = Machine::orderBy('name')->get();
        return view('master_data.form', compact('machines'));
    }

    // 3. FORM EDIT
    public function edit($id)
    {
        $product = Product::with('routings')->findOrFail($id);
        $machines = Machine::orderBy('name')->get();

        return view('master_data.form', compact('product', 'machines'));
    }

    // 4. SIMPAN DATA (STORE/UPDATE)
    public function store(Request $request, $id = null)
    {
        // 1. Validasi
        $request->validate([
            // [REVISI]: Code Part sekarang menjadi Unique Identifier (Tidak boleh kembar)
            'code_part'   => 'required|string|max:50|unique:products,code_part,' . $id, 
            
            // [REVISI]: Part Number sekarang boleh kembar (cukup required)
            'part_number' => 'required|string', 
            
            'part_name'   => 'required|string',
            'category'    => 'required|string',
            'customer'    => 'required|string',        
            
            // Validasi Array Routing
            'routings'    => 'nullable|array',
            'routings.*.machine_id' => 'required|exists:machines,id',
            'routings.*.process_name' => 'required|string',
            'routings.*.pcs_per_hour' => 'required|numeric|min:1', 
        ]);

        DB::beginTransaction();
        try {
            // 2. HITUNG REFERENSI GLOBAL (Bottleneck Cycle Time)
            $bottleneckCT = 0;
            $minPcsPerHour = 999999; 

            if ($request->has('routings')) {
                foreach ($request->routings as $r) {
                    $val = (int) $r['pcs_per_hour'];
                    if ($val > 0 && $val < $minPcsPerHour) {
                        $minPcsPerHour = $val;
                    }
                }
            }

            // Konversi Pcs/Jam ke C/T (Detik)
            if ($minPcsPerHour < 999999 && $minPcsPerHour > 0) {
                $bottleneckCT = 3600 / $minPcsPerHour;
            }

            // 3. Simpan Product (Header)
            // Ambil semua input kecuali token dan routings array
            $data = $request->except(['_token', 'routings']); 
            
            // Override cycle time dengan hasil kalkulasi bottleneck
            $data['cycle_time'] = $bottleneckCT; 

            if ($id) {
                $product = Product::findOrFail($id);
                $product->update($data);
            } else {
                $product = Product::create($data);
            }

            // 4. SIMPAN ROUTING DETIL
            if ($id) {
                // Hapus routing lama agar bersih (replace logic)
                ProductRouting::where('product_id', $product->id)->delete();
            }

            if ($request->has('routings')) {
                foreach ($request->routings as $route) {
                    ProductRouting::create([
                        'product_id'   => $product->id,
                        'machine_id'   => $route['machine_id'],
                        'process_name' => $route['process_name'],
                        'pcs_per_hour' => $route['pcs_per_hour'] 
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('master.index')->with('success', 'Data Part berhasil disimpan! (Code Part: ' . $product->code_part . ')');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors('Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // Method Delete (Hapus Part)
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return back()->with('success', 'Part berhasil dihapus.');
    }

    // Method EXPORT & IMPORT
    public function downloadTemplate()
    { 
        return Excel::download(new ProductTemplateExport, 'master_part.xlsx');
    }
    public function import(Request $request)
    { 
        $request->validate(['file' => 'required|mimes:xlsx,xls']);
        try {
            Excel::import(new ProductsImport, $request->file('file'));
            return back()->with('success', 'Import Sukses');
        } catch (\Exception $e) {
            return back()->withErrors('Gagal Import: ' . $e->getMessage());
        }
    }
}