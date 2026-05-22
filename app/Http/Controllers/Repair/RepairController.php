<?php

namespace App\Http\Controllers\Repair;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HandlesExceptions;
use App\Http\Requests\Repair\StoreRepairRequest;
use App\Http\Requests\Repair\UpdateRepairRequest;
use Illuminate\Http\Request;
use App\Models\Repair;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RepairController extends Controller
{
    use HandlesExceptions;
    public function index(Request $request)
    {
        $query = Repair::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->paid !== null) {
            $query->where('is_paid', $request->paid);
        }

        $repairs = $query->latest()->paginate(10)->withQueryString();
        $customers = \App\Models\Customer::orderBy('name')->get();

        return view('repairs.index', compact('repairs', 'customers'));
    }

    public function store(StoreRepairRequest $request)
    {
        $customerName = $request->customer_name;
        if ($request->customer_id) {
            $customerName = \App\Models\Customer::find($request->customer_id)->name;
        }

        Repair::create([
            'customer_id'   => $request->customer_id,
            'customer_name' => ucwords($customerName),
            'bike_model'    => ucwords($request->bike_model),
            'issue'         => ucfirst($request->issue),
            'cost'          => $request->cost,
            'status'        => 'pending',
            'is_paid'       => false,
        ]);

        return back()->with('success', 'Repair job created successfully!');
    }

    public function update(UpdateRepairRequest $request, Repair $repair)
    {
        $repair->status = $request->status;
        $repair->save();

        if ($repair->customer && $repair->customer->user) {
            $repair->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Repair Status Updated',
                "Your repair job for {$repair->bike_model} is now: " . strtoupper($request->status),
                '/customer/dashboard'
            ));
        }

        return redirect('/repairs')->with('success', 'Status updated!');
    }

    public function destroy(Repair $repair)
    {
        Gate::authorize('delete', $repair);

        $repair->delete();
        return back()->with('success', 'Repair job deleted.');
    }

    public function markPaid(Repair $repair)
    {
        Gate::authorize('markPaid', $repair);

        return $this->attempt(function () use ($repair) {
            DB::transaction(function () use ($repair) {
                $repair->is_paid = true;
                $repair->save();

                \App\Models\Sale::create([
                    'inventory_item_id' => null,
                    'quantity' => 1,
                    'total_price' => $repair->cost,
                    'customer_id' => $repair->customer_id,
                    'transaction_reference' => 'REPAIR-' . strtoupper(Str::random(8)),
                ]);
            });

            $owners = User::where('role', 'owner')->get();
            Notification::send($owners, new StatusUpdated(
                'Repair Payment Received',
                "Payment of ₱" . number_format($repair->cost, 2) . " received for {$repair->bike_model} ({$repair->customer_name}).",
                "/repairs"
            ));

            if ($repair->customer && $repair->customer->user) {
                $repair->customer->user->notify(new StatusUpdated(
                    'Payment Confirmed',
                    "Your payment of ₱" . number_format($repair->cost, 2) . " for {$repair->bike_model} has been received. Thank you!",
                    '/customer/dashboard'
                ));
            }

            return back()->with('success', 'Payment recorded and added to sales history!');
        }, 'Payment recording failed. Please try again.');
    }

    public function receipt(Repair $repair)
    {
        return view('repairs.receipt', compact('repair'));
    }
}
