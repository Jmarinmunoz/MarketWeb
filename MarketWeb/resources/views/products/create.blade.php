@extends('layouts.app')

@section('content')
<div class="card mw-card">
    <div class="card-header bg-white">Nuevo producto</div>
    <div class="card-body">
        <form action="{{ route('products.store') }}" method="POST">
            @csrf
            @include('products._form_create')
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary rounded-pill">Guardar</button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
