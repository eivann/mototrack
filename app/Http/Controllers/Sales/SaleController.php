<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HandlesExceptions;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    use HandlesExceptions;
    public function recordSaleForm()
    {
        $items = InventoryItem::select('id', 'sku', 'part_name', 'brand', 'model', 'price', 'quantity')
            ->orderBy('part_name')
            ->get();
        return view('sales.create', compact('items'));
    }

    public function storeSale(StoreSaleRequest $request)
    {
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

        // H-6: Use ULID for collision-resistant, non-guessable transaction IDs
        $transactionReference = 'TRX-' . Str::ulid();

        return $this->attempt(function () use ($cartItems, $transactionReference, $request) {
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
                    \App\Jobs\CheckLowStockAlertJob::dispatch($item->id);
                }
            });

            return redirect("/sales/{$transactionReference}/receipt")->with('success', 'Transaction completed successfully!');
        }, 'Transaction failed. Please check stock levels and try again.');
    }

    public function history(Request $request)
    {
        $baseQuery = Sale::query();

        if ($request->from && $request->to) {
            $baseQuery->whereBetween('created_at', [$request->from, $request->to]);
        }

        if ($request->filter == 'today') {
            $baseQuery->whereDate('created_at', today());
        } elseif ($request->filter == 'week') {
            $baseQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($request->filter == 'month') {
            $baseQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        // Use DB aggregates to avoid loading all rows into memory for totals
        $totalsQuery = clone $baseQuery;
        $totalSales = $totalsQuery->sum('total_price');
        $totalTransactions = (clone $totalsQuery)->whereNotNull('transaction_reference')->distinct('transaction_reference')->count('transaction_reference')
                           + (clone $totalsQuery)->whereNull('transaction_reference')->count();

        $sales = $baseQuery->with('item')->latest()->paginate(10)->withQueryString();
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
        DB::transaction(function () use ($id) {
            $sale = Sale::lockForUpdate()->findOrFail($id);
            $item = $sale->inventory_item_id
                ? InventoryItem::lockForUpdate()->find($sale->inventory_item_id)
                : null;

            if ($item) {
                $item->increment('quantity', $sale->quantity);
                if ($item->quantity > $item->low_stock_threshold) {
                    $item->update(['low_stock_notified' => false]);
                }
            }

            $sale->delete();
        });

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

    public function downloadReceipt($id)
    {
        $sales = Sale::with('item')->where('transaction_reference', $id)->get();
        if ($sales->isEmpty()) {
            abort(404, 'Receipt not found.');
        }

        $transactionId = $sales->first()->transaction_reference ?? 'SALE-' . $sales->first()->id;
        $date = $sales->first()->created_at->format('F d, Y h:i A');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf_receipt', compact('sales', 'transactionId', 'date'));
        
        return $pdf->download("Receipt_{$transactionId}.pdf");
    }
}
