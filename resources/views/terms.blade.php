{{-- RD2-R5: Terms and Conditions page.
     Built only from policies the shop has confirmed. Deliberately NOT stated (pending owner approval): automatic
     cancellation of service requests, a service capacity, what happens to a service request nobody answers,
     any refund or fee commitment, and the format of any BIR-registered invoice. --}}
<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-16">
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tighter mb-3">Terms and Conditions</h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm sm:text-base leading-relaxed">
                    How reservations, pre-orders, payments and service appointment requests work at Edmar Motorcycle Parts and Accessories.
                    See also the <a href="{{ route('faq') }}" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">FAQ</a>.
                </p>
            </div>
        </div>

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
            <section class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 space-y-6" aria-labelledby="t-orders">
                <h2 id="t-orders" class="text-xl font-black text-gray-900 dark:text-white">Reservations, pre-orders and cancellation</h2>
                @include('shop.partials.terms-reservation')
            </section>

            <section class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 space-y-6" aria-labelledby="t-pay">
                <h2 id="t-pay" class="text-xl font-black text-gray-900 dark:text-white">Payment</h2>
                <div>
                    <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">Cash</h4>
                    <ul class="space-y-2 list-disc list-inside">
                        <li>Cash payment is made at the shop counter when you collect your reservation or pre-order. Nothing is charged when you reserve online.</li>
                        <li>Custom-parts requests carry no online payment of any kind. They are discussed and paid at the shop.</li>
                    </ul>
                </div>
                @if (config('payments.gcash_qr.enabled'))
                    @include('payments.partials.qr-terms')
                @else
                    <p>GCash QR payment is not available yet. When the shop turns it on, its terms will appear here.</p>
                @endif
                <div>
                    <h4 class="font-black text-gray-900 dark:text-white text-base mb-3">Prices and VAT</h4>
                    <ul class="space-y-2 list-disc list-inside">
                        <li>Listed prices already include 12% VAT. Receipts show the VAT-inclusive total together with the net amount and the VAT included in it. VAT is not added on top at checkout.</li>
                    </ul>
                </div>
            </section>

            <section class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 space-y-4" aria-labelledby="t-svc">
                <h2 id="t-svc" class="text-xl font-black text-gray-900 dark:text-white">Service appointment requests</h2>
                @include('repair_reservations.partials.terms', ['expanded' => true])
            </section>

            <section class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 space-y-3" aria-labelledby="t-you">
                <h2 id="t-you" class="text-xl font-black text-gray-900 dark:text-white">Your responsibilities</h2>
                <ul class="space-y-2 list-disc list-inside">
                    <li>Give accurate account, contact, motorcycle and payment details.</li>
                    <li>Check that a part fits your motorcycle before you reserve it. The compatibility checker is a guide based on the shop's records.</li>
                    <li>Collect reservations and pre-orders within their pickup window and arrive at the time the shop accepted for a service request.</li>
                    <li>Never share your password or one-time code. If you choose "Remember this device", only do so on a device you control; you can remove remembered devices from your profile at any time.</li>
                </ul>
            </section>
        </div>
    </div>
</x-app-layout>
