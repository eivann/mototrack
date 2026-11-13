<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8 font-sans"
         x-data="{
             editModalOpen: false,
             savingModal: false,
             editingRepair: {
                 id: null,
                 customer_id: '',
                 customer_name: '',
                 bike_model: '',
                 issue: '',
                 status: 'pending',
                 estimated_minutes: {{ $walkInMinutes ?? 60 }},
                 cost: '',
                 is_paid: false,
                 reservation_id: null,
                 created_at_fmt: '',
                 started_at_fmt: '',
                 completed_at_fmt: '',
                 paid_at_fmt: ''
             },
             openEditModal(repair) {
                 this.editingRepair = Object.assign({}, repair);
                 this.editModalOpen = true;
             },
             closeEditModal() {
                 this.editModalOpen = false;
                 this.savingModal = false;
             }
         }"
         @keydown.escape.window="closeEditModal()">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Track Repairs & Service
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Live bay operations, dedicated service tracking, historical records, and payment management.</p>
                </div>

                <!-- Navigation Tabs (F06 Dedicated Service Tracking Tab & Records) -->
                <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <a href="{{ route('repairs.index', ['tab' => 'tracking']) }}"
                       class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2 {{ $activeTab === 'tracking' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        Service Tracking
                        @if(($trackingMetrics['ongoing'] ?? 0) > 0)
                            <span class="px-1.5 py-0.5 text-[10px] bg-indigo-500 text-white rounded-full font-black">{{ $trackingMetrics['ongoing'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('repairs.index', ['tab' => 'records']) }}"
                       class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 {{ $activeTab === 'records' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        All Service Records
                        <span class="px-1.5 py-0.5 text-[10px] {{ $activeTab === 'records' ? 'bg-indigo-700 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }} rounded-full font-black">{{ $trackingMetrics['total'] ?? 0 }}</span>
                    </a>
                    <a href="{{ route('repairs.reports') }}"
                       class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Service Reports
                    </a>
                    <a href="{{ route('repairs.index', ['tab' => 'new']) }}"
                       class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 {{ $activeTab === 'new' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Repair Job
                    </a>
                </div>
            </div>

            <!-- F06 Service Tracking Metrics Overview -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-gray-400">In Progress</div>
                        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $trackingMetrics['ongoing'] ?? 0 }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $trackingMetrics['pending'] ?? 0 }} pending start</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-gray-400">Overdue Bay Jobs</div>
                        <div class="text-2xl font-black {{ ($trackingMetrics['overdue'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} mt-0.5">{{ $trackingMetrics['overdue'] ?? 0 }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ ($trackingMetrics['overdue'] ?? 0) > 0 ? 'Needs attention' : 'On schedule' }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl {{ ($trackingMetrics['overdue'] ?? 0) > 0 ? 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }} flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-gray-400">Completed Jobs</div>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $trackingMetrics['completed'] ?? 0 }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $trackingMetrics['paid'] ?? 0 }} paid</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-gray-400">Total Recorded</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $trackingMetrics['total'] ?? 0 }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">All service history</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                </div>
            </div>

            <!-- Feedback Notifications -->
            @if (session('success'))
                <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-200 text-sm rounded-r-xl flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span class="font-semibold">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-800 dark:text-red-200 text-sm rounded-r-xl flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span class="font-semibold">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if ($overCapacity)
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4 text-sm text-red-800 dark:text-red-200">
                    <strong>Over capacity.</strong> More repairs and bookings overlap right now than the shop limit allows (usually an overdue job). Finish or extend the overdue job, and check upcoming bookings.
                </div>
            @endif

            <!-- ========================================== -->
            <!-- TAB 1: DEDICATED SERVICE TRACKING / EDIT   -->
            <!-- ========================================== -->
            @if ($activeTab === 'tracking')
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-5 border-b border-gray-100 dark:border-gray-700 gap-3">
                        <div>
                            <h2 class="text-xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                                Service Tracking &amp; Bay Management
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Live tracking for active and queued service jobs. Edit progress, extend bay time, or transition statuses in real-time.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-xl">
                                {{ $activeRepairs->where('status', 'ongoing')->count() }} Bays Occupied
                            </span>
                            <span class="text-xs font-bold px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-xl">
                                {{ $activeRepairs->where('status', 'pending')->count() }} In Queue
                            </span>
                        </div>
                    </div>

                    @if($activeRepairs->isEmpty())
                        <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-14 h-14 mx-auto text-emerald-400 dark:text-emerald-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">All Repair Bays Currently Available</h3>
                            <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">There are no active ongoing repair jobs occupying bays or waiting in the pending queue right now.</p>
                            <div class="mt-5 flex items-center justify-center gap-3">
                                <a href="{{ route('repairs.reports') }}"
                       class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Service Reports
                    </a>
                    <a href="{{ route('repairs.index', ['tab' => 'new']) }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                                    + Start New Repair Job
                                </a>
                                <a href="{{ route('repairs.index', ['tab' => 'records']) }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold transition-all">
                                    View Past Service Records
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Active Jobs Live Tracking Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach($activeRepairs as $job)
                                @php
                                    $isOngoing = $job->isOngoing();
                                    $isOverdue = $job->isOverdue();
                                @endphp
                                <div class="bg-gray-50 dark:bg-gray-750/60 rounded-2xl p-5 border {{ $isOverdue ? 'border-red-300 dark:border-red-800 bg-red-50/20' : ($isOngoing ? 'border-indigo-200 dark:border-indigo-800/80 shadow-sm' : 'border-amber-200 dark:border-amber-800/60') }} relative transition-shadow hover:shadow-md">
                                    <div class="flex items-start justify-between gap-3 mb-3">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">#{{ $job->id }}</span>
                                                <h4 class="text-sm font-extrabold text-gray-900 dark:text-white">{{ $job->bike_model }}</h4>
                                                @if($job->reservation_id)
                                                    <span class="px-2 py-0.5 text-[10px] bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded font-bold">Online Booking</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                Customer: <strong class="text-gray-900 dark:text-white">{{ $job->customer_name }}</strong>
                                                @if($job->customer_id)
                                                    <span class="text-[10px] bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-1.5 py-0.2 rounded font-medium ml-1">Registered</span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Live Status Badge -->
                                        <div>
                                            @if($isOngoing)
                                                <span class="px-3 py-1 text-xs font-black rounded-full {{ $isOverdue ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 animate-pulse' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' }}">
                                                    {{ $isOverdue ? '⚠️ Overdue' : '⚡ Bay Active' }}
                                                </span>
                                            @else
                                                <span class="px-3 py-1 text-xs font-black rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                    ⏳ Queued
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Issue Description -->
                                    <div class="text-xs text-gray-700 dark:text-gray-300 mb-3 bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-100 dark:border-gray-700">
                                        <span class="font-bold text-gray-400 uppercase text-[10px] block mb-0.5">Reported Issue / Work:</span>
                                        {{ $job->issue }}
                                    </div>

                                    <!-- Tracking Details & Timestamps (Asia/Manila) -->
                                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1.5 mb-4">
                                        @if($isOngoing)
                                            <div class="flex items-center justify-between text-[11px]">
                                                <span>Started: <strong class="text-gray-800 dark:text-gray-200">{{ $job->startedAtManila('g:i A') }}</strong></span>
                                                @if(! $job->isLegacyOngoing())
                                                    <span>Expected Finish: <strong class="{{ $isOverdue ? 'text-red-600 dark:text-red-400 font-black' : 'text-gray-800 dark:text-gray-200' }}">{{ $job->expectedEndAt()->timezone(config('repair_reservations.timezone'))->format('g:i A') }}</strong></span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-[11px]">
                                                Intake Date: <strong class="text-gray-800 dark:text-gray-200">{{ $job->createdAtManila('M d, Y g:i A') }}</strong>
                                            </div>
                                        @endif

                                        <div class="flex items-center justify-between text-[11px]">
                                            <span>Est. Cost: <strong class="text-gray-800 dark:text-gray-200">{{ $job->cost !== null ? '₱' . number_format($job->cost, 2) : 'Not set yet' }}</strong></span>
                                            <span>Payment: <strong class="{{ $job->is_paid ? 'text-emerald-600' : 'text-amber-600' }}">{{ $job->is_paid ? 'Paid' : 'Unpaid' }}</strong></span>
                                        </div>
                                    </div>

                                    <!-- Live Floor Controls -->
                                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
                                        <!-- Bay Duration & Status Transitions -->
                                        <div class="flex items-center gap-2">
                                            @if($isOngoing)
                                                <form method="POST" action="{{ route('repairs.extend', $job) }}" class="flex items-center gap-1">
                                                    @csrf
                                                    <select name="extra_minutes" class="text-[11px] rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-1 px-1.5 font-medium">
                                                        @foreach ([15 => '+15m', 30 => '+30m', 60 => '+1h', 120 => '+2h'] as $m => $l)
                                                            <option value="{{ $m }}">{{ $l }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-300 dark:border-indigo-600 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100">
                                                        Extend
                                                    </button>
                                                </form>
                                            @endif

                                            <!-- Status Switcher -->
                                            <form method="POST" action="/repairs/{{ $job->id }}">
                                                @csrf @method('PUT')
                                                @if($isOngoing)
                                                    <input type="hidden" name="status" value="done">
                                                    <button type="submit" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-colors">
                                                        Mark Completed
                                                    </button>
                                                @else
                                                    <input type="hidden" name="status" value="ongoing">
                                                    <button type="submit" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-colors">
                                                        Start Repair
                                                    </button>
                                                @endif
                                            </form>
                                        </div>

                                        <!-- Dedicated Edit Tracking Details Button (Opens Modal) -->
                                        <div>
                                            <button type="button"
                                                    @click="openEditModal({
                                                        id: {{ $job->id }},
                                                        customer_id: '{{ $job->customer_id ?? '' }}',
                                                        customer_name: '{{ addslashes($job->customer_name) }}',
                                                        bike_model: '{{ addslashes($job->bike_model) }}',
                                                        issue: '{{ addslashes($job->issue) }}',
                                                        status: '{{ $job->status }}',
                                                        estimated_minutes: {{ $job->estimated_minutes ?: ($walkInMinutes ?? 60) }},
                                                        cost: '{{ $job->cost !== null ? $job->cost : '' }}',
                                                        is_paid: {{ $job->is_paid ? 'true' : 'false' }},
                                                        reservation_id: {{ $job->reservation_id ? $job->reservation_id : 'null' }},
                                                        created_at_fmt: '{{ $job->createdAtManila('M d, Y g:i A') }}',
                                                        started_at_fmt: '{{ $job->startedAtManila('M d, Y g:i A') ?? '' }}',
                                                        completed_at_fmt: '{{ $job->completedAtManila('M d, Y g:i A') ?? '' }}',
                                                        paid_at_fmt: '{{ $job->paidAtManila('M d, Y g:i A') ?? ($job->is_paid ? 'Paid' : '') }}'
                                                    })"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-indigo-600 dark:text-indigo-400 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-bold transition-colors shadow-sm">
                                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Edit Tracking Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: ALL SERVICE RECORDS & AUDIT HISTORY -->
            <!-- ========================================== -->
            @elseif ($activeTab === 'records')
            <div class="space-y-6">
                <!-- F08 & F06: FILTERS & SEARCH BAR -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                    <form method="GET" action="/repairs" class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                        <input type="hidden" name="tab" value="records">
                        <div class="flex flex-wrap items-center gap-2 flex-1">
                            <!-- Search Box -->
                            <div class="relative min-w-[200px] flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer, bike, issue, #ID..." class="w-full pl-9 pr-3 py-2 text-xs border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label for="repair-status-filter" class="sr-only">Filter by status</label>
                                <select id="repair-status-filter" name="status" class="py-2 pl-3 pr-8 text-xs border-gray-200 dark:border-gray-600 rounded-xl font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>In Progress</option>
                                    <option value="done" {{ request('status') == 'done' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>

                            <!-- Paid Filter -->
                            <div>
                                <label for="repair-paid-filter" class="sr-only">Filter by payment</label>
                                <select id="repair-paid-filter" name="paid" class="py-2 pl-3 pr-8 text-xs border-gray-200 dark:border-gray-600 rounded-xl font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500">
                                    <option value="">All Payments</option>
                                    <option value="0" {{ request('paid') === '0' ? 'selected' : '' }}>Unpaid</option>
                                    <option value="1" {{ request('paid') === '1' ? 'selected' : '' }}>Paid</option>
                                </select>
                            </div>

                            <!-- Date Type -->
                            <div>
                                <label for="repair-date-type" class="sr-only">Date Type</label>
                                <select id="repair-date-type" name="date_type" class="py-2 pl-3 pr-8 text-xs border-gray-200 dark:border-gray-600 rounded-xl font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500">
                                    <option value="created" {{ request('date_type') == 'created' ? 'selected' : '' }}>Intake Date</option>
                                    <option value="started" {{ request('date_type') == 'started' ? 'selected' : '' }}>Started Date</option>
                                    <option value="completed" {{ request('date_type') == 'completed' ? 'selected' : '' }}>Completed Date</option>
                                    <option value="paid" {{ request('date_type') == 'paid' ? 'selected' : '' }}>Payment Date</option>
                                </select>
                            </div>

                            <!-- Date Range From -->
                            <div class="flex items-center gap-1">
                                <label for="date-from" class="text-xs text-gray-500 dark:text-gray-400 font-medium">From:</label>
                                <input id="date-from" type="date" name="date_from" value="{{ request('date_from', request('from')) }}" class="py-1.5 px-2 text-xs border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- Date Range To (Canonical half-open boundary) -->
                            <div class="flex items-center gap-1">
                                <label for="date-to" class="text-xs text-gray-500 dark:text-gray-400 font-medium">To:</label>
                                <input id="date-to" type="date" name="date_to" value="{{ request('date_to', request('to')) }}" class="py-1.5 px-2 text-xs border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm">
                                Filter
                            </button>
                            @if(request()->hasAny(['search', 'status', 'paid', 'date_from', 'date_to', 'from', 'to', 'date_type']))
                                <a href="/repairs?tab=records" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold transition-colors">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- TABLE (F06 & F08 Complete Service Records Table) -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-xs tracking-wider uppercase text-gray-500 dark:text-gray-400">
                                    <th class="p-4 font-semibold">Job & Vehicle Details</th>
                                    <th class="p-4 font-semibold">Issue & Schedule</th>
                                    <th class="p-4 font-semibold text-center">Tracking & Status</th>
                                    <th class="p-4 font-semibold">Cost</th>
                                    <th class="p-4 font-semibold text-center">Payment</th>
                                    <th class="p-4 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @forelse($repairs as $repair)
                                <tr id="repair-{{ $repair->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="p-4 align-top">
                                        <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ $repair->customer_name }}</span>
                                            @if($repair->customer_id)
                                                <span class="text-[10px] bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-1.5 py-0.5 rounded font-medium">Registered</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $repair->bike_model }}</span>
                                        </div>
                                        <!-- F08: Asia/Manila Creation / Intake Timestamp -->
                                        <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1" title="Job Intake Date">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            Intake: {{ $repair->createdAtManila('M d, Y g:i A') }}
                                        </div>
                                    </td>
                                    <td class="p-4 align-top text-gray-600 dark:text-gray-400">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $repair->issue }}</div>
                                        @if($repair->reservation && $repair->reservation->scheduled_at)
                                            <div class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-1 font-semibold flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Booked for: {{ $repair->scheduledAtManila('M d, Y g:i A') }}
                                            </div>
                                        @endif
                                        @if($repair->completed_at)
                                            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Completed: {{ $repair->completedAtManila('M d, Y g:i A') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-4 align-top text-center">
                                        @php
                                            $isDone = $repair->isFinished();
                                            $linked = (bool) $repair->reservation_id;
                                        @endphp
                                        <form method="POST" action="/repairs/{{ $repair->id }}">
                                            @csrf @method('PUT')
                                            <div class="relative">
                                                @if (! $repair->isOngoing() && ! ($isDone && $linked))
                                                    <label for="est-{{ $repair->id }}" class="sr-only">Estimated duration</label>
                                                    <select id="est-{{ $repair->id }}" name="estimated_minutes" class="mb-1 text-[11px] rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-0.5">
                                                        @foreach ([30 => '30 min', 60 => '1 hr', 90 => '1.5 hr', 120 => '2 hr', 180 => '3 hr', 240 => '4 hr', 480 => '8 hr'] as $m => $l)
                                                            <option value="{{ $m }}" {{ $m === $walkInMinutes ? 'selected' : '' }}>Est. {{ $l }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                                <select name="status" onchange="this.form.submit()"
                                                        class="text-xs font-bold pl-3 pr-8 py-1 rounded-full border-0 focus:ring-0 cursor-pointer text-center appearance-none
                                                        {{ $repair->status == 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                                                        {{ $repair->status == 'ongoing' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                                        {{ $isDone ? 'bg-emerald-100 text-emerald-800' : '' }}">
                                                    <option value="pending" {{ $repair->status == 'pending' ? 'selected' : '' }} {{ $linked ? 'disabled' : '' }}>Pending</option>
                                                    <option value="ongoing" {{ $repair->status == 'ongoing' ? 'selected' : '' }} {{ $linked ? 'disabled' : '' }}>In Progress</option>
                                                    <option value="done" {{ $isDone ? 'selected' : '' }}>Completed</option>
                                                </select>
                                            </div>
                                        </form>
                                        @if ($linked)
                                            <div class="mt-1 text-[11px] text-indigo-600 dark:text-indigo-300 font-semibold">Online reservation</div>
                                        @endif
                                        @if ($repair->isOngoing())
                                            @if ($repair->isLegacyOngoing())
                                                <div class="mt-1 text-[11px] font-bold text-amber-700 dark:text-amber-300">No start time recorded &mdash; treated as occupying a bay. Extend it if still running, or mark it Completed / Pending.</div>
                                            @else
                                                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Est. {{ $repair->estimated_minutes ?: $walkInMinutes }} min &middot; ends {{ $repair->expectedEndAt()->timezone(config('repair_reservations.timezone'))->format('g:i A') }}</div>
                                                @if($repair->started_at)
                                                    <div class="text-[10px] text-gray-400">Started {{ $repair->startedAtManila('g:i A') }}</div>
                                                @endif
                                            @endif
                                            @if ($repair->isOverdue())
                                                <div class="mt-1 text-[11px] font-bold text-red-600 dark:text-red-400">Overdue &mdash; still occupying a bay until marked Completed</div>
                                            @endif
                                            <form method="POST" action="{{ route('repairs.extend', $repair) }}" class="mt-1 flex items-center justify-center gap-1">
                                                @csrf
                                                <label for="ext-{{ $repair->id }}" class="sr-only">Extend by</label>
                                                <select id="ext-{{ $repair->id }}" name="extra_minutes" class="text-[11px] rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-0.5">
                                                    @foreach ([15 => '+15 min', 30 => '+30 min', 60 => '+1 hr', 120 => '+2 hr'] as $m => $l)
                                                        <option value="{{ $m }}">{{ $l }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="text-[11px] font-bold px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-300 dark:border-indigo-600 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50">Extend</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="p-4 align-top font-bold text-gray-900 dark:text-white" x-data="{ editingCost: {{ $repair->cost === null ? 'true' : 'false' }}, saving: false }">
                                        <div class="flex items-center gap-2">
                                            <span>₱{{ number_format($repair->cost ?? 0, 2) }}</span>
                                            @if ($repair->cost === null)
                                                <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">Cost not set</span>
                                            @elseif (! $repair->is_paid)
                                                <button type="button"
                                                        x-show="!editingCost"
                                                        @click="editingCost = true"
                                                        class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:underline inline-flex items-center gap-0.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 rounded">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    Edit
                                                </button>
                                            @endif
                                        </div>
                                        @unless ($repair->is_paid)
                                            <form method="POST"
                                                  action="{{ route('repairs.cost', $repair) }}"
                                                  x-show="editingCost"
                                                  x-cloak
                                                  @submit="if (!confirm('Confirm saving repair cost as ₱' + document.getElementById('cost-{{ $repair->id }}').value + '?')) { $event.preventDefault(); return false; } saving = true;"
                                                  class="mt-1 flex items-center gap-1.5">
                                                @csrf @method('PUT')
                                                <label for="cost-{{ $repair->id }}" class="sr-only">Actual cost</label>
                                                <input id="cost-{{ $repair->id }}"
                                                       name="cost"
                                                       type="number"
                                                       step="0.01"
                                                       min="0"
                                                       required
                                                       value="{{ $repair->cost }}"
                                                       placeholder="Cost"
                                                       class="w-24 text-[11px] rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-1 px-2 focus:ring-2 focus:ring-indigo-500">
                                                <button type="submit"
                                                        :disabled="saving"
                                                        class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-500 dark:hover:bg-indigo-600 shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50">
                                                    Save
                                                </button>
                                                @if ($repair->cost !== null)
                                                    <button type="button"
                                                            @click="editingCost = false"
                                                            class="text-[11px] font-semibold px-2 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                                                        Cancel
                                                    </button>
                                                @endif
                                            </form>
                                        @endunless
                                    </td>
                                    <td class="p-4 align-top text-center">
                                        @if(!$repair->is_paid)
                                            <form method="POST" action="/repairs/{{ $repair->id }}/pay" onsubmit="return confirm('Confirm payment received?');">
                                                @csrf @method('PUT')
                                                <label for="tender-{{ $repair->id }}" class="sr-only">Cash received</label>
                                                <input id="tender-{{ $repair->id }}" name="amount_tendered" type="number" step="0.01" min="{{ $repair->cost ?? 0 }}" required value="{{ $repair->cost }}" placeholder="Cash" class="mb-1 w-24 text-[11px] rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white py-0.5">
                                                <button class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500 text-white font-bold px-3 py-1 rounded-full text-xs shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    Collect
                                                </button>
                                            </form>
                                        @else
                                            <x-status-badge status="paid" />
                                            @if($repair->paid_at)
                                                <div class="text-[10px] text-gray-400 mt-1">{{ $repair->paidAtManila('M d, g:i A') }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="p-4 align-top text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- F03: Edit Repair Button (opens modal) -->
                                            <button type="button"
                                                    @click="openEditModal({
                                                        id: {{ $repair->id }},
                                                        customer_id: '{{ $repair->customer_id ?? '' }}',
                                                        customer_name: '{{ addslashes($repair->customer_name) }}',
                                                        bike_model: '{{ addslashes($repair->bike_model) }}',
                                                        issue: '{{ addslashes($repair->issue) }}',
                                                        status: '{{ $repair->status }}',
                                                        estimated_minutes: {{ $repair->estimated_minutes ?: ($walkInMinutes ?? 60) }},
                                                        cost: '{{ $repair->cost !== null ? $repair->cost : '' }}',
                                                        is_paid: {{ $repair->is_paid ? 'true' : 'false' }},
                                                        reservation_id: {{ $repair->reservation_id ? $repair->reservation_id : 'null' }},
                                                        created_at_fmt: '{{ $repair->createdAtManila('M d, Y g:i A') }}',
                                                        started_at_fmt: '{{ $repair->startedAtManila('M d, Y g:i A') ?? '' }}',
                                                        completed_at_fmt: '{{ $repair->completedAtManila('M d, Y g:i A') ?? '' }}',
                                                        paid_at_fmt: '{{ $repair->paidAtManila('M d, Y g:i A') ?? ($repair->is_paid ? 'Paid' : '') }}'
                                                    })"
                                                    title="Edit Repair Job Details"
                                                    aria-label="Edit repair details"
                                                    class="p-1.5 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>

                                            <!-- Receipt Button -->
                                            <a href="/repairs/{{ $repair->id }}/receipt" aria-label="Print receipt for {{ $repair->customer_name }}" title="Print Receipt" class="p-1.5 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                            </a>

                                            <!-- Delete Record Button -->
                                            <form method="POST" action="/repairs/{{ $repair->id }}" class="inline" onsubmit="return confirm('Delete this repair record?');">
                                                @csrf @method('DELETE')
                                                <button title="Delete Record" aria-label="Delete repair record" class="p-1.5 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-400">
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

            <!-- ========================================== -->
            <!-- TAB 3: NEW REPAIR INTAKE FORM               -->
            <!-- ========================================== -->
            @elseif ($activeTab === 'new')
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500"></div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        New Repair Job Intake
                    </h2>
                    <a href="{{ route('repairs.index', ['tab' => 'tracking']) }}" class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Cancel / Back to Tracking
                    </a>
                </div>
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 text-sm rounded-r-xl">
                        <p class="font-bold mb-1">Please fix the following errors:</p>
                        <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <form method="POST" action="/repairs" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div class="lg:col-span-1">
                            <label for="repair-customer" class="sr-only">Customer</label>
                            <select id="repair-customer" name="customer_id" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                <option value="">Walk-in / Other</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="repair-customer-name" class="sr-only">Manual Name (if walk-in)</label>
                            <input id="repair-customer-name" name="customer_name" placeholder="Manual Name (if walk-in)" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label for="repair-bike-model" class="sr-only">Bike Model</label>
                            <input id="repair-bike-model" name="bike_model" placeholder="Bike Model" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        </div>
                        <div class="lg:col-span-2">
                            <label for="repair-issue" class="sr-only">Reported Issue</label>
                            <input id="repair-issue" name="issue" placeholder="Reported Issue" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 dark:text-gray-400 font-medium">₱</span></div>
                            <label for="repair-cost" class="sr-only">Estimated Cost</label>
                            <input id="repair-cost" name="cost" type="number" step="0.01" placeholder="Est. Cost" class="pl-8 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-full text-sm" required>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-3">
                        <a href="{{ route('repairs.index', ['tab' => 'tracking']) }}" class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-bold hover:bg-gray-50 dark:hover:bg-gray-700 text-sm inline-block">
                            Cancel
                        </a>
                        <button type="submit" :disabled="loading"
                                class="inline-flex items-center gap-2 bg-gray-900 dark:bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-sm hover:bg-gray-800 dark:hover:bg-indigo-700 transition-colors active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-75 disabled:cursor-not-allowed text-sm">
                            <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Add Repair Job
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        <!-- ========================================== -->
        <!-- F03: REPAIR EDITING MODAL (Universal)      -->
        <!-- ========================================== -->
        <div x-show="editModalOpen"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog"
             aria-modal="true"
             aria-labelledby="modal-headline">
            <!-- Backdrop -->
            <div x-show="editModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                 @click="closeEditModal()"></div>

            <!-- Modal Panel -->
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-6">
                <div x-show="editModalOpen"
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white dark:bg-gray-800 p-6 sm:p-8 text-left shadow-2xl transition-all border border-gray-100 dark:border-gray-700">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div>
                                <h3 id="modal-headline" class="text-lg font-black text-gray-900 dark:text-white">
                                    Edit Service Job #<span x-text="editingRepair.id"></span>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Update vehicle, service details, and tracking status.</p>
                            </div>
                        </div>
                        <button type="button"
                                @click="closeEditModal()"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <span class="sr-only">Close</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form method="POST"
                          :action="'/repairs/' + editingRepair.id"
                          @submit="savingModal = true"
                          class="mt-6 space-y-4">
                        @csrf
                        @method('PUT')

                        <!-- Customer Selection -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="edit-repair-customer" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Customer Account
                                </label>
                                <select id="edit-repair-customer"
                                        name="customer_id"
                                        x-model="editingRepair.customer_id"
                                        class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Walk-in / No Registered Account</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="edit-repair-customer-name" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Customer Name (Walk-in)
                                </label>
                                <input id="edit-repair-customer-name"
                                       name="customer_name"
                                       type="text"
                                       x-model="editingRepair.customer_name"
                                       placeholder="Customer Name"
                                       class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <!-- Motorcycle Model -->
                        <div>
                            <label for="edit-repair-bike-model" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Motorcycle Model <span class="text-red-500">*</span>
                            </label>
                            <input id="edit-repair-bike-model"
                                   name="bike_model"
                                   type="text"
                                   x-model="editingRepair.bike_model"
                                   required
                                   placeholder="e.g. Yamaha NMAX 155, Honda Click 125i"
                                   class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Reported Issue / Service Details -->
                        <div>
                            <label for="edit-repair-issue" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Reported Issue / Service Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="edit-repair-issue"
                                      name="issue"
                                      rows="3"
                                      x-model="editingRepair.issue"
                                      required
                                      placeholder="Describe problem, parts requested, or service tasks..."
                                      class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>

                        <!-- Status & Duration -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="edit-repair-status" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Service Status <span class="text-red-500">*</span>
                                </label>
                                <select id="edit-repair-status"
                                        name="status"
                                        x-model="editingRepair.status"
                                        class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="pending" :disabled="editingRepair.reservation_id !== null">Pending</option>
                                    <option value="ongoing">In Progress</option>
                                    <option value="done">Completed</option>
                                </select>
                                <template x-if="editingRepair.reservation_id">
                                    <p class="text-[11px] text-indigo-500 mt-1">Linked to online reservation &mdash; cannot revert to pending.</p>
                                </template>
                            </div>

                            <div>
                                <label for="edit-repair-estimate" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Estimated Duration
                                </label>
                                <select id="edit-repair-estimate"
                                        name="estimated_minutes"
                                        x-model="editingRepair.estimated_minutes"
                                        :disabled="editingRepair.status !== 'ongoing'"
                                        class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50">
                                    @foreach ([30 => '30 min', 60 => '1 hr', 90 => '1.5 hr', 120 => '2 hr', 180 => '3 hr', 240 => '4 hr', 480 => '8 hr'] as $m => $l)
                                        <option value="{{ $m }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Cost & Financial Lock Protection -->
                        <div>
                            <label for="edit-repair-cost" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Repair Cost (₱)
                            </label>
                            <template x-if="editingRepair.is_paid">
                                <div>
                                    <input id="edit-repair-cost-locked"
                                           type="text"
                                           :value="'₱' + Number(editingRepair.cost || 0).toFixed(2) + ' (Payment Collected)'"
                                           disabled
                                           class="w-full text-sm bg-gray-100 dark:bg-gray-700/60 border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 rounded-xl cursor-not-allowed">
                                    <p class="mt-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        ✓ Financial Record Locked: This repair has already been collected and linked to sales history. Cost cannot be altered.
                                    </p>
                                </div>
                            </template>
                            <template x-if="!editingRepair.is_paid">
                                <div>
                                    <input id="edit-repair-cost"
                                           name="cost"
                                           type="number"
                                           step="0.01"
                                           min="0"
                                           x-model="editingRepair.cost"
                                           placeholder="Leave blank if TBD"
                                           class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="mt-1 text-[11px] text-gray-400">Actual cost can be updated anytime before collecting payment.</p>
                                </div>
                            </template>
                        </div>

                        <!-- F08: Transaction Timestamps Audit Box (Asia/Manila) -->
                        <div class="bg-gray-50 dark:bg-gray-900/40 p-3.5 rounded-2xl border border-gray-100 dark:border-gray-700/60 text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <div class="font-bold text-gray-800 dark:text-gray-200 mb-1.5 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Transaction Timestamps (Asia/Manila)
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <div>
                                    <span class="text-gray-400">Intake / Created:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="editingRepair.created_at_fmt || '—'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Started:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="editingRepair.started_at_fmt || 'Not started'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Completed:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="editingRepair.completed_at_fmt || 'In progress / pending'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Payment:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="editingRepair.paid_at_fmt || (editingRepair.is_paid ? 'Paid' : 'Unpaid')"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button type="button"
                                    @click="closeEditModal()"
                                    class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-bold hover:bg-gray-50 dark:hover:bg-gray-700 text-sm transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    :disabled="savingModal"
                                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-sm transition-colors active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-75 disabled:cursor-not-allowed text-sm">
                                <svg x-show="savingModal" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
