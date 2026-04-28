<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PreOrder;
use App\Models\InventoryItem;

class PreOrderController extends Controller
{
   public function index()
{
    $preorders  = PreOrder::latest()->get();
    return view('preorders', compact('preorders'));
}

public function complete($id)
{
    $preorders = PreOrder::findOrFail($id);
     $item = InventoryItem::where('part_name', $preorders->part_name)
                             ->where('model', $preorders->model)
                             ->first();

        if ($item) {
            $item->increment('quantity', $preorders->quantity);
        }
    $preorders->status = 'completed';
    $preorders->save();

    return back()->with('success', 'Pre-order completed!');
}

public function cancel($id)
{
    $preorders = PreOrder::findOrFail($id);
    $preorders->status = 'cancelled';
    $preorders->save();

    return back()->with('success', 'Pre-order cancelled!');
}

public function destroy($id)
{
    $order = PreOrder::findOrFail($id);
    $order->delete();

    return redirect()->back()->with('success', 'Pre-order deleted successfully.');
}

}
