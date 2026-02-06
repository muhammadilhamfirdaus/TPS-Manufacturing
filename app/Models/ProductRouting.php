<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRouting extends Model
{
    // Tambahkan 'cycle_time'
    protected $fillable = [
        'product_id',
        'production_line_id',
        'machine_id',
        'process_name',

        // --- PERBAIKAN DI SINI ---
        'pcs_per_hour', // Pastikan ini pcs_per_hour, JANGAN capacity_per_hour

        'manpower_ratio',
        'plant'
    ];
    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}