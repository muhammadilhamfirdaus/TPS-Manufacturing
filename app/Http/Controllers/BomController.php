<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ActivityLog; // <--- Import Model ActivityLog
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // <--- Import Auth untuk user login

class BomController extends Controller
{
    /**
     * TAMPILKAN HALAMAN KELOLA BOM (DETAIL)
     * URL: /bom-management/{id}/manage
     */
    public function index($productId)
    {
        // 1. Ambil data Parent beserta komponen yang sudah ada
        $parent = Product::with(['bomComponents' => function($q) {
            // Urutkan komponen berdasarkan nama part biar rapi
            $q->orderBy('part_name', 'asc');
        }])->findOrFail($productId);
        
        // 2. Ambil semua produk untuk Dropdown (KECUALI diri sendiri)
        // Diurutkan berdasarkan Code Part agar mudah dicari operator
        $allProducts = Product::where('id', '!=', $productId)
                        ->select('id', 'code_part', 'part_number', 'part_name', 'category')
                        ->orderBy('code_part', 'asc') 
                        ->get();

        return view('bom.index', compact('parent', 'allProducts'));
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
            'quantity'         => 'required|numeric|min:0.0001',
        ]);

        // 2. Validasi Logika: Tidak boleh memasukkan dirinya sendiri
        if ($request->child_product_id == $productId) {
            return back()->withErrors('Error: Tidak bisa menjadikan Part ini sebagai komponen untuk dirinya sendiri.');
        }

        // 3. Cek Duplikasi: Apakah komponen ini sudah ada?
        $exists = DB::table('bom_details')
                    ->where('parent_product_id', $productId)
                    ->where('child_product_id', $request->child_product_id)
                    ->exists();

        if($exists) {
            return back()->withErrors('Gagal: Komponen ini sudah ada di dalam BOM, silahkan edit qty saja.');
        }

        // 4. Simpan ke Database & Catat Log
        DB::beginTransaction();
        try {
            $parent = Product::findOrFail($productId);
            $child  = Product::find($request->child_product_id); // Ambil data child untuk nama di log

            $parent->bomComponents()->attach($request->child_product_id, [
                'quantity' => $request->quantity
            ]);

            // [TAMBAHAN] Catat Activity Log 
            ActivityLog::create([
                'user_id'     => Auth::id(),
                'user_name'   => Auth::user()->name,
                'action'      => 'UPDATE BOM (ADD)',
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
     * HAPUS KOMPONEN DARI BOM
     * URL: /bom-management/{parent}/{child}
     */
    public function destroy($productId, $childId)
    {
        DB::beginTransaction();
        try {
            $parent = Product::findOrFail($productId);
            $child  = Product::findOrFail($childId); // Ambil data child sebelum dihapus (untuk log)

            $parent->bomComponents()->detach($childId); // Hapus relasi pivot

            // [TAMBAHAN] Catat Activity Log
            ActivityLog::create([
                'user_id'     => Auth::id(),
                'user_name'   => Auth::user()->name,
                'action'      => 'UPDATE BOM (DELETE)',
                'description' => "Menghapus Komponen: {$child->code_part} ({$child->part_name}) dari BOM Induk: {$parent->code_part}"
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
     * Hanya menampilkan FINISH GOOD & SEMI FINISH
     * URL: /bom-management
     */
    public function list(Request $request)
    {
        // Query Dasar: Cari Produk FG atau Semi FG
        // Kita juga gunakan withCount untuk melihat status (Apakah BOM sudah diisi?)
        $query = Product::withCount('bomComponents')
                ->whereIn('category', ['FINISH GOOD', 'SEMI FINISH', 'SEMI FINISH GOOD']); 

        // --- FITUR SEARCH (Cari berdasarkan Code, Nama, atau No Part) ---
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('part_name', 'like', "%{$search}%")
                  ->orWhere('part_number', 'like', "%{$search}%")
                  ->orWhere('code_part', 'like', "%{$search}%"); 
            });
        }

        // Urutkan dan Pagination
        $products = $query->orderBy('code_part', 'asc') 
                          ->paginate(10);
                        
        return view('bom.list', compact('products'));
    }
}