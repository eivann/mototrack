<x-app-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 font-sans flex justify-center transition-colors duration-300">
        
        <!-- Error Alert -->
        @if(session('error'))
            <x-alert type="error" :message="session('error')" class="w-full max-w-md" />
        @endif

        <!-- Receipt Container -->
        <div class="bg-white dark:bg-gray-800 w-full max-w-md shadow-2xl rounded-sm overflow-hidden border border-gray-100 dark:border-gray-700" id="printableArea">
            
            <!-- Receipt Header -->
            <div class="p-8 text-center border-b border-gray-200 dark:border-gray-700 border-dashed">
                <div class="flex justify-center items-center mb-3 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-wider">MOTOTRACK</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Edmar Motorcycle Parts</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Pandi, Bulacan, Philippines</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Receipt #: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $trx ?? 'TRX-' . rand(1000,9999) }}</span></p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Date: {{ now()->format('M d, Y h:i A') }}</p>
            </div>

            <!-- Receipt Items -->
            <div class="p-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                            <th class="py-2 text-left font-semibold">Qty</th>
                            <th class="py-2 text-left font-semibold">Item</th>
                            <th class="py-2 text-right font-semibold">Amount</th>
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
                                <td class="py-3 font-medium align-top">{{ $item->quantity }}x</td>
                                <td class="py-3 px-2 align-top">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $item->item->part_name ?? $item->part_name }}</div>
                                    @if(isset($item->item) && $item->item->model)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->item->model }}</div>
                                    @elseif($item->model)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->model }}</div>
                                    @endif
                                    <div class="text-xs text-gray-400 dark:text-gray-500">@ ₱{{ number_format($item->price, 2) }} each</div>
                                </td>
                                <td class="py-3 text-right font-bold align-top text-gray-900 dark:text-white">₱{{ number_format($total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Receipt Totals -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50">
                <div class="flex justify-between items-center mb-1 text-sm text-gray-600 dark:text-gray-400">
                    <span>Subtotal:</span>
                    <span class="text-gray-900 dark:text-white">₱{{ number_format($grandTotal, 2) }}</span>
                </div>
                
                <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-300 dark:border-gray-600 border-dashed">
                    <span class="text-lg font-bold text-gray-900 dark:text-white">GRAND TOTAL:</span>
                    <span class="text-xl font-extrabold text-gray-900 dark:text-white">₱{{ number_format($grandTotal, 2) }}</span>
                </div>

                @if($orders->first() && $orders->first()->amount_tendered > 0)
                <div class="flex justify-between items-center mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <span>Amount Paid:</span>
                    <span class="font-medium text-gray-900 dark:text-white">₱{{ number_format($orders->first()->amount_tendered, 2) }}</span>
                </div>
                @if($orders->first()->amount_tendered < $grandTotal)
                <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400">
                    <span>Remaining Balance:</span>
                    <span class="font-bold text-gray-900 dark:text-white">₱{{ number_format($grandTotal - $orders->first()->amount_tendered, 2) }}</span>
                </div>
                @endif
                @endif
            </div>

            <!-- Footer -->
            <div class="p-6 text-center text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700">
                <p class="font-medium text-gray-600 dark:text-gray-400 mb-1">Thank you for your purchase!</p>
                <p>Please keep this receipt for returns/exchange.</p>
                <p class="mt-4 opacity-50 text-[10px] uppercase tracking-widest">POWERED BY MOTOTRACK</p>
            </div>
        </div>

        <!-- Receipt Actions -->
        <div class="ml-8 hidden lg:flex flex-col gap-4">
            <a href="{{ route('checkout.receipt.download', ['transaction' => $trx]) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 active:scale-95 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download PDF
            </a>
            <a href="/customer/dashboard" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-bold py-3 px-6 rounded-xl shadow-sm text-center transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Back to Dashboard
            </a>
        </div>
    </div>
</x-app-layout>