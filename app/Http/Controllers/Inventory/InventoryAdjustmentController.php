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
            ProductReturn::create($request->validated());
            
            if ($request->action_taken === 'restocked') {
                $item = InventoryItem::lockForUpdate()->findOrFail($request->inventory_item_id);
                $item->increment('quantity', $request->quantity);
            }
        });

        return redirect()->back()->with('success', 'Product return recorded successfully!');
    }
}
