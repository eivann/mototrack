<x-app-layout>
<div class="max-w-xl mx-auto p-6">
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl shadow p-6">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Custom Pre-order</h2>

        <form method="POST" action="/preorder/store">
            @csrf

            <div class="mb-3">
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Part Name</label>
                <input type="text" name="part_name" class="w-full border dark:border-gray-600 p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white" required>
            </div>

            <div class="mb-3">
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
                <input type="text" name="model" class="w-full border dark:border-gray-600 p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <div class="mb-3">
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                <input type="number" name="quantity" class="w-full border dark:border-gray-600 p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white" required>
            </div>

            <div class="mb-3">
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Down Payment</label>
                <input type="number" name="down_payment" class="w-full border dark:border-gray-600 p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <!-- Google reCAPTCHA -->
            <div class="mb-4 flex flex-col items-center">
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            </div>

            <!-- Honeypot (Invisible Trap) -->
            <div class="hidden">
                <input type="text" name="my_extra_field" value="">
            </div>

            <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Submit Request
            </button>
        </form>
    </div>
</div>
</x-app-layout>