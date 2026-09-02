<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/* ===== Auth ===== */

Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'attempt'])->name('login.attempt');
});

Route::get('/dashboard', [\App\Http\Controllers\Erp\ErpDashboardController::class, 'index'])
    ->middleware(['auth', 'active'])
    ->name('dashboard');

Route::get('/', fn() => redirect()->route('dashboard'))->middleware(['auth', 'active']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware(['auth', 'active']);

Route::middleware(['auth', 'active'])->group(function () {
    // Notifications Engine
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/badge', [\App\Http\Controllers\NotificationController::class, 'getBadge'])->name('notifications.badge');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark_all_read');

    // Switch Project Routes
    Route::get('/projects/switch', [\App\Http\Controllers\ProjectSwitchController::class, 'showSwitchForm'])->name('projects.switch');
    Route::post('/projects/switch', [\App\Http\Controllers\ProjectSwitchController::class, 'processSwitch'])->name('projects.switch.process');

    /* ===== ERP ROUTES ===== */
    Route::prefix('erp')->name('erp.')->group(function () {
        // Products
        Route::get('products/export', [\App\Http\Controllers\Erp\ErpProductController::class, 'exportExcel'])->name('products.export');
        Route::post('products/datatable', [\App\Http\Controllers\Erp\ErpProductController::class, 'datatable'])->name('products.datatable');
        Route::get('products/next-code', [\App\Http\Controllers\Erp\ErpProductController::class, 'nextCode'])->name('products.next_code');
        Route::resource('products', \App\Http\Controllers\Erp\ErpProductController::class)->except(['create', 'edit', 'show']);

        // Product Families
        Route::post('product-families/datatable', [\App\Http\Controllers\Erp\ProductFamilyController::class, 'datatable'])->name('product-families.datatable');
        Route::resource('product-families', \App\Http\Controllers\Erp\ProductFamilyController::class)->except(['create', 'edit', 'show']);

        // Product Types
        Route::post('product-types/datatable', [\App\Http\Controllers\Erp\ProductTypeController::class, 'datatable'])->name('product-types.datatable');
        Route::resource('product-types', \App\Http\Controllers\Erp\ProductTypeController::class)->except(['create', 'edit', 'show']);

        // Brands
        Route::post('brands/datatable', [\App\Http\Controllers\Erp\BrandController::class, 'datatable'])->name('brands.datatable');
        Route::resource('brands', \App\Http\Controllers\Erp\BrandController::class)->except(['create', 'edit', 'show']);

        // Product Models
        Route::post('product-models/datatable', [\App\Http\Controllers\Erp\ProductModelController::class, 'datatable'])->name('product-models.datatable');
        Route::resource('product-models', \App\Http\Controllers\Erp\ProductModelController::class)->except(['create', 'edit', 'show']);

        // Currencies
        Route::post('currencies/datatable', [\App\Http\Controllers\Erp\CurrencyController::class, 'datatable'])->name('currencies.datatable');
        Route::resource('currencies', \App\Http\Controllers\Erp\CurrencyController::class)->except(['create', 'edit', 'show']);

        // UOMs
        Route::post('uoms/datatable', [\App\Http\Controllers\Erp\UomController::class, 'datatable'])->name('uoms.datatable');
        Route::resource('uoms', \App\Http\Controllers\Erp\UomController::class)->except(['create', 'edit', 'show']);

        // Budget Project System
        Route::post('budget-parents/datatable', [\App\Http\Controllers\Erp\ErpBudgetParentController::class, 'datatable'])->name('budget-parents.datatable');
        Route::resource('budget-parents', \App\Http\Controllers\Erp\ErpBudgetParentController::class)->except(['create', 'edit', 'show']);


        Route::post('sub-projects/datatable', [\App\Http\Controllers\Erp\ErpSubProjectController::class, 'datatable'])->name('sub-projects.datatable');
        Route::resource('sub-projects', \App\Http\Controllers\Erp\ErpSubProjectController::class)->except(['create', 'edit', 'show']);

        Route::post('work-items/datatable', [\App\Http\Controllers\Erp\ErpWorkItemController::class, 'datatable'])->name('work-items.datatable');
        Route::get('work-items/next-code', [\App\Http\Controllers\Erp\ErpWorkItemController::class, 'getNextWidCode'])->name('work-items.next-code');
        Route::resource('work-items', \App\Http\Controllers\Erp\ErpWorkItemController::class)->except(['create', 'edit', 'show']);
        
        Route::get('work-items/by-project', [\App\Http\Controllers\Erp\ErpWorkItemController::class, 'getWorkItemsByProject'])->name('work-items.by_project');

        // Request Form
        Route::post('request-form/datatable', [\App\Http\Controllers\Erp\RequestFormController::class, 'datatable'])->name('request-form.datatable');
        Route::get('request-form/create', [\App\Http\Controllers\Erp\RequestFormController::class, 'create'])->name('request-form.create');
        Route::post('request-form', [\App\Http\Controllers\Erp\RequestFormController::class, 'store'])->name('request-form.store');
        Route::get('request-form', [\App\Http\Controllers\Erp\RequestFormController::class, 'index'])->name('request-form.index');
        Route::get('request-form/{requestForm}/edit', [\App\Http\Controllers\Erp\RequestFormController::class, 'edit'])->name('request-form.edit');
        Route::put('request-form/{requestForm}', [\App\Http\Controllers\Erp\RequestFormController::class, 'update'])->name('request-form.update');
        Route::get('request-form/{requestForm}', [\App\Http\Controllers\Erp\RequestFormController::class, 'show'])->name('request-form.show');
        Route::post('request-form/{requestForm}/unlock', [\App\Http\Controllers\Erp\RequestFormController::class, 'unlock'])->name('request-form.unlock');
        Route::delete('request-form/{requestForm}', [\App\Http\Controllers\Erp\RequestFormController::class, 'destroy'])->name('request-form.destroy');
        // Request Form - Notes & Attachments
        Route::post('request-form/{requestForm}/notes', [\App\Http\Controllers\Erp\RequestFormNoteController::class, 'storeNote'])->name('request-form.notes.store');
        Route::post('request-form/{requestForm}/attachments', [\App\Http\Controllers\Erp\RequestFormNoteController::class, 'storeAttachment'])->name('request-form.attachments.store');

        // Request Form - Purchase Requests
        Route::get('purchase-requests/{purchaseRequest}', [\App\Http\Controllers\Erp\PurchaseRequestController::class, 'show'])->name('purchase-requests.show');
        Route::post('request-form/{requestForm}/purchase-requests', [\App\Http\Controllers\Erp\PurchaseRequestController::class, 'store'])->name('purchase-requests.store');
        Route::delete('purchase-requests/{purchaseRequest}', [\App\Http\Controllers\Erp\PurchaseRequestController::class, 'destroy'])->name('purchase-requests.destroy');

        // Request Form Items
        Route::get('request-form-items/{requestFormItem}', [\App\Http\Controllers\Erp\RequestFormItemController::class, 'show'])->name('request-form-items.show');

        // Purchase Request Items
        Route::get('purchase-request-items/{purchaseRequestItem}', [\App\Http\Controllers\Erp\PurchaseRequestItemController::class, 'show'])->name('purchase-request-items.show');

        // Request Form - Purchase Orders
        Route::get('procurement/dashboard', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'dashboard'])->name('procurement.dashboard');
        Route::get('purchase-orders', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('purchase-orders/create/{requestForm}', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('purchase-orders', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('purchase-orders/{purchaseOrder}', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::get('purchase-orders/{purchaseOrder}/edit', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
        Route::put('purchase-orders/{purchaseOrder}', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        Route::post('purchase-orders/{purchaseOrder}/submit', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
        Route::post('purchase-orders/{purchaseOrder}/approve', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::delete('purchase-orders/{purchaseOrder}', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
        Route::post('purchase-orders/{purchaseOrder}/reject', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::post('purchase-orders/{purchaseOrder}/unlock', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'unlock'])->name('purchase-orders.unlock');
        Route::get('purchase-orders/{purchaseOrder}/print', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'print'])->name('purchase-orders.print');
        Route::post('purchase-orders/{purchaseOrder}/verify', [\App\Http\Controllers\Erp\ErpPurchaseOrderController::class, 'verify'])->name('purchase-orders.verify');

        // Goods Receipts (Delivery Orders)
        Route::get('goods-receipts/create/{purchaseOrder}', [\App\Http\Controllers\Erp\ErpGoodsReceiptController::class, 'create'])->name('goods-receipts.create');
        Route::post('goods-receipts/{purchaseOrder}', [\App\Http\Controllers\Erp\ErpGoodsReceiptController::class, 'store'])->name('goods-receipts.store');
        Route::get('goods-receipts/{goodsReceipt}', [\App\Http\Controllers\Erp\ErpGoodsReceiptController::class, 'show'])->name('goods-receipts.show');
        Route::post('goods-receipts/{goodsReceipt}/receive', [\App\Http\Controllers\Erp\ErpGoodsReceiptController::class, 'receive'])->name('goods-receipts.receive');
        Route::get('goods-receipts/{goodsReceipt}/print', [\App\Http\Controllers\Erp\ErpGoodsReceiptController::class, 'print'])->name('goods-receipts.print');
        Route::delete('goods-receipts/{goodsReceipt}', [\App\Http\Controllers\Erp\ErpGoodsReceiptController::class, 'destroy'])->name('goods-receipts.destroy');

        // Payment Advices & Supplier Invoices
        Route::get('payment-advices', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'index'])->name('payment-advices.index');
        Route::post('payment-advices/datatable', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'datatable'])->name('payment-advices.datatable');
        Route::get('payment-advices/create', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'create'])->name('payment-advices.create');
        Route::post('payment-advices', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'store'])->name('payment-advices.store');
        Route::get('payment-advices/{paymentAdvice}', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'show'])->name('payment-advices.show');
        Route::post('payment-advices/{paymentAdvice}/details', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'storeDetail'])->name('payment-advice-details.store');
        Route::get('payment-advice-details/{paymentAdviceDetail}', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'showDetail'])->name('payment-advice-details.show');
        Route::delete('payment-advice-details/{paymentAdviceDetail}', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'destroyDetail'])->name('payment-advice-details.destroy');
        Route::post('payment-advice-details/{paymentAdviceDetail}/update-invoice', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'updateInvoice'])->name('payment-advice-details.update-invoice');
        Route::post('payment-advice-details/{paymentAdviceDetail}/submit', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'submitDetail'])->name('payment-advice-details.submit');
        Route::post('payment-advice-details/{paymentAdviceDetail}/approve', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'approveDetail'])->name('payment-advice-details.approve');
        Route::post('payment-advice-details/{paymentAdviceDetail}/reject', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'rejectDetail'])->name('payment-advice-details.reject');
        Route::post('payment-advice-details/{paymentAdviceDetail}/mark-paid', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'markPaidDetail'])->name('payment-advice-details.mark-paid');
        Route::delete('payment-advices/{paymentAdvice}', [\App\Http\Controllers\Erp\ErpPaymentAdviceController::class, 'destroy'])->name('payment-advices.destroy');

        // Request Form - Approvals
        Route::post('request-form/{requestForm}/approvals/submit', [\App\Http\Controllers\Erp\ApprovalController::class, 'submit'])->name('approvals.submit');
        Route::post('approvals/{approval}/approve', [\App\Http\Controllers\Erp\ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{approval}/reject', [\App\Http\Controllers\Erp\ApprovalController::class, 'reject'])->name('approvals.reject');

        // Approval Configs (Superadmin Only)
        Route::resource('approval-configs', \App\Http\Controllers\Erp\ApprovalConfigController::class)->only(['index', 'store', 'destroy']);

        // Dedicated ERP Suppliers
        Route::get('suppliers/datatable', [\App\Http\Controllers\Erp\ErpSupplierController::class, 'datatable'])->name('suppliers.datatable');
        Route::get('suppliers/next-code', [\App\Http\Controllers\Erp\ErpSupplierController::class, 'nextCode'])->name('suppliers.next_code');
        Route::post('suppliers/{supplier}/contacts', [\App\Http\Controllers\Erp\ErpSupplierController::class, 'storeContact'])->name('suppliers.contacts.store');
        Route::delete('supplier-contacts/{contact}', [\App\Http\Controllers\Erp\ErpSupplierController::class, 'destroyContact'])->name('suppliers.contacts.destroy');
        Route::post('suppliers/{supplier}/attachments', [\App\Http\Controllers\Erp\ErpSupplierController::class, 'storeAttachment'])->name('suppliers.attachments.store');
        Route::delete('supplier-attachments/{attachment}', [\App\Http\Controllers\Erp\ErpSupplierController::class, 'destroyAttachment'])->name('suppliers.attachments.destroy');
        Route::resource('suppliers', \App\Http\Controllers\Erp\ErpSupplierController::class);

        // Dedicated ERP Warehouses / Destinations
        Route::get('warehouses/datatable', [\App\Http\Controllers\Erp\ErpWarehouseController::class, 'datatable'])->name('warehouses.datatable');
        Route::get('warehouses/next-code', [\App\Http\Controllers\Erp\ErpWarehouseController::class, 'nextCode'])->name('warehouses.next_code');
        Route::resource('warehouses', \App\Http\Controllers\Erp\ErpWarehouseController::class);

        // Dedicated ERP Payment Terms
        Route::resource('payment-terms', \App\Http\Controllers\Erp\ErpPaymentTermController::class)->except(['create', 'show', 'edit']);

        // Inventory Stocks
        Route::post('stocks/datatable', [\App\Http\Controllers\Erp\ErpStockController::class, 'datatable'])->name('stocks.datatable');
        Route::get('stocks', [\App\Http\Controllers\Erp\ErpStockController::class, 'index'])->name('stocks.index');

        // Users & Roles
        Route::get('users/export', [\App\Http\Controllers\Erp\UserController::class, 'exportExcel'])->name('users.exportExcel');
        Route::get('users/export-seeder', [\App\Http\Controllers\Erp\UserController::class, 'exportSeeder'])->name('users.export.seeder');
        Route::post('users/bulk-destroy', [\App\Http\Controllers\Erp\UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::resource('users', \App\Http\Controllers\Erp\UserController::class)->except(['create', 'edit', 'show']);
        Route::resource('roles', \App\Http\Controllers\Erp\RoleController::class)->except(['create', 'edit', 'show']);
        
        // Projects (Tenants)
        Route::resource('projects', \App\Http\Controllers\Erp\ProjectController::class)->except(['create', 'edit', 'show']);
    });

});
