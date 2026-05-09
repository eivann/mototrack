<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('part_compatibility', function (Blueprint $table) {
            $table->index('inventory_item_id');
            $table->index('motorcycle_id');
        });

        Schema::table('pre_orders', function (Blueprint $table) {
            $table->index('customer_id');
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->index('customer_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('inventory_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('part_compatibility', function (Blueprint $table) {
            $table->dropIndex(['inventory_item_id']);
            $table->dropIndex(['motorcycle_id']);
        });

        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['inventory_item_id']);
        });
    }
};
