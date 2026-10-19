<x-app-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-6 px-4 sm:px-6 font-sans flex flex-col lg:flex-row items-center lg:items-start justify-center gap-6 transition-colors duration-300">
        
        <!-- Error Alert -->
        @if(session('error'))
            <x-alert type="error" :message="session('error')" class="w-full max-w-md" />
        @endif

        <!-- Receipt Container -->
        <div class="bg-white dark:bg-gray-800 w-full max-w-md shadow-xl rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700" id="printableArea">
            
            @php
                $firstOrder = $orders->first();
                $isPaid = ($firstOrder && ($firstOrder->payment_confirmed_at !== null || $firstOrder->status === 'completed'));
                $orderDate = $firstOrder && $firstOrder->created_at ? $firstOrder->created_at->format('M d, Y h:i A') : now()->format('M d, Y h:i A');
            @endphp
            <!-- Receipt Header -->
            <div class="p-5 text-center border-b border-gray-200 dark:border-gray-700 border-dashed">
                <div class="flex justify-center items-center mb-2 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h1 class="text-xl font-extrabold text-gray-900 dark:text-white tracking-wider">MOTOTRACK</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Edmar Motorcycle Parts</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Pandi, Bulacan, Philippines</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Ref #: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $trx ?? 'TRX-' . rand(1000,9999) }}</span></p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500">Date: {{ $orderDate }}</p>
                @if(!$isPaid)
                    <div class="mt-2.5 py-1 px-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700 rounded-lg text-[10px] font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wide">
                        Reservation Acknowledgement — No Payment Collected — Not an Official Receipt
                    </div>
                @else
                    <div class="mt-2.5 py-1 px-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-300 dark:border-emerald-700 rounded-lg text-[10px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wide">
                        Cash Sales Receipt — Paid in Cash
                    </div>
                @endif
            </div>

            <!-- Receipt Items -->
            <div class="p-4 sm:p-5">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                            <th class="py-1.5 text-left font-semibold">Qty</th>
                            <th class="py-1.5 text-left font-semibold">Item</th>
                            <th class="py-1.5 text-right font-semibold">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
                        @php $grandTotal = 0; @endphp
                        @foreach($orders as $item)
                            @php 
                                $total = $item->price * $item->quantity;
                                $grandTotal += $total; 
                            @endphp
                            <tr>
                                <td class="py-2 font-medium align-top">{{ $item->quantity }}x</td>
                                <td class="py-2 px-2 align-top">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $item->item->part_name ?? $item->part_name }}</div>
                                    @if(isset($item->item) && $item->item->model)
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $item->item->model }}</div>
                                    @elseif($item->model)
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $item->model }}</div>
                                    @endif
                                    <div class="text-[10px] text-gray-400 dark:text-gray-500">@ ₱{{ number_format($item->price, 2) }} each</div>
                                </td>
                                <td class="py-2 text-right font-bold align-top text-gray-900 dark:text-white">₱{{ number_format($total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Receipt Totals -->
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50">
                <div class="flex justify-between items-center mb-1 text-xs text-gray-600 dark:text-gray-400">
                    <span>Subtotal:</span>
                    <span class="text-gray-900 dark:text-white font-semibold">₱{{ number_format($grandTotal, 2) }}</span>
                </div>
                
                <div class="flex justify-between items-center mt-2 pt-2 border-t border-gray-300 dark:border-gray-600 border-dashed">
                    <span class="text-sm font-bold text-gray-900 dark:text-white">GRAND TOTAL:</span>
                    <span class="text-base font-extrabold text-gray-900 dark:text-white">₱{{ number_format($grandTotal, 2) }}</span>
                </div>

                @if($isPaid && $firstOrder && $firstOrder->amount_tendered > 0)
                <div class="flex justify-between items-center mt-1.5 text-xs text-gray-600 dark:text-gray-400">
                    <span>Amount Tendered:</span>
                    <span class="font-medium text-gray-900 dark:text-white">₱{{ number_format($firstOrder->amount_tendered, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-xs text-gray-600 dark:text-gray-400">
                    <span>Change:</span>
                    <span class="font-bold text-gray-900 dark:text-white">₱{{ number_format($firstOrder->change, 2) }}</span>
                </div>
                @elseif(!$isPaid)
                <div class="flex justify-between items-center mt-1.5 text-xs text-amber-700 dark:text-amber-400 font-semibold">
                    <span>Payment Status:</span>
                    <span>Pending Counter Payment</span>
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="p-4 sm:p-5 text-center text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700">
                @if($isPaid)
                    <p class="font-medium text-emerald-700 dark:text-emerald-400 mb-0.5 text-[11px]">Thank you for your purchase!</p>
                    <p class="text-[11px]">Please keep this sales receipt for returns/exchange within 7 days in accordance with shop policy and RA 7394.</p>
                @else
                    <p class="font-bold text-amber-700 dark:text-amber-400 mb-0.5 text-[11px]">Reservation Acknowledgement — No Payment Collected</p>
                    <p class="text-[11px]">Please present this document to the counter within 24 hours to pay and pick up your items.</p>
                @endif
                <p class="mt-2 opacity-50 text-[9px] uppercase tracking-widest">POWERED BY MOTOTRACK</p>
            </div>
        </div>

        <!-- Receipt Actions (Visible on both desktop & mobile) -->
        <div class="w-full max-w-md lg:w-48 flex flex-row lg:flex-col gap-3 shrink-0">
            <a href="{{ route('checkout.receipt.download', ['transaction' => $trx]) }}" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition-all flex items-center justify-center gap-2 active:scale-95 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download PDF
            </a>
            <a href="/customer/dashboard" class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-bold py-2.5 px-4 rounded-xl shadow-sm text-center transition-all active:scale-95 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-center">
                Back to Dashboard
            </a>
        </div>
    </div>
</x-app-layout>