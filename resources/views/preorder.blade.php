<x-app-layout>
<div class="container mt-4">

    <h2>Pre-order Item</h2>

    <form method="POST" action="/preorder">
        @csrf

        <!-- Hidden -->
        <input type="hidden" name="part_name" value="{{ $item->part_name }}">
        <input type="hidden" name="model" value="{{ $item->model }}">

        <div class="mb-3">
            <label>Part</label>
            <input type="text" value="{{ $item->part_name }}" disabled>
        </div>

        <div class="mb-3">
            <label>Quantity</label>
            <input type="number" name="quantity" required>
        </div>

        <div class="mb-3">
            <label>Down Payment</label>
            <input type="number" name="down_payment">
        </div>

        <button type="submit">Submit Pre-order</button>
    </form>

</div>
</x-app-layout>