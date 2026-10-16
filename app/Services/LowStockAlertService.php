<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single owner of the low-stock alert state machine.
 *
 * State lives in the existing `inventory_items.low_stock_notified` flag (no
 * migration): false = "armed", true = "an alert was already raised for the
 * current low-stock episode".
 *
 *   quantity > threshold            -> re-arm (flag false), no alert
 *   quantity <= threshold, armed    -> record owner in-app notification
 *                                      synchronously, then flag true
 *   quantity <= threshold, flagged  -> nothing (no duplicate for repeated
 *                                      edits/sales while still low)
 *
 * The in-app notification is written inside the request through
 * NotificationDispatcher (database channel), so it never needs a queue worker.
 * The flag is only set once at least one owner row was durably recorded, so a
 * failed write is retried by the next quantity change.
 *
 * SMS is an optional, best-effort extra: it is queued (or sent inline when a
 * gateway is passed explicitly) and its failure never affects the in-app
 * record or the flag.
 */
class LowStockAlertService
{
    public const DEFAULT_THRESHOLD = 5;

    public function __construct(protected NotificationDispatcher $dispatcher) {}

    /**
     * Evaluate items once the surrounding DB transaction (if any) has
     * committed. Safe to call from inside transactions: a rolled-back order
     * never raises an alert, and a failure here can never abort or poison the
     * caller's transaction.
     */
    public static function checkAfterCommit(int|InventoryItem ...$items): void
    {
        $ids = array_values(array_unique(array_map(
            fn ($i) => $i instanceof InventoryItem ? $i->id : (int) $i,
            $items
        )));

        if ($ids === []) {
            return;
        }

        DB::afterCommit(function () use ($ids) {
            foreach ($ids as $id) {
                try {
                    app(self::class)->evaluate($id);
                } catch (\Throwable $e) {
                    Log::warning("Low-stock check for item {$id} failed: " . $e->getMessage());
                }
            }
        });
    }

    /**
     * @param  SmsGatewayInterface|null  $gateway  When given, SMS is sent inline with this gateway
     *                                            (used by the legacy SmsService entry point/tests).
     * @return bool true when a new alert was raised for this call.
     */
    public function evaluate(int $itemId, ?SmsGatewayInterface $gateway = null): bool
    {
        $raised = false;

        $item = DB::transaction(function () use ($itemId, &$raised) {
            $item = InventoryItem::lockForUpdate()->find($itemId);

            if (! $item) {
                return null;
            }

            $threshold = (int) ($item->low_stock_threshold ?? self::DEFAULT_THRESHOLD);

            if ((int) $item->quantity > $threshold) {
                // Healthy again: re-arm so a later drop alerts once more.
                if ($item->low_stock_notified) {
                    $this->setFlag($item->id, false);
                }

                return $item;
            }

            if ($item->low_stock_notified) {
                return $item; // already alerted for this episode
            }

            if ($this->recordOwnerNotification($item)) {
                $this->setFlag($item->id, true);
                $raised = true;
            }

            return $item;
        });

        if ($raised && $item) {
            $this->sendSms($item->fresh() ?? $item, $gateway);
        }

        return $raised;
    }

    /**
     * Returns true when the alert should be considered delivered: at least one
     * owner row was persisted, or there is no owner to notify at all.
     */
    protected function recordOwnerNotification(InventoryItem $item): bool
    {
        try {
            $owners = User::owners();

            if ($owners->isEmpty()) {
                return true; // nobody to notify; do not retry forever
            }

            $out = (int) $item->quantity <= 0;
            $message = $out
                ? "{$item->part_name} is out of stock (alert threshold: {$item->low_stock_threshold})."
                : "{$item->part_name} is down to {$item->quantity} unit(s) — at or below the alert threshold of {$item->low_stock_threshold}.";

            return $this->dispatcher->send($owners, new StatusUpdated(
                'Low Stock Alert',
                $message,
                route('low-stock.index', ['highlight' => $item->id])
            )) > 0;
        } catch (\Throwable $e) {
            Log::warning("Low-stock notification for item {$item->id} failed: " . $e->getMessage());

            return false;
        }
    }

    /** Best effort only; never throws. */
    protected function sendSms(InventoryItem $item, ?SmsGatewayInterface $gateway): void
    {
        try {
            if ($gateway) {
                SmsService::sendLowStock($item, $gateway);
            } else {
                \App\Jobs\SendLowStockSmsJob::dispatch($item->id);
            }
        } catch (\Throwable $e) {
            Log::warning("Low-stock SMS for item {$item->id} could not be sent/queued: " . $e->getMessage());
        }
    }

    /** Query-builder update: no model events, so no audit-log noise for a state flag. */
    protected function setFlag(int $itemId, bool $value): void
    {
        DB::table('inventory_items')->where('id', $itemId)->update(['low_stock_notified' => $value]);
    }
}
