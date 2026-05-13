@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Categorías</h4>
    <a href="{{ route('categories.create') }}" class="btn btn-primary rounded-pill">
        <i class="fa-solid fa-plus me-1"></i>Nueva categoría
    </a>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card mw-card">
    <div class="card-body">
        <table class="table table-striped align-middle js-datatable">
            <thead>
                <tr>
                    <th>ID local</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $category)
                    <tr>
                        <td>{{ $category->local_id ?? $category->id }}</td>
                        <td>{{ $category->name }}</td>
                        <td>
                            <span class="badge {{ $category->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $category->status ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                Editar
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
