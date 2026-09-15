<?php

use Illuminate\Support\Facades\Route;

// Admin Controllers
use App\Http\Controllers\Admin\CraftsmanController as AdminCraftsmanController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\WorkOrderController as AdminWorkOrderController;
use App\Http\Controllers\Admin\DesignCodeController as AdminDesignCodeController;

// Craftsman Controllers
use App\Http\Controllers\Craftsman\DashboardController as CraftsmanDashboardController;
use App\Http\Controllers\Craftsman\LoginController as CraftsmanLoginController;
use App\Http\Controllers\Craftsman\WorkOrderController as CraftsmanWorkOrderController;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/
// Guest Admin Routes
Route::middleware(['web', 'guest:admin'])->group(function () {
    Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'login']);
});

// Protected Admin Routes
Route::middleware(['web', 'auth:admin', 'single.admin.session'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    Route::get('/heartbeat', function () {
        return response()->json(['status' => 'active']);
    })->name('heartbeat');

    Route::post('craftsmen/{craftsman}/auto-assign', [AdminCraftsmanController::class, 'autoAssign'])->name('craftsmen.auto-assign');
    Route::resource('craftsmen', AdminCraftsmanController::class);

    // Custom static endpoints before dynamic or resource routes
    Route::get('design-codes/unassigned', [AdminDesignCodeController::class, 'unassigned'])->name('design_codes.unassigned');
    
    // Import Routes MUST be placed before work-orders/{workOrder} parameters
    Route::get('work-orders/import', [AdminWorkOrderController::class, 'importForm'])->name('work_orders.import.form');
    Route::post('work-orders/import', [AdminWorkOrderController::class, 'import'])->name('work_orders.import');

    Route::post('work-orders/bulk-allocate', [AdminWorkOrderController::class, 'bulkAllocate'])->name('work_orders.bulk-allocate');
    Route::post('work-orders/bulk-complete', [AdminWorkOrderController::class, 'bulkComplete'])->name('work_orders.bulk-complete');
    Route::post('work-orders/bulk-print', [AdminWorkOrderController::class, 'bulkPrint'])->name('work_orders.bulk-print');
    
    // Explicit craftsman resource routes
    Route::get('work-orders/{workOrder}/print', [AdminWorkOrderController::class, 'print'])->name('work_orders.print');
    Route::post('work-orders/{workOrder}/allocate', [AdminWorkOrderController::class, 'allocate'])->name('work_orders.allocate');
    Route::post('work-orders/{workOrder}/undo', [AdminWorkOrderController::class, 'undoAllocation'])->name('work_orders.undo');
    Route::post('work-orders/{workOrder}/return', [AdminWorkOrderController::class, 'returnOrder'])->name('work_orders.return');
    Route::post('work-orders/{workOrder}/approve', [AdminWorkOrderController::class, 'approve'])->name('work_orders.approve');

    Route::resource('work-orders', AdminWorkOrderController::class)->names('work_orders');
});

/*
|--------------------------------------------------------------------------
| Craftsman Portal Routes
|--------------------------------------------------------------------------
*/
// Guest Craftsman Routes
Route::prefix('craftsman')->name('craftsman.')->middleware(['web', 'guest:craftsman'])->group(function () {
    Route::get('login', [CraftsmanLoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [CraftsmanLoginController::class, 'login']);
});

// Authenticated Craftsman Routes
Route::prefix('craftsman')->name('craftsman.')->middleware(['web', 'auth:craftsman'])->group(function () {
    Route::get('dashboard', [CraftsmanDashboardController::class, 'index'])->name('dashboard');
    Route::post('logout', [CraftsmanLoginController::class, 'logout'])->name('logout');

    Route::get('work-orders', [CraftsmanWorkOrderController::class, 'index'])->name('work-orders.index');
    Route::get('work-orders/{workOrder}', [CraftsmanWorkOrderController::class, 'show'])->name('work-orders.show');
    Route::post('work-orders/{workOrder}/accept', [CraftsmanWorkOrderController::class, 'accept'])->name('work-orders.accept');
    Route::post('work-orders/{workOrder}/submit', [CraftsmanWorkOrderController::class, 'submitForApproval'])->name('work-orders.submit');
    Route::post('work-orders/bulk-print', [CraftsmanWorkOrderController::class, 'bulkPrint'])->name('work-orders.bulk-print');
    Route::post('work-orders/bulk-accept', [CraftsmanWorkOrderController::class, 'bulkAccept'])->name('work-orders.bulk-accept');
    Route::post('work-orders/bulk-submit', [CraftsmanWorkOrderController::class, 'bulkSubmit'])->name('work-orders.bulk-submit');



});
