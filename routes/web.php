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
use App\Http\Controllers\CafeTableController;
use App\Http\Controllers\PaymentSettingController;
use App\Http\Controllers\DokuPaymentController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\TableRequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerFeatureController;
use App\Http\Controllers\SaleActionController;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes(['middleware' => 'throttle:login']);

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Public QR table ordering. Payment remains at cashier in MVP.
Route::get('order/{token}', [TableOrderController::class, 'menu'])->name('table-orders.menu');
Route::post('order/{token}', [TableOrderController::class, 'store'])->name('table-orders.store');
Route::get('health', HealthController::class)->name('health');
Route::post('order/session/{token}/request', [TableRequestController::class, 'store'])->name('table-requests.store');
Route::get('order/session/{token}/status', [TableOrderController::class, 'status'])->name('table-orders.status.customer');
Route::post('customer/complaints', [CustomerFeatureController::class, 'complaint'])->name('customer.complaints');
Route::post('customer/vouchers/validate', [CustomerFeatureController::class, 'voucher'])->name('customer.vouchers.validate');
Route::post('payments/doku/notify', [DokuPaymentController::class, 'notify'])->name('doku.payment.notify');
Route::get('payments/doku/return', [DokuPaymentController::class, 'return'])->name('doku.payment.return');
Route::post('payments/doku/{order}', [DokuPaymentController::class, 'create'])->name('doku.payment.create');

Route::group(['middleware' => ['auth']], function() {
    // RBAC Management
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);

    // Master Data
    Route::resource('outlets', OutletController::class);
    Route::get('tables', [CafeTableController::class, 'index'])->name('tables.index');
    Route::get('tables/create', [CafeTableController::class, 'create'])->name('tables.create');
    Route::post('tables', [CafeTableController::class, 'store'])->name('tables.store');
    Route::get('tables/{table}/qr', [CafeTableController::class, 'qr'])->name('tables.qr');
    Route::get('tables/{table}/qr/image', [CafeTableController::class, 'qrImage'])->name('tables.qr.image');
    Route::patch('tables/{table}/occupancy', [CafeTableController::class, 'occupancy'])->name('tables.occupancy');
    Route::patch('table-sessions/{session}/move', [CafeTableController::class, 'moveSession'])->name('table-sessions.move');
    Route::patch('table-sessions/{session}/merge', [CafeTableController::class, 'mergeSession'])->name('table-sessions.merge');
    Route::get('settings/payment', [PaymentSettingController::class, 'edit'])->name('settings.payment');
    Route::post('settings/payment', [PaymentSettingController::class, 'update'])->name('settings.payment.update');
    Route::get('kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
    Route::patch('kitchen/orders/{order}/status', [KitchenController::class, 'updateStatus'])->name('kitchen.orders.status');
    Route::get('table-requests', [TableRequestController::class, 'index'])->middleware('permission:pos-access')->name('table-requests.index');
    Route::patch('table-requests/{tableRequest}', [TableRequestController::class, 'update'])->middleware('permission:pos-access')->name('table-requests.update');
    Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::patch('approvals/{approval}', [ApprovalController::class, 'update'])->name('approvals.update');
    Route::post('sales/{sale}/void', [SaleActionController::class, 'void'])->name('sales.void');
    Route::post('sales/{sale}/refund', [SaleActionController::class, 'refund'])->name('sales.refund');
    Route::post('sales/{sale}/partial-refund', [SaleActionController::class, 'partialRefund'])->name('sales.partial-refund');
    Route::post('sales/{sale}/discount', [SaleActionController::class, 'discount'])->name('sales.discount');
    Route::resource('suppliers', SupplierController::class);
    Route::resource('materials', MaterialController::class);
    Route::resource('products', ProductController::class);

    // Batches & Stock Opname / Waste Log (FIFO)
    Route::get('batches', [MaterialBatchController::class, 'index'])->name('batches.index');
    Route::get('batches/opname', [MaterialBatchController::class, 'opname'])->name('batches.opname');
    Route::post('batches/opname', [MaterialBatchController::class, 'storeOpname'])->name('batches.opname.store');
    Route::post('materials/adjustment-request', [MaterialBatchController::class, 'requestAdjustment'])->middleware('permission:stock-opname')->name('materials.adjustment.request');
    Route::post('materials/waste', [MaterialBatchController::class, 'waste'])->middleware('permission:stock-opname')->name('materials.waste');
    Route::post('materials/transfer', [MaterialBatchController::class, 'transfer'])->middleware('permission:stock-opname')->name('materials.transfer');

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
    Route::post('pos/held-orders', [PosController::class, 'hold'])->name('pos.hold');
    Route::get('pos/held-orders', [PosController::class, 'heldOrders'])->name('pos.held-orders');
    Route::get('pos/held-orders/{heldOrder}', [PosController::class, 'recall'])->name('pos.recall');
    Route::get('pos/receipt/{sale}', [PosController::class, 'receipt'])->name('pos.receipt');
    Route::get('table-orders', [TableOrderController::class, 'index'])->name('table-orders.index');
    Route::patch('table-orders/{order}/status', [TableOrderController::class, 'updateStatus'])->name('table-orders.status');
    Route::post('table-orders/{order}/checkout', [TableOrderController::class, 'checkout'])->name('table-orders.checkout');
    Route::post('table-orders/{order}/split-checkout', [TableOrderController::class, 'splitCheckout'])->name('table-orders.split-checkout');
    Route::get('table-orders/{order}/split', fn (\App\Models\CustomerOrder $order) => view('table-orders.split', compact('order')))->name('table-orders.split-form');

    // Reports & Exports
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/sales/export', [ReportController::class, 'exportSalesCsv'])->name('reports.sales.export');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('reports/inventory/export', [ReportController::class, 'exportInventoryCsv'])->name('reports.inventory.export');
    Route::get('reports/shifts', [ReportController::class, 'shifts'])->name('reports.shifts');
    Route::get('reports/products/margins', [ReportController::class, 'productMargins'])->name('reports.products.margins');
    Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('reports/analytics', [ReportController::class, 'analytics'])->name('reports.analytics');
    Route::get('reports/profit-loss/export', [ReportController::class, 'exportProfitLoss'])->name('reports.profit-loss.export');
    Route::get('reports/inventory-alerts', [ReportController::class, 'inventoryAlerts'])->name('reports.inventory-alerts');
    Route::get('reports/business-analytics', [ReportController::class, 'businessAnalytics'])->name('reports.business-analytics');
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');

    // Activity Logs (Audit Trail)
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});
