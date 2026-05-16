<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-10 px-4">

    <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-2xl p-8">

        <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            Pre-order Item
        </h2>

        <!-- ITEM INFO -->
        <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-700/50">
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $item->part_name }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Model: {{ $item->model ?? 'N/A' }}</p>
            <p class="text-green-600 dark:text-green-400 font-bold mt-1">₱{{ number_format($item->price, 2) }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 text-red-500 dark:text-red-400 text-sm">
                <ul>@foreach ($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <!-- FORM -->
        <form method="POST" action="/preorder/store">
            @csrf
            <input type="hidden" name="part_name" value="{{ $item->part_name }}">
            <input type="hidden" name="model" value="{{ $item->model }}">
            <input type="hidden" name="price" value="{{ $item->price }}">

            <!-- Quantity -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" value="1" required
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-400 outline-none transition-all">
            </div>

            <!-- Down Payment (Auto Calculated) -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    Required Down Payment (50%)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 uppercase">Auto-calculated</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="text-gray-500 font-bold">₱</span>
                    </div>
                    <input type="number" id="down_payment" name="down_payment" step="0.01" readonly
                        class="w-full border border-gray-200 dark:border-gray-700 rounded-lg pl-8 pr-4 py-2 bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 font-bold outline-none cursor-not-allowed">
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const quantityInput = document.getElementById('quantity');
                    const downPaymentInput = document.getElementById('down_payment');
                    const itemPrice = {{ $item->price }};

                    function calculateDownPayment() {
                        const qty = parseInt(quantityInput.value) || 0;
                        const total = qty * itemPrice;
                        const downPayment = total * 0.50;
                        downPaymentInput.value = downPayment.toFixed(2);
                    }

                    // Calculate initially
                    calculateDownPayment();

                    // Recalculate on input change
                    quantityInput.addEventListener('input', calculateDownPayment);
                });
            </script>

            <!-- Google reCAPTCHA -->
            <div class="mb-4 flex flex-col items-center">
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            </div>

            <!-- Honeypot -->
            <div class="hidden"><input type="text" name="my_extra_field" value=""></div>

            @if(session('success'))
                <div class="mb-4 text-green-600 dark:text-green-400">{{ session('success') }}</div>
            @endif

            <!-- Buttons -->
            <div class="flex justify-between mt-6">
                <a href="/preorder-parts" class="text-gray-600 dark:text-gray-400 hover:underline">← Back</a>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors shadow-sm">
                    Submit Pre-order
                </button>
            </div>
        </form>

    </div>

</div>
</x-app-layout>