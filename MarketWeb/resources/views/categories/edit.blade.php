@extends('layouts.app')

@section('content')
<div class="card mw-card">
    <div class="card-header bg-white">Editar categoría</div>
    <div class="card-body">
        <form action="{{ route('categories.update', $category) }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-8">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="1" @selected(old('status', (int) $category->status) === 1)>Activa</option>
                    <option value="0" @selected(old('status', (int) $category->status) === 0)>Inactiva</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary rounded-pill">Actualizar</button>
                <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
