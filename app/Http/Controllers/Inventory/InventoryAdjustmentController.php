<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreDamagedItemRequest;
use App\Http\Requests\Inventory\StoreProductReturnRequest;
use App\Models\DamagedItem;
use App\Models\ProductReturn;
use App\Models\InventoryItem;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentController extends Controller
{
    public function index()
    {
        $damagedItems = DamagedItem::with('item')->latest()->paginate(10, ['*'], 'damaged_page')->withQueryString();
        $productReturns = ProductReturn::with('item', 'customer')->latest()->paginate(10, ['*'], 'returns_page')->withQueryString();
        $inventoryItems = InventoryItem::orderBy('part_name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('inventory.adjustments', compact('damagedItems', 'productReturns', 'inventoryItems', 'customers'));
    }

    public function storeDamaged(StoreDamagedItemRequest $request)
    {
        DB::transaction(function () use ($request) {
            $item = InventoryItem::lockForUpdate()->findOrFail($request->inventory_item_id);
            
            if ($item->quantity < $request->quantity) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantity' => 'Quantity to mark as damaged exceeds available stock.'
                ]);
            }

            DamagedItem::create($request->validated());
            $item->decrement('quantity', $request->quantity);
            
            // Trigger stock notification if threshold is breached
            \App\Jobs\CheckLowStockAlertJob::dispatch($item->id);
        });

        return redirect()->back()->with('success', 'Damaged stock recorded successfully!');
    }

    public function storeReturn(StoreProductReturnRequest $request)
    {
        DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // If linked to an original sale, validate against prior returns to prevent double/over-refunding
            if (!empty($validated['sale_id'])) {
                $sale = \App\Models\Sale::lockForUpdate()->find($validated['sale_id']);
                if ($sale) {
                    if ($sale->status === \App\Enums\OrderStatus::SALE_REFUNDED) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'quantity' => 'This sale transaction has already been fully refunded and closed.'
                        ]);
                    }

                    if ($sale->status !== 'completed') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'sale_id' => 'Only completed, paid sales can be processed for returns and refunds.'
                        ]);
                    }

                    $priorReturnedQty = (int) ProductReturn::where('sale_id', $sale->id)->sum('quantity');
                    $newTotalReturnedQty = $priorReturnedQty + (int)$validated['quantity'];

                    if ($priorReturnedQty >= (int)$sale->quantity) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'quantity' => 'All items for this sale transaction have already been returned.'
                        ]);
                    }

                    if ($newTotalReturnedQty > (int)$sale->quantity) {
                        $remainingQty = (int)$sale->quantity - $priorReturnedQty;
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'quantity' => "Return quantity ({$validated['quantity']}) exceeds remaining refundable items ({$remainingQty})."
                        ]);
                    }

                    $productReturn = ProductReturn::create($validated);

                    // Update sale status to refunded once 100% of quantity is returned (either single or cumulative)
                    if ($newTotalReturnedQty >= (int)$sale->quantity) {
                        $sale->update(['status' => \App\Enums\OrderStatus::SALE_REFUNDED]);
                    }
                } else {
                    $productReturn = ProductReturn::create($validated);
                }
            } else {
                $productReturn = ProductReturn::create($validated);
            }

            if ($request->action_taken === 'restocked') {
                $item = InventoryItem::lockForUpdate()->findOrFail($request->inventory_item_id);
                $item->increment('quantity', $request->quantity);
                
                if ($item->quantity > ($item->low_stock_threshold ?? 5)) {
                    $item->low_stock_notified = false;
                    $item->save();
                }
            } elseif ($request->action_taken === 'discarded') {
                // Distinguish damaged write-off from resellable return in audit trail
                DamagedItem::create([
                    'inventory_item_id' => $request->inventory_item_id,
                    'quantity'          => $request->quantity,
                    'notes'             => 'Customer Return Write-Off: ' . ($request->notes ?? 'Discarded / Defective'),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Product return and physical refund recorded successfully!');
    }
}
