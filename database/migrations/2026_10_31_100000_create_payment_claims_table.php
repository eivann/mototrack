<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RD2-R2: manual GCash QR payment claims for product reservations and catalogue pre-orders.
 *
 * LOCAL MIGRATION ONLY — not applied to any live/production database by this change.
 *
 * The claim is its own record: the payment state (awaiting_payment / pending_verification / verified /
 * rejected / cancelled) is deliberately separate from the reservation (sales.status) and pre-order
 * (pre_orders.status) lifecycles, so verifying a payment never changes stock or fulfilment.
 *
 * The three "active_*" columns are NULL once a claim stops being active (rejected/cancelled) and carry a
 * UNIQUE index, which gives portable (SQLite + PostgreSQL) database-level protection against two active
 * claims for one payable, a payment reference reused across orders, or one screenshot reused.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_claims', function (Blueprint $table) {
            $table->id();
            $table->string('payable_type', 20);           // reservation | preorder
            $table->string('payable_key', 64);            // transaction_reference | pre_orders.id
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('method', 20)->default('gcash_qr');
            $table->string('status', 24)->default('awaiting_payment');
            $table->decimal('expected_amount', 12, 2);    // server-calculated, VAT-inclusive gross
            $table->char('currency', 3)->default('PHP');
            $table->decimal('declared_amount', 12, 2)->nullable();   // what the customer says they sent
            $table->string('reference_number', 40)->nullable();      // normalised; never logged
            $table->string('proof_path')->nullable();                // private disk path
            $table->char('active_reference_fingerprint', 64)->nullable()->unique();
            $table->char('active_proof_hash', 64)->nullable()->unique();
            $table->string('active_payable_key', 90)->nullable()->unique();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('verified_amount', 12, 2)->nullable();   // amount the owner saw in the account
            $table->string('decision_note', 255)->nullable();
            $table->boolean('needs_owner_review')->default(false);
            $table->string('review_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['payable_type', 'payable_key']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_claims');
    }
};
