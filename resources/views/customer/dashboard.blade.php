<x-app-layout>
    <div x-data="{ 
        activeTab: (['overview','reservations','repairs','preorders','purchases'].includes(new URLSearchParams(window.location.search).get('tab')) ? new URLSearchParams(window.location.search).get('tab') : 'overview'),
        recentActivities: {{ Js::from(auth()->user()->latestUnreadNotifications(5)->map(fn($n) => ['id' => $n->id, 'title' => $n->data['title'] ?? 'Notification', 'message' => $n->data['message'] ?? '', 'time' => $n->created_at->diffForHumans(), 'is_repair' => str_contains(strtolower($n->data['title'] ?? ''), 'repair')])) }},
        viewingRepair: null,
        openRepairModal(repair) { this.viewingRepair = repair; },
        closeRepairModal() { this.viewingRepair = null; },
        initEcho() {
            if (window.Echo) {
                window.Echo.private('App.Models.User.{{ auth()->id() }}')
                    .notification((notification) => {
                        this.recentActivities.unshift({
                            id: notification.id,
                            title: notification.title,
                            message: notification.message,
                            time: 'Just now',
                            is_repair: (notification.title || '').toLowerCase().includes('repair')
                        });
                        if (this.recentActivities.length > 5) this.recentActivities.pop();
                        
                        // Re-render lucide icons for dynamically added ones
                        if (window.lucide) {
                            setTimeout(() => window.lucide.createIcons(), 50);
                        }
                    });
            }
        },
        markRead(id) {
            axios.post('/notifications/' + id + '/read').then(() => {
                this.recentActivities = this.recentActivities.filter(n => n.id !== id);
            });
        }
    }" x-init="initEcho()" class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-4">
        <!-- Section Header -->
        <div data-mt-subheader class="sticky top-[64px] md:top-[80px] lg:top-[88px] z-40 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="w-full px-4 sm:px-8 lg:px-12">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-300">
                            <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tighter">Customer Dashboard</h2>
                            <p class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em]">Orders and service tracking</p>
                        </div>
                </div>
            </div>
            </div>
        </div>

        <div class="w-full px-4 sm:px-8 lg:px-12 py-8">
            <div class="flex flex-col md:flex-row gap-8 items-start">
                
                <!-- Sidebar Navigation (Left): pinned on desktop by .mt-dash-sidebar (layouts/app) -->
                <nav aria-label="Dashboard sections" class="mt-dash-sidebar w-full md:w-64 flex-shrink-0 order-1 space-y-2">
                    @php
                        $navIcons = [
                            'overview' => 'layout-dashboard',
                            'reservations' => 'calendar-clock',
                            'repairs' => 'wrench',
                            'preorders' => 'clipboard-list',
                            'purchases' => 'shopping-bag',
                        ];
                    @endphp
                    @foreach(['overview' => 'Overview', 'reservations' => 'My Reservations', 'repairs' => 'Track Repairs', 'preorders' => 'Pre-orders', 'purchases' => 'Purchase History'] as $id => $label)
                        <button
                            @click="activeTab = '{{ $id }}'"
                            :class="activeTab === '{{ $id }}' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-gray-500 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400'"
                            class="w-full text-left py-4 px-6 rounded-2xl font-black text-xs uppercase tracking-widest text-gray-600 dark:text-gray-300 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-between group"
                        >
                            <span class="flex items-center gap-3">
                                <span class="nav-icon inline-flex w-5 h-5 shrink-0 items-center justify-center transition-transform group-hover:scale-110" :class="activeTab === '{{ $id }}' ? 'text-white' : 'text-gray-400 group-hover:text-indigo-500'" data-nav-icon="{{ $id }}"><i data-lucide="{{ $navIcons[$id] }}" class="w-5 h-5"></i></span>
                                {{ $label }}
                            </span>
                            <i data-lucide="chevron-right" class="w-4 h-4 opacity-50" :class="activeTab === '{{ $id }}' ? 'text-white opacity-100' : 'text-gray-400'"></i>
                        </button>
                    @endforeach
                </nav>

                <!-- Main Content (Center) -->
                <div class="w-full md:flex-1 min-w-0 order-2">
                    
                    <!-- Overview Tab -->
                    <div x-show="activeTab === 'overview'" x-transition class="space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                        <!-- Welcome Hero Card (Takes up 2 columns on lg, stabilized height) -->
                        <div class="lg:col-span-2 bg-indigo-600 rounded-[1.5rem] p-5 sm:p-6 text-white relative overflow-hidden shadow-xl shadow-indigo-300 dark:shadow-none min-h-[190px] flex flex-col justify-between">
                            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                            <div class="relative z-10">
                                <h3 class="text-xl font-black tracking-tighter mb-2">Welcome back, {{ explode(' ', Auth::user()->name)[0] }}!</h3>
                                <p class="text-indigo-100 text-xs sm:text-sm max-w-md font-medium leading-relaxed mb-4">Ready to upgrade your ride? Track your active repairs, view pre-orders, and browse our massive catalog of premium parts.</p>
                                <div class="flex flex-wrap gap-3">
                                    <a href="/browse" class="px-6 py-3 bg-white text-indigo-600 rounded-xl font-black text-xs hover:scale-105 transition-all shadow-md inline-block focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">Shop Now</a>
                                    <a href="/compatibility" class="px-6 py-3 bg-indigo-500/50 text-white border border-white/20 rounded-xl font-black text-xs hover:bg-indigo-400 transition-all inline-block focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">Check Compatibility</a>
                                </div>
                            </div>
                        </div>

                        <!-- Live Notifications (Takes up 1 column on lg) -->
                        <div class="bg-white dark:bg-gray-900 p-6 rounded-[1.5rem] shadow-sm border border-gray-100 dark:border-gray-800">
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-indigo-600 rounded-full animate-ping"></span>
                                Recent Updates
                            </h3>
                            <div class="space-y-4 max-h-[250px] overflow-y-auto scrollbar-thin">
                                <template x-for="(notification, index) in recentActivities" :key="notification.id">
                                    <div>
                                        <div class="flex gap-4 group">
                                            <div class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 p-2 rounded-xl h-fit shrink-0">
                                                <i :data-lucide="notification.is_repair ? 'wrench' : 'package'" class="w-4 h-4"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-[11px] font-black text-gray-900 dark:text-white uppercase tracking-tight leading-tight" x-text="notification.title"></div>
                                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 leading-tight" x-text="notification.message"></div>
                                                <div class="text-[9px] font-bold text-gray-400 mt-2" x-text="notification.time"></div>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-right">
                                            <button @click="markRead(notification.id); hapticPulse()" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline py-2 px-3 inline-block focus:outline-none focus:ring-2 focus:ring-indigo-400 rounded">Mark Read</button>
                                        </div>
                                        <hr x-show="index < recentActivities.length - 1" class="border-gray-100 dark:border-gray-800 mt-3">
                                    </div>
                                </template>
                                <div x-show="recentActivities.length === 0" style="display:none;" class="text-center py-4 text-gray-400 text-xs font-bold italic">No new updates</div>
                            </div>
                        </div>
                    </div>

                    </div>

                    <!-- My Reservations Tab: one reviewable card per checkout reference -->
                    <div x-show="activeTab === 'reservations'" x-cloak class="space-y-6">
                        @if(isset($activeReservations) && $activeReservations->count() > 0)
                            <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Active Reservations</h3>
                            @foreach($activeReservations as $reservation)
                                <div id="reservation-{{ $reservation->reference }}" class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 p-6 sm:p-8 shadow-sm space-y-5">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <div class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em]">Reservation</div>
                                            <div class="font-black text-gray-900 dark:text-white text-lg">{{ $reservation->reference }}</div>
                                            <div class="text-xs text-gray-500 font-bold mt-1">Placed {{ $reservation->created_at->format('M d, Y h:i A') }}</div>
                                        </div>
                                        <span class="px-3 py-1.5 text-[10px] font-black text-amber-600 bg-amber-50 dark:bg-amber-900/30 rounded-xl uppercase tracking-widest">Pending Pickup</span>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach($reservation->lines as $line)
                                            <div class="flex items-center justify-between gap-3 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <x-product-image :item="$line->item" sizes="48px" :alt="$line->item?->part_name ?? 'Part'" class="w-12 h-12 rounded-xl shrink-0 bg-white dark:bg-gray-700" img-class="p-1" />
                                                    <div class="min-w-0">
                                                        <div class="font-black text-gray-900 dark:text-white text-sm truncate">{{ $line->item?->part_name ?? 'Unknown Item' }}</div>
                                                        <div class="text-xs text-gray-500 font-bold">Qty: {{ $line->quantity }}</div>
                                                    </div>
                                                </div>
                                                <div class="font-black text-gray-900 dark:text-white text-sm shrink-0">₱{{ number_format($line->total_price, 2) }}</div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                        <div class="text-sm font-black text-gray-900 dark:text-white">Due at store: <span class="text-indigo-600">₱{{ number_format($reservation->total, 2) }}</span> <span class="text-xs font-bold text-gray-400">(Cash payment at pickup)</span></div>
                                        @if($reservation->deadline && now()->lt($reservation->deadline))
                                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <span>Pickup by {{ $reservation->deadline->format('M d, h:i A') }} ({{ $reservation->deadline->diffForHumans() }})</span>
                                            </div>
                                        @elseif($reservation->deadline)
                                            <div class="text-[11px] font-bold text-red-600">Reservation window expired — it will be released shortly.</div>
                                        @endif
                                    </div>

                                    <form action="{{ route('customer.reservation.cancel', ['reference' => $reservation->reference]) }}" method="POST" class="pt-1"
                                          x-data="{ cancelling: false, reason: '' }"
                                          @pageshow.window="cancelling = false"
                                          @submit="if (cancelling) { $event.preventDefault(); return; } if (!confirm(@js('Cancel reservation '.$reservation->reference.'? All '.$reservation->lines->count().' item(s) will be released back to stock. This cannot be undone.'))) { $event.preventDefault(); return; } cancelling = true; setTimeout(() => cancelling = false, 20000);">
                                        @csrf
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <input type="text" name="reason" x-model="reason" maxlength="255" placeholder="Cancellation reason (optional)"
                                                   class="flex-1 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm px-4 py-2.5 focus:ring-indigo-500 focus:border-indigo-500" aria-label="Cancellation reason">
                                            <button type="submit" :disabled="cancelling"
                                                    class="px-6 py-2.5 text-xs font-black text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50"
                                                    aria-label="Cancel reservation {{ $reservation->reference }}">
                                                <span x-show="!cancelling">Cancel Reservation</span>
                                                <span x-show="cancelling" x-cloak>Cancelling…</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        @else
                            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 p-12 text-center">
                                <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-400">
                                    <i data-lucide="calendar-clock" class="w-8 h-8"></i>
                                </div>
                                <p class="text-gray-900 dark:text-white font-bold text-lg mb-1">No active reservations</p>
                                <p class="text-gray-400 text-sm font-medium">Reserve an in-stock part and it will appear here with its 24-hour pickup deadline.</p>
                            </div>
                        @endif

                        @if(isset($reservationHistory) && $reservationHistory->count() > 0)
                            <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight pt-4">Reservation History</h3>
                            @foreach($reservationHistory as $reservation)
                                <div id="reservation-{{ $reservation->reference }}" class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 p-6 sm:p-8 shadow-sm space-y-4 opacity-90">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Reservation</div>
                                            <div class="font-black text-gray-900 dark:text-white">{{ $reservation->reference }}</div>
                                            <div class="text-xs text-gray-500 font-bold mt-1">{{ $reservation->lines->count() }} item(s) • ₱{{ number_format($reservation->total, 2) }} • {{ $reservation->created_at->format('M d, Y') }}</div>
                                        </div>
                                        <span class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-xl
                                            {{ $reservation->status === 'completed' ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30' : '' }}
                                            {{ $reservation->status === 'cancelled' ? 'text-red-600 bg-red-50 dark:bg-red-900/30' : '' }}
                                            {{ $reservation->status === 'expired' ? 'text-gray-600 bg-gray-100 dark:bg-gray-800' : '' }}">
                                            {{ ucfirst($reservation->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <!-- Repairs Tab -->
                    <div x-show="activeTab === 'repairs'" x-cloak class="space-y-8">
                    <!-- RD2-R3: service / repair appointment requests -->
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                        <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Service Appointments</h3>
                                <p class="text-xs text-gray-400 font-medium mt-1">Same-day requests; appointments start {{ \Carbon\Carbon::createFromFormat('H:i', config('repair_reservations.open'))->format('g:i A') }}–{{ \Carbon\Carbon::createFromFormat('H:i', config('repair_reservations.last_start'))->format('g:i A') }}. A request is confirmed only after the shop owner accepts it.</p>
                            </div>
                            <a href="{{ route('repair-reservations.create') }}" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-black">Book a service today</a>
                        </div>
                        @if(isset($serviceRequests) && $serviceRequests->count() > 0)
                            <div class="divide-y divide-gray-50 dark:divide-gray-800">
                                @foreach($serviceRequests as $sr)
                                    <a href="{{ route('repair-reservations.show', $sr->reference) }}" id="service-request-{{ $sr->id }}" class="flex flex-wrap items-center justify-between gap-2 p-6 hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white">{{ $sr->serviceLabel() }} &middot; {{ $sr->bike_model }}</div>
                                            <div class="text-xs text-gray-500">{{ $sr->reference }} &middot; {{ $sr->dateLabel() }}, {{ $sr->timeLabel() }}</div>
                                        </div>
                                        <span class="px-3 py-1 rounded-lg {{ $sr->status === 'accepted' ? 'bg-emerald-50 text-emerald-600' : ($sr->status === 'pending' ? 'bg-yellow-50 text-yellow-600' : 'bg-gray-100 text-gray-500') }} font-black text-[10px] uppercase tracking-widest">{{ $sr->label() }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Repairs Table & Maintenance Overview -->
                    @if(isset($maintenanceStats) && $maintenanceStats['total_services'] > 0)
                        <!-- Maintenance Summary Stats -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Total Services</p>
                                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $maintenanceStats['total_services'] }}</p>
                            </div>
                            <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                <p class="text-[10px] font-black uppercase tracking-widest text-amber-500 mb-1">In Progress</p>
                                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $maintenanceStats['active_services'] }}</p>
                            </div>
                            <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500 mb-1">Completed</p>
                                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $maintenanceStats['completed_services'] }}</p>
                            </div>
                            <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                <p class="text-[10px] font-black uppercase tracking-widest text-indigo-500 mb-1">Motorcycles</p>
                                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $maintenanceStats['unique_motorcycles'] }}</p>
                            </div>
                        </div>

                        <!-- Motorcycle Maintenance Cards by Bike Model -->
                        @if(isset($maintenanceByBike) && count($maintenanceByBike) > 0)
                            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 p-8 shadow-sm">
                                <h4 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight mb-4">Motorcycle Service History</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($maintenanceByBike as $bike)
                                        <div class="p-5 rounded-2xl bg-gray-50/70 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                                            <div>
                                                <div class="flex items-center justify-between gap-2 mb-2">
                                                    <span class="font-black text-gray-900 dark:text-white text-base">{{ $bike['model'] }}</span>
                                                    @if($bike['active_job'])
                                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-600 border border-indigo-200">
                                                            Active: {{ $bike['active_job']->status }}
                                                        </span>
                                                    @else
                                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-600 border border-emerald-200">
                                                            Up to Date
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                                                    <p><span class="font-bold text-gray-700 dark:text-gray-300">Total Visits:</span> {{ $bike['total_visits'] }} service{{ $bike['total_visits'] === 1 ? '' : 's' }}</p>
                                                    <p><span class="font-bold text-gray-700 dark:text-gray-300">Last Service:</span> {{ $bike['last_service_date'] ? \Carbon\Carbon::parse($bike['last_service_date'])->setTimezone(config('app.timezone', 'Asia/Manila'))->format('M d, Y') : 'N/A' }}</p>
                                                    @if($bike['last_service_issue'])
                                                        <p class="truncate"><span class="font-bold text-gray-700 dark:text-gray-300">Recent:</span> {{ $bike['last_service_issue'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Repairs Table -->
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                        <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Track My Repairs</h3>
                                <p class="text-xs text-gray-400 font-medium mt-1">Real-time status and service milestones for all in-shop repairs.</p>
                            </div>
                        </div>
                        @if(isset($repairs) && $repairs->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        <tr>
                                            <th class="p-6">Bike Model</th>
                                            <th class="p-6">Service / Issue</th>
                                            <th class="p-6">Date</th>
                                            <th class="p-6">Status</th>
                                            <th class="p-6 text-right">Cost</th>
                                            <th class="p-6 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($repairs as $repair)
                                            <tr id="repair-{{ $repair->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $repair->bike_model }}</td>
                                                <td class="p-6 text-sm text-gray-500">
                                                    <div class="font-semibold text-gray-900 dark:text-white">{{ Str::limit($repair->issue ?? 'General Service', 36) }}</div>
                                                    @if($repair->completed_at)
                                                        <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">Completed {{ $repair->completedAtManila('M d, Y') }}</div>
                                                    @elseif($repair->started_at)
                                                        <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium mt-0.5">Started {{ $repair->startedAtManila('M d, Y') }}</div>
                                                    @endif
                                                </td>
                                                <td class="p-6 text-xs text-gray-500 font-medium">
                                                    {{ $repair->createdAtManila('M d, Y') }}
                                                </td>
                                                <td class="p-6">
                                                    @php
                                                        $st = strtolower($repair->status ?? 'pending');
                                                        $badgeClass = match($st) {
                                                            'pending' => 'bg-yellow-50 text-yellow-600 border border-yellow-200',
                                                            'ongoing' => 'bg-indigo-50 text-indigo-600 border border-indigo-200',
                                                            'done', 'completed' => 'bg-emerald-50 text-emerald-600 border border-emerald-200',
                                                            default => 'bg-gray-100 text-gray-600',
                                                        };
                                                    @endphp
                                                    <span class="px-3 py-1 rounded-lg {{ $badgeClass }} font-black text-[10px] uppercase tracking-widest inline-flex items-center gap-1">
                                                        {{ $repair->status ?? 'pending' }}
                                                    </span>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">
                                                    <div>{{ ($repair->cost ?? 0) > 0 ? '₱'.number_format((float)($repair->cost), 2) : 'TBD' }}</div>
                                                    <div class="text-[10px] font-bold uppercase tracking-wider mt-0.5 {{ $repair->is_paid ? 'text-emerald-600' : 'text-gray-400' }}">
                                                        {{ $repair->is_paid ? 'Paid' : 'Unpaid' }}
                                                    </div>
                                                </td>
                                                <td class="p-6 text-right">
                                                    <button type="button"
                                                            @click="openRepairModal({{ json_encode([
                                                                'id' => $repair->id,
                                                                'bike_model' => $repair->bike_model,
                                                                'issue' => $repair->issue,
                                                                'status' => $repair->status,
                                                                'cost' => $repair->cost !== null ? (float) $repair->cost : null,
                                                                'is_paid' => (bool) $repair->is_paid,
                                                                'created_at_fmt' => $repair->createdAtManila('M d, Y g:i A'),
                                                                'started_at_fmt' => $repair->startedAtManila('M d, Y g:i A'),
                                                                'completed_at_fmt' => $repair->completedAtManila('M d, Y g:i A'),
                                                                'paid_at_fmt' => $repair->paidAtManila('M d, Y g:i A'),
                                                                'is_ongoing' => $repair->isOngoing(),
                                                                'is_finished' => $repair->isFinished(),
                                                            ]) }})"
                                                            class="px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold transition-colors">
                                                        Details
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-400">
                                    <i data-lucide="wrench" class="w-8 h-8"></i>
                                </div>
                                <p class="text-gray-900 dark:text-white font-bold text-lg mb-1">No active repairs</p>
                                <p class="text-gray-400 text-sm font-medium">When you drop off a bike, track it here.</p>
                            </div>
                        @endif
                    </div>

                    </div>

                    <!-- Pre-orders Tab -->
                    <div x-show="activeTab === 'preorders'" x-cloak class="space-y-8">
                    <!-- Pre-orders Table -->
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                        <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Track My Pre-Orders</h3>
                        </div>
                        @if(isset($preorders) && $preorders->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        <tr>
                                            <th class="p-6">Requested Part</th>
                                            <th class="p-6 text-center">Qty</th>
                                            <th class="p-6">Status &amp; Progress</th>
                                            <th class="p-6 text-right">Total &amp; Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($preorders as $order)
                                            <tr id="preorder-{{ $order->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6">
                                                    <div class="font-bold text-gray-900 dark:text-white">{{ $order->part_name }}</div>
                                                    <div class="text-[10px] text-gray-500 font-bold uppercase mt-1">{{ $order->model ?? 'Universal' }}</div>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-center">x{{ $order->quantity }}</td>
                                                <td class="p-6">
                                                    @php
                                                        $stage = $order->stage();
                                                        $steps = ['pending_sourcing' => 'Pending Sourcing', 'awaiting_arrival' => 'Sourced / Awaiting Arrival', 'ready' => 'Ready for Pickup', 'completed' => 'Completed'];
                                                        $stepKeys = array_keys($steps);
                                                        $currentIdx = array_search($stage['key'], $stepKeys, true);
                                                        $terminal = in_array($stage['key'], ['cancelled', 'expired'], true);
                                                    @endphp
                                                    <span class="px-3 py-1 rounded-lg {{ $terminal ? 'bg-red-50 text-red-600' : ($stage['key'] === 'completed' ? 'bg-emerald-50 text-emerald-600' : ($stage['key'] === 'ready' ? 'bg-blue-50 text-blue-600' : 'bg-yellow-50 text-yellow-600')) }} font-black text-[10px] uppercase tracking-widest">
                                                        {{ $stage['label'] }}
                                                    </span>
                                                    @unless($terminal)
                                                        <ol class="mt-2 flex flex-wrap items-center gap-x-1 gap-y-0.5 text-[10px] font-bold" aria-label="Pre-order progress">
                                                            @foreach($steps as $key => $stepLabel)
                                                                <li class="flex items-center gap-1 {{ $loop->index <= $currentIdx ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-300 dark:text-gray-600' }}">
                                                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-current"></span>{{ $stepLabel }}@if(!$loop->last)<span class="text-gray-300 ml-1">›</span>@endif
                                                                </li>
                                                            @endforeach
                                                        </ol>
                                                    @endunless
                                                    @if($order->is_stocked && $order->pickup_expires_at && !in_array($order->status, ['completed', 'cancelled', 'expired']))
                                                        @if(now()->lt($order->pickup_expires_at))
                                                            <div class="mt-2 flex items-center gap-1 text-[11px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 rounded-md">
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                <span>Pickup: {{ $order->pickup_expires_at->diffForHumans() }}</span>
                                                            </div>
                                                        @else
                                                            <div class="mt-2 text-[10px] font-bold text-red-600 bg-red-50 dark:bg-red-900/20 px-2 py-0.5 rounded-md">
                                                                Pickup Window Expired
                                                            </div>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="p-6 text-right">
                                                    <div class="font-black text-gray-900 dark:text-white mb-1">Total: {{ ($order->total ?? 0) > 0 ? '₱'.number_format((float)($order->total), 2) : 'TBD' }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-bold">{{ $order->is_paid ? 'Paid' : 'Cash payment at pickup' }}</div>

                                                    @php
                                                        // Cancel is offered only while the request is still an
                                                        // uncollected, unpaid sourcing record the customer owns.
                                                        $preorderCancellable = in_array($order->status, ['pending', 'arrived'], true)
                                                            && !$order->is_paid
                                                            && $order->picked_up_at === null;
                                                    @endphp
                                                    @if($preorderCancellable)
                                                        <form action="{{ route('customer.preorder.cancel', $order->id) }}" method="POST" class="mt-3"
                                                              x-data="{ cancelling: false }"
                                                              @pageshow.window="cancelling = false"
                                                              @submit="if (cancelling) { $event.preventDefault(); return; } if (!confirm(@js('Cancel this pre-order for '.$order->part_name.'? This cannot be undone.'))) { $event.preventDefault(); return; } cancelling = true; setTimeout(() => cancelling = false, 20000);">
                                                            @csrf
                                                            <button type="submit" :disabled="cancelling"
                                                                    class="w-full px-4 py-2 text-[10px] font-black text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 rounded-xl transition-colors uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-red-400 disabled:opacity-50"
                                                                    aria-label="Cancel pre-order for {{ $order->part_name }}">
                                                                <span x-show="!cancelling">Cancel Pre-order</span>
                                                                <span x-show="cancelling" x-cloak>Cancelling…</span>
                                                            </button>
                                                        </form>
                                                    @elseif($order->status === 'completed')
                                                        <span class="mt-3 inline-block px-3 py-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl uppercase tracking-widest">Completed</span>
                                                    @elseif($order->status === 'cancelled')
                                                        <span class="mt-3 inline-block px-3 py-1.5 text-[10px] font-black text-red-600 bg-red-50 dark:bg-red-900/30 rounded-xl uppercase tracking-widest">Cancelled</span>
                                                    @elseif($order->status === 'expired')
                                                        <span class="mt-3 inline-block px-3 py-1.5 text-[10px] font-black text-gray-600 bg-gray-100 dark:bg-gray-800 rounded-xl uppercase tracking-widest">Expired</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-400">
                                    <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                                </div>
                                <p class="text-gray-900 dark:text-white font-bold text-lg mb-1">No active pre-orders</p>
                                <p class="text-gray-400 text-sm font-medium">Request parts you need and track them here.</p>
                            </div>
                        @endif
                    </div>

                    </div>

                    <!-- Purchases Tab -->
                    <div x-show="activeTab === 'purchases'" x-cloak class="space-y-8">
                    <!-- Purchase History Table -->
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                        <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Purchase History</h3>
                        </div>
                        @if(isset($purchases) && $purchases->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left" x-data="{ expanded: (window.location.hash.startsWith('#purchase-') ? window.location.hash.replace('#purchase-', '') : null) }">
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($purchases as $trx => $items)
                                            <tr id="purchase-{{ $trx }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors cursor-pointer" @click="expanded = (expanded === '{{ $trx }}' ? null : '{{ $trx }}')">
                                                <td class="p-6">
                                                    <div class="flex items-center gap-3">
                                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="expanded === '{{ $trx }}' ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                                        <div>
                                                            <div class="font-bold text-gray-900 dark:text-white">{{ $trx }}</div>
                                                            <div class="text-[10px] text-gray-400 font-bold uppercase mt-1">{{ $items->first()->created_at->format('M d, Y') }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-6 text-sm text-gray-500">
                                                    {{ $items->count() }} item(s)
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">
                                                    ₱{{ number_format($items->sum('total_price'), 2) }}
                                                </td>
                                                <td class="p-6 text-right">
                                                    <a href="{{ url('/checkout/receipt?trx=' . $trx) }}" @click.stop class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline px-3 py-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg inline-block">Receipt</a>
                                                </td>
                                            </tr>
                                            <!-- Expanded Items -->
                                            <tr x-show="expanded === '{{ $trx }}'" x-cloak class="bg-gray-50/30 dark:bg-gray-800/10">
                                                <td colspan="4" class="p-0 border-b border-gray-100 dark:border-gray-800">
                                                    <div class="p-6 pl-12 space-y-4">
                                                        @foreach($items as $item)
                                                            <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm">
                                                                <div class="flex-1">
                                                                    <div class="flex items-center gap-2 mb-1">
                                                                        <h4 class="font-black text-gray-900 dark:text-white">{{ $item->item?->part_name ?? 'Unknown Item' }}</h4>
                                                                        <span class="px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-md 
                                                                            {{ $item->status === 'pending' ? 'bg-yellow-50 text-yellow-600' : '' }}
                                                                            {{ $item->status === 'completed' ? 'bg-emerald-50 text-emerald-600' : '' }}
                                                                            {{ $item->status === 'cancelled' ? 'bg-red-50 text-red-600' : '' }}
                                                                            {{ str_starts_with($item->status, 'refund') ? 'bg-orange-50 text-orange-600' : '' }}">
                                                                            {{ str_replace('_', ' ', $item->status) }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="text-xs text-gray-500 font-bold">Qty: {{ $item->quantity }} <span class="mx-2">•</span> ₱{{ number_format($item->total_price, 2) }}</div>
                                                                    @if($item->status === 'pending')
                                                                        @php $deadline = $item->expires_at ?? ($item->reserved_at ? $item->reserved_at->addHours(24) : $item->created_at->addHours(24)); @endphp
                                                                        @if(now()->lt($deadline))
                                                                            <div class="mt-1.5 flex items-center gap-1.5 text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                                <span>24h Pickup Deadline: {{ $deadline->diffForHumans() }} (by {{ $deadline->format('M d, h:i A') }})</span>
                                                                            </div>
                                                                        @else
                                                                            <div class="mt-1 text-[11px] font-bold text-red-600">
                                                                                Reservation window expired
                                                                            </div>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                                <div class="flex gap-2">
                                                                    @if($item->status === 'pending')
                                                                        <form action="{{ route('customer.order.cancel', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation? Stock will be released immediately.');">
                                                                            @csrf
                                                                            <button type="submit" class="px-4 py-2 text-xs font-black text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 rounded-xl transition-colors uppercase tracking-widest focus:outline-none">Cancel Reservation</button>
                                                                        </form>
                                                                    @elseif($item->isPickedUp() && in_array($item->status, ['completed']))
                                                                        <span class="px-3 py-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl uppercase tracking-widest" title="Completed In-Store">Picked Up</span>
                                                                    @elseif($item->status === 'cancelled')
                                                                        <span class="px-3 py-1.5 text-[10px] font-black text-red-600 bg-red-50 dark:bg-red-900/30 rounded-xl uppercase tracking-widest">Cancelled</span>
                                                                    @elseif($item->status === 'refunded')
                                                                        <span class="px-3 py-1.5 text-[10px] font-black text-orange-600 bg-orange-50 dark:bg-orange-900/30 rounded-xl uppercase tracking-widest">Refunded (In-Store)</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4 text-gray-400">
                                    <i data-lucide="shopping-bag" class="w-8 h-8"></i>
                                </div>
                                <p class="text-gray-900 dark:text-white font-bold text-lg mb-1">No past purchases</p>
                                <p class="text-gray-400 text-sm font-medium">Your completed orders will appear here.</p>
                            </div>
                        @endif
                    </div>
                </div>

                    </div>
                </div>

        </div>
    </div>

    <!-- Customer Read-Only Repair Details Modal (F10) -->
    <div x-show="viewingRepair !== null"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="viewingRepair !== null"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeRepairModal()"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

            <!-- Modal Card (Strictly Read-Only, No Administrative Editing Controls) -->
            <div x-show="viewingRepair !== null"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-900 shadow-2xl rounded-3xl border border-gray-100 dark:border-gray-800">

                <!-- Header -->
                <div class="flex items-start justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Service Ticket #<span x-text="viewingRepair?.id"></span></span>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white" x-text="viewingRepair?.bike_model"></h3>
                    </div>
                    <button type="button" @click="closeRepairModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="py-5 space-y-4 text-sm" x-show="viewingRepair">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Issue / Service Requested</label>
                        <p class="font-medium text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-100 dark:border-gray-700/50" x-text="viewingRepair?.issue || 'General Maintenance'"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Status</label>
                            <span class="inline-block px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider"
                                  :class="{
                                      'bg-yellow-50 text-yellow-700 border border-yellow-200': viewingRepair?.status === 'pending',
                                      'bg-indigo-50 text-indigo-700 border border-indigo-200': viewingRepair?.status === 'ongoing',
                                      'bg-emerald-50 text-emerald-700 border border-emerald-200': viewingRepair?.status === 'done' || viewingRepair?.status === 'completed'
                                  }"
                                  x-text="viewingRepair?.status"></span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Payment Status</label>
                            <span class="inline-block px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider"
                                  :class="viewingRepair?.is_paid ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                  x-text="viewingRepair?.is_paid ? 'Paid' : 'Unpaid'"></span>
                        </div>
                    </div>

                    <!-- Service Milestones (Asia/Manila) -->
                    <div class="pt-2 border-t border-gray-100 dark:border-gray-800 space-y-2">
                        <label class="block text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Service Milestones (Asia/Manila)</label>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between text-gray-600 dark:text-gray-300">
                                <span>Intake / Registered:</span>
                                <span class="font-semibold text-gray-900 dark:text-white" x-text="viewingRepair?.created_at_fmt || '—'"></span>
                            </div>
                            <div class="flex justify-between text-gray-600 dark:text-gray-300">
                                <span>Bay Work Started:</span>
                                <span class="font-semibold text-gray-900 dark:text-white" x-text="viewingRepair?.started_at_fmt || (viewingRepair?.is_ongoing ? 'In progress' : 'Awaiting bay')"></span>
                            </div>
                            <div class="flex justify-between text-gray-600 dark:text-gray-300">
                                <span>Service Completed:</span>
                                <span class="font-semibold text-gray-900 dark:text-white" x-text="viewingRepair?.completed_at_fmt || (viewingRepair?.is_finished ? 'Completed' : 'Pending completion')"></span>
                            </div>
                            <template x-if="viewingRepair?.is_paid && viewingRepair?.paid_at_fmt">
                                <div class="flex justify-between text-gray-600 dark:text-gray-300">
                                    <span>Payment Recorded:</span>
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400" x-text="viewingRepair?.paid_at_fmt"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Invoiced Service Charges -->
                    <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-500">Service Charges</span>
                        <span class="text-lg font-black text-gray-900 dark:text-white"
                              x-text="viewingRepair?.cost ? '₱' + Number(viewingRepair.cost).toFixed(2) : 'TBD'"></span>
                    </div>

                    <div class="text-[11px] text-gray-400 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl">
                        <span>ℹ For questions regarding parts or work updates, please speak with your service technician or front desk upon pickup.</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 text-right">
                    <button type="button" @click="closeRepairModal()" class="px-5 py-2.5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-black transition-opacity hover:opacity-90">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
