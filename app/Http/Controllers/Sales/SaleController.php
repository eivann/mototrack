<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HandlesExceptions;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Actions\CommitOrder;
use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    use HandlesExceptions;

    public function __construct(protected CommitOrder $commitOrder)
    {
    }

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

        // Audit #6: the two-pass lock/total/create/decrement logic lives in the
        // shared CommitOrder action. POS semantics: the tendered amount is
        // stamped on every row and change is not clamped (can show negatives),
        // and a low-stock alert is dispatched per item.
        return $this->attempt(function () use ($cartItems, $transactionReference, $request) {
            $lines = [];
            foreach ($cartItems as $cartItem) {
                $lines[] = ['id' => $cartItem['id'], 'quantity' => $cartItem['quantity']];
            }

            $tendered = (float) $request->amount_tendered;

            $this->commitOrder->execute(
                $lines,
                $transactionReference,
                null,
                'Not enough stock for ',
                function (bool $isFirst, float $grandTotal) use ($tendered) {
                    if ($tendered < $grandTotal) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'amount_tendered' => 'Insufficient cash received.',
                        ]);
                    }
                    return $isFirst ? $tendered : 0;
                },
                fn (bool $isFirst, float $grandTotal) => $isFirst ? round($tendered - $grandTotal, 2) : 0,
                dispatchLowStockAlert: true
            );

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
        // Audit #3.5: Exclude refunded/unpaid sales from total revenue
        $totalSales = Sale::query()
            ->where(function($q) {
                $q->where('status', 'completed')->orWhere('amount_tendered', '>', 0);
            })
            ->where('status', '!=', \App\Enums\OrderStatus::SALE_REFUNDED)
            ->whereNotNull('payment_confirmed_at');

        if ($request->from && $request->to) {
            $totalSales->whereBetween('payment_confirmed_at', [$request->from, $request->to]);
        }
        if ($request->filter == 'today') {
            $totalSales->whereDate('payment_confirmed_at', today());
        } elseif ($request->filter == 'week') {
            $totalSales->whereBetween('payment_confirmed_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($request->filter == 'month') {
            $totalSales->whereMonth('payment_confirmed_at', now()->month)->whereYear('payment_confirmed_at', now()->year);
        }

        $totalSales = $totalSales->sum('total_price');

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

    public function complete($id, Request $request)
    {
        DB::transaction(function () use ($id, $request) {
            $sales = Sale::lockForUpdate()->where('transaction_reference', $id)->get();
            if ($sales->isEmpty()) {
                abort(404, 'Reservation not found.');
            }

            $tendered = (float) $request->input('amount_tendered');
            $grandTotal = $sales->sum('total_price');
            $isPending = false;

            foreach ($sales as $sale) {
                if ($sale->status === 'pending') {
                    $isPending = true;
                    if ($sale->expires_at && now()->greaterThan($sale->expires_at)) {
                        abort(400, 'This reservation has expired and cannot be completed.');
                    }
                }
            }

            if (!$isPending) {
                return; // already completed
            }

            if ($tendered < $grandTotal) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount_tendered' => 'Insufficient cash received for pickup completion.',
                ]);
            }

            $isFirst = true;
            foreach ($sales as $sale) {
                if ($sale->status === 'pending') {
                    $sale->update([
                        'status' => 'completed',
                        'amount_tendered' => $isFirst ? $tendered : 0,
                        'change' => $isFirst ? round($tendered - $grandTotal, 2) : 0,
                        'picked_up_at' => now(),
                        'payment_confirmed_at' => now()
                    ]);
                    $isFirst = false;
                }
            }
        });

        return redirect()->back()->with('success', 'Reservation marked as completed/paid!');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $sale = Sale::lockForUpdate()->findOrFail($id);
            // Inventory is no longer affected by archiving/deletion.
            // Cancellations/expirations must be handled via specific cancel endpoints.

            $sale->delete();
        });

        return redirect()->back()->with('success', 'Sale archived successfully.');
    }

    public function receipt($id)
    {
        $sales = Sale::with('item')->where('transaction_reference', $id)->get();
        if ($sales->isEmpty()) {
            abort(404, 'Receipt not found.');
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
