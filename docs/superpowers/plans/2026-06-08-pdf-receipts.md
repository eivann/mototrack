# PDF Receipts & Export Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Provide shop owners and customers with the ability to download permanent, offline PDF copies of their POS transactions and sales receipts.

**Architecture:** Install `barryvdh/laravel-dompdf` to handle backend HTML-to-PDF compilation. Add a new `downloadReceipt` method to the existing `SaleController`, which will load a print-optimized Blade view (`pdf_receipt.blade.php`) and stream/download the generated PDF file. Finally, add download action buttons to the existing web receipt view.

**Tech Stack:** Laravel 13.x, DOMPDF, Pest PHP

---

### Task 1: Install Dependencies

**Files:**
- Modify: `composer.json` (Automatically via CLI)

- [x] **Step 1: Install the DOMPDF package**

Run: `composer require barryvdh/laravel-dompdf`
Expected: Package installs successfully and auto-discovers the service provider.

- [x] **Step 2: Commit dependency addition**

```bash
git add composer.json composer.lock
git commit -m "chore: install barryvdh/laravel-dompdf for receipt generation"
```

---

### Task 2: Create the PDF Blade View

**Files:**
- Create: `resources/views/sales/pdf_receipt.blade.php`

- [x] **Step 1: Create the printable receipt view**

Create `resources/views/sales/pdf_receipt.blade.php` containing inline, simple CSS styling optimized for the DOMPDF engine (avoiding complex Tailwind utilities that don't translate well to PDF):
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Receipt - {{ $transactionId }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .title { font-size: 24px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .subtitle { font-size: 10px; color: #666; letter-spacing: 1px; }
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
        <p style="margin-top: 5px;">Official Transaction Receipt</p>
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
        Thank you for your business!<br>
        This receipt is system generated.
    </div>
</body>
</html>
```

- [x] **Step 2: Commit view file**

```bash
git add resources/views/sales/pdf_receipt.blade.php
git commit -m "feat: create pdf receipt layout"
```

---

### Task 3: Update Controller and Routes

**Files:**
- Modify: `routes/modules/sales.php`
- Modify: `app/Http/Controllers/Sales/SaleController.php`

- [x] **Step 1: Add PDF download route**

Open `routes/modules/sales.php` (or `web.php` if `sales.php` does not exist, but let's check `routes/web.php` since the receipt route is already there). Add the download route right below the receipt route:
```php
Route::get('/sales/{transaction}/download', [\App\Http\Controllers\Sales\SaleController::class, 'downloadReceipt'])->name('sales.download');
```

- [x] **Step 2: Implement the download logic**

Open `app/Http/Controllers/Sales/SaleController.php` and add the new `downloadReceipt` method:
```php
    public function downloadReceipt($id)
    {
        $sales = Sale::with('item')->where('transaction_reference', $id)->get();
        if ($sales->isEmpty()) {
            $sales = collect([Sale::with('item')->findOrFail($id)]);
        }

        $transactionId = $sales->first()->transaction_reference ?? 'SALE-' . $sales->first()->id;
        $date = $sales->first()->created_at->format('F d, Y h:i A');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf_receipt', compact('sales', 'transactionId', 'date'));
        
        return $pdf->download("Receipt_{$transactionId}.pdf");
    }
```

- [x] **Step 3: Commit controller and routes**

```bash
git add routes app/Http/Controllers/Sales/SaleController.php
git commit -m "feat: implement pdf generation logic and download route"
```

---

### Task 4: UI Integration (Download Button)

**Files:**
- Modify: `resources/views/sales/receipt.blade.php`

- [x] **Step 1: Add Download Button to Web Receipt**

Open `resources/views/sales/receipt.blade.php` and locate the action buttons at the bottom of the receipt view. Add a secondary button to trigger the PDF download alongside the existing "Print" or "Back" buttons:
```html
<a href="{{ route('sales.download', ['transaction' => $sales->first()->transaction_reference ?? $sales->first()->id]) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-transform active:scale-95 flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
    Download PDF
</a>
```

- [x] **Step 2: Commit UI updates**

```bash
git add resources/views/sales/receipt.blade.php
git commit -m "feat: add download pdf button to web receipt"
```

---

### Task 5: Feature Tests

**Files:**
- Create: `tests/Feature/Sales/PdfExportTest.php`

- [x] **Step 1: Write integration tests verifying PDF generation**

Create `tests/Feature/Sales/PdfExportTest.php`:
```php
<?php

use App\Models\Sale;
use App\Models\User;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can download a pdf receipt', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    
    $item = InventoryItem::create([
        'part_name' => 'Tire',
        'price' => 1500,
        'quantity' => 10,
    ]);

    $sale = Sale::create([
        'inventory_item_id' => $item->id,
        'quantity' => 1,
        'price' => 1500,
        'total_price' => 1500,
        'transaction_reference' => 'TRX-123456',
    ]);

    $response = $this->actingAs($owner)->get("/sales/TRX-123456/download");
    
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    $this->assertStringContainsString('attachment; filename="Receipt_TRX-123456.pdf"', $response->headers->get('content-disposition'));
});
```

- [x] **Step 2: Run tests**

Run: `php artisan test --filter=PdfExportTest`
Expected: PASS

- [x] **Step 3: Commit tests**

```bash
git add tests/Feature/Sales/PdfExportTest.php
git commit -m "test: verify pdf export functionality"
```

---

## Self-Review

**1. Spec coverage:**
- Target Requirement: Generate exportable, downloadable offline PDF files of receipts.
- Covers Composer installation, Controller logic, printable view format, frontend access point, and integration testing.

**2. Architecture context:**
- `DOMPDF` is safely sandboxed within `SaleController` avoiding tight-coupling to models.
- Reuses the existing `transaction_reference` structure.
