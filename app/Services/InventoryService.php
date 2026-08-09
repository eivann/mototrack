<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\Cache;

class InventoryService
{
    /**
     * Create a new inventory item.
     */
    public function store(array $data): InventoryItem
    {
        $item = InventoryItem::create($data);
        Cache::forget('inventory_categories_v3');
        return $item;
    }

    /**
     * Update an existing inventory item and reset the low-stock flag
     * automatically when stock rises back above the threshold.
     */
    public function update(InventoryItem $item, array $data): InventoryItem
    {
        $item->update($data);

        if ($item->quantity > ($item->low_stock_threshold ?? 5)) {
            $item->low_stock_notified = false;
            $item->save();
        }

        Cache::forget('inventory_categories_v3');
        return $item->fresh();
    }

    /**
     * Delete an inventory item.
     */
    public function destroy(InventoryItem $item): void
    {
        $item->delete();
        Cache::forget('inventory_categories_v3');
    }

    /**
     * Generate a unique SKU for an inventory item based on its attributes.
     */
    public function generateSku(string $category, string $brand, string $partName): string
    {
        $prefix = strtoupper(
            substr($category, 0, 2) .
            substr($brand, 0, 2) .
            substr($partName, 0, 2)
        );

        $suffix = strtoupper(substr(uniqid(), -4));

        return "{$prefix}-{$suffix}";
    }
}
