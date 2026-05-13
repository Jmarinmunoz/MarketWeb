@extends('layouts.app')

@section('content')
<div class="card mw-card">
    <div class="card-header bg-white">Nuevo usuario</div>
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            @include('users._form')
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary rounded-pill">Guardar</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
