<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'name',
        'machine_code',
        'machine_group',
        'capacity_per_hour', // <--- TAMBAHKAN INI
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}