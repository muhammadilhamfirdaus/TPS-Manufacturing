<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Logika Pengecekan Role
        if (Auth::check() && Auth::user()->role !== 'admin') {
            abort(403, 'AKSES DITOLAK: Halaman ini hanya untuk Administrator/Planner.');
        }

        return $next($request);
    }
}