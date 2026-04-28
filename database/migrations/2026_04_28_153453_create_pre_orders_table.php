<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('pre_orders', function (Blueprint $table) {
        $table->id();
        $table->string('customer_name');
        $table->string('part_name');
        $table->string('model')->nullable();
        $table->integer('quantity');
        $table->decimal('down_payment', 10, 2)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_orders');
    }
};
