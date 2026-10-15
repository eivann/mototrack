<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reservations:expire')]
#[Description('Cancel pending reservations older than 24 hours and restore stock')]
class ExpireReservations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Group by checkout reference: one reference is one reservation, so a
        // multi-item reservation expires as a single unit and cannot be split
        // across separate release() calls.
        $expiredReferences = \App\Models\Sale::where('status', 'pending')
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                      ->orWhere(function ($q) {
                          $q->whereNull('expires_at')
                            ->where('created_at', '<=', now()->subHours(24));
                      });
            })
            ->distinct()
            ->pluck('transaction_reference');

        $count = 0;
        $reservationService = app(\App\Services\ReservationService::class);
        foreach ($expiredReferences as $reference) {
            try {
                $sale = \App\Models\Sale::where('transaction_reference', $reference)->first();
                if ($sale && $reservationService->releaseGroup($sale, 'Automatically expired after 24 hours', \App\Enums\OrderStatus::SALE_EXPIRED)) {
                    $count++;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to expire reservation #{$reference}: " . $e->getMessage());
            }
        }

        $this->info("Expired {$count} reservations.");
    }
}
