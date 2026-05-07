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
        return view('dashboard');
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
    Route::get('/customer-dashboard', function () {
        $user = auth()->user();
        $customer = $user->customer;
        
        // Ensure customer record exists
        if (!$customer) {
            $customer = \App\Models\Customer::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }

        // Handle success redirect from Xendit
        if (request()->query('success') === 'preorder_paid') {
            $preorderId = request()->query('id');
            $latestUnpaid = \App\Models\PreOrder::where('id', $preorderId)
                ->where('customer_id', $customer->id)
                ->where('is_paid', false)
                ->first();
            
            if ($latestUnpaid) {
                $latestUnpaid->update(['is_paid' => true]);

                // Notify Owner that payment is received
                $owners = \App\Models\User::where('role', 'owner')->get();
                \Illuminate\Support\Facades\Notification::send($owners, new \App\Notifications\StatusUpdated(
                    'Pre-order Payment Received',
                    "Customer {$user->name} has completed the down payment for {$latestUnpaid->part_name}.",
                    "/preorders"
                ));
            }
        }
        
        $repairs = \App\Models\Repair::where('customer_id', $customer->id)
            ->orWhere('customer_name', $user->name) // Fallback for old records
            ->latest()
            ->get();
            
        $preorders = \App\Models\PreOrder::where('customer_id', $customer->id)
            ->orWhere('customer_name', $user->name) // Fallback for old records
            ->latest()
            ->get();
            
        return view('customer_dashboard', compact('repairs', 'preorders'));
    })->name('customer.dashboard');

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
