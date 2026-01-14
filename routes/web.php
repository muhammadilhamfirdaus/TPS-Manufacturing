<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\ProductionPlanController;
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

// 1. Authentication Routes
Auth::routes();

// 2. Root URL
Route::get('/', function () {
    return redirect()->route('login');
});

// 3. Testing Route
Route::get('/test-tps', [SimulationController::class, 'testLogic']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Hanya bisa diakses jika sudah Login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Dashboard Home
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // === MODUL ACTIVITY LOGS (TRACKING) ===
    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

    // === MODUL PLANNING ===
    // [PENTING] Urutan Route Custom harus di ATAS Resource

    // 1. Loading Report & Downloads
    Route::get('plans/loading-report/pdf', [ProductionPlanController::class, 'downloadLoadingPdf'])->name('plans.loading_pdf');
    Route::get('plans/loading-report/excel', [ProductionPlanController::class, 'downloadLoadingExcel'])->name('plans.loading_excel');
    Route::get('plans/loading-report', [ProductionPlanController::class, 'loadingReport'])->name('plans.loading_report');

    // 2. Custom Actions Lainnya
    Route::post('plans/loading-store', [ProductionPlanController::class, 'storeLoading'])->name('plans.loading_store');
    Route::get('plans/export', [ProductionPlanController::class, 'export'])->name('plans.export');
    Route::post('plans/import', [ProductionPlanController::class, 'import'])->name('plans.import');

    // 3. Resource CRUD
    Route::resource('plans', ProductionPlanController::class);

    // === MODUL KANBAN ===
    Route::get('/kanban', [KanbanController::class, 'index'])->name('kanban.index');

    // === MODUL MPP (MAN POWER PLANNING) ===
    Route::get('/mpp', [MppController::class, 'index'])->name('mpp.index');

    // === MODUL BOM MANAGEMENT (BARU) ===
    // Mengelola Resep Produk (Finish Good & Semi FG)
    Route::prefix('bom-management')->name('bom.')->group(function () {
        // Halaman List Produk yang butuh BOM
        Route::get('/', [BomController::class, 'list'])->name('list'); 
        
        // Halaman Edit/Kelola BOM per Produk
        Route::get('/{id}/manage', [BomController::class, 'index'])->name('index'); 
        
        // Action Simpan Komponen
        Route::post('/{id}/store', [BomController::class, 'store'])->name('store');
        
        // Action Hapus Komponen
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