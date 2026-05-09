<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\PreOrderController;
use App\Http\Controllers\CustomerController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'owner') {
        $stats = [
            'totalProducts' => \App\Models\InventoryItem::count(),
            'lowStockCount' => \App\Models\InventoryItem::whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            'activeRepairs' => \App\Models\Repair::whereIn('status', ['pending', 'ongoing'])->count(),
            'todayRevenue' => \App\Models\Sale::whereDate('created_at', now()->today())->sum('total_price'),
            'monthlyRevenue' => \App\Models\Sale::whereMonth('created_at', now()->month)->sum('total_price'),
            'totalSalesCount' => \App\Models\Sale::count(),
            'completedRepairs' => \App\Models\Repair::where('status', 'completed')->count(),
        ];
        
        $recentActivities = auth()->user()->notifications()->latest()->take(5)->get();
        
        // Data for other tabs
        $inventorySummary = \App\Models\InventoryItem::latest()->take(10)->get();
        $recentSales = \App\Models\Sale::with('item', 'customer')->latest()->take(10)->get();
        $activeServiceJobs = \App\Models\Repair::whereIn('status', ['pending', 'ongoing'])->latest()->take(10)->get();
        $recentPreorders = \App\Models\PreOrder::latest()->take(10)->get();
        
        return view('dashboard', compact('stats', 'recentActivities', 'inventorySummary', 'recentSales', 'activeServiceJobs', 'recentPreorders'));
    }
    return redirect()->route('customer.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/browse', [CustomerController::class, 'browse'])->name('browse');
Route::get('/search-parts', [CustomerController::class, 'searchParts'])->middleware('throttle:60,1')->name('search.parts');
Route::get('/compatibility', [CustomerController::class, 'compatibilityForm'])->name('compatibility.view');
Route::post('/compatibility', [CustomerController::class, 'checkCompatibility'])->name('compatibility.check');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Customer Dashboard
    Route::get('/customer-dashboard', [CustomerController::class, 'dashboard'])->name('customer.dashboard');

    // Checkout & Cart
    Route::get('/cart', [CustomerController::class, 'viewCart'])->name('cart.view');
    Route::post('/cart/add/{id}', [CustomerController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/remove/{id}', [CustomerController::class, 'removeFromCart'])->name('cart.remove');
    Route::post('/cart/increase/{id}', [CustomerController::class, 'increase'])->name('cart.increase');
    Route::post('/cart/decrease/{id}', [CustomerController::class, 'decrease'])->name('cart.decrease');
    Route::post('/cart/update/{id}', [CustomerController::class, 'updateCart'])->name('cart.update');
    
    Route::get('/checkout', [CustomerController::class, 'checkout'])->name('checkout.view');
    Route::post('/checkout', [CustomerController::class, 'processCheckout'])->name('checkout.process');
    Route::get('/receiptcart', [CustomerController::class, 'receipt'])->name('checkout.receipt');

    // Pre-orders
    Route::get('/preorder-parts', [CustomerController::class, 'preorderParts'])->name('preorder.parts');
    Route::get('/preorder-custom', [CustomerController::class, 'customPreorderForm'])->name('preorder.custom');
    Route::get('/preorder/{id}', [CustomerController::class, 'preOrderForm'])->name('preorder.form');
    Route::post('/preorder/store', [CustomerController::class, 'storePreOrder'])->name('preorder.store');

    Route::post('/notifications/{id}/read', function ($id) {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
        return back();
    })->name('notifications.read');

    Route::post('/notifications/read-all', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.read_all');
});

Route::middleware(['auth', 'owner'])->group(function () {
    // Inventory
    Route::resource('inventory', InventoryController::class);
    Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('low-stock.index');

    // Sales POS
    Route::get('/sales', [SaleController::class, 'recordSaleForm'])->name('sales.index');
    Route::post('/sales/store', [SaleController::class, 'storeSale'])->name('sales.store');
    Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');
    Route::delete('/sales/{id}', [SaleController::class, 'destroy'])->name('sales.destroy');
    Route::get('/sales/report/receipt', [SaleController::class, 'reportReceipt'])->name('sales.report_receipt');
    Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');

    // Repairs
    Route::get('/repairs', [RepairController::class, 'index'])->name('repairs.index');
    Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
    Route::put('/repairs/{id}', [RepairController::class, 'update'])->name('repairs.update');
    Route::delete('/repairs/{id}', [RepairController::class, 'destroy'])->name('repairs.destroy');
    Route::put('/repairs/{id}/pay', [RepairController::class, 'markPaid'])->name('repairs.pay');
    Route::get('/repairs/{id}/receipt', [RepairController::class, 'receipt'])->name('repairs.receipt');

    // Pre-orders Management
    Route::get('/preorders', [PreOrderController::class, 'index'])->name('preorders.index');
    Route::post('/preorders/{id}/complete', [PreOrderController::class, 'complete'])->name('preorders.complete');
    Route::post('/preorders/{id}/cancel', [PreOrderController::class, 'cancel'])->name('preorders.cancel');
    Route::post('/preorders/{id}/pay', [PreOrderController::class, 'markPaid'])->name('preorders.pay');
    Route::delete('/preorders/{id}', [PreOrderController::class, 'destroy'])->name('preorders.destroy');
});

require __DIR__.'/auth.php';
