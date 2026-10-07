<?php

namespace App\Http\Controllers\PreOrder;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HandlesExceptions;
use App\Http\Requests\PreOrder\StorePreOrderRequest;
use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\User;
use App\Models\Customer;
use App\Notifications\StatusUpdated;
use App\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CustomerPreOrderController extends Controller
{
    use HandlesExceptions;

    protected $paymentService;

    public function __construct(PaymentGateway $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function parts()
    {
        $items = InventoryItem::where('quantity', 0)->get();
        return view('preorders.parts', compact('items'));
    }

    public function customForm()
    {
        return view('preorders.custom');
    }

    public function form($id)
    {
        $item = InventoryItem::findOrFail($id);
        return view('preorders.form', compact('item'));
    }

    public function store(StorePreOrderRequest $request)
    {
        $user = auth()->user();
        $customer = Customer::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email]
        );

        $price = 0;
        $partName = $request->part_name;
        $model = $request->model ? ucwords($request->model) : null;

        if ($request->inventory_item_id) {
            $item = InventoryItem::findOrFail($request->inventory_item_id);
            $price = $item->price;
            $partName = $item->part_name;
            $model = $item->model;
        }

        $total = $price * $request->quantity;

        $preorder = PreOrder::create([
            'customer_id' => $customer->id,
            'inventory_item_id' => $request->inventory_item_id,
            'customer_name' => ucwords($user->name),
            'part_name' => ucwords($partName),
            'model' => $model,
            'quantity' => $request->quantity,
            'price' => $price,
            'total' => $total,
            'down_payment' => 0,
            'status' => 'pending',
            'is_paid' => false,
        ]);

        $owners = User::owners();
        Notification::send($owners, new StatusUpdated(
            'New Pre-order Request',
            "Customer {$user->name} has requested a pre-order for {$partName}.",
            "/preorders"
        ));

        return redirect('/customer/dashboard')->with('success', 'Pre-order request submitted! Please coordinate for pickup/payment.');
    }
}
