<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a UNIQUE constraint on customers.user_id to enforce
     * one customer profile per user at the database level.
     *
     * Pre-flight check: if duplicate user_id values exist, the
     * migration refuses to run and prints remediation instructions.
     * This prevents silent data loss — duplicates must be resolved
     * manually before the constraint can be applied.
     *
     * Note: user_id is nullable (walk-in customers created by admin
     * have no linked user). Both PostgreSQL and MySQL allow multiple
     * NULL values in a UNIQUE column, so this is safe.
     */
    public function up(): void
    {
        // ── Pre-flight duplicate detection ────────────────────────────
        $duplicates = DB::table('customers')
            ->select('user_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $message = "Cannot add UNIQUE(user_id): {$duplicates->count()} user(s) have duplicate customer profiles.\n";
            foreach ($duplicates as $d) {
                $message .= "  user_id={$d->user_id} has {$d->cnt} customer records\n";
            }
            $message .= "\nRemediation steps:\n";
            $message .= "1. Run: SELECT * FROM customers WHERE user_id IN (" . $duplicates->pluck('user_id')->implode(',') . ")\n";
            $message .= "2. Identify the canonical record (usually the one with related sales/preorders/repairs).\n";
            $message .= "3. Reassign orphaned foreign keys: UPDATE sales SET customer_id = <canonical_id> WHERE customer_id = <duplicate_id>\n";
            $message .= "4. Delete the duplicate: DELETE FROM customers WHERE id = <duplicate_id>\n";
            $message .= "5. Re-run this migration.\n";

            throw new \RuntimeException($message);
        }

        // Drop the existing plain index before adding the unique one.
        // Wrapped in try/catch because the index may be required by foreign key in MySQL
        // or may not exist in SQLite/Postgres.
        try {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
            });
        } catch (\Throwable $e) {
            // Index may not exist or cannot be dropped while FK exists in MySQL — safe to ignore.
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->index('user_id');
        });
    }
};
