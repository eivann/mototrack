@props(['reference', 'date', 'title' => 'MOTOTRACK', 'isPaid' => true])

<div class="bg-white dark:bg-gray-800 w-full max-w-md shadow-xl rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700 transition-colors duration-300" id="printableArea">
    <!-- Receipt Header -->
    <div class="p-5 text-center border-b border-gray-200 dark:border-gray-700 border-dashed">
        <div class="flex justify-center items-center mb-2 text-indigo-600 dark:text-indigo-400">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white tracking-wider">{{ $title }}</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Edmar Motorcycle Parts</p>
        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Pandi, Bulacan, Philippines</p>
        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Ref #: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $reference }}</span></p>
        <p class="text-[11px] text-gray-400 dark:text-gray-500">Date: {{ $date }}</p>
        @if(!$isPaid)
            <div class="mt-2.5 py-1 px-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700 rounded-lg text-[10px] font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wide">
                Reservation Acknowledgement — No Payment Collected — Not an Official Receipt
            </div>
        @else
            <div class="mt-2.5 py-1 px-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-300 dark:border-emerald-700 rounded-lg text-[10px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wide">
                <span class="sr-only">POS Sales Receipt — Paid in Cash</span>
                Cash Sales Receipt — Paid in Cash
            </div>
        @endif
    </div>

    <!-- Receipt Body -->
    <div {{ $attributes->merge(['class' => 'p-4 sm:p-5']) }}>
        {{ $slot }}
    </div>

    <!-- Footer -->
    <div class="p-4 sm:p-5 text-center text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        @if($isPaid)
            <p class="font-medium text-gray-600 dark:text-gray-400 mb-0.5">Thank you for your purchase!</p>
            <p class="text-[11px]">Please keep this sales receipt for returns/exchange within 7 days.</p>
        @else
            <p class="font-semibold text-amber-600 dark:text-amber-400 mb-0.5">Reservation Acknowledgement — No Payment Collected</p>
            <p class="text-[11px]">Please present this acknowledgement at the counter within 24 hours to pay and claim your reserved items.</p>
        @endif
        <p class="mt-3 opacity-50 text-[10px] uppercase tracking-widest">Powered by MotoTrack</p>
    </div>
</div>
