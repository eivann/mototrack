{{-- RD2-R5: reservation and order terms, shared by the checkout modal and the public /terms page so the two can never drift apart.
     Only confirmed policies: cash at the counter, owner-verified GCash QR (while enabled), 24-hour pickup, customer cancellation, in-person returns. --}}
<div>
    <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">1. Reservation & Order Details</h4>
    <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
        <li>Online checkout creates an unpaid stock reservation acknowledgement, holding available items exclusively for you.</li>
        <li>Checkout does not charge you. @if (config('payments.gcash_qr.enabled'))Pay in cash at the physical shop counter upon pickup, or choose GCash QR (see "Payment by GCash QR" below).@else Full payment is made in cash at the physical shop counter upon pickup.@endif</li>
    </ul>
</div>

<div>
    <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">2. Multiple Orders</h4>
    <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
        <li>Customers may reserve multiple parts concurrently. Each reservation is tracked with its own status under your customer dashboard.</li>
    </ul>
</div>

<div>
    <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">3. 24-Hour Pickup Policies</h4>
    <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
        <li><strong class="text-indigo-600 dark:text-indigo-400">In-Stock Reservations:</strong> In-stock items are allocated immediately. The 24-hour pickup countdown begins upon reservation creation. Unclaimed reservations automatically expire, releasing stock back to the catalog.</li>
        <li><strong class="text-amber-700 dark:text-amber-400">Pre-Orders:</strong> Pre-ordered items are ordered from suppliers. The 24-hour pickup countdown begins <em>only after</em> the item physically arrives at our shop and a readiness notification is sent to your account.</li>
    </ul>
</div>

<div>
    <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">4. Compatibility Checker</h4>
    <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
        <li>The compatibility checker evaluates parts against database records of verified motorcycles in our inventory system.</li>
        <li>Please ensure your make, model, and year match your vehicle before reserving.</li>
    </ul>
</div>

<div>
    <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">5. Cancellation Policy</h4>
    <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
        <li>You may cancel a reservation at any time prior to pickup directly through your customer dashboard with immediate stock restoration.</li>
    </ul>
</div>

<div>
    <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">6. Returns & Physical Refunds</h4>
    <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-gray-400">
        <li>Purchases are completed at the store counter (in cash{{ config('payments.gcash_qr.enabled') ? ', or by a GCash QR payment the shop has verified' : '' }}). All returns, exchanges, or defect claims must be presented in person at the physical shop counter with the official receipt.</li>
        <li>Defective items are inspected by shop technicians in accordance with the Consumer Act of the Philippines (RA 7394) and processed as either a resellable return or damaged write-off.</li>
    </ul>
</div>
