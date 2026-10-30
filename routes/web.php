<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\CashierShiftController;
use App\Http\Controllers\MaterialBatchController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\TableOrderController;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Public QR table ordering. Payment remains at cashier in MVP.
Route::get('order/{token}', [TableOrderController::class, 'menu'])->name('table-orders.menu');
Route::post('order/{token}', [TableOrderController::class, 'store'])->name('table-orders.store');

Route::group(['middleware' => ['auth']], function() {
    // RBAC Management
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);

    // Master Data
    Route::resource('outlets', OutletController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('materials', MaterialController::class);
    Route::resource('products', ProductController::class);

    // Batches & Stock Opname / Waste Log (FIFO)
    Route::get('batches', [MaterialBatchController::class, 'index'])->name('batches.index');
    Route::get('batches/opname', [MaterialBatchController::class, 'opname'])->name('batches.opname');
    Route::post('batches/opname', [MaterialBatchController::class, 'storeOpname'])->name('batches.opname.store');

    // Purchasing & Goods Receipt
    Route::resource('purchases', PurchaseController::class);
    Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
    Route::post('purchases/{purchase}/reject', [PurchaseController::class, 'reject'])->name('purchases.reject');
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');

    // Shift Management & Cash Reconciliation
    Route::get('shifts', [CashierShiftController::class, 'index'])->name('shifts.index');
    Route::get('shifts/create', [CashierShiftController::class, 'create'])->name('shifts.create');
    Route::post('shifts', [CashierShiftController::class, 'store'])->name('shifts.store');
    Route::get('shifts/{shift}', [CashierShiftController::class, 'show'])->name('shifts.show');
    Route::get('shifts/{shift}/close', [CashierShiftController::class, 'closeForm'])->name('shifts.close.form');
    Route::post('shifts/{shift}/close', [CashierShiftController::class, 'close'])->name('shifts.close');
    Route::post('shifts/petty-cash', [CashierShiftController::class, 'storePettyCash'])->name('shifts.petty_cash');

    // POS (Kasir)
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('pos/receipt/{sale}', [PosController::class, 'receipt'])->name('pos.receipt');
    Route::get('table-orders', [TableOrderController::class, 'index'])->name('table-orders.index');
    Route::patch('table-orders/{order}/status', [TableOrderController::class, 'updateStatus'])->name('table-orders.status');
    Route::post('table-orders/{order}/checkout', [TableOrderController::class, 'checkout'])->name('table-orders.checkout');

    // Reports & Exports
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/sales/export', [ReportController::class, 'exportSalesCsv'])->name('reports.sales.export');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('reports/inventory/export', [ReportController::class, 'exportInventoryCsv'])->name('reports.inventory.export');
    Route::get('reports/shifts', [ReportController::class, 'shifts'])->name('reports.shifts');

    // Activity Logs (Audit Trail)
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});
