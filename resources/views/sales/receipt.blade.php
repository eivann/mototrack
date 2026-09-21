<x-app-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 font-sans flex justify-center transition-colors duration-300">
        
        <!-- Receipt Container -->
        <x-receipt-card 
            :reference="$sales->first()->transaction_reference ?? 'TRX-OLD-' . $sales->first()->id" 
            :date="$sales->first()->created_at->format('M d, Y h:i A')"
        >
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
                    @foreach($sales as $sale)
                        @php $grandTotal += $sale->total_price; @endphp
                        <tr>
                            <td class="py-3 font-medium align-top">{{ $sale->quantity }}x</td>
                            <td class="py-3 px-2 align-top">
                                <div class="font-bold text-gray-900 dark:text-white">{{ $sale->item->part_name ?? 'Repair Service' }}</div>
                                @if($sale->item)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $sale->item->brand }} - {{ $sale->item->model }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">@ ₱{{ number_format($sale->price, 2) }} each</div>
                                @else
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Service Fee / Labor</div>
                                @endif
                            </td>
                            <td class="py-3 text-right font-bold align-top">₱{{ number_format($sale->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Receipt Totals -->
            <div class="py-8 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 mt-4">
                <div class="flex justify-between items-center mb-1 text-sm text-gray-600 dark:text-gray-400">
                    <span>Subtotal:</span>
                    <span>₱{{ number_format($grandTotal, 2) }}</span>
                </div>
                
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-300 dark:border-gray-700 border-dashed">
                    <span class="text-lg font-bold text-gray-900 dark:text-white uppercase tracking-tight">Grand Total:</span>
                    <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">₱{{ number_format($grandTotal, 2) }}</span>
                </div>

                @if($sales->first()->amount_tendered)
                <div class="flex justify-between items-center mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <span>Amount Tendered:</span>
                    <span class="font-medium">₱{{ number_format($sales->first()->amount_tendered, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400">
                    <span>Change:</span>
                    <span class="font-bold text-gray-900 dark:text-white">₱{{ number_format($sales->first()->change, 2) }}</span>
                </div>
                @endif
            </div>
        </x-receipt-card>

        <!-- Receipt Actions -->
        <div class="ml-8 hidden lg:flex flex-col gap-4">
            <a href="{{ route('sales.download', ['transaction' => $sales->first()->transaction_reference ?? $sales->first()->id]) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 px-8 rounded-xl shadow-lg shadow-emerald-200 dark:shadow-none transition-all flex items-center justify-center gap-3 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download PDF
            </a>
            <a href="/sales" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-bold py-4 px-8 rounded-xl shadow-sm text-center transition-all active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                New Sale
            </a>
            <a href="/sales/history" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-bold py-4 px-8 rounded-xl shadow-sm text-center transition-all active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                View Reports
            </a>
        </div>
    </div>
</x-app-layout>