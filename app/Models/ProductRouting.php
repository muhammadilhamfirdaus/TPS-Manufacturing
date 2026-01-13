<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRouting extends Model
{
    // Tambahkan 'cycle_time'
    protected $fillable = [
        'product_id',
        'machine_id',
        'process_name',
        'pcs_per_hour' // <--- GANTI INI
    ];
    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}