@extends('layouts.app')

@section('content')
<div class="card mw-card">
    <div class="card-header bg-white">Nueva categoría</div>
    <div class="card-body">
        <form action="{{ route('categories.store') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-8">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="1">Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary rounded-pill">Guardar</button>
                <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
