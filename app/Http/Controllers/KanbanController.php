<?php

namespace App\Http\Controllers;

use App\Models\KanbanMaster;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    public function index()
    {
        // Ambil data kanban beserta produknya
        $kanbans = KanbanMaster::with('product')->get();

        return view('kanban.index', compact('kanbans'));
    }
}