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
                    'todayRevenue' => (function() {
                        $gross = Sale::whereDate('payment_confirmed_at', now()->today())
                            ->where(function($q) { $q->where('status', 'completed')->orWhere('status', \App\Enums\OrderStatus::SALE_REFUNDED)->orWhere('amount_tendered', '>', 0); })
                            ->whereNotNull('payment_confirmed_at')->sum('total_price');
                        $refunds = \App\Models\ProductReturn::whereDate('created_at', now()->today())->whereNotNull('refund_amount')->sum('refund_amount');
                        return max(0, (float)$gross - (float)$refunds);
                    })(),
                    'monthlyRevenue' => (function() {
                        $gross = Sale::whereMonth('payment_confirmed_at', now()->month)->whereYear('payment_confirmed_at', now()->year)
                            ->where(function($q) { $q->where('status', 'completed')->orWhere('status', \App\Enums\OrderStatus::SALE_REFUNDED)->orWhere('amount_tendered', '>', 0); })
                            ->whereNotNull('payment_confirmed_at')->sum('total_price');
                        $refunds = \App\Models\ProductReturn::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->whereNotNull('refund_amount')->sum('refund_amount');
                        return max(0, (float)$gross - (float)$refunds);
                    })(),
                    'totalSalesCount' => Sale::count(),
                    'completedRepairs' => Repair::where('status', 'completed')->count(),
                ];
            });

            $recentActivities = auth()->user()->notifications()->latest()->take(5)->get();

            $inventorySummary = InventoryItem::latest('id')->take(10)->get();
            $recentSales = Sale::with('item', 'customer')->latest()->take(10)->get();
            $activeServiceJobs = Repair::whereIn('status', ['pending', 'ongoing'])->latest()->take(10)->get();
            $recentPreorders = \App\Models\PreOrder::latest()->take(10)->get();
            // Pending reservations are short-lived (24h), so load every pending line: a
            // multi-line reservation must show its complete total before pickup.
            $pendingReservations = Sale::with('item', 'customer.user')->where('status', 'pending')->latest()->get();
            $settledReservations = Sale::with('item', 'customer.user')
                ->whereIn('status', [\App\Enums\OrderStatus::SALE_CANCELLED, \App\Enums\OrderStatus::SALE_EXPIRED])
                ->latest()->take(15)->get();
            $recentReturns = \App\Models\ProductReturn::with('item', 'sale')->latest()->take(10)->get();

            $lowStockItems = InventoryItem::whereColumn('quantity', '<=', 'low_stock_threshold')
                ->orderBy('quantity')
                ->orderBy('part_name')
                ->limit(10)
                ->get();
            $outOfStockCount = InventoryItem::where('quantity', '<=', 0)->count();

            // Daily net revenue for the last 30 days (Chart.js)
            $dailyGross = Sale::selectRaw('DATE(payment_confirmed_at) as date, SUM(total_price) as gross')
                ->where('payment_confirmed_at', '>=', now()->subDays(30))
                ->where(function($q) { $q->where('status', 'completed')->orWhere('status', \App\Enums\OrderStatus::SALE_REFUNDED)->orWhere('amount_tendered', '>', 0); })
                ->whereNotNull('payment_confirmed_at')
                ->groupByRaw('DATE(payment_confirmed_at)')
                ->orderByRaw('DATE(payment_confirmed_at)')
                ->pluck('gross', 'date');

            $dailyRefunds = \App\Models\ProductReturn::selectRaw('DATE(created_at) as date, SUM(refund_amount) as refunds')
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('refund_amount')
                ->groupByRaw('DATE(created_at)')
                ->pluck('refunds', 'date');

            // Fill in missing days with 0
            $chartLabels = [];
            $chartData = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $chartLabels[] = now()->subDays($i)->format('M d');
                $net = ((float) ($dailyGross[$date] ?? 0)) - ((float) ($dailyRefunds[$date] ?? 0));
                $chartData[] = max(0, $net);
            }

            $pendingRefunds = \App\Models\RefundRequest::with(['sale.item', 'customer.user'])->where('status', 'pending')->latest()->get();

            return view('admin.dashboard', compact('stats', 'recentActivities', 'inventorySummary', 'recentSales', 'activeServiceJobs', 'recentPreorders', 'pendingReservations', 'settledReservations', 'recentReturns', 'chartLabels', 'chartData', 'pendingRefunds', 'lowStockItems', 'outOfStockCount'));
        }

        return redirect()->route('customer.dashboard');
    }
}
