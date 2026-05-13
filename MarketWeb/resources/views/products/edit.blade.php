@extends('layouts.app')

@section('content')
<div class="card mw-card">
    <div class="card-header bg-white">Editar producto</div>
    <div class="card-body">
        <form action="{{ route('products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')
            @include('products._form')
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary rounded-pill">Actualizar</button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
