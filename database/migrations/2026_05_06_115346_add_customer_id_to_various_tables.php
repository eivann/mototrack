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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            } else {
                // Fix type if it exists manually to avoid Error 150
                $table->unsignedBigInteger('customer_id')->nullable()->change();
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            }
        });

        Schema::table('repairs', function (Blueprint $table) {
            if (!Schema::hasColumn('repairs', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            } else {
                $table->unsignedBigInteger('customer_id')->nullable()->change();
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            }
        });

        Schema::table('pre_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pre_orders', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            } else {
                $table->unsignedBigInteger('customer_id')->nullable()->change();
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
