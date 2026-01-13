<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionMachineAllocation extends Model
{
    protected $fillable = [
        'production_plan_detail_id',
        'machine_id',
        'allocated_qty',
        'calculated_hours'
    ];
}