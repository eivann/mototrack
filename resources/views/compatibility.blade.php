<x-app-layout>
<div class="container mt-4">

<h2>Compatibility Checker</h2>

<form method="POST" action="/compatibility">
    @csrf

    <div class="row">
        <div class="col">
            <input type="text" name="model" class="form-control" placeholder="Enter Model (e.g. Mio, Raider)" required>
        </div>
        <div class="col">
            <button class="btn btn-primary">Check</button>
        </div>
    </div>
</form>

@if(isset($items))
    <h4 class="mt-4">Compatible Parts</h4>

    <table class="table table-bordered mt-2">
        <tr>
            <th>Part</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Price</th>
        </tr>

        @forelse($items as $item)
        <tr>
            <td>{{ $item->part_name }}</td>
            <td>{{ $item->brand }}</td>
            <td>{{ $item->model }}</td>
            <td>₱{{ number_format($item->price, 2) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4">No compatible parts found</td>
        </tr>
        @endforelse
    </table>
@endif

</div>
</x-app-layout>