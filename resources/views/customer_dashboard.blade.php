<x-app-layout>

    <x-slot name="header">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Customer Dashboard
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Notifications Section -->
            @if(auth()->user()->unreadNotifications->count() > 0)
            <div class="mb-10">
                <h2 class="text-xl font-extrabold text-gray-900 dark:text-white mb-6 flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="bell-ring" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    Recent Updates
                </h2>
                <div class="grid grid-cols-1 gap-4">
                    @foreach(auth()->user()->unreadNotifications->take(1) as $notification)
                    <div class="bg-white dark:bg-gray-800 border border-indigo-100 dark:border-indigo-900/50 p-5 rounded-2xl flex items-start justify-between gap-4 transition-all hover:shadow-md">
                        <div class="flex items-start gap-4">
                            <div class="bg-indigo-100 dark:bg-indigo-900/50 p-2.5 rounded-xl text-indigo-600 dark:text-indigo-400 shrink-0">
                                 <i data-lucide="{{ str_contains(strtolower($notification->data['title']), 'repair') ? 'wrench' : 'package' }}" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-gray-900 dark:text-white leading-tight">{{ $notification->data['title'] }}</h4>
                                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1 leading-relaxed">{{ $notification->data['message'] }}</p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-2 uppercase tracking-widest font-black">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="shrink-0">
                            @csrf
                            <button type="submit" class="p-2 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400 transition-colors" title="Mark as Read">
                                <i data-lucide="check" class="w-5 h-5"></i>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">

                <!-- Browse Parts -->
                <a href="/browse" class="group bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-indigo-100 dark:border-indigo-900 hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-500 transition-all active:scale-[0.98] flex flex-col gap-5 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-50 dark:bg-indigo-900/30 rounded-bl-full -z-10 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="bg-indigo-100 dark:bg-indigo-900/50 w-16 h-16 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                        <i data-lucide="shopping-bag" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-lg text-gray-900 dark:text-white group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors">Browse Parts</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View available motorcycle parts</p>
                    </div>
                </a>

                <!-- Cart -->
                <a href="/cart" class="group bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-emerald-100 dark:border-emerald-900 hover:shadow-md hover:border-emerald-400 dark:hover:border-emerald-500 transition-all active:scale-[0.98] flex flex-col gap-5 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-50 dark:bg-emerald-900/30 rounded-bl-full -z-10 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="bg-emerald-100 dark:bg-emerald-900/50 w-16 h-16 rounded-2xl flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                        <i data-lucide="shopping-cart" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-lg text-gray-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors">View Cart</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Check items in your cart</p>
                    </div>
                </a>

                <!-- Compatibility -->
                <a href="/compatibility" class="group bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-blue-100 dark:border-blue-900 hover:shadow-md hover:border-blue-400 dark:hover:border-blue-500 transition-all active:scale-[0.98] flex flex-col gap-5 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-50 dark:bg-blue-900/30 rounded-bl-full -z-10 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="bg-blue-100 dark:bg-blue-900/50 w-16 h-16 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                        <i data-lucide="search" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-lg text-gray-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors">Compatibility Checker</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Find parts for your motorcycle</p>
                    </div>
                </a>

                <!-- Pre-order -->
                <a href="/preorder-parts" class="group bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-cyan-100 dark:border-cyan-900 hover:shadow-md hover:border-cyan-400 dark:hover:border-cyan-500 transition-all active:scale-[0.98] flex flex-col gap-5 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-cyan-50 dark:bg-cyan-900/30 rounded-bl-full -z-10 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="bg-cyan-100 dark:bg-cyan-900/50 w-16 h-16 rounded-2xl flex items-center justify-center text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform">
                        <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-lg text-gray-900 dark:text-white group-hover:text-cyan-700 dark:group-hover:text-cyan-400 transition-colors">Pre-order Part</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Request unavailable parts</p>
                    </div>
                </a>

            </div>

            <!-- Track Repairs Section -->
            <div class="mt-[12rem] pb-16">
                <h2 class="text-xl font-extrabold text-gray-900 dark:text-white mb-12 flex items-center gap-3 uppercase tracking-wider">
                    <div class="w-1.5 h-8 bg-indigo-600 dark:bg-indigo-500 rounded-full shadow-[0_0_15px_rgba(79,70,229,0.4)]"></div>
                    <i data-lucide="wrench" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    Track My Repairs
                </h2>

                @if(isset($repairs) && $repairs->count() > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700 text-[11px] tracking-widest uppercase text-gray-400 dark:text-gray-500">
                                        <th class="p-10 font-bold">Bike Model</th>
                                        <th class="p-10 font-bold">Issue / Details</th>
                                        <th class="p-10 font-bold text-center">Status</th>
                                        <th class="p-10 font-bold text-right">Cost</th>
                                        <th class="p-10 font-bold text-center">Payment</th>
                                        <th class="p-10 font-bold text-right">Date Received</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50 text-sm">
                                    @foreach($repairs as $repair)
                                        <tr class="hover:bg-gray-50/30 dark:hover:bg-gray-700/20 transition-colors group">
                                            <td class="p-10 font-extrabold text-gray-900 dark:text-white">{{ $repair->bike_model }}</td>
                                            <td class="p-10 text-gray-600 dark:text-gray-400 max-w-xs truncate" title="{{ $repair->issue }}">{{ $repair->issue }}</td>
                                            <td class="p-10 text-center">
                                                <div class="flex justify-center">
                                                    @if(strtolower($repair->status) == 'pending')
                                                        <span class="px-5 py-2 rounded-full text-[10px] font-black uppercase bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 ring-1 ring-yellow-200 dark:ring-yellow-800/50 shadow-sm">Pending</span>
                                                    @elseif(in_array(strtolower($repair->status), ['in_progress', 'ongoing', 'working']))
                                                        <span class="px-5 py-2 rounded-full text-[10px] font-black uppercase bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 ring-1 ring-blue-200 dark:ring-blue-800/50 shadow-sm">In Progress</span>
                                                    @elseif(in_array(strtolower($repair->status), ['completed', 'done', 'ready']))
                                                        <span class="px-5 py-2 rounded-full text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800/50 shadow-sm">Completed</span>
                                                    @else
                                                        <span class="px-5 py-2 rounded-full text-[10px] font-black uppercase bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-700/50 shadow-sm">{{ ucfirst($repair->status) }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="p-10 font-black text-gray-900 dark:text-white text-right">₱{{ number_format($repair->cost, 2) }}</td>
                                            <td class="p-10">
                                                <div class="flex justify-center">
                                                    @if($repair->is_paid)
                                                        <span class="text-emerald-600 dark:text-emerald-400 font-black text-[10px] uppercase flex items-center gap-2"><i data-lucide="check-circle" class="w-5 h-5"></i> Paid</span>
                                                    @else
                                                        <span class="text-red-500 dark:text-red-400 font-black text-[10px] uppercase flex items-center gap-2"><i data-lucide="x-circle" class="w-5 h-5"></i> Unpaid</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="p-10 text-gray-500 dark:text-gray-400 text-right font-medium">{{ $repair->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-[3rem] border-2 border-dashed border-gray-100 dark:border-gray-700 p-24 text-center">
                        <div class="w-24 h-24 bg-indigo-50 dark:bg-indigo-900/30 rounded-3xl flex items-center justify-center mx-auto mb-10 shadow-sm border border-indigo-100 dark:border-indigo-800/50">
                            <i data-lucide="wrench" class="w-12 h-12 text-indigo-200 dark:text-indigo-600"></i>
                        </div>
                        <p class="text-gray-900 dark:text-white font-extrabold text-3xl">No active repairs</p>
                        <p class="text-gray-500 dark:text-gray-400 text-lg mt-4 max-w-md mx-auto leading-relaxed">When you drop off a bike for servicing, your progress, costs, and payment status will appear here for real-time tracking.</p>
                    </div>
                @endif
            </div>
            
            <!-- Track Pre-orders Section -->
            <div class="mt-[10rem] pb-24">
                <h2 class="text-xl font-extrabold text-gray-900 dark:text-white mb-12 flex items-center gap-3 uppercase tracking-wider">
                    <div class="w-1.5 h-8 bg-indigo-600 dark:bg-indigo-500 rounded-full shadow-[0_0_15px_rgba(79,70,229,0.4)]"></div>
                    <i data-lucide="clipboard-list" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    Track My Pre-Orders
                </h2>

                @if(isset($preorders) && $preorders->count() > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700 text-[11px] tracking-widest uppercase text-gray-400 dark:text-gray-500">
                                        <th class="p-8 font-bold">Part Name</th>
                                        <th class="p-8 font-bold">Model</th>
                                        <th class="p-8 font-bold text-center">Qty</th>
                                        <th class="p-8 font-bold text-right">Down Payment</th>
                                        <th class="p-8 font-bold text-center">Payment</th>
                                        <th class="p-8 font-bold text-center">Status</th>
                                        <th class="p-8 font-bold text-right">Date Requested</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50 text-sm">
                                    @foreach($preorders as $order)
                                        <tr class="hover:bg-gray-50/30 dark:hover:bg-gray-700/20 transition-colors group">
                                            <td class="p-8 font-extrabold text-gray-900 dark:text-white">{{ $order->part_name }}</td>
                                            <td class="p-8 text-gray-600 dark:text-gray-400">{{ $order->model ?? 'N/A' }}</td>
                                            <td class="p-6 text-center font-black text-gray-900 dark:text-white">{{ $order->quantity }}</td>
                                            <td class="p-8 font-black text-gray-900 dark:text-white text-right">₱{{ number_format($order->down_payment, 2) }}</td>
                                            <td class="p-8">
                                                <div class="flex justify-center">
                                                    @if($order->is_paid)
                                                        <span class="text-emerald-600 dark:text-emerald-400 font-black text-[10px] uppercase flex items-center gap-2"><i data-lucide="check-circle" class="w-5 h-5"></i> Paid</span>
                                                    @else
                                                        <div class="flex flex-col items-center gap-1">
                                                            <span class="text-red-500 dark:text-red-400 font-black text-[10px] uppercase flex items-center gap-2"><i data-lucide="x-circle" class="w-5 h-5"></i> Unpaid</span>
                                                            @if($order->xendit_invoice_url)
                                                                <a href="{{ $order->xendit_invoice_url }}" target="_blank" class="text-[9px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline">Pay Now</a>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="p-8 text-center">
                                                <div class="flex justify-center">
                                                    @if(strtolower($order->status) == 'pending')
                                                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 ring-1 ring-yellow-200 dark:ring-yellow-800/50">Pending Review</span>
                                                    @elseif(in_array(strtolower($order->status), ['approved', 'processing']))
                                                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 ring-1 ring-blue-200 dark:ring-blue-800/50">Sourcing</span>
                                                    @elseif(strtolower($order->status) == 'ready')
                                                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800/50 animate-pulse">Ready</span>
                                                    @elseif(in_array(strtolower($order->status), ['completed', 'done']))
                                                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-700/50">Completed</span>
                                                    @else
                                                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800/50">{{ ucfirst($order->status) }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="p-8 text-gray-500 dark:text-gray-400 text-right font-medium">{{ $order->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] border-2 border-dashed border-gray-100 dark:border-gray-700 p-20 text-center">
                        <div class="w-24 h-24 bg-indigo-50 dark:bg-indigo-900/30 rounded-3xl flex items-center justify-center mx-auto mb-8 shadow-sm border border-indigo-100 dark:border-indigo-800/50">
                            <i data-lucide="clipboard-list" class="w-12 h-12 text-indigo-200 dark:text-indigo-600"></i>
                        </div>
                        <p class="text-gray-900 dark:text-white font-extrabold text-2xl">No active pre-orders</p>
                        <p class="text-gray-500 dark:text-gray-400 text-base mt-3 max-w-sm mx-auto leading-relaxed">Any specialized motorcycle parts you've requested will appear here for tracking once submitted.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>out>