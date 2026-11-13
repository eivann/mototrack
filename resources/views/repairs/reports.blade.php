<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header with Title & Export Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-gray-200 dark:border-gray-800">
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 mb-1 uppercase tracking-wider">
                        <a href="{{ route('repairs.index') }}" class="hover:underline flex items-center gap-1">
                            &larr; Repair Management
                        </a>
                        <span>/</span>
                        <span>Reports &amp; Analytics</span>
                    </div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Service &amp; Repair Reports
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Operational performance, service volume, turnaround time, invoiced charges, and authoritative sales revenue.
                    </p>
                </div>

                <!-- Export Action Buttons (F14) -->
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('repairs.reports.export.csv', request()->query()) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold shadow-sm transition-all active:scale-[0.98]">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </a>
                    <a href="{{ route('repairs.reports.export.pdf', request()->query()) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all active:scale-[0.98]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Export PDF
                    </a>
                </div>
            </div>

            <!-- F01: FILTERS BAR -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <form method="GET" action="{{ route('repairs.reports') }}" class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 flex-1">
                        <!-- Date Type -->
                        <div>
                            <label for="filter-date-type" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Date Boundary</label>
                            <select id="filter-date-type" name="date_type" class="w-full text-xs py-2 pl-3 pr-8 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl font-medium focus:ring-2 focus:ring-indigo-500">
                                <option value="created" {{ request('date_type', 'created') === 'created' ? 'selected' : '' }}>Intake Date</option>
                                <option value="completed" {{ request('date_type') === 'completed' ? 'selected' : '' }}>Completed Date</option>
                                <option value="started" {{ request('date_type') === 'started' ? 'selected' : '' }}>Started Date</option>
                                <option value="paid" {{ request('date_type') === 'paid' ? 'selected' : '' }}>Payment Date</option>
                            </select>
                        </div>

                        <!-- Date Range From -->
                        <div>
                            <label for="filter-date-from" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">From (Manila)</label>
                            <input id="filter-date-from" type="date" name="date_from" value="{{ request('date_from', request('from')) }}" class="w-full text-xs py-1.5 px-3 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Date Range To -->
                        <div>
                            <label for="filter-date-to" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">To (Manila)</label>
                            <input id="filter-date-to" type="date" name="date_to" value="{{ request('date_to', request('to')) }}" class="w-full text-xs py-1.5 px-3 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="filter-status" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Service Status</label>
                            <select id="filter-status" name="status" class="w-full text-xs py-2 pl-3 pr-8 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl font-medium focus:ring-2 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>In Progress</option>
                                <option value="done" {{ in_array(request('status'), ['done', 'completed']) ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>

                        <!-- Customer Filter -->
                        <div>
                            <label for="filter-customer" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Customer Account</label>
                            <select id="filter-customer" name="customer_id" class="w-full text-xs py-2 pl-3 pr-8 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl font-medium focus:ring-2 focus:ring-indigo-500">
                                <option value="">All Customers</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Payment Filter -->
                        <div>
                            <label for="filter-paid" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Payment</label>
                            <select id="filter-paid" name="paid" class="w-full text-xs py-2 pl-3 pr-8 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl font-medium focus:ring-2 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="1" {{ request('paid') === '1' ? 'selected' : '' }}>Paid</option>
                                <option value="0" {{ request('paid') === '0' ? 'selected' : '' }}>Unpaid</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                            Apply Filters
                        </button>
                        @if(request()->hasAny(['date_from', 'date_to', 'from', 'to', 'date_type', 'status', 'customer_id', 'paid', 'search']))
                            <a href="{{ route('repairs.reports') }}" class="px-3.5 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold transition-all">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- F01: EXECUTIVE KPI SUMMARY CARDS -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Total Services -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-gray-400">Total Services</div>
                        <div class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ $reportData['total_jobs'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Filtered service jobs</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                </div>

                <!-- Completed Services -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-gray-400">Completed Jobs</div>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $reportData['status_counts']['completed'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $reportData['status_counts']['ongoing'] }} in bay &middot; {{ $reportData['status_counts']['pending'] }} pending
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <!-- Average Turnaround Time -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-gray-400">Avg Turnaround Time</div>
                        <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $reportData['avg_turnaround_formatted'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">From bay start to completion</div>
                        @if(($reportData['invalid_turnaround_count'] ?? 0) > 0)
                            <div class="text-[10px] text-amber-500 font-bold mt-1">
                                ⚠ {{ $reportData['invalid_turnaround_count'] }} job(s) with invalid timestamps excluded
                            </div>
                        @endif
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <!-- Total Invoiced Service Charges -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-gray-400">Invoiced Repair Charges</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">₱{{ number_format($reportData['total_invoiced_charges'], 2) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ₱{{ number_format($reportData['paid_invoiced_charges'], 2) }} paid &middot; ₱{{ number_format($reportData['unpaid_invoiced_charges'], 2) }} unpaid
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                </div>
            </div>

            <!-- F01: FINANCIAL REVENUE RECONCILIATION & VAT PANEL (Requirement 5 & 6) -->
            <div class="bg-gradient-to-br from-indigo-900 to-gray-900 text-white rounded-3xl p-6 shadow-xl relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 mb-5 border-b border-white/10 gap-2">
                    <div>
                        <h2 class="text-lg font-black tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Authoritative Sales Revenue &amp; VAT Breakdown
                        </h2>
                        <p class="text-xs text-gray-300 mt-0.5">Authoritative revenue derived from completed sales receipts (SaleHeader type: repair) reconciled against invoiced repair charges.</p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 bg-white/10 rounded-xl">Philippine Standard 12% VAT Inclusive</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="bg-white/5 backdrop-blur-sm p-4 rounded-2xl border border-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Recorded Cash Sales</div>
                        <div class="text-2xl font-black text-emerald-400 mt-1">₱{{ number_format($reportData['recorded_gross_revenue'], 2) }}</div>
                        <div class="text-xs text-gray-300 mt-1">{{ $reportData['recorded_payment_transactions'] }} confirmed payment transactions</div>
                    </div>

                    <div class="bg-white/5 backdrop-blur-sm p-4 rounded-2xl border border-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">VATable Net Revenue</div>
                        <div class="text-2xl font-black text-white mt-1">₱{{ number_format($reportData['vat_net'], 2) }}</div>
                        <div class="text-xs text-gray-300 mt-1">Net of 12% value-added tax</div>
                    </div>

                    <div class="bg-white/5 backdrop-blur-sm p-4 rounded-2xl border border-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">VAT Amount (12%)</div>
                        <div class="text-2xl font-black text-indigo-300 mt-1">₱{{ number_format($reportData['vat_amount'], 2) }}</div>
                        <div class="text-xs text-gray-300 mt-1">Tax collected for remittance</div>
                    </div>
                </div>

                @if($reportData['historical_unstamped_count'] > 0)
                    <div class="mt-4 pt-3 border-t border-white/10 text-[11px] text-gray-400 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Historical Notice: {{ $reportData['historical_unstamped_count'] }} older paid repair(s) precede the paid_at timestamp column. Their costs and sales receipts are fully preserved without fabricating artificial payment timestamps.</span>
                    </div>
                @endif
            </div>

            <!-- F01: SERVICE RECORDS DETAIL TABLE -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Service Breakdown &amp; Detail Logs</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Showing filtered repair orders with Manila timestamps and payment reconciliation.</p>
                    </div>
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Total: {{ $repairs->total() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400 font-semibold">
                                <th class="p-4">Ticket &amp; Vehicle</th>
                                <th class="p-4">Customer</th>
                                <th class="p-4">Service Description</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4">Timestamps (Asia/Manila)</th>
                                <th class="p-4 text-right">Invoiced Cost</th>
                                <th class="p-4 text-center">Payment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @forelse($repairs as $repair)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="p-4 align-top">
                                    <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">#{{ $repair->id }}</span>
                                    <div class="font-bold text-gray-900 dark:text-white mt-0.5">{{ $repair->bike_model }}</div>
                                    @if($repair->reservation_id)
                                        <span class="px-1.5 py-0.5 text-[10px] bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded font-semibold mt-1 inline-block">Online Booking</span>
                                    @endif
                                </td>
                                <td class="p-4 align-top text-gray-700 dark:text-gray-300">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $repair->customer_name }}</div>
                                    @if($repair->customer_id)
                                        <span class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold">Registered Account</span>
                                    @else
                                        <span class="text-[10px] text-gray-400">Walk-in</span>
                                    @endif
                                </td>
                                <td class="p-4 align-top text-gray-600 dark:text-gray-400 max-w-xs">
                                    <p class="line-clamp-2">{{ $repair->issue }}</p>
                                </td>
                                <td class="p-4 align-top text-center">
                                    @if($repair->status === 'pending')
                                        <span class="px-2.5 py-1 text-xs font-black rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Pending</span>
                                    @elseif($repair->status === 'ongoing')
                                        <span class="px-2.5 py-1 text-xs font-black rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">In Progress</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-black rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Completed</span>
                                    @endif
                                </td>
                                <td class="p-4 align-top text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                    <div>Intake: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $repair->createdAtManila('M d, Y g:i A') }}</span></div>
                                    @if($repair->completed_at)
                                        <div>Completed: <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $repair->completedAtManila('M d, Y g:i A') }}</span></div>
                                    @endif
                                    @if($repair->paid_at)
                                        <div>Paid: <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ $repair->paidAtManila('M d, Y g:i A') }}</span></div>
                                    @endif
                                </td>
                                <td class="p-4 align-top text-right font-black text-gray-900 dark:text-white">
                                    {{ $repair->cost !== null ? '₱' . number_format($repair->cost, 2) : 'TBD' }}
                                </td>
                                <td class="p-4 align-top text-center">
                                    @if($repair->is_paid)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Paid</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Unpaid</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="font-bold text-gray-700 dark:text-gray-300">No service records match the selected criteria.</p>
                                        <p class="text-xs text-gray-500 mt-1">Adjust your date range or filter criteria above.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $repairs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

