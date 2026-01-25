<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    // Di ActivityLogController.php
    public function index(Request $request)
    {
        $query = ActivityLog::query();

        // Filter Search
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('user_name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter Action
        if ($request->has('action') && $request->action != '') {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('logs.index', compact('logs'));
    }
}