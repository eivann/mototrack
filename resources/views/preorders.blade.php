<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pre-orders
        </h2>
    </x-slot>

    <div class="p-6 max-w-7xl mx-auto">

        <div class="bg-white shadow rounded overflow-hidden">

            <table class="w-full text-sm text-left">

                <!-- HEADER -->
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Customer</th>
                        <th class="p-3">Part</th>
                        <th class="p-3">Model</th>
                        <th class="p-3 text-center">Qty</th>
                        <th class="p-3 text-center">Down Payment</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>

                <!-- BODY -->
                <tbody>
                    @forelse($preorders as $order)
                        <tr class="border-t hover:bg-gray-50">

                            <td class="p-3">{{ $order->id }}</td>
                            <td class="p-3">{{ $order->customer_name }}</td>
                            <td class="p-3">{{ $order->part_name }}</td>
                            <td class="p-3">{{ $order->model }}</td>

                            <td class="p-3 text-center">{{ $order->quantity }}</td>

                            <td class="p-3 text-center">
                                ₱{{ number_format($order->down_payment, 2) }}
                            </td>

                            <!-- STATUS -->
                            <td class="p-3 text-center">
                                <span class="px-2 py-1 rounded text-white text-xs
                                            {{ $order->status == 'pending' ? 'bg-yellow-500' : '' }}
                                            {{ $order->status == 'completed' ? 'bg-green-600' : '' }}
                                            {{ $order->status == 'cancelled' ? 'bg-red-600' : '' }}
                                        ">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>

                            <!-- ACTION -->
                            <td class="p-3">
                                <div class="flex justify-center gap-2">

                                    <!-- COMPLETE -->
                                    <form method="POST" action="/preorders/{{ $order->id }}/complete">
                                        @csrf
                                        <button
                                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                            Complete
                                        </button>
                                    </form>

                                    <!-- CANCEL -->
                                    <form method="POST" action="/preorders/{{ $order->id }}/cancel">
                                        @csrf
                                        <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                            Cancel
                                        </button>
                                    </form>

                                    <!-- DELETE -->
                                    <form method="POST" action="/preorders/{{ $order->id }}"
                                        onsubmit="return confirm('Delete this pre-order?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="bg-gray-700 hover:bg-gray-800 text-white px-3 py-1 rounded text-sm">
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-gray-500">
                                No pre-orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

        </div>

    </div>

</x-app-layout>