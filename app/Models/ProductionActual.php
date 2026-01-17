<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionActual extends Model
{
    use HasFactory;

    // TAMBAHKAN 'production_line_id' DAN 'product_id' KE SINI
    protected $fillable = [
        'production_plan_detail_id',
        'production_date',
        
        'production_line_id', // <--- WAJIB DITAMBAHKAN
        'product_id',         // <--- WAJIB DITAMBAHKAN
        'shift_id',
        
        'qty_good',
        'qty_reject',
        'created_by'
    ];

    public function planDetail()
    {
        return $this->belongsTo(ProductionPlanDetail::class, 'production_plan_detail_id');
    }
}