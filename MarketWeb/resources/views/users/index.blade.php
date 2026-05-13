@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Usuarios</h4>
    <a href="{{ route('users.create') }}" class="btn btn-primary rounded-pill">
        <i class="fa-solid fa-user-plus me-1"></i>Nuevo usuario
    </a>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<p class="text-muted small mb-2">Pulsa sobre una fila para abrir la edición del usuario.</p>

<div class="card mw-card">
    <div class="card-body">
        <table id="mw-users-table" class="table table-striped table-hover align-middle js-datatable">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Local</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="mw-user-row" role="button" tabindex="0" style="cursor: pointer;" data-edit-url="{{ route('users.edit', $user) }}" title="Editar usuario">
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role?->name }}</td>
                        <td>
                            @if ($user->role?->name === 'Administrador')
                                <span class="text-muted">—</span>
                            @else
                                <span class="text-muted">{{ $user->store?->businessSetting?->business_name ?: ($user->store?->name ?? '—') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $user->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $user->status ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('mw-users-table');
    if (!table) return;

    table.addEventListener('click', (e) => {
        const tr = e.target.closest('tbody tr[data-edit-url]');
        if (!tr) return;
        window.location.href = tr.dataset.editUrl;
    });

    table.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') return;
        const tr = e.target.closest('tbody tr[data-edit-url]');
        if (tr) window.location.href = tr.dataset.editUrl;
    });
});
</script>
@endsection
