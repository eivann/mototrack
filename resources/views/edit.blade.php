<!DOCTYPE html>
<html>
<head>
    <title>Edit Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">
    <h2>Edit Item</h2>

    <div class="card p-4">
        <form method="POST" action="/inventory/{{ $item->id }}">
            @csrf
            @method('PUT')

            <div class="row mb-2">
                <div class="col">
                    <label>Category</label>
                    <input type="text" name="category" class="form-control" value="{{ $item->category }}">
                </div>
                <div class="col">
                    <label>Part Name</label>
                    <input type="text" name="part_name" class="form-control" value="{{ $item->part_name }}">
                </div>
                <div class="col">
                    <label>Brand</label>
                    <input type="text" name="brand" class="form-control" value="{{ $item->brand }}">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col">
                    <label>Model</label>
                    <input type="text" name="model" class="form-control" value="{{ $item->model }}">
                </div>
                <div class="col">
                    <label>Quantity</label>
                    <input type="number" name="quantity" class="form-control" value="{{ $item->quantity }}">
                </div>
                <div class="col">
                    <label>Price</label>
                    <input type="number" name="price" class="form-control" value="{{ $item->price }}">
                </div>
            </div>

            <div class="mb-3">
                <label>Notes</label>
                <input type="text" name="notes" class="form-control" value="{{ $item->notes }}">
            </div>

            <button class="btn btn-primary">Update</button>
            <a href="/inventory" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

</body>
</html>