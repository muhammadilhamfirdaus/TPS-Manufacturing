<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code_part',
        'part_number',
        'part_name',
        'category',
        'customer',
        'photo',
        'cycle_time',
        'manpower_ratio', // Ratio level product (jika ada)
        'uom',
        'qty_per_box',
        'kode_box',
        'lot_size_pcs',
        'kanban_post',  
        'fluctuation',
        'kanban_aktif',
        'material_remarks', 
        'safety_stock',
        'collecting_post',
        'flow_process',
        'line',
        'lot_size',
        'kode_box',
        'load_time',
        'lead_time',
        'kanban_aktif',
        'stock_pcs',
        'kanban_type',
    ];

    protected $casts = [
        'cycle_time' => 'float',
        'qty_per_box' => 'integer',
        'safety_stock' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * 1. ROUTINGS (Has Many)
     * Mengambil routing urut berdasarkan ID agar flow process teratur (A -> B -> C).
     */
    public function routings()
    {
        return $this->hasMany(ProductRouting::class)->orderBy('id', 'asc');
    }

    /**
     * 2. MACHINES (Belongs To Many)
     * Mengambil daftar mesin via tabel pivot 'product_routings'.
     */
    public function machines()
    {
        return $this->belongsToMany(Machine::class, 'product_routings', 'product_id', 'machine_id')
            ->withPivot([
                'process_name',
                'capacity_per_hour', // Sesuaikan nama kolom di DB 
                'manpower_ratio',    // Sesuaikan nama kolom di DB
                'production_line_id' // Tambahan kolom baru (PENTING)
            ])
            ->withTimestamps();
    }

    /**
     * 3. PRODUCTION PLAN DETAILS
     */
    public function productionPlanDetails()
    {
        return $this->hasMany(ProductionPlanDetail::class);
    }

    /**
     * 4. KANBAN MASTER
     */
    public function kanbanMaster()
    {
        return $this->hasOne(KanbanMaster::class);
    }

    /**
     * 5. BOM COMPONENTS
     */
    public function bomComponents()
    {
        return $this->belongsToMany(Product::class, 'bom_details', 'parent_product_id', 'child_product_id')
            ->withPivot('id', 'quantity')
            ->withTimestamps();
    }

    /**
     * 6. USED IN
     */
    public function usedIn()
    {
        return $this->belongsToMany(Product::class, 'bom_details', 'child_product_id', 'parent_product_id')
            ->withPivot('id', 'quantity')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (ATTRIBUTE CUSTOM)
    |--------------------------------------------------------------------------
    */

    /**
     * Mengambil Flow Process.
     * Jika kolom manual diisi, pakai itu.
     * Jika kosong, generate otomatis dari Routing.
     */
    public function getFlowProcessAttribute($value)
    {
        // 1. Jika di database sudah diisi manual, gunakan itu
        if (!empty($value) && $value !== '-') {
            return $value;
        }

        // 2. Jika kosong, generate dari Routing Process Name
        // Cek apakah relasi 'routings' sudah di-load agar tidak berat (Optimasi N+1)
        if ($this->relationLoaded('routings')) {
            $flows = $this->routings->pluck('process_name')->filter()->toArray();
            return !empty($flows) ? implode(' ➔ ', $flows) : '-';
        }

        // 3. Fallback jika relation belum di-load
        return '-';
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES (QUERY HELPER)
    |--------------------------------------------------------------------------
    */

    /**
     * Scope untuk pencarian data (Search Bar)
     * Cara pakai di Controller: Product::search('kata kunci')->get();
     */
    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('code_part', 'like', "%{$term}%")
                ->orWhere('part_number', 'like', "%{$term}%")
                ->orWhere('part_name', 'like', "%{$term}%")
                ->orWhere('customer', 'like', "%{$term}%");
        });
    }
}