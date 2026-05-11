<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Repair;
use App\Models\Sale;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->role === 'owner') {
            $stats = [
                'totalProducts' => InventoryItem::count(),
                'lowStockCount' => InventoryItem::whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
                'activeRepairs' => Repair::whereIn('status', ['pending', 'ongoing'])->count(),
                'todayRevenue' => Sale::whereDate('created_at', now()->today())->sum('total_price'),
                'monthlyRevenue' => Sale::whereMonth('created_at', now()->month)->sum('total_price'),
                'totalSalesCount' => Sale::count(),
                'completedRepairs' => Repair::where('status', 'completed')->count(),
            ];

            $recentActivities = auth()->user()->notifications()->latest()->take(5)->get();

            // Data for other tabs
            $inventorySummary = InventoryItem::latest()->take(10)->get();
            $recentSales = Sale::with('item', 'customer')->latest()->take(10)->get();
            $activeServiceJobs = Repair::whereIn('status', ['pending', 'ongoing'])->latest()->take(10)->get();
            $recentPreorders = \App\Models\PreOrder::latest()->take(10)->get();

            return view('dashboard', compact('stats', 'recentActivities', 'inventorySummary', 'recentSales', 'activeServiceJobs', 'recentPreorders'));
        }

        return redirect()->route('customer.dashboard');
    }
}
