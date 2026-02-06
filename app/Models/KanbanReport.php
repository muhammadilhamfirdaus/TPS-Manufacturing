<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KanbanReport extends Model
{
    // 1. GUNAKAN FILLABLE (Best Practice untuk keamanan & dokumentasi)
    // Ini memastikan kolom-kolom baru (NG & Keterangan) terdaftar.
    protected $fillable = [
        'report_date',
        'code_part',
        'qty_target',
        'qty_delay',
        
        // Data Shift 1
        'pic_shift_1',
        'act_shift_1', // Output OK
        'ng_shift_1',  // Output NG (Reject)
        'lot_shift_1',
        
        // Data Shift 2
        'pic_shift_2',
        'act_shift_2', // Output OK
        'ng_shift_2',  // Output NG (Reject)
        'lot_shift_2',
        
        // Catatan
        'keterangan'
    ];

    // 2. CASTING TIPE DATA
    // Agar saat diambil dari database, PHP langsung tahu ini Angka atau Tanggal
    protected $casts = [
        'report_date' => 'date',     // Otomatis jadi Carbon Object (bisa $row->report_date->format('d-m-Y'))
        'qty_target'  => 'integer',
        'qty_delay'   => 'integer',
        'act_shift_1' => 'integer',
        'ng_shift_1'  => 'integer',
        'act_shift_2' => 'integer',
        'ng_shift_2'  => 'integer',
    ];

    // 3. RELASI KE PRODUCT
    public function product()
    {
        return $this->belongsTo(Product::class, 'code_part', 'code_part');
    }

    // --- FITUR TAMBAHAN (ACCESSOR / VIRTUAL COLUMN) ---
    // Fitur ini membuat Anda bisa memanggil $row->total_ok di View, 
    // meskipun kolom 'total_ok' tidak ada di database.

    // Total Barang Bagus (Shift 1 + Shift 2)
    public function getTotalOkAttribute()
    {
        return $this->act_shift_1 + $this->act_shift_2;
    }

    // Total Barang Reject (Shift 1 + Shift 2)
    public function getTotalNgAttribute()
    {
        return $this->ng_shift_1 + $this->ng_shift_2;
    }
    
    // Total Produksi (OK + NG)
    public function getTotalProduksiAttribute()
    {
        return $this->total_ok + $this->total_ng;
    }
}