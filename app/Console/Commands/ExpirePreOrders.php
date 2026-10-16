<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Enums\OrderStatus;
use App\Models\PreOrder;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

#[Signature('preorders:expire')]
#[Description('Cancel arrived pre-orders older than 24 hours from readiness notification and restore stock')]
class ExpirePreOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredPreorders = PreOrder::whereIn('status', ['pending', 'arrived'])
            ->where('is_stocked', true)
            ->whereNotNull('pickup_expires_at')
            ->where('pickup_expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expiredPreorders as $preorder) {
            try {
                $customerUser = null;
                $partName = $preorder->part_name;

                $expired = DB::transaction(function () use ($preorder, &$customerUser) {
                    $order = PreOrder::lockForUpdate()->find($preorder->id);

                    if (!$order || !in_array($order->status, ['pending', 'arrived']) || !$order->is_stocked) {
                        return false;
                    }

                    // Restore catalog stock exactly once if linked to an item
                    if ($order->inventory_item_id) {
                        $item = InventoryItem::lockForUpdate()->find($order->inventory_item_id);
                        if ($item) {
                            $item->increment('quantity', $order->quantity);
                            \App\Services\LowStockAlertService::checkAfterCommit($item->id);
                        }
                    }

                    $order->is_stocked = false;
                    $order->status = \App\Enums\OrderStatus::PREORDER_EXPIRED;
                    $order->save();

                    if ($order->customer && $order->customer->user) {
                        $customerUser = $order->customer->user;
                    }

                    return true;
                });

                if ($expired) {
                    $count++;

                    // Notify customer
                    if ($customerUser) {
                        $customerUser->notify(new StatusUpdated(
                            'Pre-order Expired',
                            "Your pre-order for {$partName} has expired after 24 hours without pickup. The allocated item has been returned.",
                            '/customer/dashboard'
                        ));
                    }

                    // Notify owners
                    $owners = User::owners();
                    Notification::send($owners, new StatusUpdated(
                        'Pre-order Expired',
                        "Pre-order #{$preorder->id} ({$partName}) has expired after 24 hours without pickup. Stock has been restored.",
                        '/preorders'
                    ));
                }
            } catch (\Throwable $e) {
                Log::error("Failed to expire pre-order #{$preorder->id}: " . $e->getMessage());
            }
        }

        $this->info("Expired {$count} pre-orders.");

        return Command::SUCCESS;
    }
}
