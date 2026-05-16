<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function recordSaleForm()
    {
        $items = InventoryItem::all();
        return view('sales.create', compact('items'));
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
            DB::transaction(function () use ($cartItems, $transactionReference, $request) {
                $totalAmount = 0;
                $itemsToProcess = [];

                foreach ($cartItems as $cartItem) {
                    $item = InventoryItem::lockForUpdate()->find($cartItem['id']);
                    if (!$item || $cartItem['quantity'] > $item->quantity) {
                        throw new \Exception('Not enough stock for ' . ($item ? $item->part_name : 'Unknown item'));
                    }
                    $totalAmount += ($item->price * $cartItem['quantity']);
                    $itemsToProcess[] = [
                        'item' => $item,
                        'quantity' => $cartItem['quantity']
                    ];
                }

                $change = $request->amount_tendered - $totalAmount;

                foreach ($itemsToProcess as $process) {
                    $item = $process['item'];
                    $quantity = $process['quantity'];
                    $total = $item->price * $quantity;

                    Sale::create([
                        'inventory_item_id' => $item->id,
                        'quantity' => $quantity,
                        'price' => $item->price,
                        'total_price' => $total,
                        'transaction_reference' => $transactionReference,
                        'amount_tendered' => $request->amount_tendered,
                        'change' => $change
                    ]);

                    $item->decrement('quantity', $quantity);
                    \App\Services\SmsService::checkLowStockAlert($item);
                }
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect("/sales/{$transactionReference}/receipt")->with('success', 'Transaction completed successfully!');
    }

    public function history(Request $request)
    {
        $query = Sale::with('item');

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
        $allSalesForTotals = $salesQuery->get();
        $totalSales = $allSalesForTotals->sum('total_price');
        $totalTransactions = $allSalesForTotals->whereNotNull('transaction_reference')->unique('transaction_reference')->count()
                            + $allSalesForTotals->whereNull('transaction_reference')->count();

        $sales = $salesQuery->paginate(10)->withQueryString();
        $inventoryItems = InventoryItem::orderBy('quantity', 'asc')->paginate(12, ['*'], 'stock_page')->withQueryString();

        return view('sales.history', compact(
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
        $sales = Sale::with('item')->where('transaction_reference', $id)->get();
        if ($sales->isEmpty()) {
            $sales = collect([Sale::with('item')->findOrFail($id)]);
        }
        return view('sales.receipt', compact('sales'));
    }
}
