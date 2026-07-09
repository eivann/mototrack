<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Audit #10: the earlier migration 2026_05_08_172200_fix_sales_price_type
 * changed sales.price from integer to decimal(10,2) with no data-validation
 * step. This migration adds the missing guard.
 *
 * Inspection of the app and tests shows prices have always been stored in
 * PESOS (e.g. 1500, 3000.00), never integer cents — so the integer→decimal
 * cast did not mis-scale any values. There is consequently nothing to
 * back-fill, and an automated rescale would corrupt correct data. This
 * migration therefore VERIFIES and LOGS the price distribution and flags any
 * out-of-range rows for manual review, but intentionally performs no
 * transformation. It is a safe no-op on healthy data and fully reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales') || !Schema::hasColumn('sales', 'price')) {
            Log::info('Audit #10: sales.price not present — price verification skipped.');
            return;
        }

        $total = DB::table('sales')->count();
        $stats = DB::table('sales')
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price')
            ->first();

        // Sanity ceiling: a single line-item price above ₱10,000,000 almost
        // certainly indicates a mis-scaled (e.g. centavo) value worth review.
        $suspiciousCeiling = 10_000_000;
        $negativeCount = DB::table('sales')->where('price', '<', 0)->count();
        $overCeilingCount = DB::table('sales')->where('price', '>', $suspiciousCeiling)->count();

        Log::info('Audit #10: sales.price verification', [
            'rows' => $total,
            'min' => $stats->min_price ?? null,
            'max' => $stats->max_price ?? null,
            'avg' => $stats->avg_price ?? null,
            'negative_rows' => $negativeCount,
            'rows_over_ceiling' => $overCeilingCount,
        ]);

        if ($negativeCount > 0 || $overCeilingCount > 0) {
            Log::warning('Audit #10: sales.price has out-of-range rows requiring manual review. '
                . "negative={$negativeCount}, over_ceiling={$overCeilingCount}. "
                . 'No automatic transformation was applied to avoid corrupting valid data.');
        }
    }

    public function down(): void
    {
        // Verification-only migration; nothing to reverse.
    }
};
