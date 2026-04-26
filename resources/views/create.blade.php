<x-app-layout>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <div class="p-6 max-w-2xl mx-auto">
        <h1 class="text-xl font-bold mb-4">Record Sale</h1>

@if(session('error'))
    <p style="color:red;">{{ session('error') }}</p>
@endif

 <form method="POST" action="/sales/store">
        @csrf

        <label class="text-lg font-semibold">Item:</label>
        <select name="inventory_item_id" id="itemSelect"
    class="w-full border p-3 mb-4 text-lg rounded">
            @foreach($items as $item)
                <option 
    value="{{ $item->id }}" 
    data-price="{{ $item->price }}"
    data-stock="{{ $item->quantity }}"
>
    {{ $item->part_name }} 
    - {{ $item->brand }} 
    - {{ $item->model }} 
    | ₱{{ number_format($item->price, 2) }} 
    | Stock: {{ $item->quantity }}
    {{ $item->quantity <= 0 ? '❌ OUT OF STOCK' : '' }}
</option>
            @endforeach
        </select>

        <label class="text-lg font-semibold">Quantity:</label>
        <input type="number" name="quantity" id="qty"
    class="w-full border p-3 mb-4 text-lg rounded" required>

    <p id="stockWarning" class="text-red-500 text-sm mb-3"></p>

        <label class="text-lg font-semibold">Total Price:</label>
        <input type="text" id="total"
    class="w-full border p-3 mb-4 text-lg rounded bg-gray-100" readonly>

       <div class="mt-4">
    <button type="submit"
        class="bg-blue-600 text-white px-6 py-3 text-lg rounded-lg hover:bg-blue-700">
        Record Sale
    </button>
</div>
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
    </form>


<script>
document.addEventListener('DOMContentLoaded', function () {

new TomSelect("#itemSelect", {
    create: false,
    maxOptions: null, 
    searchField: ["text"],


        render: {
            option: function(data, escape) {
                let stock = data.text.match(/Stock: (\d+)/);
                stock = stock ? parseInt(stock[1]) : 0;

                let color = "black";
                if (stock <= 0) color = "red";
                else if (stock <= 3) color = "orange";

                return `<div style="color:${color};">
                    ${escape(data.text)}
                </div>`;
            }
        }
});
    const select = document.getElementById('itemSelect');
    const qty = document.getElementById('qty');
    const total = document.getElementById('total');
    const warning = document.getElementById('stockWarning');

    function updateTotal() {
        const price = parseFloat(select.options[select.selectedIndex].dataset.price || 0);
        const stock = parseInt(select.options[select.selectedIndex].dataset.stock || 0);
        const quantity = parseInt(qty.value || 0);

        total.value = (price * quantity).toFixed(2);

  if (quantity > stock || stock <= 0) {
        e.preventDefault();
        alert("Cannot proceed: Not enough stock!");
    }else {
    warning.textContent = "";
}
    }

    select.addEventListener('change', updateTotal);
    qty.addEventListener('input', updateTotal);

});
</script>

</div>
</x-app-layout>