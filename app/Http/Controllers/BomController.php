<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BomController extends Controller
{
    // TAMPILKAN HALAMAN BOM UNTUK 1 PRODUK
    public function index($productId)
    {
        $parent = Product::with('bomComponents')->findOrFail($productId);
        
        // Ambil semua produk KECUALI diri sendiri (untuk dropdown tambah komponen)
        // Dan idealnya mencegah circular reference (A butuh B, B butuh A), tapi simple dulu:
        $allProducts = Product::where('id', '!=', $productId)
                        ->orderBy('part_name')
                        ->get();

        return view('bom.index', compact('parent', 'allProducts'));
    }

    // TAMBAH KOMPONEN (CHILD)
    public function store(Request $request, $productId)
    {
        $request->validate([
            'child_product_id' => 'required|exists:products,id|different:parent_product_id',
            'quantity' => 'required|numeric|min:0.0001',
        ]);

        // Cek duplikasi manual (opsional, krn sudah ada unique di DB)
        $exists = DB::table('bom_details')
                    ->where('parent_product_id', $productId)
                    ->where('child_product_id', $request->child_product_id)
                    ->exists();

        if($exists) {
            return back()->withErrors('Komponen ini sudah ada di dalam BOM!');
        }

        $parent = Product::findOrFail($productId);
        $parent->bomComponents()->attach($request->child_product_id, [
            'quantity' => $request->quantity
        ]);

        return back()->with('success', 'Komponen berhasil ditambahkan!');
    }

    // HAPUS KOMPONEN
    public function destroy($productId, $childId)
    {
        $parent = Product::findOrFail($productId);
        $parent->bomComponents()->detach($childId); // Hapus relasi

        return back()->with('success', 'Komponen dihapus dari BOM.');
    }


    // List Produk yang Butuh BOM (Hanya FG & Semi FG)
    public function list()
    {
        $products = Product::whereIn('category', ['FINISH GOOD', 'SEMI FINISH GOOD'])
                        ->withCount('bomComponents') // Hitung jumlah komponen yang sudah ada
                        ->orderBy('part_name')
                        ->paginate(10);
                        
        return view('bom.list', compact('products'));
    }
}