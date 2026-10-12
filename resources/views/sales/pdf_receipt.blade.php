@php
    $isPaid = ($sales->first()->payment_confirmed_at !== null || $sales->first()->status === 'completed');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $isPaid ? 'POS Sales Receipt' : 'Reservation Acknowledgement' }} - {{ $transactionId }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .title { font-size: 24px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .subtitle { font-size: 10px; color: #666; letter-spacing: 1px; }
        .disclaimer-banner { margin-top: 8px; padding: 6px; background-color: #fef3c7; border: 1px solid #f59e0b; font-weight: bold; font-size: 10px; color: #92400e; text-transform: uppercase; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { padding: 5px 0; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { border-bottom: 1px solid #000; text-align: left; padding: 8px; text-transform: uppercase; font-size: 10px; }
        .items-table td { padding: 8px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .totals-table { width: 50%; float: right; border-collapse: collapse; }
        .totals-table td { padding: 5px 8px; }
        .totals-table .grand-total { font-size: 16px; font-weight: bold; border-top: 2px solid #000; }
        .footer { clear: both; text-align: center; margin-top: 50px; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">MOTOTRACK</h1>
        <div class="subtitle">EDMAR MOTORCYCLE PARTS & ACCESSORIES</div>
        @if($isPaid)
            <p style="margin-top: 5px; font-weight: bold;">POS Sales Receipt — Cash Payment</p>
        @else
            <div class="disclaimer-banner">
                Reservation Acknowledgement — No Payment Collected — Not an Official Receipt
            </div>
        @endif
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Transaction Ref:</strong> {{ $transactionId }}</td>
            <td class="text-right"><strong>Date:</strong> {{ $date }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th>Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            <tr>
                <td>{{ $sale->item->part_name ?? 'Unknown Item' }}</td>
                <td>{{ $sale->quantity }}</td>
                <td class="text-right">PHP {{ number_format($sale->price, 2) }}</td>
                <td class="text-right">PHP {{ number_format($sale->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Amount Tendered:</td>
            <td class="text-right">PHP {{ number_format($sales->first()->amount_tendered ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>Change:</td>
            <td class="text-right">PHP {{ number_format($sales->first()->change ?? 0, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>GRAND TOTAL:</td>
            <td class="text-right">PHP {{ number_format($sales->sum('total_price'), 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        @if($isPaid)
            Thank you for your business!<br>
            POS Sales Receipt — Paid in Full at POS.
        @else
            <strong>Reservation Acknowledgement — No Payment Collected — Not an Official Receipt</strong><br>
            Please present this acknowledgement at the counter within 24 hours to complete payment and claim your items.
        @endif
    </div>
</body>
</html>
