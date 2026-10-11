<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Repair;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->role === 'owner') {
            $stats = Cache::remember('admin_dashboard_stats', 60, function () {
                return [
                    'totalProducts' => InventoryItem::count(),
                    'lowStockCount' => InventoryItem::whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
                    'activeRepairs' => Repair::whereIn('status', ['pending', 'ongoing'])->count(),
                    'todayRevenue' => Sale::whereDate('payment_confirmed_at', now()->today())
                        ->where(function($q) { $q->where('status', 'completed')->orWhere('amount_tendered', '>', 0); })
                        ->where('status', '!=', \App\Enums\OrderStatus::SALE_REFUNDED)->whereNotNull('payment_confirmed_at')->sum('total_price'),
                    'monthlyRevenue' => Sale::whereMonth('payment_confirmed_at', now()->month)->whereYear('payment_confirmed_at', now()->year)
                        ->where(function($q) { $q->where('status', 'completed')->orWhere('amount_tendered', '>', 0); })
                        ->where('status', '!=', \App\Enums\OrderStatus::SALE_REFUNDED)->whereNotNull('payment_confirmed_at')->sum('total_price'),
                    'totalSalesCount' => Sale::count(),
                    'completedRepairs' => Repair::where('status', 'completed')->count(),
                ];
            });

            $recentActivities = auth()->user()->notifications()->latest()->take(5)->get();

            $inventorySummary = InventoryItem::latest('id')->take(10)->get();
            $recentSales = Sale::with('item', 'customer')->latest()->take(10)->get();
            $activeServiceJobs = Repair::whereIn('status', ['pending', 'ongoing'])->latest()->take(10)->get();
            $recentPreorders = \App\Models\PreOrder::latest()->take(10)->get();

            // Daily revenue for the last 30 days (Chart.js)
            $dailyRevenue = Sale::selectRaw('DATE(payment_confirmed_at) as date, SUM(total_price) as revenue')
                ->where('payment_confirmed_at', '>=', now()->subDays(30))
                ->where(function($q) { $q->where('status', 'completed')->orWhere('amount_tendered', '>', 0); })
                ->where('status', '!=', \App\Enums\OrderStatus::SALE_REFUNDED)
                ->whereNotNull('payment_confirmed_at')
                ->groupByRaw('DATE(payment_confirmed_at)')
                ->orderByRaw('DATE(payment_confirmed_at)')
                ->pluck('revenue', 'date');

            // Fill in missing days with 0
            $chartLabels = [];
            $chartData = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $chartLabels[] = now()->subDays($i)->format('M d');
                $chartData[] = (float) ($dailyRevenue[$date] ?? 0);
            }

            $pendingRefunds = \App\Models\RefundRequest::with(['sale.item', 'customer.user'])->where('status', 'pending')->latest()->get();

            return view('admin.dashboard', compact('stats', 'recentActivities', 'inventorySummary', 'recentSales', 'activeServiceJobs', 'recentPreorders', 'chartLabels', 'chartData', 'pendingRefunds'));
        }

        return redirect()->route('customer.dashboard');
    }
}
