<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionPlanDetail;
use App\Models\Holiday;
use App\Services\KanbanCalculatorService;
use Carbon\Carbon;

class KanbanController extends Controller
{
    protected $calculator;

    public function __construct(KanbanCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    public function index(Request $request)
    {
        // 1. Filter Bulan & Tahun
        $month = $request->get('month', date('m'));
        $year  = $request->get('year', date('Y'));

        // 2. Hitung Hari Kerja Efektif (Sama seperti logic MPP)
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $holidays = Holiday::whereMonth('date', $month)->whereYear('date', $year)->pluck('date')->toArray();
        
        $workDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::create($year, $month, $d);
            if (!$date->isWeekend() && !in_array($date->format('Y-m-d'), $holidays)) {
                $workDays++;
            }
        }
        $workDays = $workDays > 0 ? $workDays : 22; // Default safeguard

        // 3. Ambil Data Plan Produksi (Sebagai Demand)
        // Kita group by Product ID agar jika ada split plan, totalnya tetap terjumlah
        $kanbanData = ProductionPlanDetail::with('product')
            ->whereHas('productionPlan', function($q) use ($month, $year) {
                $q->whereMonth('plan_date', $month)
                  ->whereYear('plan_date', $year)
                  ->where('status', '!=', 'HISTORY');
            })
            ->get()
            ->groupBy('product_id')
            ->map(function ($details) use ($workDays) {
                $product = $details->first()->product;
                $totalOrder = $details->sum('qty_plan');

                // Panggil Service Kalkulasi
                $calc = $this->calculator->calculate(
                    $totalOrder,
                    $workDays,
                    $product->lead_time ?? 1,
                    $product->qty_per_box ?? 1,
                    $product->safety_stock ?? 0
                );

                return (object) [
                    'code_part'    => $product->code_part,
                    'part_name'    => $product->part_name,
                    'part_number'  => $product->part_number,
                    'customer'     => $product->customer,
                    'qty_per_box'  => $product->qty_per_box, // Lot Size
                    'lead_time'    => $product->lead_time,
                    'safety_stock' => $product->safety_stock,
                    'total_order'  => $totalOrder,
                    'daily_plan'   => $calc['daily_plan'],
                    'kanban_qty'   => $calc['kanban_cards'], // Jumlah Kartu
                    'max_stock'    => $calc['total_qty'] // Max inventory level
                ];
            });

        return view('kanban.index', compact('kanbanData', 'month', 'year', 'workDays'));
    }
}