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
        Cache::forget(InventoryItem::CATEGORY_CACHE_KEY);
        LowStockAlertService::checkAfterCommit($item->id);
        return $item;
    }

    /**
     * Update an existing inventory item and reset the low-stock flag
     * automatically when stock rises back above the threshold.
     */
    public function update(InventoryItem $item, array $data): InventoryItem
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($item, $data) {
            $expected = (int) $data['expected_quantity'];
            unset($data['expected_quantity']);
            
            $locked = InventoryItem::lockForUpdate()->findOrFail($item->id);
            
            if ($locked->quantity !== $expected) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantity' => 'Stock changed while editing. Reload and retry.',
                ]);
            }
            
            $locked->update($data);

            Cache::forget(InventoryItem::CATEGORY_CACHE_KEY);

            // Manual edits are a quantity/threshold-changing path: alert once if now
            // low, re-arm if back above the threshold. Runs after commit.
            LowStockAlertService::checkAfterCommit($locked->id);

            return $locked->fresh();
        });
    }

    /**
     * Delete an inventory item.
     */
    public function destroy(InventoryItem $item): void
    {
        $item->delete();
        Cache::forget(InventoryItem::CATEGORY_CACHE_KEY);
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
