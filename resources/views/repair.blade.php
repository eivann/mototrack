<x-app-layout>
    <div class="p-6">

        <h2 class="text-xl font-bold mb-4">Track Repairs</h2>

        <form method="POST" action="/repairs" class="mb-6 flex flex-wrap gap-3 items-end">
            @csrf

            <div> <label class="text-sm">Customer</label> <input name="customer_name" placeholder="Customer Name"
                    class="border p-2 rounded w-48"> </div>
            <div> <label class="text-sm">Bike</label> <input name="bike_model" placeholder="Bike Model"
                    class="border p-2 rounded w-40"> </div>
            <div> <label class="text-sm">Issue</label> <input name="issue" placeholder="Issue"
                    class="border p-2 rounded w-52"> </div>
            <div> <label class="text-sm">Cost</label> <input name="cost" type="number" step="0.01"
                    class="border p-2 rounded w-32"> </div> <button
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"> Add </button>
        </form>

        <form method="GET" class="mb-4 flex gap-2">
            <select name="status" onchange="this.form.submit()"
                class="w-48 border border-gray-300 bg-white text-gray-800 px-3 py-2 rounded">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="ongoing">Ongoing</option>
                <option value="done">Done</option>
            </select>

            <div class="relative inline-block">
                <select class="w-48 border border-gray-300 bg-white text-gray-800 px-3 py-2 rounded">
                    <option>Payment</option>
                    <option>Paid</option>
                    <option>Unpaid</option>
                </select>

                <!-- SVG arrow -->
                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <button class="bg-blue-700 text-blue px-4 py-2 rounded hover:bg-gray-800">
                Filter
            </button>
        </form>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <tr class="bg-gray-100 text-gray-700">
                    <th class="p-3">Customer</th>
                    <th class="p-3">Bike</th>
                    <th class="p-3">Issue</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Cost</th>
                    <th class="p-3">Action</th>
                </tr> @foreach($repairs as $repair) <tr class="border-t hover:bg-gray-50">
                            <td class="p-3">{{ $repair->customer_name }}</td>
                            <td class="p-3">{{ $repair->bike_model }}</td>
                            <td class="p-3">{{ $repair->issue }}</td>
                            <td class="p-3">
                                <form method="POST" action="/repairs/{{ $repair->id }}">
                                    @csrf
                                    @method('PUT')

                                   <select name="status" onchange="this.form.submit()"
    class="px-3 py-1 rounded text-white text-xs border-0
    {{ $repair->status == 'pending' ? 'bg-yellow-500' : '' }}
    {{ $repair->status == 'ongoing' ? 'bg-blue-500' : '' }}
    {{ $repair->status == 'done' ? 'bg-green-600' : '' }}">

    <option value="pending" {{ $repair->status == 'pending' ? 'selected' : '' }} class="text-black bg-white">
        Pending
    </option>

    <option value="ongoing" {{ $repair->status == 'ongoing' ? 'selected' : '' }} class="text-black bg-white">
        Ongoing
    </option>

    <option value="done" {{ $repair->status == 'done' ? 'selected' : '' }} class="text-black bg-white">
        Done
    </option>

</select>

                                </form>
                            </td>
                            <td class="p-3">₱{{ number_format($repair->cost ?? 0, 2) }}</td>
                            <td class="p-3 flex gap-2">

                                @if(!$repair->is_paid)
                                    <form method="POST" action="/repairs/{{ $repair->id }}/pay">
                                        @csrf
                                        @method('PUT')
                                        <button class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600">
                                            Pay
                                        </button>
                                    </form>
                                @else
                                    <span class="text-green-600 text-xs font-bold">Paid</span>
                                @endif

                                <a href="/repairs/{{ $repair->id }}/receipt"
                                    class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">
                                    Receipt
                                </a>

                                <form method="POST" action="/repairs/{{ $repair->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">
                                        Delete
                                    </button>
                                </form>


                            </td>

                </tr> @endforeach

            </table>
        </div>


    </div>
</x-app-layout>