<x-app-layout>
    <div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8 font-sans flex justify-center">
        
        <!-- Receipt Container -->
        <div class="bg-white w-full max-w-md shadow-2xl rounded-sm overflow-hidden" id="printableArea">
            
            <!-- Receipt Header -->
            <div class="p-8 text-center border-b border-gray-200 border-dashed">
                <div class="flex justify-center items-center mb-3 text-indigo-600">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-wider">MOTOTRACK</h1>
                <p class="text-sm text-gray-500 font-medium">Edmar Motorcycle Parts</p>
                <p class="text-xs text-gray-400 mt-1">Pandi, Bulacan, Philippines</p>
                <p class="text-xs text-gray-400 mt-1">Receipt #: <span class="font-bold text-gray-800">{{ $sales->first()->transaction_reference ?? 'TRX-OLD-' . $sales->first()->id }}</span></p>
                <p class="text-xs text-gray-400">Date: {{ $sales->first()->created_at->format('M d, Y h:i A') }}</p>
            </div>

            <!-- Receipt Items -->
            <div class="p-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2 text-left font-semibold">Qty</th>
                            <th class="py-2 text-left font-semibold">Item</th>
                            <th class="py-2 text-right font-semibold">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-800">
                        @php $grandTotal = 0; @endphp
                        @foreach($sales as $sale)
                            @php $grandTotal += $sale->total_price; @endphp
                            <tr>
                                <td class="py-3 font-medium align-top">{{ $sale->quantity }}x</td>
                                <td class="py-3 px-2 align-top">
                                    <div class="font-bold">{{ $sale->item->part_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $sale->item->brand }} - {{ $sale->item->model }}</div>
                                    <div class="text-xs text-gray-400">@ ₱{{ number_format($sale->price, 2) }} each</div>
                                </td>
                                <td class="py-3 text-right font-bold align-top">₱{{ number_format($sale->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Receipt Totals -->
            <div class="px-6 py-4 bg-gray-50">
                <div class="flex justify-between items-center mb-1 text-sm text-gray-600">
                    <span>Subtotal:</span>
                    <span>₱{{ number_format($grandTotal, 2) }}</span>
                </div>
                
                <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-300 border-dashed">
                    <span class="text-lg font-bold text-gray-900">GRAND TOTAL:</span>
                    <span class="text-xl font-extrabold text-gray-900">₱{{ number_format($grandTotal, 2) }}</span>
                </div>

                @if($sales->first()->amount_tendered)
                <div class="flex justify-between items-center mt-2 text-sm text-gray-600">
                    <span>Amount Tendered:</span>
                    <span class="font-medium">₱{{ number_format($sales->first()->amount_tendered, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm text-gray-600">
                    <span>Change:</span>
                    <span class="font-bold text-gray-900">₱{{ number_format($sales->first()->change, 2) }}</span>
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="p-6 text-center text-xs text-gray-400 border-t border-gray-200">
                <p class="font-medium text-gray-600 mb-1">Thank you for your purchase!</p>
                <p>Please keep this receipt for returns/exchange.</p>
                <p class="mt-4 opacity-50 text-[10px] uppercase tracking-widest">Powered by MotoTrack</p>
            </div>
        </div>

        <!-- Print Action -->
        <div class="ml-8 hidden lg:flex flex-col gap-4">
            <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Receipt
            </button>
            <a href="/sales" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-bold py-3 px-6 rounded-xl shadow-sm text-center transition-all">
                New Sale
            </a>
            <a href="/sales/history" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-bold py-3 px-6 rounded-xl shadow-sm text-center transition-all">
                View Reports
            </a>
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printableArea, #printableArea * {
                visibility: visible;
            }
            #printableArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 100%;
                box-shadow: none;
            }
            .min-h-screen {
                background: white !important;
            }
        }
    </style>
</x-app-layout>