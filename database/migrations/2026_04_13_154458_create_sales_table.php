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
        Schema::create('sales', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('inventory_item_id');

    $table->foreign('inventory_item_id')
          ->references('id')
          ->on('inventory_items')
          ->onDelete('cascade');

    $table->integer('quantity');
    $table->decimal('total_price', 10, 2);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
