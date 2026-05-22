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

        return view('customer.dashboard', compact('repairs', 'preorders'));
    }
}
