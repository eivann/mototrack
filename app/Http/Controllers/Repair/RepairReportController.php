<?php

namespace App\Http\Controllers\Repair;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Repair;
use App\Services\RepairReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RepairReportController extends Controller
{
    public function __construct(protected RepairReportService $reportService)
    {
    }

    /**
     * Display the owner-authorized service reporting interface.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Repair::class);

        $filters = $request->only([
            'date_from', 'date_to', 'from', 'to', 'date_type', 'status', 'customer_id', 'paid', 'search',
        ]);

        $reportData = $this->reportService->getReportData($filters);
        $repairs = $this->reportService->buildQuery($filters)->latest()->paginate(15)->withQueryString();
        $customers = Customer::select('id', 'name')->orderBy('name')->get();

        return view('repairs.reports', compact('repairs', 'customers', 'reportData'));
    }

    /**
     * Memory-safe CSV export with UTF-8 BOM and formula injection protection.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Repair::class);

        $filters = $request->only([
            'date_from', 'date_to', 'from', 'to', 'date_type', 'status', 'customer_id', 'paid', 'search',
        ]);

        $reportData = $this->reportService->getReportData($filters);
        $query = $this->reportService->buildQuery($filters)->latest();
        $tz = config('app.timezone', 'Asia/Manila');
        $generatedAt = Carbon::now($tz)->format('M d, Y g:i A');
        $filename = 'service-report-'.Carbon::now($tz)->format('Ymd-His').'.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->streamDownload(function () use ($query, $reportData, $filters, $generatedAt, $tz) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Report Header
            fputcsv($handle, ['EDMAR MOTORCYCLE PARTS - SERVICE & REPAIR REPORT']);
            fputcsv($handle, ['Generated At (Asia/Manila)', $generatedAt]);
            fputcsv($handle, ['Date Range Filter', ($filters['date_from'] ?? $filters['from'] ?? 'All').' to '.($filters['date_to'] ?? $filters['to'] ?? 'All')]);
            fputcsv($handle, ['Date Boundary Type', ucfirst($filters['date_type'] ?? 'created')]);
            fputcsv($handle, ['Status Filter', ucfirst($filters['status'] ?? 'All')]);
            fputcsv($handle, []);

            // Summary Totals
            fputcsv($handle, ['--- EXECUTIVE SUMMARY & TOTALS ---']);
            fputcsv($handle, ['Total Jobs', $reportData['total_jobs']]);
            fputcsv($handle, ['In Progress Jobs', $reportData['status_counts']['ongoing']]);
            fputcsv($handle, ['Pending Queue Jobs', $reportData['status_counts']['pending']]);
            fputcsv($handle, ['Completed Jobs', $reportData['status_counts']['completed']]);
            fputcsv($handle, ['Average Turnaround Time', $reportData['avg_turnaround_formatted']]);
            fputcsv($handle, ['Total Invoiced Charges (₱)', number_format($reportData['total_invoiced_charges'], 2, '.', '')]);
            fputcsv($handle, ['Paid Invoiced Charges (₱)', number_format($reportData['paid_invoiced_charges'], 2, '.', '')]);
            fputcsv($handle, ['Unpaid Invoiced Charges (₱)', number_format($reportData['unpaid_invoiced_charges'], 2, '.', '')]);
            fputcsv($handle, ['Recorded Cash Sales Revenue (₱)', number_format($reportData['recorded_gross_revenue'], 2, '.', '')]);
            if (($reportData['refunded_amount'] ?? 0) > 0) {
                fputcsv($handle, ['Refunded Repair Sales (₱)', number_format($reportData['refunded_amount'], 2, '.', '')]);
                fputcsv($handle, ['Net Recorded Cash Revenue (₱)', number_format($reportData['recorded_net_revenue'], 2, '.', '')]);
            }
            fputcsv($handle, ['VATable Net Sales (₱)', number_format($reportData['vat_net'], 2, '.', '')]);
            fputcsv($handle, ['VAT Collected (12%) (₱)', number_format($reportData['vat_amount'], 2, '.', '')]);
            fputcsv($handle, []);

            // Data Table Headers
            fputcsv($handle, [
                'Job ID',
                'Customer Name',
                'Motorcycle Model',
                'Reported Issue',
                'Status',
                'Intake Date (Manila)',
                'Started Date (Manila)',
                'Completed Date (Manila)',
                'Payment Date (Manila)',
                'Invoiced Cost (PHP)',
                'Payment Status',
            ]);

            // Memory-safe chunked streaming (prevents OOM on large datasets)
            $query->chunk(200, function ($repairs) use ($handle, $tz) {
                foreach ($repairs as $repair) {
                    $intakeDate    = $repair->created_at ? $repair->created_at->timezone($tz)->format('Y-m-d H:i') : '';
                    $startedDate   = $repair->started_at ? $repair->started_at->timezone($tz)->format('Y-m-d H:i') : '';
                    $completedDate = $repair->completed_at ? $repair->completed_at->timezone($tz)->format('Y-m-d H:i') : '';
                    $paidDate      = $repair->paid_at ? $repair->paid_at->timezone($tz)->format('Y-m-d H:i') : ($repair->is_paid ? 'Historical / Unstamped' : 'Unpaid');

                    fputcsv($handle, [
                        RepairReportService::sanitizeCsvField($repair->id),
                        RepairReportService::sanitizeCsvField($repair->customer_name),
                        RepairReportService::sanitizeCsvField($repair->bike_model),
                        RepairReportService::sanitizeCsvField($repair->issue),
                        RepairReportService::sanitizeCsvField(ucfirst($repair->status)),
                        RepairReportService::sanitizeCsvField($intakeDate),
                        RepairReportService::sanitizeCsvField($startedDate),
                        RepairReportService::sanitizeCsvField($completedDate),
                        RepairReportService::sanitizeCsvField($paidDate),
                        $repair->cost !== null ? number_format((float) $repair->cost, 2, '.', '') : '0.00',
                        $repair->is_paid ? 'Paid' : 'Unpaid',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, $headers);
    }

    /**
     * PDF report export using DomPDF.
     */
    public function exportPdf(Request $request)
    {
        Gate::authorize('viewAny', Repair::class);

        $filters = $request->only([
            'date_from', 'date_to', 'from', 'to', 'date_type', 'status', 'customer_id', 'paid', 'search',
        ]);

        $reportData = $this->reportService->getReportData($filters);
        // Safe limit for printable PDF export
        $repairs = $this->reportService->buildQuery($filters)->latest()->limit(500)->get();
        $tz = config('app.timezone', 'Asia/Manila');
        $generatedAt = Carbon::now($tz)->format('M d, Y g:i A');
        $filename = 'service-report-'.Carbon::now($tz)->format('Ymd-His').'.pdf';

        $pdf = Pdf::loadView('repairs.reports_pdf', compact('repairs', 'reportData', 'filters', 'generatedAt'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}

