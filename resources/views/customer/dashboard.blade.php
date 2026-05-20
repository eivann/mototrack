<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-12">
        <!-- Premium Header -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 sticky top-0 z-30 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-600 to-indigo-900 rounded-2xl flex items-center justify-center shadow-lg">
                            <span class="text-white text-2xl font-black">C</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tighter">MOTOTRACK</h2>
                            <p class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.2em]">Customer Dashboard</p>
                        </div>
                    </div>
                    

                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row gap-8 items-start">
                
                <!-- Main Content (Left) -->
                <div class="w-full md:flex-1 space-y-8 min-w-0 order-2 md:order-1">
                    
                    <!-- Welcome Hero Card -->
                    <div class="bg-indigo-600 rounded-[3rem] p-10 text-white relative overflow-hidden shadow-2xl shadow-indigo-300 dark:shadow-none">
                        <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                        <div class="relative z-10">
                            <h3 class="text-4xl font-black tracking-tighter mb-4">Welcome back, {{ explode(' ', Auth::user()->name)[0] }}!</h3>
                            <p class="text-indigo-100 text-lg max-w-md font-medium leading-relaxed mb-8">Ready to upgrade your ride? Track your active repairs, view pre-orders, and browse our massive catalog of premium parts.</p>
                            <div class="flex flex-wrap gap-4">
                                <a href="/browse" class="px-8 py-4 bg-white text-indigo-600 rounded-2xl font-black text-sm hover:scale-105 transition-all shadow-xl inline-block focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">Shop Now</a>
                                <a href="/compatibility" class="px-8 py-4 bg-indigo-500/50 text-white border border-white/20 rounded-2xl font-black text-sm hover:bg-indigo-400 transition-all inline-block focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">Check Compatibility</a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <a href="/browse" class="group bg-white dark:bg-gray-900 p-6 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all relative overflow-hidden flex items-center gap-6 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform shrink-0">
                                <i data-lucide="shopping-bag" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-lg text-gray-900 dark:text-white uppercase tracking-tight">Browse Parts</h4>
                                <p class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-widest">View catalog</p>
                            </div>
                        </a>
                        <a href="/compatibility" class="group bg-white dark:bg-gray-900 p-6 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all relative overflow-hidden flex items-center gap-6 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform shrink-0">
                                <i data-lucide="search" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-lg text-gray-900 dark:text-white uppercase tracking-tight">Compatibility</h4>
                                <p class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-widest">Find your fit</p>
                            </div>
                        </a>
                        <a href="/preorder-parts" class="group bg-white dark:bg-gray-900 p-6 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all relative overflow-hidden flex items-center gap-6 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2">
                            <div class="w-16 h-16 rounded-2xl bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center text-cyan-600 group-hover:scale-110 transition-transform shrink-0">
                                <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-lg text-gray-900 dark:text-white uppercase tracking-tight">Pre-Order</h4>
                                <p class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-widest">Request parts</p>
                            </div>
                        </a>
                        <a href="/cart" class="group bg-white dark:bg-gray-900 p-6 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all relative overflow-hidden flex items-center gap-6 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform shrink-0">
                                <i data-lucide="shopping-cart" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-lg text-gray-900 dark:text-white uppercase tracking-tight">My Cart</h4>
                                <p class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-widest">Checkout now</p>
                            </div>
                        </a>
                    </div>

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
                                                <td class="p-6 text-sm text-gray-500">{{ Str::limit($repair->issue, 30) }}</td>
                                                <td class="p-6">
                                                    <span class="px-3 py-1 rounded-lg {{ strtolower($repair->status) === 'pending' ? 'bg-yellow-50 text-yellow-600' : (in_array(strtolower($repair->status), ['completed','done','ready']) ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600') }} font-black text-[10px] uppercase tracking-widest">
                                                        {{ $repair->status }}
                                                    </span>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">₱{{ number_format($repair->cost, 2) }}</td>
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
                                                <td class="p-6 font-bold text-gray-900 dark:text-white">{{ $order->part_name }}</td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-center">x{{ $order->quantity }}</td>
                                                <td class="p-6">
                                                    <span class="px-3 py-1 rounded-lg {{ strtolower($order->status) === 'pending' ? 'bg-yellow-50 text-yellow-600' : (in_array(strtolower($order->status), ['completed','done','ready']) ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600') }} font-black text-[10px] uppercase tracking-widest">
                                                        {{ $order->status }}
                                                    </span>
                                                </td>
                                                <td class="p-6 font-black text-gray-900 dark:text-white text-right">₱{{ number_format($order->down_payment, 2) }}</td>
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

                <!-- Sidebar (Right) -->
                <div class="w-full md:w-80 space-y-8 flex-shrink-0 order-1 md:order-2">
                    
                    <!-- Pro Tip -->
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-8 rounded-[2.5rem] shadow-xl text-white relative overflow-hidden group">
                        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                        <h4 class="text-xl font-black mb-2 tracking-tighter uppercase">Rider Tip</h4>
                        <p class="text-xs text-gray-300 mb-8 font-medium leading-relaxed">
                            Not sure if a part fits your motorcycle model? Use our intelligent compatibility checker before purchasing to guarantee a perfect fit.
                        </p>
                        <a href="/compatibility" class="block text-center w-full py-4 bg-white text-gray-900 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl active:scale-95 transition-all">Check Now</a>
                    </div>

                    <!-- Live Notifications -->
                    <div class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-800">
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-indigo-600 rounded-full animate-ping"></span>
                            Recent Updates
                        </h3>
                        <div class="space-y-4">
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                @foreach(auth()->user()->unreadNotifications->take(5) as $notification)
                                    <div class="flex gap-4 group">
                                        <div class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 p-2 rounded-xl h-fit shrink-0">
                                            <i data-lucide="{{ str_contains(strtolower($notification->data['title']), 'repair') ? 'wrench' : 'package' }}" class="w-4 h-4"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-[11px] font-black text-gray-900 dark:text-white uppercase tracking-tight leading-tight">{{ $notification->data['title'] }}</div>
                                            <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 leading-tight">{{ $notification->data['message'] }}</div>
                                            <div class="text-[9px] font-bold text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="mt-2 text-right">
                                        @csrf
                                        <button type="submit" @click="hapticPulse()" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline py-3 px-4 -mr-4 inline-block focus:outline-none focus:ring-2 focus:ring-indigo-400 rounded">Mark Read</button>
                                    </form>
                                    @if(!$loop->last)
                                        <hr class="border-gray-100 dark:border-gray-800">
                                    @endif
                                @endforeach
                            @else
                                <div class="text-center py-4 text-gray-400 text-xs font-bold italic">No new updates</div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>