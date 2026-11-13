<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CustomerRepairController extends Controller
{
    /**
     * Display customer-visible repair and maintenance detail.
     * Enforces customer ownership at the controller/policy level to prevent IDOR vulnerabilities.
     */
    public function show(Repair $repair): JsonResponse
    {
        // Enforce customer ownership policy: user must be the owner of the customer profile linked to the repair
        Gate::authorize('view', $repair);

        return response()->json([
            'id'             => $repair->id,
            'bike_model'     => $repair->bike_model,
            'issue'          => $repair->issue,
            'status'         => $repair->status,
            'cost'           => $repair->cost !== null ? (float) $repair->cost : null,
            'is_paid'        => (bool) $repair->is_paid,
            'created_at_fmt' => $repair->createdAtManila('M d, Y g:i A'),
            'started_at_fmt' => $repair->startedAtManila('M d, Y g:i A'),
            'completed_at_fmt' => $repair->completedAtManila('M d, Y g:i A'),
            'paid_at_fmt'    => $repair->paidAtManila('M d, Y g:i A'),
            'is_ongoing'     => $repair->isOngoing(),
            'is_finished'    => $repair->isFinished(),
        ]);
    }
}

