<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Repair;
use App\Models\PreOrder;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email]
        );

        // H-2 Security: Query by customer_id only. Matching by
        // customer_name leaks data when two users share the same display
        // name. All records must be linked via the foreign key.
        $repairs = Repair::where('customer_id', $customer->id)
            ->latest()
            ->get();

        $preorders = PreOrder::where('customer_id', $customer->id)
            ->latest()
            ->get();

        // SYS4-06 D4: one entry per transaction. Key = header reference when linked, else the sale's own
        // reference, else SALE-<id> (a reference-less legacy sale is its own transaction instead of being
        // lumped with every other reference-less sale).
        $purchases = \App\Models\Sale::with('item', 'header:id,transaction_reference')
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($s) => $s->header?->transaction_reference
                ?? (trim((string) $s->transaction_reference) !== '' ? $s->transaction_reference : 'SALE-'.$s->id));

        // My Reservations: one card per checkout reference, split into the
        // still-actionable reservations and the settled history.
        $reservations = $purchases->map(function ($lines) {
            $first = $lines->first();
            $isSettled = $lines->contains(fn ($line) => $line->status !== 'pending');

            return (object) [
                'reference' => $first->transaction_reference,
                'lines' => $lines,
                'created_at' => $first->created_at,
                'status' => $first->status,
                'is_settled' => $isSettled,
                'total' => $lines->sum('total_price'),
                // A grouped reservation is cancellable only while every line is
                // still pending, so eligibility is decided across the whole group.
                'can_cancel' => !$isSettled,
                'deadline' => $first->expires_at
                    ?? ($first->reserved_at ? $first->reserved_at->copy()->addHours(24) : null),
            ];
        });

        $activeReservations = $reservations->where('can_cancel', true)->values();
        $reservationHistory = $reservations->where('is_settled', true)->values();

        return view('customer.dashboard', compact(
            'repairs',
            'preorders',
            'purchases',
            'activeReservations',
            'reservationHistory'
        ));
    }
}
