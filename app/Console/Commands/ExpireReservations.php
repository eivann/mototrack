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
        // Retrieve pending sales where expires_at has passed
        // Fallback to created_at + 24 hours if expires_at is null
        $expiredSales = \App\Models\Sale::where('status', 'pending')
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                      ->orWhere(function ($q) {
                          $q->whereNull('expires_at')
                            ->where('created_at', '<=', now()->subHours(24));
                      });
            })
            ->get();

        $count = 0;
        $reservationService = app(\App\Services\ReservationService::class);
        foreach ($expiredSales as $sale) {
            try {
                if ($reservationService->release($sale, 'Automatically expired after 24 hours', 'expired')) {
                    $count++;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to expire reservation #{$sale->transaction_reference}: " . $e->getMessage());
            }
        }

        $this->info("Expired {$count} reservations.");
    }
}
