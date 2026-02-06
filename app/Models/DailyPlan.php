<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DailyPlan extends Model
{
    use HasFactory;
    
    // Izinkan semua kolom diisi
    protected $guarded = [];

    // Accessor untuk mengambil angka tanggal saja (1-31)
    public function getDayOnlyAttribute()
    {
        return Carbon::parse($this->plan_date)->day;
    }
}