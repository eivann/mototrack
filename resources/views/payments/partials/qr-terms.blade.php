{{-- RD2-R2: GCash QR terms. Rendered only while GCash QR is switched on, so the terms never describe an unavailable method.
     No refund commitment is made here: refunds are decided case by case by the shop owner (pending owner approval). --}}
@if (config('payments.gcash_qr.enabled'))
    <div>
        <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">Payment by GCash QR (optional)</h4>
        <ul class="space-y-2 list-disc list-inside">
            <li>GCash QR is available for product reservations and catalogue pre-orders only, not for custom-parts requests. Cash at the shop counter remains available.</li>
            <li>You pay from your own GCash app outside MotoTrack. MotoTrack does not process or hold your money, and never asks for your GCash PIN or login.</li>
            <li><strong>Submitting a reference number or screenshot is not confirmation of payment.</strong> It is a claim that you paid. Your payment counts only after the shop owner checks the shop's GCash account and verifies that the money was actually received.</li>
            <li>The owner may reject a claim if the amount, reference or screenshot cannot be matched to funds received. You will be notified and may submit a corrected claim while your order is still open.</li>
            <li>Pay exactly the amount shown for your order. A payment made for a different amount, to a different order, or after your order has closed may need manual review.</li>
            <li>If a reservation or pre-order expires or is cancelled while a payment claim exists, the order is <em>not</em> reactivated and the payment is not automatically refunded. The shop owner reviews the claim, checks the funds actually received, and decides the resolution with you. Specific refund terms are set by the shop owner.</li>
        </ul>
    </div>
@endif
