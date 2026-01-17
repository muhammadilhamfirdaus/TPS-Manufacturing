<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlanDetail extends Model
{
    use HasFactory;

    // Gunakan guarded agar lebih fleksibel (aman selama tidak mass assignment sembarangan)
    // atau tetap gunakan fillable seperti sebelumnya.
    protected $fillable = [
        'production_plan_id',
        'product_id',
        'qty_plan',
        'qty_actual',
        'calculated_loading_pct',
        'calculated_manpower',
        'calculated_kanban_cards'
    ];

    // 1. Relasi balik ke Header Plan (Parent)
    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    // 2. Relasi ke Master Produk
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 3. [PENTING] Relasi ke Hasil Produksi Aktual (ProductionActual)
    // Ini yang memperbaiki error "Call to undefined relationship [productionActuals]"
    public function productionActuals()
    {
        return $this->hasMany(ProductionActual::class, 'production_plan_detail_id');
    }

    // 4. Relasi Allocations (Jika fitur alokasi mesin per jam digunakan)
    public function allocations()
    {
        return $this->hasMany(ProductionMachineAllocation::class);
    }
}