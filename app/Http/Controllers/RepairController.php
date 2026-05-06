<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Repair;
use Illuminate\Support\Str;


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

        $repairs = $query->latest()->get();
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

        return back()->with('success', 'Payment recorded and added to sales history!');
    }

public function receipt($id)
{
    $repair = Repair::findOrFail($id);
    return view('receipt', compact('repair'));
}


}
