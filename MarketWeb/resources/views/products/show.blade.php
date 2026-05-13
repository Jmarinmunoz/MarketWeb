@extends('layouts.app')

@section('content')
<div class="card mw-card">
    <div class="card-header bg-white d-flex justify-content-between">
        <span>Detalle de producto</span>
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">Volver</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><strong>Nombre:</strong> {{ $product->name }}</div>
            <div class="col-md-6"><strong>Categoría:</strong> {{ $product->category?->name }}</div>
            <div class="col-md-6"><strong>Código barra:</strong> {{ $product->barcode ?? '-' }}</div>
            <div class="col-md-4"><strong>Precio compra:</strong> ${{ number_format($product->purchase_price, 0, ',', '.') }}</div>
            <div class="col-md-4"><strong>Precio venta:</strong> ${{ number_format($product->sale_price, 0, ',', '.') }}</div>
            <div class="col-md-4"><strong>Stock:</strong> {{ $product->stock }}</div>
            <div class="col-12"><strong>Descripción:</strong> {{ $product->description ?: '-' }}</div>
        </div>
    </div>
</div>
@endsection
