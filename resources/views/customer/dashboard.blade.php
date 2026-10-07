<x-app-layout>
    <div x-data="{ 
        activeTab: 'overview',
        recentActivities: {{ Js::from(auth()->user()->unreadNotifications->take(5)->map(fn($n) => ['id' => $n->id, 'title' => $n->data['title'] ?? 'Notification', 'message' => $n->data['message'] ?? '', 'time' => $n->created_at->diffForHumans(), 'is_repair' => str_contains(strtolower($n->data['title'] ?? ''), 'repair')])) }},
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
    }" x-init="initEcho()" class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-12">
        <!-- Section Header -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm">
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
                
                <!-- Sidebar Navigation (Left) -->
                <div class="w-full md:w-64 flex-shrink-0 order-1 space-y-2">
                    @foreach(['overview' => 'Overview', 'repairs' => 'Track Repairs', 'preorders' => 'Pre-orders', 'purchases' => 'Purchase History'] as $id => $label)
                        <button
                            @click="activeTab = '{{ $id }}'"
                            :class="activeTab === '{{ $id }}' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-none' : 'text-gray-500 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400'"
                            class="w-full text-left py-4 px-6 rounded-2xl font-black text-xs uppercase tracking-widest text-gray-600 dark:text-gray-300 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center justify-between group"
                        >
                            <span class="flex items-center gap-3">
                                @php
                                    $icons = [
                                        'overview' => 'layout-dashboard',
                                        'repairs' => 'wrench',
                                        'preorders' => 'clipboard-list',
                                        'purchases' => 'shopping-bag'
                                    ];
                                @endphp
                                <i data-lucide="{{ $icons[$id] }}" class="w-5 h-5 transition-transform group-hover:scale-110" :class="activeTab === '{{ $id }}' ? 'text-white' : 'text-gray-400 group-hover:text-indigo-500'"></i>
                                {{ $label }}
                            </span>
                            <i data-lucide="chevron-right" class="w-4 h-4 opacity-50" :class="activeTab === '{{ $id }}' ? 'text-white opacity-100' : 'text-gray-400'"></i>
                        </button>
                    @endforeach
                </div>

                <!-- Main Content (Center) -->
                <div class="w-full md:flex-1 min-w-0 order-2">
                    
                    <!-- Overview Tab -->
                    <div x-show="activeTab === 'overview'" x-transition class="space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Welcome Hero Card (Takes up 2 columns on lg) -->
                        <div class="lg:col-span-2 bg-indigo-600 rounded-[1.5rem] p-5 sm:p-6 text-white relative overflow-hidden shadow-xl shadow-indigo-300 dark:shadow-none">
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

                    <!-- Repairs Tab -->
                    <div x-show="activeTab === 'repairs'" x-cloak class="space-y-8">
                    <!-- Repairs Table -->
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                        <div class="p-8 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Track My Repairs</h3>
                        </div>
                        @if(isset($repairs) && $repairs->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($repairs as $repair)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $repair->bike_model }}</td>
                                                <td class="p-6 text-sm text-gray-500">{{ Str::limit($repair->issue ?? '', 30) }}</td>
                                                <td class="p-6">
                                                    <span class="px-3 py-1 rounded-lg {{ strtolower($repair->status ?? 'pending') === 'pending' ? 'bg-yellow-50 text-yellow-600' : (in_array(strtolower($repair->status ?? 'pending'), ['completed','done','ready']) ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600') }} font-black text-[10px] uppercase tracking-widest">
                                                        {{ $repair->status ?? 'pending' }}
                                                    </span>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">{{ ($repair->cost ?? 0) > 0 ? '₱'.number_format((float)($repair->cost), 2) : 'TBD' }}</td>
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
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($preorders as $order)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                <td class="p-6">
                                                    <div class="font-bold text-gray-900 dark:text-white">{{ $order->part_name }}</div>
                                                    <div class="text-[10px] text-gray-500 font-bold uppercase mt-1">{{ $order->model ?? 'Universal' }}</div>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-center">x{{ $order->quantity }}</td>
                                                <td class="p-6">
                                                    <span class="px-3 py-1 rounded-lg {{ strtolower($order->status ?? 'pending') === 'pending' ? 'bg-yellow-50 text-yellow-600' : (in_array(strtolower($order->status ?? 'pending'), ['completed','done','ready']) ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600') }} font-black text-[10px] uppercase tracking-widest">
                                                        {{ $order->status ?? 'pending' }}
                                                    </span>
                                                </td>
                                                <td class="p-6 text-right">
                                                    <div class="font-black text-gray-900 dark:text-white mb-1">Total: {{ ($order->total ?? 0) > 0 ? '₱'.number_format((float)($order->total), 2) : 'TBD' }}</div>
                                                    <div class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">Paid DP: ₱{{ number_format((float)($order->down_payment ?? 0), 2) }}</div>
                                                    <div class="text-xs text-amber-600 dark:text-amber-400 font-bold">Bal: {{ ($order->total ?? 0) > 0 ? '₱'.number_format((float)(($order->total ?? 0) - ($order->down_payment ?? 0)), 2) : 'TBD' }}</div>
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
                                <table class="w-full text-left" x-data="{ expanded: null }">
                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                        @foreach($purchases as $trx => $items)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors cursor-pointer" @click="expanded = (expanded === '{{ $trx }}' ? null : '{{ $trx }}')">
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
                                                                </div>
                                                                <div class="flex gap-2">
                                                                    @if($item->status === 'pending')
                                                                        <form action="{{ route('customer.order.cancel', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this item?');">
                                                                            @csrf
                                                                            <button type="submit" class="px-4 py-2 text-xs font-black text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 rounded-xl transition-colors uppercase tracking-widest focus:outline-none">Cancel</button>
                                                                        </form>
                                                                    @elseif($item->canBeRefunded())
                                                                        <button type="button" @click="$dispatch('open-refund-modal', { id: {{ $item->id }}, name: '{{ addslashes($item->item?->part_name) }}' })" class="px-4 py-2 text-xs font-black text-orange-600 bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 rounded-xl transition-colors uppercase tracking-widest focus:outline-none">Refund</button>
                                                                    @elseif($item->isPickedUp() && in_array($item->status, ['completed']))
                                                                        <span class="px-4 py-2 text-xs font-black text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-xl uppercase tracking-widest cursor-not-allowed" title="Picked-up items cannot be refunded">Picked Up</span>
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


            <!-- Refund Request Modal -->
            <div x-data="{
                open: false,
                saleId: null,
                itemName: '',
                submitting: false,
                refundMethod: '',
                init() {
                    window.addEventListener('open-refund-modal', (e) => {
                        this.saleId = e.detail.id;
                        this.itemName = e.detail.name;
                        this.refundMethod = '';
                        this.open = true;
                    });
                }
            }" @keydown.escape.window="open = false" x-cloak>
                <div x-show="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="open = false" x-transition.opacity></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 dark:border-gray-700 max-h-[90vh] overflow-y-auto"
                         x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                        <div class="p-6 sm:p-8 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Request Refund</h3>
                            <p class="text-xs text-gray-500 font-bold mt-1" x-text="'Item: ' + itemName"></p>
                        </div>
                        <form :action="'/orders/' + saleId + '/refund'" method="POST" enctype="multipart/form-data" @submit="submitting = true">
                            @csrf
                            <div class="p-6 sm:p-8 space-y-6">
                                {{-- Reason for Refund --}}
                                <div>
                                    <label class="block text-sm font-black text-gray-900 dark:text-white mb-2 uppercase tracking-widest">Reason for Refund</label>
                                    <select name="reason" required class="w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold p-3">
                                        <option value="" disabled selected class="text-gray-400">Select a reason...</option>
                                        <option value="Change of Mind">Change of Mind</option>
                                        <option value="No Longer Needed">No Longer Needed</option>
                                        <option value="Found Better Price">Found Better Price</option>
                                        <option value="Ordered by Mistake">Ordered by Mistake</option>
                                        <option value="Damaged or Defective">Damaged or Defective (Requires photo)</option>
                                        <option value="Wrong Item Received">Wrong Item Received</option>
                                        <option value="Other">Other (Please specify in details)</option>
                                    </select>
                                </div>

                                {{-- Refund Method Selection --}}
                                <div>
                                    <label class="block text-sm font-black text-gray-900 dark:text-white mb-3 uppercase tracking-widest">Refund Method</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="relative cursor-pointer">
                                            <input type="radio" name="refund_method" value="digital" x-model="refundMethod" class="peer sr-only" required>
                                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-2xl text-center transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 hover:border-gray-300">
                                                <i data-lucide="smartphone" class="w-6 h-6 mx-auto mb-2 text-gray-400 peer-checked:text-indigo-600"></i>
                                                <div class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest">Digital</div>
                                                <div class="text-[10px] text-gray-400 font-bold mt-1">Upload QR Code</div>
                                            </div>
                                        </label>
                                        <label class="relative cursor-pointer">
                                            <input type="radio" name="refund_method" value="on_site" x-model="refundMethod" class="peer sr-only">
                                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-2xl text-center transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 hover:border-gray-300">
                                                <i data-lucide="store" class="w-6 h-6 mx-auto mb-2 text-gray-400 peer-checked:text-indigo-600"></i>
                                                <div class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest">On-site</div>
                                                <div class="text-[10px] text-gray-400 font-bold mt-1">Pick up at store</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                {{-- Digital Refund: QR Code Upload --}}
                                <div x-show="refundMethod === 'digital'" x-transition>
                                    <label class="block text-sm font-black text-gray-900 dark:text-white mb-2 uppercase tracking-widest">Upload QR Code <span class="text-red-500">*</span></label>
                                    <p class="text-[10px] text-gray-500 font-bold mb-3">Upload your payment QR code (GCash, Maya, etc.) so the owner can send your refund.</p>
                                    <input type="file" name="qr_code" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:tracking-widest file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 transition-colors">
                                </div>

                                {{-- On-site Refund: Pickup Time --}}
                                <div x-show="refundMethod === 'on_site'" x-transition>
                                    <label class="block text-sm font-black text-gray-900 dark:text-white mb-2 uppercase tracking-widest">Pickup Time <span class="text-red-500">*</span></label>
                                    <p class="text-[10px] text-gray-500 font-bold mb-3">Select when you will visit the store to pick up your refund.</p>
                                    <input type="datetime-local" name="pickup_time" class="w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold p-3" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}">
                                </div>

                                {{-- Additional Details --}}
                                <div>
                                    <label class="block text-sm font-black text-gray-900 dark:text-white mb-2 uppercase tracking-widest">Additional Details</label>
                                    <textarea name="details" rows="3" class="w-full border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3" placeholder="Please describe the issue..."></textarea>
                                </div>

                                {{-- Photo Evidence --}}
                                <div>
                                    <label class="block text-sm font-black text-gray-900 dark:text-white mb-2 uppercase tracking-widest">Photo Evidence (Optional)</label>
                                    <input type="file" name="photo" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:tracking-widest file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 transition-colors">
                                </div>
                            </div>
                            <div class="p-6 sm:p-8 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex justify-end gap-3">
                                <button type="button" @click="open = false" class="px-6 py-3 text-sm font-black text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors uppercase tracking-widest focus:outline-none">Cancel</button>
                                <button type="submit" :disabled="submitting" class="px-6 py-3 text-sm font-black text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors uppercase tracking-widest shadow-lg shadow-indigo-200 dark:shadow-none focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center gap-2">
                                    <span x-show="submitting">Submitting...</span>
                                    <span x-show="!submitting">Submit Request</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
