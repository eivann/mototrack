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
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->index('parent_id', 'idx_inventory_items_parent_id');
            $table->index('category', 'idx_inventory_items_category');
            $table->index('model', 'idx_inventory_items_model');
            $table->index('price', 'idx_inventory_items_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex('idx_inventory_items_parent_id');
            $table->dropIndex('idx_inventory_items_category');
            $table->dropIndex('idx_inventory_items_model');
            $table->dropIndex('idx_inventory_items_price');
        });
    }
};
