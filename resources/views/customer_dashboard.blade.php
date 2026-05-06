<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Customer Dashboard
        </h2>
    </x-slot>

    <div class="p-6 max-w-7xl mx-auto">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- Browse Parts -->
            <a href="/browse" class="bg-white shadow rounded-lg p-6 hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold mb-2">Browse Parts</h3>
                <p class="text-gray-500 text-sm">View available motorcycle parts</p>
            </a>

            <!-- Cart -->
            <a href="/cart" class="bg-white shadow rounded-lg p-6 hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold mb-2">View Cart</h3>
                <p class="text-gray-500 text-sm">Check items in your cart</p>
            </a>

            <!-- Compatibility -->
            <a href="/compatibility" class="bg-white shadow rounded-lg p-6 hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold mb-2">Compatibility Checker</h3>
                <p class="text-gray-500 text-sm">Find parts for your motorcycle</p>
            </a>

            <!-- Pre-order -->
            <a href="/preorder-parts" class="bg-white shadow rounded-lg p-6 hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold mb-2">Pre-order Part</h3>
                <p class="text-gray-500 text-sm">Request unavailable parts</p>
            </a>

        </div>

        <!-- Track Repairs Section -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Track My Repairs
            </h2>

            @if(isset($repairs) && $repairs->count() > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs tracking-wider uppercase text-gray-500">
                                    <th class="p-5 font-semibold">Bike Model</th>
                                    <th class="p-5 font-semibold">Issue / Details</th>
                                    <th class="p-5 font-semibold">Status</th>
                                    <th class="p-5 font-semibold">Cost</th>
                                    <th class="p-5 font-semibold">Payment</th>
                                    <th class="p-5 font-semibold">Date Received</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                @foreach($repairs as $repair)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="p-5 font-semibold text-gray-900">{{ $repair->bike_model }}</td>
                                        <td class="p-5 text-gray-600 max-w-xs truncate" title="{{ $repair->issue }}">{{ $repair->issue }}</td>
                                        <td class="p-5">
                                            @if(strtolower($repair->status) == 'pending')
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pending</span>
                                            @elseif(in_array(strtolower($repair->status), ['in_progress', 'ongoing', 'working']))
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">In Progress</span>
                                            @elseif(in_array(strtolower($repair->status), ['completed', 'done', 'ready']))
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Completed</span>
                                            @else
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">{{ ucfirst($repair->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="p-5 font-medium text-gray-900">₱{{ number_format($repair->cost, 2) }}</td>
                                        <td class="p-5">
                                            @if($repair->is_paid)
                                                <span class="text-green-600 font-semibold flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Paid</span>
                                            @else
                                                <span class="text-red-500 font-semibold flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Unpaid</span>
                                            @endif
                                        </td>
                                        <td class="p-5 text-gray-500">{{ $repair->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-gray-50 border border-dashed border-gray-300 rounded-2xl p-10 text-center">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <p class="text-gray-500 font-medium">You don't have any repairs currently being tracked.</p>
                    <p class="text-gray-400 text-sm mt-1">If you dropped off a motorcycle recently, it will appear here soon.</p>
                </div>
            @endif
        </div>
        
        <!-- Track Pre-orders Section -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                Track My Pre-Orders
            </h2>

            @if(isset($preorders) && $preorders->count() > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs tracking-wider uppercase text-gray-500">
                                    <th class="p-5 font-semibold">Part Name</th>
                                    <th class="p-5 font-semibold">Model</th>
                                    <th class="p-5 font-semibold text-center">Quantity</th>
                                    <th class="p-5 font-semibold">Down Payment</th>
                                    <th class="p-5 font-semibold">Status</th>
                                    <th class="p-5 font-semibold">Date Requested</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                @foreach($preorders as $order)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="p-5 font-semibold text-gray-900">{{ $order->part_name }}</td>
                                        <td class="p-5 text-gray-600">{{ $order->model ?? 'N/A' }}</td>
                                        <td class="p-5 text-center font-medium">{{ $order->quantity }}</td>
                                        <td class="p-5 font-medium text-gray-900">₱{{ number_format($order->down_payment, 2) }}</td>
                                        <td class="p-5">
                                            @if(strtolower($order->status) == 'pending')
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pending Review</span>
                                            @elseif(in_array(strtolower($order->status), ['approved', 'processing']))
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Sourcing Part</span>
                                            @elseif(strtolower($order->status) == 'ready')
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 animate-pulse">Ready for Pickup</span>
                                            @elseif(in_array(strtolower($order->status), ['completed', 'done']))
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Completed</span>
                                            @else
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">{{ ucfirst($order->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="p-5 text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-gray-50 border border-dashed border-gray-300 rounded-2xl p-10 text-center">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12H4M8 16l-4-4 4-4"></path></svg>
                    <p class="text-gray-500 font-medium">You don't have any active pre-orders.</p>
                    <p class="text-gray-400 text-sm mt-1">If you need a specific part we don't have in stock, you can request it above.</p>
                </div>
            @endif
        </div>

    </div>

</x-app-layout>