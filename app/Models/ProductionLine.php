<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant',         // Pastikan nama kolom ini sesuai DB (apakah 'plant' atau 'plant_id')
        'name', 
        'std_manpower',
        'total_shifts'   // <--- WAJIB DITAMBAHKAN
    ];

    // Relasi: Satu Line punya banyak Mesin
    public function machines()
    {
        return $this->hasMany(Machine::class);
    }
}