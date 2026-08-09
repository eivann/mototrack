<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit #10: link a pre-order to the inventory item it was placed against.
     *
     * Previously `AdminPreOrderController::complete()` restocked by matching the
     * free-text `part_name` + `model`, which silently failed if either string was
     * edited between pre-order and completion. Custom pre-orders (no catalog item)
     * legitimately have no link, so the column is nullable and falls back to the
     * legacy string match at completion time.
     */
    public function up(): void
    {
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')
                ->nullable()
                ->constrained('inventory_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
            $table->dropColumn('inventory_item_id');
        });
    }
};
