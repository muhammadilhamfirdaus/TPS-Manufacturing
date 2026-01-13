<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    use HasFactory;

    // INI YANG TADI KURANG (Daftar kolom yang boleh diisi)
    protected $fillable = [
        'plan_date',
        'production_line_id',
        'shift_id',
        'status',
        'created_by'
    ];

    // Relasi ke Line
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    // Relasi ke Detail (Items)
    public function details()
    {
        return $this->hasMany(ProductionPlanDetail::class);
    }
}