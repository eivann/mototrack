<x-app-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-12 px-4 transition-colors duration-300">
        <div class="max-w-xl mx-auto p-8 bg-white dark:bg-gray-800 shadow-xl rounded-2xl border border-gray-100 dark:border-gray-700">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-900 dark:text-white">Repair Receipt</h2>

            <div class="space-y-4 text-gray-700 dark:text-gray-300">
                <div class="flex justify-between">
                    <span class="font-bold">Repair ID:</span>
                    <span>#{{ $repair->id }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
                    <span class="font-bold">Date:</span>
                    <span>{{ $repair->created_at->format('M d, Y h:i A') }}</span>
                </div>

                <div class="pt-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Customer Info</p>
                    <p class="font-bold text-lg text-gray-900 dark:text-white">{{ $repair->customer_name }}</p>
                    <p class="text-sm">{{ $repair->bike_model }}</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Reported Issue</p>
                    <p class="italic text-sm">"{{ $repair->issue }}"</p>
                </div>

                <div class="flex justify-between items-center pt-4">
                    <div class="flex flex-col gap-1">
                        <span class="font-bold">Status:</span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold text-white w-fit
                            {{ $repair->status == 'pending' ? 'bg-amber-500' : '' }}
                            {{ $repair->status == 'ongoing' ? 'bg-indigo-500' : '' }}
                            {{ $repair->status == 'done' ? 'bg-emerald-600' : '' }}
                        ">
                            {{ ucfirst($repair->status) }}
                        </span>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="font-bold">Payment:</span>
                        @if($repair->is_paid)
                            <span class="text-emerald-500 font-extrabold flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                PAID
                            </span>
                        @else
                            <span class="text-red-500 font-extrabold">UNPAID</span>
                        @endif
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-end">
                    <span class="text-xl font-bold text-gray-900 dark:text-white">Total Cost:</span>
                    <span class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">₱{{ number_format($repair->cost ?? 0, 2) }}</span>
                </div>

                <div class="text-center mt-12 pt-8 border-t border-gray-100 dark:border-gray-700">
                    <button onclick="window.print()"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg transition-all active:scale-[0.98]">
                        Print Receipt
                    </button>
                    <p class="mt-4 text-[10px] text-gray-400 uppercase tracking-widest">Powered by MotoTrack</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>