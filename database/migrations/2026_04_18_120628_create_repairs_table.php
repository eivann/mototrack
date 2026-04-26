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
        Schema::create('repairs', function (Blueprint $table) {
        $table->id();
        $table->string('customer_name');
        $table->string('bike_model')->nullable();
        $table->text('issue');
        $table->string('status')->default('pending'); // pending, ongoing, done
        $table->decimal('cost', 10, 2)->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
