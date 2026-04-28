<x-app-layout>
<div class="container mt-4">

<h2>Checkout</h2>

@if(session('cart'))
<table class="table table-bordered">
    <tr>
        <th>Item</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Total</th>
    </tr>

    @php $grandTotal = 0; @endphp

    @foreach($cart as $item)
    @php $total = $item['price'] * $item['quantity']; @endphp
    @php $grandTotal += $total; @endphp

    <tr>
        <td>{{ $item['name'] }}</td>
        <td>₱{{ number_format($item['price'], 2) }}</td>
        <td>{{ $item['quantity'] }}</td>
        <td>₱{{ number_format($total, 2) }}</td>
    </tr>
    @endforeach
</table>

<h4>Total: ₱{{ number_format($grandTotal, 2) }}</h4>

<form method="POST" action="/checkout">
    @csrf
    <button class="btn btn-success">Confirm Order</button>
</form>

@else
<p>Cart is empty</p>
@endif

</div>
</x-app-layout>