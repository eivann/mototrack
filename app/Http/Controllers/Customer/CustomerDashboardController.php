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
        $customer = $user->customer ?: Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $repairs = Repair::where('customer_id', $customer->id)
            ->orWhere('customer_name', $user->name)
            ->latest()
            ->get();

        $preorders = PreOrder::where('customer_id', $customer->id)
            ->orWhere('customer_name', $user->name)
            ->latest()
            ->get();

        return view('customer.dashboard', compact('repairs', 'preorders'));
    }
}
