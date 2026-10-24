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

    private const PRICE_CHANGED_MESSAGE = 'A price changed after this sale was started. The cart was refreshed with the current prices; please review the total and complete the sale again.';

    public function __construct(protected CommitOrder $commitOrder)
    {
    }

    public function recordSaleForm()
    {
        // Only the fields the POS uses. Plain arrays keep the model's appended `image_url` (a base64
        // placeholder for every item without a picture) out of the page: on a full catalogue that
        // placeholder alone made up most of a ~1 MB POS page.
        $items = InventoryItem::select('id', 'sku', 'part_name', 'brand', 'model', 'category', 'price', 'quantity')
            ->orderBy('part_name')
            ->get()
            ->map(fn (InventoryItem $item) => [
                'id' => $item->id,
                'sku' => $item->sku,
                'part_name' => $item->part_name,
                'brand' => $item->brand,
                'model' => $item->model,
                'category' => $item->category,
                'price' => number_format((float) $item->price, 2, '.', ''),
                'quantity' => (int) $item->quantity,
            ])
            ->values();

        // After a refused checkout the cashier's cart is restored from the browser draft and re-priced
        // with the current catalogue; a fresh "New Sale" always starts empty.
        $restoreDraft = session()->has('error') || session()->has('errors');

        return view('sales.create', compact('items', 'restoreDraft'));
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
                || $cartItem['quantity'] < 1
                // Optional per-item price snapshot (what the POS displayed): a plain 2-decimal amount.
                || (array_key_exists('price', $cartItem) && !(
                    (is_string($cartItem['price']) || is_int($cartItem['price']) || is_float($cartItem['price']))
                    && preg_match('/^\d+(\.\d{1,2})?$/', (string) $cartItem['price'])
                ))) {
                return back()->with('error', 'Invalid cart data.');
            }
        }

        // The POS screen sends the total it displayed and, per line, the unit price it displayed. If a
        // price changed after the screen was opened (even when changes offset each other in the total),
        // refuse with a clear message instead of charging amounts the cashier never saw; the cart is
        // restored with current prices. Both are re-checked under the stock lock below. Requests
        // without these optional fields (older clients, tests) behave exactly as before.
        $expectedTotal = $request->filled('expected_total') ? round((float) $request->expected_total, 2) : null;
        if ($this->posPricesChanged($cartItems, $expectedTotal)) {
            return back()->with('error', self::PRICE_CHANGED_MESSAGE);
        }

        // H-6: Use ULID for collision-resistant, non-guessable transaction IDs
        $transactionReference = 'TRX-' . Str::ulid();

        // Audit #6: the two-pass lock/total/create/decrement logic lives in the
        // shared CommitOrder action. POS semantics: the tendered amount is
        // stamped on every row and change is not clamped (can show negatives),
        // and a low-stock alert is dispatched per item.
        return $this->attempt(function () use ($cartItems, $transactionReference, $request, $expectedTotal) {
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
                function (bool $isFirst, float $grandTotal) use ($tendered, $expectedTotal, $cartItems) {
                    // Evaluated before anything is written, after CommitOrder has locked the stock rows:
                    // a price change between the check above and the lock still refuses the sale rather
                    // than charging an unseen amount (the re-read sees the locked, final prices).
                    if ($isFirst && (
                        ($expectedTotal !== null && abs(round($grandTotal, 2) - $expectedTotal) >= 0.005)
                        || $this->posPricesChanged($cartItems, null)
                    )) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'expected_total' => self::PRICE_CHANGED_MESSAGE,
                        ]);
                    }
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

    /**
     * Whether the current catalogue prices differ from what the POS displayed: the optional per-line
     * `price` snapshots and/or the optional expected total. Missing fields are simply not checked.
     * An item that no longer exists is left to the stock check (reported as such, not as a price change).
     */
    private function posPricesChanged(array $cartItems, ?float $expectedTotal): bool
    {
        $hasSnapshots = collect($cartItems)->contains(fn ($line) => array_key_exists('price', $line));
        if ($expectedTotal === null && !$hasSnapshots) {
            return false;
        }

        $prices = InventoryItem::query()->toBase()
            ->whereIn('id', array_map(fn ($line) => (int) $line['id'], $cartItems))
            ->pluck('price', 'id');

        $currentTotal = 0.0;
        foreach ($cartItems as $line) {
            $id = (int) $line['id'];
            if (!$prices->has($id)) {
                return false;
            }
            $current = round((float) $prices[$id], 2);
            if (array_key_exists('price', $line) && abs($current - round((float) $line['price'], 2)) >= 0.005) {
                return true;
            }
            $currentTotal += $current * (int) $line['quantity'];
        }

        return $expectedTotal !== null && abs(round($currentTotal, 2) - $expectedTotal) >= 0.005;
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
        // Net Sales Calculation: Gross confirmed revenue minus confirmed ProductReturn refunds.
        // Handles partial refunds, multi-item transactions, and full returns without removing unrelated collected revenue.
        $grossSalesQuery = Sale::query()
            ->where(function($q) {
                $q->where('status', 'completed')
                  ->orWhere('status', \App\Enums\OrderStatus::SALE_REFUNDED)
                  ->orWhere('amount_tendered', '>', 0);
            })
            ->whereNotNull('payment_confirmed_at');

        $refundsQuery = \App\Models\ProductReturn::query()->whereNotNull('refund_amount');

        if ($request->from && $request->to) {
            $grossSalesQuery->whereBetween('payment_confirmed_at', [$request->from, $request->to]);
            $refundsQuery->whereBetween('created_at', [$request->from, $request->to]);
        }
        if ($request->filter == 'today') {
            $grossSalesQuery->whereDate('payment_confirmed_at', today());
            $refundsQuery->whereDate('created_at', today());
        } elseif ($request->filter == 'week') {
            $grossSalesQuery->whereBetween('payment_confirmed_at', [now()->startOfWeek(), now()->endOfWeek()]);
            $refundsQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($request->filter == 'month') {
            $grossSalesQuery->whereMonth('payment_confirmed_at', now()->month)->whereYear('payment_confirmed_at', now()->year);
            $refundsQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        $grossSales = (float) $grossSalesQuery->sum('total_price');
        $totalRefunds = (float) $refundsQuery->sum('refund_amount');
        $totalSales = max(0, $grossSales - $totalRefunds);

        $totalTransactions = (clone $totalsQuery)->whereNotNull('transaction_reference')->distinct('transaction_reference')->count('transaction_reference')
                           + (clone $totalsQuery)->whereNull('transaction_reference')->count();

        $sales = $baseQuery->with('item')->latest()->paginate(10)->withQueryString();
        $inventoryItems = InventoryItem::orderBy('quantity', 'asc')->paginate(12, ['*'], 'stock_page')->withQueryString();

        // Full pending lines per reference so the pickup modal always shows the true total due.
        $pendingRefs = $sales->getCollection()
            ->where('status', 'pending')->pluck('transaction_reference')->filter()->unique()->values();
        $pendingGroups = $pendingRefs->isEmpty()
            ? collect()
            : Sale::with('item')->where('status', 'pending')->whereIn('transaction_reference', $pendingRefs)->get()->groupBy('transaction_reference');

        return view('sales.history', compact(
            'pendingGroups',
            'sales',
            'totalSales',
            'totalTransactions',
            'inventoryItems'
        ));
    }

    public function complete($id, Request $request)
    {
        $blocked = null;

        DB::transaction(function () use ($id, $request, &$blocked) {
            $sales = Sale::lockForUpdate()->where('transaction_reference', $id)->orderBy('id')->get();
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
                // A cancelled/expired reservation must never be reported as paid.
                if ($sales->contains(fn ($sale) => in_array($sale->status, ['cancelled', 'expired'], true))) {
                    $blocked = 'This reservation was ' . $sales->first()->status . ' and can no longer be completed.';
                }

                return; // already completed (idempotent) or blocked above
            }

            // SYS4-06 D2C: resolve the header from the locked lines' sale_header_id BEFORE any write.
            // All NULL = legacy (no header). One shared non-NULL id = that exact header, locked after
            // the lines. Anything else (mixed NULL/non-NULL, several ids, missing header, or a header
            // that is already paid while lines are still pending) is refused with nothing changed.
            // The header is never guessed from transaction_reference.
            $header = null;
            $headerIds = $sales->pluck('sale_header_id');
            if ($headerIds->contains(null)) {
                if ($headerIds->filter()->isNotEmpty()) {
                    $blocked = 'This reservation needs review before it can be completed.';

                    return;
                }
            } else {
                if ($headerIds->unique()->count() !== 1) {
                    $blocked = 'This reservation needs review before it can be completed.';

                    return;
                }
                $header = \App\Models\SaleHeader::lockForUpdate()->find($headerIds->first());
                if (!$header || $header->payment_confirmed_at !== null) {
                    $blocked = 'This reservation needs review before it can be completed.';

                    return;
                }
            }

            if ($tendered < $grandTotal) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount_tendered' => 'Insufficient cash received for pickup completion.',
                ]);
            }

            $now = now();
            $change = round($tendered - $grandTotal, 2);

            $isFirst = true;
            foreach ($sales as $sale) {
                if ($sale->status === 'pending') {
                    $sale->update([
                        'status' => 'completed',
                        'amount_tendered' => $isFirst ? $tendered : 0,
                        'change' => $isFirst ? $change : 0,
                        'picked_up_at' => $now,
                        'payment_confirmed_at' => $now
                    ]);
                    $isFirst = false;
                }
            }

            // Header payment is written in the same transaction, only after the checks above passed.
            // total_amount, reserved_at and expires_at stay as the original facts.
            if ($header) {
                $header->update([
                    'amount_tendered' => $tendered,
                    'change_amount' => $change,
                    'payment_confirmed_at' => $now,
                    'picked_up_at' => $now,
                ]);
            }
        });

        if ($blocked) {
            return redirect()->back()->with('error', $blocked);
        }

        return redirect()->back()->with('success', 'Reservation marked as completed/paid!');
    }

    /**
     * Sales are never deleted. This method is deliberately kept (and routed
     * nowhere) so that re-adding a DELETE route in the future fails closed
     * instead of silently re-enabling destruction of financial history.
     * Use cancel / expire / refund flows, which change status and keep the row.
     */
    public function destroy($id)
    {
        abort(405, 'Sale records cannot be deleted. Use cancellation or refund instead.');
    }

    /**
     * SYS4-01 (corrected): receipt accessed via sale primary key.
     * Used exclusively by the Sales History page (route: sales.receipt.by-id).
     *
     * Resolution logic:
     *   1. Find the specific sale by numeric primary key. 404 if missing or soft-deleted.
     *   2. If that sale has a transaction_reference, load ALL non-deleted sale lines
     *      sharing that reference â€” this preserves multi-item receipt completeness.
     *   3. If transaction_reference is NULL (legacy sale), display only that single sale.
     *
     * This approach eliminates both ChatGPT-identified defects:
     *   A) No collision: this route (/sales/by-id/{id}/receipt) is structurally
     *      separate from the transaction-reference route (/sales/{id}/receipt).
     *   B) Multi-item receipts are complete because we expand by transaction_reference
     *      after finding the anchor sale, not by the numeric ID alone.
     *
     * Authorization: owner-only middleware on the route group.
     */
    private function resolveSalesByPrimaryKey(int $id): \Illuminate\Database\Eloquent\Collection
    {
        // Step 1: find the specific sale by PK (respects SoftDeletes scope).
        $anchor = Sale::find($id);
        if ($anchor === null) {
            return Sale::newModelInstance()->newCollection(); // empty Eloquent collection
        }

        // SYS4-06 D4: header-linked transactions resolve through sale_header_id; legacy ones through
        // their reference (unlinked rows only) or alone when they have none.
        return Sale::transactionLines($anchor);
    }

    public function receiptById($id)
    {
        $sales = $this->resolveSalesByPrimaryKey((int) $id);
        if ($sales->isEmpty()) {
            abort(404, 'Receipt not found.');
        }
        // Use the by-id download route so legacy NULL-reference sales work correctly.
        $downloadUrl = route('sales.download.by-id', ['id' => $id]);
        return view('sales.receipt', compact('sales', 'downloadUrl'));
    }

    public function downloadReceiptById($id)
    {
        $sales = $this->resolveSalesByPrimaryKey((int) $id);
        if ($sales->isEmpty()) {
            abort(404, 'Receipt not found.');
        }

        $transactionId = $sales->first()->transaction_reference ?? 'SALE-' . $sales->first()->id;
        $date = $sales->first()->created_at->format('F d, Y h:i A');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf_receipt', compact('sales', 'transactionId', 'date'));

        return $pdf->download("Receipt_{$transactionId}.pdf");
    }

    /**
     * Receipt accessed via transaction reference (e.g. from POS storeSale redirect).
     * Route: sales.receipt — GET /sales/{id}/receipt
     * The {id} route parameter carries a transaction_reference string (TRX-ULID).
     */
    public function receipt($id)
    {
        $sales = Sale::transactionByKey($id);
        if ($sales->isEmpty()) {
            abort(404, 'Receipt not found.');
        }
        // Use the standard transaction-reference download route.
        $downloadUrl = route('sales.download', ['transaction' => $id]);
        return view('sales.receipt', compact('sales', 'downloadUrl'));
    }

    /**
     * PDF download accessed via transaction reference.
     * Route: sales.download — GET /sales/{transaction}/download
     */
    public function downloadReceipt($id)
    {
        $sales = Sale::transactionByKey($id);
        if ($sales->isEmpty()) {
            abort(404, 'Receipt not found.');
        }

        $transactionId = $sales->first()->transaction_reference ?? 'SALE-' . $sales->first()->id;
        $date = $sales->first()->created_at->format('F d, Y h:i A');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf_receipt', compact('sales', 'transactionId', 'date'));

        return $pdf->download("Receipt_{$transactionId}.pdf");
    }
}