<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\ProductionActualController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MasterLineController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\MppController;
use App\Http\Controllers\BomController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Auth::routes();

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/test-tps', [SimulationController::class, 'testLogic']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Hanya bisa diakses jika sudah Login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // === DASHBOARD & LOGS ===
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

    // === MODUL PLANNING (PLANNER) ===

    // 1. Custom Routes (Summary, Report, Export/Import)
    Route::get('plans/summary', [ProductionPlanController::class, 'summary'])->name('plans.summary');
    Route::get('plans/loading-report/pdf', [ProductionPlanController::class, 'downloadLoadingPdf'])->name('plans.loading_pdf');
    Route::get('plans/loading-report/excel', [ProductionPlanController::class, 'downloadLoadingExcel'])->name('plans.loading_excel');
    Route::get('plans/loading-report', [ProductionPlanController::class, 'loadingReport'])->name('plans.loading_report');
    
    Route::get('plans/export', [ProductionPlanController::class, 'export'])->name('plans.export');
    Route::post('plans/import', [ProductionPlanController::class, 'import'])->name('plans.import');

    // 2. Resource CRUD (Standard)
    Route::resource('plans', ProductionPlanController::class)->names('plans');


    // === MODUL PRODUKSI AKTUAL (OPERATOR) - MATRIX VIEW ===
    Route::prefix('production')->name('production.')->group(function () {
        // Hanya ada 2 route utama: Lihat Matrix & Simpan Matrix
        // 'index' di controller sudah memuat tampilan Matrix
        // 'store' di controller sudah memuat logic Bulk Save
        Route::get('/input', [ProductionActualController::class, 'index'])->name('input');
        Route::post('/store', [ProductionActualController::class, 'store'])->name('store');
    });


    // === MODUL KANBAN ===
    Route::get('/kanban', [KanbanController::class, 'index'])->name('kanban.index');

    // === MODUL MPP (MAN POWER PLANNING) ===
    Route::get('/mpp', [MppController::class, 'index'])->name('mpp.index');

    // === MODUL BOM MANAGEMENT ===
    Route::prefix('bom-management')->name('bom.')->group(function () {
        Route::get('/', [BomController::class, 'list'])->name('list');
        Route::get('/{id}/manage', [BomController::class, 'index'])->name('index');
        Route::post('/{id}/store', [BomController::class, 'store'])->name('store');
        Route::delete('/{id}/{childId}', [BomController::class, 'destroy'])->name('destroy');
    });

    // === MODUL MASTER DATA (PART & ROUTING) ===
    Route::prefix('master-data')->name('master.')->group(function () {
        Route::get('/', [MasterDataController::class, 'index'])->name('index');
        Route::get('/create', [MasterDataController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [MasterDataController::class, 'edit'])->name('edit');
        Route::post('/store/{id?}', [MasterDataController::class, 'store'])->name('store');
        Route::delete('/{id}', [MasterDataController::class, 'destroy'])->name('destroy');
        Route::get('/template', [MasterDataController::class, 'downloadTemplate'])->name('template');
        Route::post('/import', [MasterDataController::class, 'import'])->name('import');
    });

    // === MODUL MASTER LINE & MESIN ===
    Route::prefix('master-line')->name('master-line.')->group(function () {
        Route::get('/', [MasterLineController::class, 'index'])->name('index');
        Route::get('/create', [MasterLineController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [MasterLineController::class, 'edit'])->name('edit');
        Route::post('/store/{id?}', [MasterLineController::class, 'store'])->name('store');
        Route::delete('/{id}', [MasterLineController::class, 'destroy'])->name('destroy');
    });

});