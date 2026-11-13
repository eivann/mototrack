<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Service &amp; Repair Report</title>
    <style>
        @page {
            margin: 24px 28px;
            size: a4 landscape;
        }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .brand-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .brand-sub {
            font-size: 9px;
            color: #4f46e5;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .report-meta {
            margin-top: 6px;
            font-size: 9px;
            color: #6b7280;
        }
        .meta-row {
            display: inline-block;
            margin-right: 16px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .summary-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            vertical-align: top;
            width: 25%;
        }
        .summary-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 3px;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }
        .summary-sub {
            font-size: 8px;
            color: #9ca3af;
            margin-top: 2px;
        }
        .rev-box {
            background-color: #f5f3ff;
            border: 1px solid #c7d2fe;
            padding: 8px 12px;
            margin-bottom: 14px;
            border-radius: 4px;
        }
        .rev-title {
            font-size: 9px;
            font-weight: bold;
            color: #3730a3;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        .data-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            padding: 6px 8px;
            border-bottom: 1px solid #d1d5db;
            text-align: left;
        }
        .data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-ongoing { background-color: #e0e7ff; color: #3730a3; }
        .badge-completed { background-color: #d1fae5; color: #065f46; }
        .badge-paid { background-color: #dcfce7; color: #166534; }
        .badge-unpaid { background-color: #f3f4f6; color: #4b5563; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer {
            margin-top: 14px;
            font-size: 8px;
            color: #9ca3af;
            text-align: right;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="brand-title">Edmar Motorcycle Parts and Accessories</div>
        <div class="brand-sub">Service &amp; Repair Operations Report</div>
        <div class="report-meta">
            <span class="meta-row"><strong>Generated:</strong> {{ $generatedAt }} (Asia/Manila)</span>
            <span class="meta-row"><strong>Date Filter:</strong> {{ $filters['date_from'] ?? $filters['from'] ?? 'All' }} to {{ $filters['date_to'] ?? $filters['to'] ?? 'All' }} ({{ ucfirst($filters['date_type'] ?? 'created') }})</span>
            <span class="meta-row"><strong>Status:</strong> {{ ucfirst($filters['status'] ?? 'All') }}</span>
            <span class="meta-row"><strong>Payment:</strong> {{ isset($filters['paid']) && $filters['paid'] !== '' ? ($filters['paid'] ? 'Paid' : 'Unpaid') : 'All' }}</span>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <table class="summary-table">
        <tr>
            <td class="summary-card">
                <div class="summary-label">Total Services</div>
                <div class="summary-value">{{ $reportData['total_jobs'] }}</div>
                <div class="summary-sub">{{ $reportData['status_counts']['completed'] }} completed &middot; {{ $reportData['status_counts']['ongoing'] }} in bay</div>
            </td>
            <td class="summary-card">
                <div class="summary-label">Avg Turnaround</div>
                <div class="summary-value">{{ $reportData['avg_turnaround_formatted'] }}</div>
                <div class="summary-sub">Bay start to completion</div>
            </td>
            <td class="summary-card">
                <div class="summary-label">Invoiced Service Charges</div>
                <div class="summary-value">PHP {{ number_format($reportData['total_invoiced_charges'], 2) }}</div>
                <div class="summary-sub">Paid: PHP {{ number_format($reportData['paid_invoiced_charges'], 2) }}</div>
            </td>
            <td class="summary-card">
                <div class="summary-label">Recorded Cash Sales</div>
                <div class="summary-value" style="color: #047857;">PHP {{ number_format($reportData['recorded_gross_revenue'], 2) }}</div>
                <div class="summary-sub">From authoritative receipts</div>
            </td>
        </tr>
    </table>

    <!-- VAT Reconciliation Breakdown (Requirement 5 & 6) -->
    <div class="rev-box">
        <div class="rev-title">Authoritative Revenue &amp; 12% VAT Breakdown (From Confirmed SaleHeader Records)</div>
        <table style="width: 100%; font-size: 8.5px;">
            <tr>
                <td style="width: 25%;"><strong>Gross Cash Revenue:</strong> PHP {{ number_format($reportData['recorded_gross_revenue'], 2) }}</td>
                @if(($reportData['refunded_amount'] ?? 0) > 0)
                    <td style="width: 25%;"><strong>Refunds:</strong> PHP {{ number_format($reportData['refunded_amount'], 2) }}</td>
                    <td style="width: 25%;"><strong>Net Cash:</strong> PHP {{ number_format($reportData['recorded_net_revenue'], 2) }}</td>
                @endif
                <td style="width: 25%;"><strong>VATable Net:</strong> PHP {{ number_format($reportData['vat_net'], 2) }}</td>
                <td style="width: 25%;"><strong>VAT (12%):</strong> PHP {{ number_format($reportData['vat_amount'], 2) }}</td>
            </tr>
        </table>
    </div>

    @if(($reportData['total_jobs'] ?? 0) > 500 || count($repairs) >= 500)
        <div style="background-color: #fffbeb; border: 1px solid #fde68a; padding: 6px 10px; margin-bottom: 10px; font-size: 8.5px; color: #92400e; border-radius: 4px;">
            <strong>Export Limitation Notice:</strong> Showing the latest 500 itemized records in this PDF export out of {{ $reportData['total_jobs'] }} total matching jobs. The Executive Summary metrics and VAT totals above reflect the complete dataset. For the full itemized dataset without record limits, download the CSV export.
        </div>
    @endif

    <!-- Detailed Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">#ID</th>
                <th style="width: 15%;">Customer</th>
                <th style="width: 15%;">Motorcycle Model</th>
                <th style="width: 24%;">Service Description</th>
                <th style="width: 9%;" class="text-center">Status</th>
                <th style="width: 15%;">Timestamps (Manila)</th>
                <th style="width: 8%;" class="text-right">Cost (PHP)</th>
                <th style="width: 8%;" class="text-center">Payment</th>
            </tr>
        </thead>
        <tbody>
            @forelse($repairs as $repair)
            <tr>
                <td>#{{ $repair->id }}</td>
                <td><strong>{{ $repair->customer_name }}</strong></td>
                <td>{{ $repair->bike_model }}</td>
                <td>{{ $repair->issue }}</td>
                <td class="text-center">
                    @if($repair->status === 'pending')
                        <span class="badge badge-pending">Pending</span>
                    @elseif($repair->status === 'ongoing')
                        <span class="badge badge-ongoing">Ongoing</span>
                    @else
                        <span class="badge badge-completed">Done</span>
                    @endif
                </td>
                <td style="font-size: 7.5px; color: #4b5563;">
                    Intake: {{ $repair->createdAtManila('M d, Y g:i A') }}<br>
                    @if($repair->completed_at)
                        Done: {{ $repair->completedAtManila('M d, Y g:i A') }}<br>
                    @endif
                    @if($repair->paid_at)
                        Paid: {{ $repair->paidAtManila('M d, Y g:i A') }}
                    @endif
                </td>
                <td class="text-right"><strong>{{ $repair->cost !== null ? number_format((float) $repair->cost, 2) : 'TBD' }}</strong></td>
                <td class="text-center">
                    @if($repair->is_paid)
                        <span class="badge badge-paid">Paid</span>
                    @else
                        <span class="badge badge-unpaid">Unpaid</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #9ca3af;">
                    No service records match the selected report filter.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        MotoTrack Repair &amp; Service Management System &middot; Page 1 of 1
    </div>
</body>
</html>

