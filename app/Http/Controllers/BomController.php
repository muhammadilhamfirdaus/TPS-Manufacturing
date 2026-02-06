<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Bom; // <--- PASTIKAN MODEL INI ADA (Sesuai revisi sebelumnya)
use App\Models\ActivityLog; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; 

use App\Exports\BomTemplateExport;
use App\Imports\BomsImport;
use Maatwebsite\Excel\Facades\Excel;

class BomController extends Controller
{
    /**
     * TAMPILKAN HALAMAN KELOLA BOM (DETAIL)
     * URL: /bom-management/{id}/manage
     */
    public function index($productId)
    {
        // 1. Ambil data Parent
        $parent = Product::findOrFail($productId);

        // 2. [REVISI] Ambil BOM Detail lewat Model Bom agar bisa di-sort by Sequence
        // Kita menggunakan with('childProduct') relasi yang ada di model Bom.php
        $bomDetails = Bom::with('childProduct')
            ->where('parent_product_id', $productId)
            ->orderBy('sequence', 'asc') // Urutkan berdasarkan hasil Drag & Drop
            ->get();

        // 3. Ambil semua produk untuk Dropdown (KECUALI diri sendiri)
        $allProducts = Product::where('id', '!=', $productId)
            ->select('id', 'code_part', 'part_number', 'part_name', 'category')
            ->orderBy('code_part', 'asc')
            ->get();

        return view('bom.index', compact('parent', 'bomDetails', 'allProducts'));
    }

    /**
     * TAMBAH KOMPONEN (CHILD) KE DALAM BOM
     * URL: /bom-management/{id}/store
     */
    public function store(Request $request, $productId)
    {
        // 1. Validasi Input
        $request->validate([
            'child_product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.00000001',
        ]);

        // 2. Validasi Logika: Tidak boleh memasukkan dirinya sendiri
        if ($request->child_product_id == $productId) {
            return back()->withErrors('Error: Tidak bisa menjadikan Part ini sebagai komponen untuk dirinya sendiri.');
        }

        // 3. Cek Duplikasi menggunakan Model Bom
        $exists = Bom::where('parent_product_id', $productId)
            ->where('child_product_id', $request->child_product_id)
            ->exists();

        if ($exists) {
            return back()->withErrors('Gagal: Komponen ini sudah ada di dalam BOM, silahkan edit qty saja.');
        }

        // 4. Simpan ke Database
        DB::beginTransaction();
        try {
            $parent = Product::findOrFail($productId);
            $child = Product::find($request->child_product_id);

            // [REVISI] Cari sequence terakhir agar item baru masuk di paling bawah
            $lastSeq = Bom::where('parent_product_id', $productId)->max('sequence');

            // [REVISI] Gunakan Bom::create daripada attach() untuk kontrol sequence
            Bom::create([
                'parent_product_id' => $productId,
                'child_product_id'  => $request->child_product_id,
                'quantity'          => $request->quantity,
                'sequence'          => $lastSeq + 1
            ]);

            // Catat Activity Log 
            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'UPDATE BOM (ADD)',
                'description' => "Menambahkan Komponen: {$child->code_part} ({$child->part_name}) ke BOM Induk: {$parent->code_part} - Qty: {$request->quantity}"
            ]);

            DB::commit();
            return back()->with('success', 'Komponen berhasil ditambahkan ke BOM!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * [BARU] UPDATE QUANTITY KOMPONEN
     * URL: /bom-management/{childId}/update
     * Method: PUT
     */
    public function update(Request $request, $childId)
    {
        // Validasi
        $request->validate([
            'quantity' => 'required|numeric|min:0.00000001',
        ]);

        // Cari berdasarkan ID tabel bom_details
        $bomDetail = Bom::findOrFail($childId);
        
        // Update Quantity
        $bomDetail->update([
            'quantity' => $request->quantity
        ]);

        return back()->with('success', 'Quantity berhasil diperbarui!');
    }

    /**
     * [BARU] REORDER SEQUENCE (DRAG & DROP)
     * URL: /bom-management/reorder
     * Method: POST (AJAX)
     */
    public function reorder(Request $request)
    {
        $orderData = $request->order; // Array dari JS berisi {id, sequence}

        if($orderData) {
            foreach ($orderData as $row) {
                // Update kolom sequence berdasarkan ID baris
                Bom::where('id', $row['id'])->update([
                    'sequence' => $row['sequence']
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Urutan diperbarui']);
    }

    /**
     * HAPUS KOMPONEN DARI BOM
     * URL: /bom-management/{parent}/{child}
     * Note: $childId disini sekarang menerima ID dari tabel bom_details (Primary Key)
     */
    public function destroy($productId, $childId)
    {
        DB::beginTransaction();
        try {
            // [REVISI] Ambil data Bom detailnya dulu sebelum dihapus (untuk keperluan Log)
            // childId di route sekarang mengacu pada ID tabel bom_details
            $bomDetail = Bom::with(['parentProduct', 'childProduct'])->findOrFail($childId);

            $parentName = $bomDetail->parentProduct->code_part ?? '-';
            $childName  = $bomDetail->childProduct->code_part ?? '-';

            // Hapus data
            $bomDetail->delete();

            // Catat Activity Log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'UPDATE BOM (DELETE)',
                'description' => "Menghapus Komponen: {$childName} dari BOM Induk: {$parentName}"
            ]);

            DB::commit();
            return back()->with('success', 'Komponen berhasil dihapus dari BOM.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menghapus: ' . $e->getMessage());
        }
    }

    /**
     * LIST PRODUK YANG BUTUH BOM (HEADER)
     * Tidak ada perubahan logic, tetap sama.
     */
    public function list(Request $request)
    {
        $query = Product::withCount('bomComponents')
            ->whereIn('category', ['FINISH GOOD', 'SEMI FINISH', 'SEMI FINISH GOOD']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('part_name', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%")
                    ->orWhere('code_part', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('code_part', 'asc')->paginate(10);

        return view('bom.list', compact('products'));
    }

    // DOWNLOAD TEMPLATE (Tidak berubah)
    public function downloadTemplate()
    {
        return Excel::download(new BomTemplateExport, 'template_bom.xlsx');
    }

    // IMPORT EXCEL (Tidak berubah)
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new BomsImport, $request->file('file'));
            return back()->with('success', 'Data BOM berhasil diimport!');

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            dd($failures);
        } catch (\Exception $e) {
            dd([
                'STATUS' => 'ERROR IMPORT BOM',
                'PESAN' => $e->getMessage(),
                'FILE' => $e->getFile(),
                'LINE' => $e->getLine()
            ]);
        }
    }
}