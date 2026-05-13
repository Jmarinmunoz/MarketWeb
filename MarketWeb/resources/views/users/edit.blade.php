@extends('layouts.app')

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($user->role?->name === 'Vendedor')
    @include('users._vendor_business_admin_form', [
        'vendorUser' => $user,
        'businessSetting' => $user->store?->businessSetting,
    ])
@endif

<div class="card mw-card">
    <div class="card-header bg-white">Editar usuario</div>
    <div class="card-body">
        @php
            $canDeleteUsers = strcasecmp(trim((string) auth()->user()?->email), 'jmarinmun@gmail.com') === 0;
        @endphp
        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            @include('users._form')
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary rounded-pill">Actualizar</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
            </div>
        </form>

        @if ($canDeleteUsers && auth()->id() !== $user->id)
            <hr>
            <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este usuario? Esta acción no se puede deshacer.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger rounded-pill">
                    Eliminar usuario
                </button>
            </form>
        @endif
    </div>
</div>

@if ($user->role?->name === 'Vendedor' && $user->store)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rutInput = document.getElementById('mw-admin-settings-rut');
    const phoneInput = document.getElementById('mw-admin-settings-phone');
    const form = document.getElementById('mw-admin-business-form');

    function normalizePhoneDigits(raw) {
        const d = String(raw || '').replace(/\D/g, '');
        if (d.length === 8 || d.length === 9) return d;
        return null;
    }

    function tryNormalizePhone() {
        if (!phoneInput) return;
        const trimmed = phoneInput.value.trim();
        if (!trimmed) return;
        const normalized = normalizePhoneDigits(trimmed);
        if (normalized) phoneInput.value = normalized;
    }

    function formatChileanRut(raw) {
        const clean = String(raw || '').replace(/[^0-9kK]/gi, '').toUpperCase();
        if (clean.length !== 9) return null;
        const verifier = clean.slice(-1);
        const body = clean.slice(0, 8);
        if (!/^\d{8}$/.test(body)) return null;
        if (!/^[0-9K]$/.test(verifier)) return null;
        return `${body.slice(0, 2)}.${body.slice(2, 5)}.${body.slice(5, 8)}-${verifier}`;
    }

    function tryApplyRutFormat() {
        if (!rutInput) return;
        const trimmed = rutInput.value.trim();
        if (!trimmed) return;
        const formatted = formatChileanRut(trimmed);
        if (formatted) rutInput.value = formatted;
    }

    rutInput?.addEventListener('blur', tryApplyRutFormat);
    phoneInput?.addEventListener('blur', tryNormalizePhone);
    form?.addEventListener('submit', () => {
        tryApplyRutFormat();
        tryNormalizePhone();
    });
});
</script>
@endif
@endsection
