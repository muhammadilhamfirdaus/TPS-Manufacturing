<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlanDetail extends Model
{
    use HasFactory;

    // Izinkan kolom-kolom ini diisi
    protected $fillable = [
        'production_plan_id',
        'product_id',
        'qty_plan',
        'qty_actual',
        'calculated_loading_pct',
        'calculated_manpower',
        'calculated_kanban_cards' // <--- TAMBAHKAN INI
    ];

    // Relasi balik ke Header Plan
    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    // Relasi ke Produk
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Tambahkan ini
    public function allocations()
    {
        return $this->hasMany(ProductionMachineAllocation::class);
    }
}