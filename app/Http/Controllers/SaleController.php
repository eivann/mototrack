<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function recordSaleForm()
    {
        $items = InventoryItem::all();
        return view('create', compact('items'));
    }

public function storeSale(Request $request)
{
    $request->validate([
        'cart_data' => 'required|json',
        'amount_tendered' => 'required|numeric|min:0',
    ]);

    $cartItems = json_decode($request->cart_data, true);
    
    if (empty($cartItems) || !is_array($cartItems)) {
        return back()->with('error', 'Cart is empty!');
    }

    // Validate each cart item structure
    foreach ($cartItems as $cartItem) {
        if (!isset($cartItem['id'], $cartItem['quantity'])
            || !is_numeric($cartItem['id'])
            || !is_numeric($cartItem['quantity'])
            || $cartItem['quantity'] < 1) {
            return back()->with('error', 'Invalid cart data.');
        }
    }

    $transactionReference = 'TRX-' . strtoupper(uniqid());

    try {
        $totalAmount = \Illuminate\Support\Facades\DB::transaction(function () use ($cartItems, $transactionReference, $request) {
            $totalAmount = 0;

            // Validate all stock with locks
            foreach ($cartItems as $cartItem) {
                $item = InventoryItem::lockForUpdate()->find($cartItem['id']);
                if (!$item || $cartItem['quantity'] > $item->quantity) {
                    throw new \Exception('Not enough stock for ' . ($item ? $item->part_name : 'Unknown item'));
                }
                $totalAmount += ($item->price * $cartItem['quantity']);
            }

            $change = $request->amount_tendered - $totalAmount;

            // Process sales
            foreach ($cartItems as $cartItem) {
                $item = InventoryItem::find($cartItem['id']);
                $total = $item->price * $cartItem['quantity'];

                Sale::create([
                    'inventory_item_id' => $item->id,
                    'quantity' => $cartItem['quantity'],
                    'price' => $item->price, 
                    'total_price' => $total,
                    'transaction_reference' => $transactionReference,
                    'amount_tendered' => $request->amount_tendered,
                    'change' => $change
                ]);

                $item->decrement('quantity', $cartItem['quantity']);

                // Check for low stock and send alert if necessary
                \App\Services\SmsService::checkLowStockAlert($item);
            }

            return $totalAmount;
        });
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }

    return redirect("/sales/{$transactionReference}/receipt")->with('success', 'Transaction completed successfully!');
}
    public function history(Request $request)
    {
        $query = \App\Models\Sale::with('item');


        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        if ($request->filter == 'today') {
            $query->whereDate('created_at', today());
        } elseif ($request->filter == 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($request->filter == 'month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        $salesQuery = $query->latest();
        
        // Totals should still be calculated from ALL records matching filters
        $allSalesForTotals = $salesQuery->get();
        $totalSales = $allSalesForTotals->sum('total_price');
        $totalTransactions = $allSalesForTotals->whereNotNull('transaction_reference')->unique('transaction_reference')->count() 
                            + $allSalesForTotals->whereNull('transaction_reference')->count();

        // Paginated results for the table
        $sales = $salesQuery->paginate(10)->withQueryString();

        // Paginate inventory items for the Stock Levels tab too
        $inventoryItems = \App\Models\InventoryItem::orderBy('quantity', 'asc')->paginate(12, ['*'], 'stock_page')->withQueryString();

        return view('history', compact(
            'sales',
            'totalSales',
            'totalTransactions',
            'inventoryItems'
        ));
    }
public function destroy($id)
{
    $sale = Sale::findOrFail($id);
     $item = $sale->item;

    if ($item) {
        $item->quantity += $sale->quantity;
        
        // If stock is back above threshold, reset the notification flag
        if ($item->quantity > $item->low_stock_threshold) {
            $item->low_stock_notified = false;
        }
        
        $item->save();
    }
    $sale->delete();

    return redirect()->back()->with('success', 'Sale archived and stock restored!');
}
public function receipt($id)
{
    // Try to find by transaction_reference first
    $sales = Sale::with('item')->where('transaction_reference', $id)->get();
    
    // Fallback for old single sales that don't have a transaction_reference
    if ($sales->isEmpty()) {
        $sales = collect([Sale::with('item')->findOrFail($id)]);
    }

    return view('receipt_sales', compact('sales'));
}
}