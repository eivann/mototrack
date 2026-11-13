<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Repair;
use App\Models\SaleHeader;
use App\Support\Vat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RepairReportService
{
    /**
     * Build the filtered query for repairs matching reporting criteria.
     *
     * @param  array<string, mixed>  $filters
     */
    public function buildQuery(array $filters): Builder
    {
        $query = Repair::query()->with(['customer', 'reservation']);

        $tz = config('app.timezone', 'Asia/Manila');
        $fromInput = $filters['date_from'] ?? $filters['from'] ?? null;
        $toInput   = $filters['date_to'] ?? $filters['to'] ?? null;
        $dateType  = $filters['date_type'] ?? 'created';

        $dateColumn = match ($dateType) {
            'started'   => 'started_at',
            'completed' => 'completed_at',
            'paid'      => 'paid_at',
            default     => 'created_at',
        };

        // Canonical half-open date boundaries: [startOfDay(from), startOfDay(to + 1 day))
        if ($fromInput && $toInput) {
            $start        = Carbon::parse($fromInput, $tz)->startOfDay();
            $endExclusive = Carbon::parse($toInput,   $tz)->addDay()->startOfDay();

            if (Carbon::parse($fromInput, $tz)->startOfDay()->lte(Carbon::parse($toInput, $tz)->startOfDay())) {
                $query->where($dateColumn, '>=', $start)->where($dateColumn, '<', $endExclusive);
            } else {
                // Guard against reversed ranges (from > to) — zero results cleanly
                $query->whereRaw('1 = 0');
            }
        } elseif ($fromInput) {
            $start = Carbon::parse($fromInput, $tz)->startOfDay();
            $query->where($dateColumn, '>=', $start);
        } elseif ($toInput) {
            $endExclusive = Carbon::parse($toInput, $tz)->addDay()->startOfDay();
            $query->where($dateColumn, '<', $endExclusive);
        }

        // Status filter
        if (! empty($filters['status'])) {
            $status = $filters['status'];
            if (in_array($status, ['done', 'completed'], true)) {
                $query->whereIn('status', ['done', 'completed']);
            } else {
                $query->where('status', $status);
            }
        }

        // Customer filter
        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Payment status filter
        if (isset($filters['paid']) && $filters['paid'] !== '') {
            $query->where('is_paid', (bool) $filters['paid']);
        }

        // Optional search term
        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', "%{$term}%")
                  ->orWhere('bike_model', 'like', "%{$term}%")
                  ->orWhere('issue', 'like', "%{$term}%")
                  ->orWhere('id', $term);
            });
        }

        return $query;
    }

    /**
     * Compute comprehensive report metrics from existing database records.
     * Distinguishes repair charges (invoiced costs) from actual sales revenue (sale_headers).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters): array
    {
        $query = $this->buildQuery($filters);

        // Cloned queries for aggregations
        $totalJobs = (clone $query)->count();

        $statusCounts = [
            'pending'   => (clone $query)->where('status', 'pending')->count(),
            'ongoing'   => (clone $query)->where('status', 'ongoing')->count(),
            'completed' => (clone $query)->whereIn('status', ['done', 'completed'])->count(),
        ];

        // Average Turnaround Time for completed jobs with both timestamps
        // Excludes or explicitly flags invalid negative intervals (completed_at < started_at)
        // instead of using abs() to conceal inconsistent timestamps.
        $turnaroundJobs = (clone $query)
            ->whereIn('status', ['done', 'completed'])
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get(['started_at', 'completed_at']);

        $validTurnaroundJobs = $turnaroundJobs->filter(function ($r) {
            return $r->completed_at->gte($r->started_at);
        });

        $invalidTurnaroundCount = $turnaroundJobs->count() - $validTurnaroundJobs->count();

        $avgTurnaroundMinutes = 0;
        $avgTurnaroundFormatted = 'N/A';

        if ($validTurnaroundJobs->isNotEmpty()) {
            $totalMinutes = $validTurnaroundJobs->sum(function ($r) {
                return (int) $r->started_at->diffInMinutes($r->completed_at);
            });
            $avgTurnaroundMinutes = (int) round($totalMinutes / $validTurnaroundJobs->count());
            $hours = intdiv($avgTurnaroundMinutes, 60);
            $mins  = $avgTurnaroundMinutes % 60;
            $avgTurnaroundFormatted = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
        }

        // Service Invoiced Charges (from repairs table)
        $totalInvoicedCharges = (float) ((clone $query)->sum('cost') ?? 0);
        $paidInvoicedCharges  = (float) ((clone $query)->where('is_paid', true)->sum('cost') ?? 0);
        $unpaidInvoicedCharges = (float) ((clone $query)->where('is_paid', false)->sum('cost') ?? 0);

        // Actual Recorded Sales Revenue from Authoritative SaleHeader records
        $tz = config('app.timezone', 'Asia/Manila');
        $fromInput = $filters['date_from'] ?? $filters['from'] ?? null;
        $toInput   = $filters['date_to'] ?? $filters['to'] ?? null;

        $saleHeaderQuery = SaleHeader::query()
            ->where('transaction_type', SaleHeader::TYPE_REPAIR);

        if (! empty($filters['customer_id'])) {
            $saleHeaderQuery->where('customer_id', $filters['customer_id']);
        }

        if ($fromInput && $toInput) {
            $start        = Carbon::parse($fromInput, $tz)->startOfDay();
            $endExclusive = Carbon::parse($toInput,   $tz)->addDay()->startOfDay();

            if (Carbon::parse($fromInput, $tz)->startOfDay()->lte(Carbon::parse($toInput, $tz)->startOfDay())) {
                $saleHeaderQuery->where('payment_confirmed_at', '>=', $start)
                                ->where('payment_confirmed_at', '<', $endExclusive);
            } else {
                $saleHeaderQuery->whereRaw('1 = 0');
            }
        } elseif ($fromInput) {
            $start = Carbon::parse($fromInput, $tz)->startOfDay();
            $saleHeaderQuery->where('payment_confirmed_at', '>=', $start);
        } elseif ($toInput) {
            $endExclusive = Carbon::parse($toInput, $tz)->addDay()->startOfDay();
            $saleHeaderQuery->where('payment_confirmed_at', '<', $endExclusive);
        }

        $recordedGrossRevenue = (float) ($saleHeaderQuery->sum('total_amount') ?? 0);
        $recordedPaymentTransactions = $saleHeaderQuery->count();

        // Account correctly for refunds on repair transactions
        $refundedAmount = (float) \App\Models\Sale::whereIn('sale_header_id', (clone $saleHeaderQuery)->select('id'))
            ->where('status', \App\Enums\OrderStatus::SALE_REFUNDED)
            ->sum('total_price');

        $recordedNetRevenue = max(0.0, $recordedGrossRevenue - $refundedAmount);
        $vatBreakdown = Vat::fromInclusive($recordedNetRevenue);

        // Historical paid repairs with null paid_at count (auditing metric)
        $historicalUnstampedPayments = Repair::where('is_paid', true)->whereNull('paid_at')->count();

        return [
            'filters'                     => $filters,
            'total_jobs'                  => $totalJobs,
            'status_counts'               => $statusCounts,
            'avg_turnaround_minutes'      => $avgTurnaroundMinutes,
            'avg_turnaround_formatted'    => $avgTurnaroundFormatted,
            'invalid_turnaround_count'    => $invalidTurnaroundCount,
            'total_invoiced_charges'      => $totalInvoicedCharges,
            'paid_invoiced_charges'       => $paidInvoicedCharges,
            'unpaid_invoiced_charges'     => $unpaidInvoicedCharges,
            'recorded_gross_revenue'      => $recordedGrossRevenue,
            'refunded_amount'             => $refundedAmount,
            'recorded_net_revenue'        => $recordedNetRevenue,
            'recorded_payment_transactions' => $recordedPaymentTransactions,
            'vat_net'                     => $vatBreakdown->net,
            'vat_amount'                  => $vatBreakdown->vat,
            'vat_rate'                    => $vatBreakdown->rate_percent,
            'historical_unstamped_count'  => $historicalUnstampedPayments,
        ];
    }

    /**
     * Prevent CSV Formula Injection by prepending single quote to formulas or control characters.
     * Defends against leading whitespace, tabs, and carriage returns before formula triggers (=, +, -, @, %, \t, \r).
     */
    public static function sanitizeCsvField(mixed $value): string
    {
        $str = (string) ($value ?? '');
        if (preg_match('/^\s*[=\+\-@%\t\r]/', $str)) {
            return "'".$str;
        }

        return $str;
    }
}
