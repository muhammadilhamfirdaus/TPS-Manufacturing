<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
    use HasFactory;

    protected $table = 'bom_details'; 

    protected $fillable = [
        'parent_product_id',
        'child_product_id',
        'quantity', // Pastikan nama kolom di DB 'quantity'
        'sequence'  // <--- WAJIB DITAMBAHKAN (Untuk Drag & Drop)
    ];

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    /**
     * Relasi ke Child Product
     * Saya tambahkan nama 'childProduct' agar sinkron dengan Controller saya.
     * Fungsi 'child()' yang lama BIARKAN SAJA agar kode lain tidak error.
     */
    public function childProduct()
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }

    // Fungsi lama Anda (tetap ada biar aman)
    public function child()
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }
}