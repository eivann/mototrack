<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Models\Sale;
use App\Models\RefundRequest;
use App\Models\User;
use App\Notifications\StatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CustomerOrderController extends Controller
{
    public function cancel(Request $request, $id)
    {
        $user = auth()->user();
        $sale = Sale::findOrFail($id);

        if ($sale->customer_id !== $user->customer?->id) {
            abort(403, 'Unauthorized action.');
        }

        $reason = $request->input('reason', 'Customer changed their mind.');
        
        $success = app(\App\Services\ReservationService::class)->release($sale, $reason);

        if (!$success) {
            return back()->with('error', 'Only pending reservations can be cancelled. Completed purchases must be returned in person at the shop counter.');
        }

        $owners = User::owners();
        Notification::send($owners, new StatusUpdated(
            'Reservation Cancelled',
            "Customer {$user->name} has cancelled their reservation for {$sale->item?->part_name}.",
            "/dashboard?tab=reservations"
        ));

        return back()->with('success', 'Reservation cancelled successfully and stock has been released.');
    }

    public function requestRefund(Request $request, $id)
    {
        return back()->with('error', 'Online refund requests are not supported. As an in-store cash payment system, returns and refunds must be processed directly at the physical shop counter.');
    }
}

