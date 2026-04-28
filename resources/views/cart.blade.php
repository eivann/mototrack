<x-app-layout>
<div class="container mt-4">

<h2>Your Cart</h2>

@if(session('cart'))
<table class="table table-bordered">
    <tr>
        <th>Item</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Total</th>
    </tr>

    @foreach($cart as $item)
    <tr>
        <td>{{ $item['name'] }}</td>
        <td>₱{{ number_format($item['price'], 2) }}</td>
        <td>{{ $item['quantity'] }}</td>
        <td>₱{{ number_format($item['price'] * $item['quantity'], 2) }}</td>
    </tr>
    @endforeach
</table>

<a href="/checkout" class="btn btn-primary">Checkout</a>

@else
<p>Your cart is empty</p>
@endif

</div>
</x-app-layout>