<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KanbanMaster extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'kanban_type', // FG, WIP, SUBCONT
        'number_of_cards',
        'location_code',
        'daily_demand_forecast',
        'lead_time_days'
    ];

    // Agar atribut 'current_stock' selalu muncul saat dipanggil
    protected $appends = ['current_stock', 'status_color'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function transactions()
    {
        return $this->hasMany(KanbanTransaction::class);
    }

    // Atribut Virtual: Hitung Stok Saat Ini
    public function getCurrentStockAttribute()
    {
        // Total Masuk - Total Keluar
        $in = $this->transactions()->where('transaction_type', 'IN')->sum('qty_total');
        $out = $this->transactions()->where('transaction_type', 'OUT')->sum('qty_total');
        
        return $in - $out;
    }

    // Atribut Virtual: Tentukan Warna Status (Logic TPS)
    public function getStatusColorAttribute()
    {
        $stock = $this->current_stock;
        $maxStock = $this->number_of_cards * $this->product->qty_per_box;
        
        // Logika sederhana TPS:
        // Merah (Bahaya) = Stok < 30%
        // Kuning (Warning) = Stok < 60%
        // Hijau (Aman) = Stok > 60%
        
        if ($maxStock == 0) return 'secondary'; // Error handler
        
        $percentage = ($stock / $maxStock) * 100;

        if ($percentage <= 30) return 'danger'; // Merah
        if ($percentage <= 60) return 'warning'; // Kuning
        return 'success'; // Hijau
    }
}