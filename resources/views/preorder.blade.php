<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-10 px-4">

    <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-2xl p-8">

        <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">📦 Pre-order Item</h2>

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

            <!-- Quantity -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Quantity</label>
                <input type="number" name="quantity" min="1" required
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-400 outline-none">
            </div>

            <!-- Down Payment -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Down Payment</label>
                <input type="number" name="down_payment" min="0"
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-400 outline-none">
            </div>

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