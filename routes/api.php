<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CraftsmanController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Common Public Authentication Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/common/login', [AuthController::class, 'login'])->name('api.common.login');

/*
|--------------------------------------------------------------------------
| Authenticated Common / Unified Routes
|--------------------------------------------------------------------------
| All endpoints automatically detect whether an Admin or Craftsman is calling,
| delivering the appropriate data and enforcing permissions.
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Shared Auth
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // 1. Unified Dashboard (Admin & Craftsman)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

    // 2. Unified Work Orders (Admin: all orders view-only; Craftsman: assigned orders + actions)
    Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('api.work-orders.index');
    Route::get('/work-orders/tabs', [WorkOrderController::class, 'tabs'])->name('api.work-orders.tabs');
    Route::get('/work-orders/filters', [WorkOrderController::class, 'filters'])->name('api.work-orders.filters');
    Route::get('/work-orders/design-codes', [WorkOrderController::class, 'designCodes'])->name('api.work-orders.design-codes');
    Route::get('/work-orders/nicknames', [WorkOrderController::class, 'nicknames'])->name('api.work-orders.nicknames');
    Route::get('/work-orders/order-types', [WorkOrderController::class, 'orderTypes'])->name('api.work-orders.order-types');
    Route::get('/work-orders/product-names', [WorkOrderController::class, 'productNames'])->name('api.work-orders.product-names');
    Route::get('/work-orders/categories', [WorkOrderController::class, 'categories'])->name('api.work-orders.categories');
    Route::get('/work-orders/subcategories', [WorkOrderController::class, 'subcategories'])->name('api.work-orders.subcategories');
    Route::get('/work-orders/craftsmen', [WorkOrderController::class, 'craftsmenFilter'])->name('api.work-orders.craftsmen-filter');
    Route::post('/work-orders/bulk-accept', [WorkOrderController::class, 'bulkAccept'])->name('api.work-orders.bulk-accept');
    Route::post('/work-orders/bulk-submit', [WorkOrderController::class, 'bulkSubmit'])->name('api.work-orders.bulk-submit');
    Route::post('/work-orders/bulk-reject', [WorkOrderController::class, 'bulkReject'])->name('api.work-orders.bulk-reject');
    Route::post('/work-orders/bulk-print', [WorkOrderController::class, 'bulkPrint'])->name('api.work-orders.bulk-print');
    Route::get('/work-orders/{workOrder}/print', [WorkOrderController::class, 'print'])->name('api.work-orders.print');
    Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('api.work-orders.show');
    Route::post('/work-orders/{workOrder}/accept', [WorkOrderController::class, 'accept'])->name('api.work-orders.accept');
    Route::post('/work-orders/{workOrder}/reject', [WorkOrderController::class, 'reject'])->name('api.work-orders.reject');
    Route::post('/work-orders/{workOrder}/submit', [WorkOrderController::class, 'submitForApproval'])->name('api.work-orders.submit');

    // 3. Unified Customer Orders (Admin: all orders + allocate + approve; Craftsman: assigned orders + accept + reject + submit/complete)
    Route::get('/customer-orders', [OrderController::class, 'index'])->name('api.customer-orders.index');
    Route::get('/customer-orders/filters', [OrderController::class, 'filters'])->name('api.customer-orders.filters');
    Route::get('/customer-orders/order-types', [OrderController::class, 'orderTypes'])->name('api.customer-orders.order-types');
    Route::post('/customer-orders/bulk-accept', [OrderController::class, 'bulkAccept'])->name('api.customer-orders.bulk-accept');
    Route::post('/customer-orders/bulk-reject', [OrderController::class, 'bulkReject'])->name('api.customer-orders.bulk-reject');
    Route::post('/customer-orders/bulk-complete', [OrderController::class, 'bulkComplete'])->name('api.customer-orders.bulk-complete');
    Route::post('/customer-orders/bulk-submit', [OrderController::class, 'bulkComplete'])->name('api.customer-orders.bulk-submit');
    Route::post('/customer-orders/bulk-allocate', [OrderController::class, 'bulkAllocate'])->name('api.customer-orders.bulk-allocate');
    Route::post('/customer-orders/bulk-approve', [OrderController::class, 'bulkApprove'])->name('api.customer-orders.bulk-approve');
    Route::get('/customer-orders/{order}', [OrderController::class, 'show'])->name('api.customer-orders.show');
    Route::post('/customer-orders/{order}/accept', [OrderController::class, 'accept'])->name('api.customer-orders.accept');
    Route::post('/customer-orders/{order}/reject', [OrderController::class, 'reject'])->name('api.customer-orders.reject');
    Route::post('/customer-orders/{order}/submit', [OrderController::class, 'submitForApproval'])->name('api.customer-orders.submit');
    Route::post('/customer-orders/{order}/complete', [OrderController::class, 'submitForApproval'])->name('api.customer-orders.complete');
    Route::post('/customer-orders/{order}/allocate', [OrderController::class, 'allocate'])->name('api.customer-orders.allocate');
    Route::post('/customer-orders/{order}/approve', [OrderController::class, 'approve'])->name('api.customer-orders.approve');
    Route::post('/customer-orders/{order}/status', [OrderController::class, 'updateStatus'])->name('api.customer-orders.update-status');

    Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index');
    Route::get('/orders/filters', [OrderController::class, 'filters'])->name('api.orders.filters');
    Route::get('/orders/order-types', [OrderController::class, 'orderTypes'])->name('api.orders.order-types');
    Route::post('/orders/bulk-accept', [OrderController::class, 'bulkAccept'])->name('api.orders.bulk-accept');
    Route::post('/orders/bulk-reject', [OrderController::class, 'bulkReject'])->name('api.orders.bulk-reject');
    Route::post('/orders/bulk-complete', [OrderController::class, 'bulkComplete'])->name('api.orders.bulk-complete');
    Route::post('/orders/bulk-submit', [OrderController::class, 'bulkComplete'])->name('api.orders.bulk-submit');
    Route::post('/orders/bulk-allocate', [OrderController::class, 'bulkAllocate'])->name('api.orders.bulk-allocate');
    Route::post('/orders/bulk-approve', [OrderController::class, 'bulkApprove'])->name('api.orders.bulk-approve');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('api.orders.show');
    Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])->name('api.orders.accept');
    Route::post('/orders/{order}/reject', [OrderController::class, 'reject'])->name('api.orders.reject');
    Route::post('/orders/{order}/submit', [OrderController::class, 'submitForApproval'])->name('api.orders.submit');
    Route::post('/orders/{order}/complete', [OrderController::class, 'submitForApproval'])->name('api.orders.complete');
    Route::post('/orders/{order}/allocate', [OrderController::class, 'allocate'])->name('api.orders.allocate');
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve'])->name('api.orders.approve');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('api.orders.update-status');

    // 4. Craftsmen Directory Module (Admin Only)
    Route::get('/craftsmen', [CraftsmanController::class, 'index'])->name('api.craftsmen.index');
    Route::get('/craftsmen/{craftsman}', [CraftsmanController::class, 'show'])->name('api.craftsmen.show');

    /*
    |--------------------------------------------------------------------------
    | Role-Prefixed & Common Routes (Backward-compatibility aliases)
    |--------------------------------------------------------------------------
    */
    Route::prefix('common')->name('api.common.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('/work-orders/tabs', [WorkOrderController::class, 'tabs'])->name('work-orders.tabs');
        Route::get('/work-orders/filters', [WorkOrderController::class, 'filters'])->name('work-orders.filters');
        Route::get('/work-orders/design-codes', [WorkOrderController::class, 'designCodes'])->name('work-orders.design-codes');
        Route::get('/work-orders/nicknames', [WorkOrderController::class, 'nicknames'])->name('work-orders.nicknames');
        Route::get('/work-orders/order-types', [WorkOrderController::class, 'orderTypes'])->name('work-orders.order-types');
        Route::get('/work-orders/product-names', [WorkOrderController::class, 'productNames'])->name('work-orders.product-names');
        Route::get('/work-orders/categories', [WorkOrderController::class, 'categories'])->name('work-orders.categories');
        Route::get('/work-orders/subcategories', [WorkOrderController::class, 'subcategories'])->name('work-orders.subcategories');
        Route::get('/work-orders/craftsmen', [WorkOrderController::class, 'craftsmenFilter'])->name('work-orders.craftsmen-filter');
        Route::post('/work-orders/bulk-accept', [WorkOrderController::class, 'bulkAccept'])->name('work-orders.bulk-accept');
        Route::post('/work-orders/bulk-submit', [WorkOrderController::class, 'bulkSubmit'])->name('work-orders.bulk-submit');
        Route::post('/work-orders/bulk-reject', [WorkOrderController::class, 'bulkReject'])->name('work-orders.bulk-reject');
        Route::post('/work-orders/bulk-print', [WorkOrderController::class, 'bulkPrint'])->name('work-orders.bulk-print');
        Route::get('/work-orders/{workOrder}/print', [WorkOrderController::class, 'print'])->name('work-orders.print');
        Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
        Route::post('/work-orders/{workOrder}/accept', [WorkOrderController::class, 'accept'])->name('work-orders.accept');
        Route::post('/work-orders/{workOrder}/reject', [WorkOrderController::class, 'reject'])->name('work-orders.reject');
        Route::post('/work-orders/{workOrder}/submit', [WorkOrderController::class, 'submitForApproval'])->name('work-orders.submit');
        Route::get('/customer-orders', [OrderController::class, 'index'])->name('customer-orders.index');
        Route::get('/customer-orders/filters', [OrderController::class, 'filters'])->name('customer-orders.filters');
        Route::get('/customer-orders/order-types', [OrderController::class, 'orderTypes'])->name('customer-orders.order-types');
        Route::post('/customer-orders/bulk-accept', [OrderController::class, 'bulkAccept'])->name('customer-orders.bulk-accept');
        Route::post('/customer-orders/bulk-reject', [OrderController::class, 'bulkReject'])->name('customer-orders.bulk-reject');
        Route::post('/customer-orders/bulk-complete', [OrderController::class, 'bulkComplete'])->name('customer-orders.bulk-complete');
        Route::post('/customer-orders/bulk-submit', [OrderController::class, 'bulkComplete'])->name('customer-orders.bulk-submit');
        Route::post('/customer-orders/bulk-allocate', [OrderController::class, 'bulkAllocate'])->name('customer-orders.bulk-allocate');
        Route::post('/customer-orders/bulk-approve', [OrderController::class, 'bulkApprove'])->name('customer-orders.bulk-approve');
        Route::get('/customer-orders/{order}', [OrderController::class, 'show'])->name('customer-orders.show');
        Route::post('/customer-orders/{order}/accept', [OrderController::class, 'accept'])->name('customer-orders.accept');
        Route::post('/customer-orders/{order}/reject', [OrderController::class, 'reject'])->name('customer-orders.reject');
        Route::post('/customer-orders/{order}/submit', [OrderController::class, 'submitForApproval'])->name('customer-orders.submit');
        Route::post('/customer-orders/{order}/complete', [OrderController::class, 'submitForApproval'])->name('customer-orders.complete');
        Route::post('/customer-orders/{order}/allocate', [OrderController::class, 'allocate'])->name('customer-orders.allocate');
        Route::post('/customer-orders/{order}/approve', [OrderController::class, 'approve'])->name('customer-orders.approve');
        Route::post('/customer-orders/{order}/status', [OrderController::class, 'updateStatus'])->name('customer-orders.update-status');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/filters', [OrderController::class, 'filters'])->name('orders.filters');
        Route::get('/orders/order-types', [OrderController::class, 'orderTypes'])->name('orders.order-types');
        Route::post('/orders/bulk-accept', [OrderController::class, 'bulkAccept'])->name('orders.bulk-accept');
        Route::post('/orders/bulk-reject', [OrderController::class, 'bulkReject'])->name('orders.bulk-reject');
        Route::post('/orders/bulk-complete', [OrderController::class, 'bulkComplete'])->name('orders.bulk-complete');
        Route::post('/orders/bulk-submit', [OrderController::class, 'bulkComplete'])->name('orders.bulk-submit');
        Route::post('/orders/bulk-allocate', [OrderController::class, 'bulkAllocate'])->name('orders.bulk-allocate');
        Route::post('/orders/bulk-approve', [OrderController::class, 'bulkApprove'])->name('orders.bulk-approve');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
        Route::post('/orders/{order}/reject', [OrderController::class, 'reject'])->name('orders.reject');
        Route::post('/orders/{order}/submit', [OrderController::class, 'submitForApproval'])->name('orders.submit');
        Route::post('/orders/{order}/complete', [OrderController::class, 'submitForApproval'])->name('orders.complete');
        Route::post('/orders/{order}/allocate', [OrderController::class, 'allocate'])->name('orders.allocate');
        Route::post('/orders/{order}/approve', [OrderController::class, 'approve'])->name('orders.approve');
        Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::get('/craftsmen', [CraftsmanController::class, 'index'])->name('craftsmen.index');
        Route::get('/craftsmen/{craftsman}', [CraftsmanController::class, 'show'])->name('craftsmen.show');
    });
    Route::prefix('admin')->middleware('api.admin')->name('api.admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('/work-orders/tabs', [WorkOrderController::class, 'tabs'])->name('work-orders.tabs');
        Route::get('/work-orders/filters', [WorkOrderController::class, 'filters'])->name('work-orders.filters');
        Route::get('/work-orders/design-codes', [WorkOrderController::class, 'designCodes'])->name('work-orders.design-codes');
        Route::get('/work-orders/nicknames', [WorkOrderController::class, 'nicknames'])->name('work-orders.nicknames');
        Route::get('/work-orders/order-types', [WorkOrderController::class, 'orderTypes'])->name('work-orders.order-types');
        Route::get('/work-orders/product-names', [WorkOrderController::class, 'productNames'])->name('work-orders.product-names');
        Route::get('/work-orders/categories', [WorkOrderController::class, 'categories'])->name('work-orders.categories');
        Route::get('/work-orders/subcategories', [WorkOrderController::class, 'subcategories'])->name('work-orders.subcategories');
        Route::get('/work-orders/craftsmen', [WorkOrderController::class, 'craftsmenFilter'])->name('work-orders.craftsmen-filter');
        Route::post('/work-orders/bulk-reject', [WorkOrderController::class, 'bulkReject'])->name('work-orders.bulk-reject');
        Route::post('/work-orders/bulk-print', [WorkOrderController::class, 'bulkPrint'])->name('work-orders.bulk-print');
        Route::get('/work-orders/{workOrder}/print', [WorkOrderController::class, 'print'])->name('work-orders.print');
        Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
        Route::get('/customer-orders', [OrderController::class, 'index'])->name('customer-orders.index');
        Route::get('/customer-orders/filters', [OrderController::class, 'filters'])->name('customer-orders.filters');
        Route::get('/customer-orders/order-types', [OrderController::class, 'orderTypes'])->name('customer-orders.order-types');
        Route::post('/customer-orders/bulk-allocate', [OrderController::class, 'bulkAllocate'])->name('customer-orders.bulk-allocate');
        Route::post('/customer-orders/bulk-approve', [OrderController::class, 'bulkApprove'])->name('customer-orders.bulk-approve');
        Route::post('/customer-orders/bulk-reject', [OrderController::class, 'bulkReject'])->name('customer-orders.bulk-reject');
        Route::get('/customer-orders/{order}', [OrderController::class, 'show'])->name('customer-orders.show');
        Route::post('/customer-orders/{order}/allocate', [OrderController::class, 'allocate'])->name('customer-orders.allocate');
        Route::post('/customer-orders/{order}/approve', [OrderController::class, 'approve'])->name('customer-orders.approve');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/filters', [OrderController::class, 'filters'])->name('orders.filters');
        Route::get('/orders/order-types', [OrderController::class, 'orderTypes'])->name('orders.order-types');
        Route::post('/orders/bulk-allocate', [OrderController::class, 'bulkAllocate'])->name('orders.bulk-allocate');
        Route::post('/orders/bulk-approve', [OrderController::class, 'bulkApprove'])->name('orders.bulk-approve');
        Route::post('/orders/bulk-reject', [OrderController::class, 'bulkReject'])->name('orders.bulk-reject');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/allocate', [OrderController::class, 'allocate'])->name('orders.allocate');
        Route::post('/orders/{order}/approve', [OrderController::class, 'approve'])->name('orders.approve');
        Route::get('/craftsmen', [CraftsmanController::class, 'index'])->name('craftsmen.index');
        Route::get('/craftsmen/{craftsman}', [CraftsmanController::class, 'show'])->name('craftsmen.show');
    });

    Route::prefix('craftsman')->middleware('api.craftsman')->name('api.craftsman.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('/work-orders/tabs', [WorkOrderController::class, 'tabs'])->name('work-orders.tabs');
        Route::get('/work-orders/filters', [WorkOrderController::class, 'filters'])->name('work-orders.filters');
        Route::get('/work-orders/design-codes', [WorkOrderController::class, 'designCodes'])->name('work-orders.design-codes');
        Route::get('/work-orders/nicknames', [WorkOrderController::class, 'nicknames'])->name('work-orders.nicknames');
        Route::get('/work-orders/order-types', [WorkOrderController::class, 'orderTypes'])->name('work-orders.order-types');
        Route::get('/work-orders/product-names', [WorkOrderController::class, 'productNames'])->name('work-orders.product-names');
        Route::get('/work-orders/categories', [WorkOrderController::class, 'categories'])->name('work-orders.categories');
        Route::get('/work-orders/subcategories', [WorkOrderController::class, 'subcategories'])->name('work-orders.subcategories');
        Route::post('/work-orders/bulk-accept', [WorkOrderController::class, 'bulkAccept'])->name('work-orders.bulk-accept');
        Route::post('/work-orders/bulk-submit', [WorkOrderController::class, 'bulkSubmit'])->name('work-orders.bulk-submit');
        Route::post('/work-orders/bulk-reject', [WorkOrderController::class, 'bulkReject'])->name('work-orders.bulk-reject');
        Route::post('/work-orders/bulk-print', [WorkOrderController::class, 'bulkPrint'])->name('work-orders.bulk-print');
        Route::get('/work-orders/{workOrder}/print', [WorkOrderController::class, 'print'])->name('work-orders.print');
        Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
        Route::post('/work-orders/{workOrder}/accept', [WorkOrderController::class, 'accept'])->name('work-orders.accept');
        Route::post('/work-orders/{workOrder}/reject', [WorkOrderController::class, 'reject'])->name('work-orders.reject');
        Route::post('/work-orders/{workOrder}/submit', [WorkOrderController::class, 'submitForApproval'])->name('work-orders.submit');
        Route::get('/customer-orders', [OrderController::class, 'index'])->name('customer-orders.index');
        Route::get('/customer-orders/filters', [OrderController::class, 'filters'])->name('customer-orders.filters');
        Route::get('/customer-orders/order-types', [OrderController::class, 'orderTypes'])->name('customer-orders.order-types');
        Route::post('/customer-orders/bulk-accept', [OrderController::class, 'bulkAccept'])->name('customer-orders.bulk-accept');
        Route::post('/customer-orders/bulk-submit', [OrderController::class, 'bulkComplete'])->name('customer-orders.bulk-submit');
        Route::post('/customer-orders/bulk-complete', [OrderController::class, 'bulkComplete'])->name('customer-orders.bulk-complete');
        Route::post('/customer-orders/bulk-reject', [OrderController::class, 'bulkReject'])->name('customer-orders.bulk-reject');
        Route::get('/customer-orders/{order}', [OrderController::class, 'show'])->name('customer-orders.show');
        Route::post('/customer-orders/{order}/accept', [OrderController::class, 'accept'])->name('customer-orders.accept');
        Route::post('/customer-orders/{order}/reject', [OrderController::class, 'reject'])->name('customer-orders.reject');
        Route::post('/customer-orders/{order}/submit', [OrderController::class, 'submitForApproval'])->name('customer-orders.submit');
        Route::post('/customer-orders/{order}/complete', [OrderController::class, 'submitForApproval'])->name('customer-orders.complete');
        Route::post('/customer-orders/{order}/status', [OrderController::class, 'updateStatus'])->name('customer-orders.update-status');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/filters', [OrderController::class, 'filters'])->name('orders.filters');
        Route::get('/orders/order-types', [OrderController::class, 'orderTypes'])->name('orders.order-types');
        Route::post('/orders/bulk-accept', [OrderController::class, 'bulkAccept'])->name('orders.bulk-accept');
        Route::post('/orders/bulk-submit', [OrderController::class, 'bulkComplete'])->name('orders.bulk-submit');
        Route::post('/orders/bulk-complete', [OrderController::class, 'bulkComplete'])->name('orders.bulk-complete');
        Route::post('/orders/bulk-reject', [OrderController::class, 'bulkReject'])->name('orders.bulk-reject');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
        Route::post('/orders/{order}/reject', [OrderController::class, 'reject'])->name('orders.reject');
        Route::post('/orders/{order}/submit', [OrderController::class, 'submitForApproval'])->name('orders.submit');
        Route::post('/orders/{order}/complete', [OrderController::class, 'submitForApproval'])->name('orders.complete');
        Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    });
});
