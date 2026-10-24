@php
    $firstSale = $sales->first();
    $isPaid = ($firstSale->payment_confirmed_at !== null || $firstSale->status === 'completed');
    $customer = $firstSale->customer;
    $grandTotal = $sales->sum('total_price');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $isPaid ? 'Cash Sales Receipt' : 'Reservation Acknowledgement' }} - {{ $transactionId }}</title>
    <style>
        @page {
            margin: 24px 28px;
            size: letter portrait;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: top;
        }
        .brand-title {
            font-size: 22px;
            font-weight: bold;
            color: #4338ca;
            letter-spacing: 1px;
            margin: 0;
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 10px;
            font-weight: bold;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }
        .brand-address {
            font-size: 9px;
            color: #6b7280;
            margin-top: 2px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .status-paid {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .status-unpaid {
            background-color: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .meta-text {
            font-size: 10px;
            color: #374151;
            margin-top: 2px;
        }
        .meta-text strong {
            color: #111827;
        }
        .accent-bar {
            height: 3px;
            background-color: #4f46e5;
            margin-bottom: 14px;
        }
        .customer-card {
            width: 100%;
            border-collapse: collapse;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            margin-bottom: 14px;
            border-radius: 4px;
        }
        .customer-card td {
            padding: 6px 10px;
            font-size: 10px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .items-table th {
            background-color: #f5f3ff;
            color: #3730a3;
            border-bottom: 2px solid #c7d2fe;
            border-top: 1px solid #e0e7ff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            text-align: left;
        }
        .items-table td {
            padding: 8px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
            font-size: 10.5px;
        }
        .items-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .item-name {
            font-weight: bold;
            color: #111827;
        }
        .item-sub {
            font-size: 9px;
            color: #6b7280;
            margin-top: 2px;
        }
        .summary-wrapper {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .summary-wrapper td {
            vertical-align: top;
        }
        .instructions-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 9.5px;
            color: #475569;
            line-height: 1.35;
        }
        .totals-table {
            width: 250px;
            float: right;
            border-collapse: collapse;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .totals-table td {
            padding: 5px 10px;
            font-size: 10px;
        }
        .totals-table .grand-row td {
            background-color: #f5f3ff;
            border-top: 2px solid #4f46e5;
            font-size: 12px;
            font-weight: bold;
            color: #312e81;
            padding: 7px 10px;
        }
        .footer-disclaimer {
            clear: both;
            border-top: 1px dashed #d1d5db;
            padding-top: 10px;
            margin-top: 16px;
            text-align: center;
            font-size: 8.5px;
            color: #6b7280;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <!-- Header Block -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="brand-title">MOTOTRACK</div>
                <div class="brand-sub">Edmar Motorcycle Parts &amp; Accessories</div>
                <div class="brand-address">
                    Pandi, Bulacan, Philippines<br>
                    Contact: edmarmotorshop@gmail.com
                </div>
            </td>
            <td style="width: 45%;" class="text-right">
                @if($isPaid)
                    <div class="status-badge status-paid">Cash Sales Receipt — Paid in Cash</div>
                @else
                    <div class="status-badge status-unpaid">Reservation Acknowledgement — Unpaid</div>
                @endif
                <div class="meta-text">Ref #: <strong>{{ $transactionId }}</strong></div>
                <div class="meta-text">Date: <strong>{{ $date }}</strong></div>
            </td>
        </tr>
    </table>

    <div class="accent-bar"></div>

    <!-- Customer Details (if present) -->
    @if($customer)
    <table class="customer-card">
        <tr>
            <td style="width: 50%;">
                <strong>Customer:</strong> {{ $customer->name ?? 'Registered Customer' }}
            </td>
            <td style="width: 50%;" class="text-right">
                <strong>Contact:</strong> {{ $customer->phone ?? $customer->email ?? 'N/A' }}
            </td>
        </tr>
    </table>
    @endif

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">#</th>
                <th style="width: 54%;">Part Description &amp; Specification</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 15%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $index => $sale)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div class="item-name">{{ $sale->item->part_name ?? 'Item / Service' }}</div>
                    @if($sale->item && ($sale->item->brand || $sale->item->model))
                        <div class="item-sub">
                            {{ trim(($sale->item->brand ?? '') . ' ' . ($sale->item->model ?? '')) }}
                            @if($sale->item->sku) • SKU: {{ $sale->item->sku }} @endif
                        </div>
                    @endif
                </td>
                <td class="text-center">{{ $sale->quantity }}</td>
                <td class="text-right">PHP {{ number_format($sale->price, 2) }}</td>
                <td class="text-right"><strong>PHP {{ number_format($sale->total_price, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals and Instruction Summary -->
    <table class="summary-wrapper">
        <tr>
            <td style="width: 55%; padding-right: 15px;">
                <div class="instructions-box">
                    @if($isPaid)
                        <strong style="color: #065f46;">Payment Confirmed at POS Counter</strong><br>
                        This transaction was settled in cash at our Pandi shop counter. Please retain this cash sales receipt for return, exchange, or defect inspection at our shop counter within 7 calendar days in accordance with shop policy and the Consumer Act of the Philippines (RA 7394).
                    @else
                        <strong style="color: #92400e;">24-Hour Pickup &amp; Payment Notice</strong><br>
                        This document acknowledges your reservation request. No payment was collected online. Reserved items are held for up to 24 hours. Please present this document at the shop counter to pay in cash and collect your items.
                    @endif
                </div>
            </td>
            <td style="width: 45%;">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-right">PHP {{ number_format($grandTotal, 2) }}</td>
                    </tr>
                    @if($isPaid && $firstSale && $firstSale->amount_tendered > 0)
                        <tr>
                            <td>Amount Tendered:</td>
                            <td class="text-right">PHP {{ number_format($firstSale->amount_tendered, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Change Given:</td>
                            <td class="text-right">PHP {{ number_format($firstSale->change, 2) }}</td>
                        </tr>
                    @elseif(!$isPaid)
                        <tr>
                            <td>Amount Collected:</td>
                            <td class="text-right" style="color: #92400e;">PHP 0.00</td>
                        </tr>
                        <tr>
                            <td>Payment Due:</td>
                            <td class="text-right" style="font-weight: bold; color: #b45309;">Cash at Pickup</td>
                        </tr>
                    @endif
                    <tr class="grand-row">
                        <td>{{ $isPaid ? 'GRAND TOTAL:' : 'TOTAL DUE:' }}</td>
                        <td class="text-right">PHP {{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer Disclaimer -->
    <div class="footer-disclaimer">
        Edmar Motorcycle Parts and Accessories • Pandi, Bulacan, Philippines<br>
        This electronic document is a transaction receipt and acknowledgement. It is not an official BIR tax invoice. For official tax invoicing, please request a BIR-registered sales invoice at our store counter.
        <br>
        <strong>Powered by MotoTrack</strong>
    </div>

</body>
</html>
