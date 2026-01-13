<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        // Tampilkan 20 log terakhir, urut dari yang terbaru
        $logs = ActivityLog::orderBy('created_at', 'desc')->paginate(20);
        return view('logs.index', compact('logs'));
    }
}