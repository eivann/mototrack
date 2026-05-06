<x-app-layout>
<div class="max-w-xl mx-auto p-6 bg-white rounded shadow">

    <h2 class="text-xl font-bold mb-4">Custom Pre-order</h2>

    <form method="POST" action="/preorder/store">
        @csrf

        <div class="mb-3">
            <label>Part Name</label>
            <input type="text" name="part_name" class="w-full border p-2" required>
        </div>

        <div class="mb-3">
            <label>Model</label>
            <input type="text" name="model" class="w-full border p-2">
        </div>

        <div class="mb-3">
            <label>Quantity</label>
            <input type="number" name="quantity" class="w-full border p-2" required>
        </div>

        <div class="mb-3">
            <label>Down Payment</label>
            <input type="number" name="down_payment" class="w-full border p-2">
        </div>

        <!-- Google reCAPTCHA -->
        <div class="mb-4 flex flex-col items-center">
            <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
        </div>

        <!-- Honeypot (Invisible Trap) -->
        <div class="hidden">
            <input type="text" name="my_extra_field" value="">
        </div>

        <button class="bg-green-500 text-white px-4 py-2 rounded">
            Submit Request
        </button>

    </form>

</div>
</x-app-layout>