<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Track Repairs
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Manage service jobs, update repair statuses, and collect payments.</p>
                </div>
                <form method="GET" class="flex flex-wrap gap-3">
                    <select name="status" onchange="this.form.submit()" class="appearance-none pl-4 pr-10 py-2.5 border-gray-200 dark:border-gray-600 rounded-xl focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>In Progress</option>
                        <option value="done" {{ request('status') == 'done' ? 'selected' : '' }}>Completed</option>
                    </select>
                </form>
            </div>

            <!-- ADD REPAIR FORM -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    New Repair Job
                </h2>
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 text-sm rounded-r-xl">
                        <p class="font-bold mb-1">Please fix the following errors:</p>
                        <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <form method="POST" action="/repairs">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div class="lg:col-span-1">
                            <select name="customer_id" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Walk-in / Other</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input name="customer_name" placeholder="Manual Name (if walk-in)" class="border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                        <input name="bike_model" placeholder="Bike Model" class="border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500" required>
                        <input name="issue" placeholder="Reported Issue" class="border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500 lg:col-span-2" required>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 dark:text-gray-400 font-medium">₱</span></div>
                            <input name="cost" type="number" step="0.01" placeholder="Est. Cost" class="pl-8 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500 w-full" required>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="bg-gray-900 dark:bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-sm hover:bg-gray-800 dark:hover:bg-indigo-700 transition-colors active:scale-[0.98]">Add Repair Job</button>
                    </div>
                </form>
            </div>

            <!-- TABLE -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400">
                                <th class="p-4 font-semibold">Job Details</th>
                                <th class="p-4 font-semibold">Issue</th>
                                <th class="p-4 font-semibold text-center">Status</th>
                                <th class="p-4 font-semibold">Cost</th>
                                <th class="p-4 font-semibold text-center">Payment</th>
                                <th class="p-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($repairs as $repair)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="p-4 align-top">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $repair->customer_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        {{ $repair->bike_model }}
                                    </div>
                                </td>
                                <td class="p-4 align-top text-gray-600 dark:text-gray-400">{{ $repair->issue }}</td>
                                <td class="p-4 align-top text-center">
                                    <form method="POST" action="/repairs/{{ $repair->id }}">
                                        @csrf @method('PUT')
                                        <select name="status" onchange="this.form.submit()"
                                                class="text-xs font-bold pl-3 pr-8 py-1 rounded-full border-0 focus:ring-0 cursor-pointer text-center appearance-none
                                                {{ $repair->status == 'pending' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300' : '' }}
                                                {{ $repair->status == 'ongoing' ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300' : '' }}
                                                {{ $repair->status == 'done' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300' : '' }}">
                                            <option value="pending" {{ $repair->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="ongoing" {{ $repair->status == 'ongoing' ? 'selected' : '' }}>In Progress</option>
                                            <option value="done" {{ $repair->status == 'done' ? 'selected' : '' }}>Completed</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="p-4 align-top font-bold text-gray-900 dark:text-white">₱{{ number_format($repair->cost ?? 0, 2) }}</td>
                                <td class="p-4 align-top text-center">
                                    @if(!$repair->is_paid)
                                        <form method="POST" action="/repairs/{{ $repair->id }}/pay" onsubmit="return confirm('Confirm payment received?');">
                                            @csrf @method('PUT')
                                            <button class="inline-flex items-center gap-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-emerald-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 px-3 py-1 rounded-full text-xs font-bold transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Collect
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-xs font-extrabold bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            PAID
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 align-top text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="/repairs/{{ $repair->id }}/receipt" title="Print Receipt" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        </a>
                                        <form method="POST" action="/repairs/{{ $repair->id }}" class="inline" onsubmit="return confirm('Delete this repair record?');">
                                            @csrf @method('DELETE')
                                            <button title="Delete Record" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                                        <p class="font-medium text-gray-600 dark:text-gray-400">No repairs found.</p>
                                        <p class="text-sm mt-1 text-gray-500 dark:text-gray-500">Add a new repair job or adjust your filters.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8">
                {{ $repairs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>