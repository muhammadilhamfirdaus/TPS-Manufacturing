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

// --- MIDDLEWARE ---
use App\Http\Middleware\IsAdmin; 

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Auth::routes();

Route::get('/', function () {
    return redirect()->route('login');
});

// Test Logic (Bisa dihapus saat production)
Route::get('/test-tps', [SimulationController::class, 'testLogic']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Hanya bisa diakses jika sudah Login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // =============================================================
    // 1. AKSES UMUM (ADMIN & OPERATOR BISA AKSES)
    // =============================================================
    
    // Dashboard
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Modul Produksi (Operator Input Output Harian)
    Route::prefix('production')->name('production.')->group(function () {
        Route::get('/input', [ProductionActualController::class, 'index'])->name('input');
        Route::post('/store', [ProductionActualController::class, 'store'])->name('store');
    });


    // =============================================================
    // 2. AKSES KHUSUS ADMIN (DIBATASI DENGAN MIDDLEWARE CLASS)
    // =============================================================
    
    Route::middleware([IsAdmin::class])->group(function () {

        // === LOGS ===
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

        // === MODUL PLANNING (PLANNER) ===
        
        // [BARU] Integrasi Google Sheet & Input Manual Actual (Matrix)
        Route::post('plans/sync-plan', [ProductionPlanController::class, 'syncDailyPlan'])->name('plans.sync_plan');
        Route::post('plans/store-actuals', [ProductionPlanController::class, 'storeActuals'])->name('plans.store_actuals');

        // [BARU] Sum Loading Report (Kapasitas vs Load)
        Route::get('plans/sum-loading', [ProductionPlanController::class, 'sumLoading'])->name('plans.sum_loading');

        // Report & Tools Existing
        Route::get('plans/summary', [ProductionPlanController::class, 'summary'])->name('plans.summary');
        Route::get('plans/loading-report/pdf', [ProductionPlanController::class, 'downloadLoadingPdf'])->name('plans.loading_pdf');
        Route::get('plans/loading-report/excel', [ProductionPlanController::class, 'downloadLoadingExcel'])->name('plans.loading_excel');
        Route::get('plans/loading-report', [ProductionPlanController::class, 'loadingReport'])->name('plans.loading_report');
        Route::get('plans/template', [ProductionPlanController::class, 'downloadTemplate'])->name('plans.template');
        Route::post('plans/import', [ProductionPlanController::class, 'import'])->name('plans.import');
        Route::get('plans/export', [ProductionPlanController::class, 'export'])->name('plans.export');
        
        // Route Revisi Plan
        Route::post('plans/{id}/revise', [ProductionPlanController::class, 'revise'])->name('plans.revise');

        // Resource Utama (CRUD) - Ditaruh paling bawah agar tidak menimpa route custom di atasnya
        Route::resource('plans', ProductionPlanController::class)->names('plans');

        // === MODUL KANBAN ===
        Route::get('/kanban', [KanbanController::class, 'index'])->name('kanban.index');

        // === MODUL MPP (MAN POWER PLANNING) ===
        Route::get('/mpp/pdf', [MppController::class, 'exportPdf'])->name('mpp.pdf');
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

    }); // End Middleware Check Admin

}); // End Middleware Auth