<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit #8 / #9: introduce an explicit, nullable `shop_id` ownership/tenancy
 * column on the users table and the domain tables. This is additive
 * scaffolding only — columns are nullable and NOT backfilled, and no query
 * scoping or middleware is wired yet. It gives a future multi-shop / franchise
 * rollout a column to scope on without a schema migration touching every
 * domain table at that time.
 */
return new class extends Migration
{
    /**
     * Tables that gain a nullable, indexed shop_id.
     */
    private array $tables = ['users', 'sales', 'repairs', 'pre_orders'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'shop_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('shop_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'shop_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('shop_id');
            });
        }
    }
};
