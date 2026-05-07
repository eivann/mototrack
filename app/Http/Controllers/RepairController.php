<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Repair;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StatusUpdated;
use Illuminate\Support\Facades\DB;


class RepairController extends Controller
{
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

        return view('repair', compact('repairs', 'customers'));
    }

public function store(Request $request)
{
    $request->validate([
        'customer_id'   => 'nullable|exists:customers,id',
        'customer_name' => 'required_without:customer_id|nullable|string|max:255',
        'bike_model'    => 'required|string|max:255',
        'issue'         => 'required|string|max:1000',
        'cost'          => 'required|numeric|min:0',
    ]);

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

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,ongoing,done',
        ]);

        $repair = Repair::findOrFail($id);
        $repair->status = $request->status;
        $repair->save();

        // Notify customer via dashboard
        if ($repair->customer && $repair->customer->user) {
            $repair->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Repair Status Updated',
                "Your repair job for {$repair->bike_model} is now: " . strtoupper($request->status),
                '/customer-dashboard'
            ));
        }

        return redirect('/repairs')->with('success', 'Status updated!');
    }

public function destroy($id)
{
    Repair::findOrFail($id)->delete();
    return back();
    
}
    public function markPaid($id)
    {
        $repair = Repair::findOrFail($id);

        DB::transaction(function () use ($repair) {
            $repair->is_paid = true;
            $repair->save();

            // Record this as a sale for the reports
            \App\Models\Sale::create([
                'inventory_item_id' => null, // Service/Labor
                'quantity' => 1,
                'total_price' => $repair->cost,
                'customer_id' => $repair->customer_id,
                'transaction_reference' => 'REPAIR-' . strtoupper(Str::random(8)),
            ]);
        });

        // Notify Owner
        $owners = User::where('role', 'owner')->get();
        Notification::send($owners, new StatusUpdated(
            'Repair Payment Received',
            "Payment of ₱" . number_format($repair->cost, 2) . " received for {$repair->bike_model} ({$repair->customer_name}).",
            "/repairs"
        ));

        // Notify Customer
        if ($repair->customer && $repair->customer->user) {
            $repair->customer->user->notify(new StatusUpdated(
                'Payment Confirmed',
                "Your payment of ₱" . number_format($repair->cost, 2) . " for {$repair->bike_model} has been received. Thank you!",
                '/customer-dashboard'
            ));
        }

        return back()->with('success', 'Payment recorded and added to sales history!');
    }

public function receipt($id)
{
    $repair = Repair::findOrFail($id);
    return view('receipt', compact('repair'));
}


}
