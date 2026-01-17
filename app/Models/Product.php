<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code_part',
        'part_number',
        'part_name',
        'category',      // <--- WAJIB DITAMBAHKAN AGAR BISA DISIMPAN
        'customer',
        'cycle_time',
        'uom',
        'qty_per_box',
        'safety_stock',
        'flow_process'
    ];

    /**
     * RELASI PENTING UNTUK FITUR AUTO LOADING
     * Mengambil daftar routing (mesin & proses) untuk produk ini.
     */
    public function routings()
    {
        return $this->hasMany(ProductRouting::class);
    }

    /**
     * Helper: Mengambil daftar mesin yang digunakan produk ini secara langsung
     */
    public function machines()
    {
        return $this->belongsToMany(Machine::class, 'product_routings');
    }

    // Relasi ke Plan Detail (Opsional, untuk tracking history)
    public function productionPlanDetails()
    {
        return $this->hasMany(ProductionPlanDetail::class);
    }

    // Relasi ke Kanban Master (Opsional, jika modul Kanban dipakai)
    public function kanbanMaster()
    {
        return $this->hasOne(KanbanMaster::class);
    }

    // Relasi ke Child (Komponen yang dibutuhkan produk ini - BOM)
    public function bomComponents()
    {
        return $this->belongsToMany(Product::class, 'bom_details', 'parent_product_id', 'child_product_id')
            ->withPivot('id', 'quantity') // Agar bisa akses kolom quantity & ID pivot
            ->withTimestamps();
    }

    // Relasi ke Parent (Produk ini dipakai di mana saja? - Where Used)
    public function usedIn()
    {
        return $this->belongsToMany(Product::class, 'bom_details', 'child_product_id', 'parent_product_id')
            ->withPivot('id', 'quantity')
            ->withTimestamps();
    }
}