<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SimulationController;

// Modul Controllers
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\ProductionActualController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\MppController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MasterLineController;

// Middleware
use App\Http\Middleware\IsAdmin;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Auth::routes();

// Redirect root ke login
Route::redirect('/', '/login');

// Test Logic (Hapus saat live production)
Route::get('/test-tps', [SimulationController::class, 'testLogic']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Login Required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // =============================================================
    // 1. DASHBOARD & OPERATOR (AKSES UMUM)
    // =============================================================

    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Modul Input Produksi (Untuk Operator di Lantai Produksi)
    Route::prefix('production')->name('production.')->group(function () {
        Route::get('/input', [ProductionActualController::class, 'index'])->name('input'); // Halaman Matrix
        Route::post('/store', [ProductionActualController::class, 'store'])->name('store'); // Simpan Input
        // Sync Plan milik Operator
        Route::post('/sync-plan', [ProductionActualController::class, 'syncDailyPlan'])->name('sync_plan');
    });


    // =============================================================
    // 2. ADMINISTRATOR & PPIC (ACCESS CONTROL)
    // =============================================================

    Route::middleware([IsAdmin::class])->group(function () {

        // --- SYSTEM LOGS ---
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

        // --- MODUL PLANNING (PPIC) ---
        Route::prefix('plans')->name('plans.')->group(function () {

            // A. Custom Actions
            Route::post('sync-plan-ppic', [ProductionPlanController::class, 'syncDailyPlan'])->name('sync_plan_ppic');
            Route::post('store-actuals', [ProductionPlanController::class, 'storeActuals'])->name('store_actuals');
            Route::post('{id}/revise', [ProductionPlanController::class, 'revise'])->name('revise');

            // [PERBAIKAN KONFLIK ROUTE]
            // 1. Sync All Plans (Sinkronisasi Plan vs Actual)
            Route::post('sync-plans', [ProductionPlanController::class, 'syncAllPlans'])->name('sync_plans');

            // Route khusus untuk update struktur BOM
            Route::post('sync-bom-structure', [ProductionPlanController::class, 'syncBOMStructure'])->name('sync_bom');

            // 2. Sync Master Data (Update Plan berdasarkan Master Part terbaru)
            Route::post('sync-master-data', [ProductionPlanController::class, 'syncAllMasterData'])->name('sync_master_data');

            // B. Reports & Charts
            Route::get('sum-loading', [ProductionPlanController::class, 'sumLoading'])->name('sum_loading');
            Route::get('summary', [ProductionPlanController::class, 'summary'])->name('summary');

            // C. Loading Report (PDF/Excel)
            Route::get('loading-report/pdf', [ProductionPlanController::class, 'downloadLoadingPdf'])->name('loading_pdf');
            Route::get('loading-report/excel', [ProductionPlanController::class, 'downloadLoadingExcel'])->name('loading_excel');
            Route::get('loading-report', [ProductionPlanController::class, 'loadingReport'])->name('loading_report');

            // D. Import/Export
            Route::get('template', [ProductionPlanController::class, 'downloadTemplate'])->name('template');
            Route::post('import', [ProductionPlanController::class, 'import'])->name('import');
            Route::get('export', [ProductionPlanController::class, 'export'])->name('export');
        });

        // CRUD Utama Planning (Resource ditaruh diluar prefix group manual agar rapi, tapi tetap kena middleware admin)
        Route::resource('plans', ProductionPlanController::class);


        // --- MODUL KANBAN CONTROL ---
        Route::prefix('kanban')->name('kanban.')->group(function () {
            // Redirect default
            Route::get('/', function () {
                return redirect()->route('kanban.index'); });

            // Dashboard & Calc
            Route::get('/calculation', [KanbanController::class, 'index'])->name('index');

            // Template & Upload
            Route::get('/download-template', [KanbanController::class, 'downloadTemplate'])->name('download_template');
            Route::post('/upload-data', [KanbanController::class, 'uploadData'])->name('upload_data');
            Route::post('/save-inputs', [KanbanController::class, 'saveInputs'])->name('save_inputs');

            // Daily Report
            Route::get('/daily-report', [KanbanController::class, 'dailyReport'])->name('daily_report');
            Route::post('/daily-report/store', [KanbanController::class, 'storeDailyReport'])->name('store_daily_report');

            // Grafik Dashboard (Jika ada, sesuai instruksi sebelumnya)
            // Route::get('/dashboard', [KanbanController::class, 'dashboard'])->name('dashboard');
        });


        // --- MODUL MANPOWER (MPP) ---
        Route::prefix('mpp')->name('mpp.')->group(function () {
            Route::get('/', [MppController::class, 'index'])->name('index');
            Route::get('/pdf', [MppController::class, 'exportPdf'])->name('pdf');
            Route::post('/store-adjustment', [MppController::class, 'storeAdjustment'])->name('store_adjustment');
        });


        // --- MODUL BOM (BILL OF MATERIALS) ---
        Route::prefix('bom-management')->name('bom.')->group(function () {
            Route::get('/', [BomController::class, 'list'])->name('list');
            Route::get('/template', [BomController::class, 'downloadTemplate'])->name('template'); // Pindah ke atas agar tidak tertutup {id}
            Route::post('/import', [BomController::class, 'import'])->name('import');

            Route::get('/{id}/manage', [BomController::class, 'index'])->name('index');
            Route::post('/{id}/store', [BomController::class, 'store'])->name('store');
            Route::delete('/{id}/{childId}', [BomController::class, 'destroy'])->name('destroy');
            Route::put('/{childId}/update', [BomController::class, 'update'])->name('update');
            Route::post('/reorder', [BomController::class, 'reorder'])->name('reorder');
        });


        // --- MASTER DATA (PART & ROUTING) ---
        Route::prefix('master-data')->name('master.')->group(function () {
            // Import/Export
            Route::get('/template', [MasterDataController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [MasterDataController::class, 'import'])->name('import');

            // CRUD
            Route::get('/', [MasterDataController::class, 'index'])->name('index');
            Route::get('/create', [MasterDataController::class, 'create'])->name('create');
            Route::post('/store/{id?}', [MasterDataController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [MasterDataController::class, 'edit'])->name('edit');
            Route::delete('/{id}', [MasterDataController::class, 'destroy'])->name('destroy');

            // [OPSIONAL] Route Sync via Master Controller (Jika tombol sync ada di halaman Master)
            // Route::post('/sync-all', [MasterDataController::class, 'syncAllData'])->name('sync_all');
        });


        // --- MASTER LINE ---
        Route::prefix('master-line')->name('lines.')->group(function () {
            // Import/Export
            Route::get('/template', [MasterLineController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [MasterLineController::class, 'import'])->name('import');

            // CRUD
            Route::get('/', [MasterLineController::class, 'index'])->name('index');
            Route::get('/create', [MasterLineController::class, 'create'])->name('create');
            Route::post('/store/{id?}', [MasterLineController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [MasterLineController::class, 'edit'])->name('edit');
            Route::delete('/{id}', [MasterLineController::class, 'destroy'])->name('destroy');
        });

    }); // End Admin Middleware

}); // End Auth Middleware