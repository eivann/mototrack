<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Repair;


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
        'customer_name' => 'required|string|max:255',
        'bike_model'    => 'nullable|string|max:255',
        'issue'         => 'nullable|string|max:1000',
        'cost'          => 'required|numeric|min:0',
    ]);

    Repair::create([
        'customer_id'   => $request->customer_id,
        'customer_name' => $request->customer_id ? \App\Models\Customer::find($request->customer_id)->name : ucwords($request->customer_name),
        'bike_model'    => $request->bike_model ? ucwords($request->bike_model) : null,
        'issue'         => $request->issue ? ucfirst($request->issue) : null,
        'cost'          => $request->cost,
        'status'        => 'pending',
        'is_paid'       => false,
    ]);

    return back()->with('success', 'Repair added!');
}

public function update(Request $request, $id)
{
    $request->validate([
        'status' => 'required|string|in:pending,in_progress,completed',
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

    return back();
}

public function receipt($id)
{
    $repair = Repair::findOrFail($id);
    return view('receipt', compact('repair'));
}


}
