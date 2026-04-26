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

    return view('repair', compact('repairs'));
}

public function store(Request $request)
{
    Repair::create($request->all());
    return back()->with('success', 'Repair added!');
}

public function update(Request $request, $id)
{
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
