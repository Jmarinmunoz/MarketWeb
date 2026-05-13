@extends('layouts.app')

@section('content')
<div class="card mw-card mb-4">
    <div class="card-header bg-white">Configuración del negocio</div>
    <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('password_status'))
            <div class="alert alert-{{ str_contains(session('password_status'), 'No se indicó') ? 'info' : 'success' }}">{{ session('password_status') }}</div>
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

        @if (!empty($vendorBusinessLocked))
            <div class="alert alert-info mb-3 mb-md-4">
                Ya registraste los datos de tu negocio una vez. Solo el <strong>administrador</strong> puede modificarlos desde tu ficha de usuario.
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-muted mb-1">Nombre del negocio</div>
                    <div class="fw-medium">{{ $setting->business_name ?: '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted mb-1">RUT del negocio</div>
                    <div class="fw-medium">{{ $setting->rut ?: '—' }}</div>
                </div>
                <div class="col-12">
                    <div class="small text-muted mb-1">Dirección del negocio</div>
                    <div class="fw-medium">{{ $setting->address ?: '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted mb-1">Teléfono del negocio</div>
                    <div class="fw-medium">{{ $setting->phone ?: '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted mb-1">Correo del negocio</div>
                    <div class="fw-medium">{{ $setting->email ?: auth()->user()->email }}</div>
                </div>
            </div>
        @else
            <p class="text-muted small mb-3">Completa estos datos una sola vez. El RUT puede escribirse solo con números (ej. <strong>771234567</strong>) y se guardará como <strong>77.123.456-7</strong>. El teléfono debe tener <strong>8</strong> o <strong>9</strong> dígitos. El correo del negocio es el de tu cuenta.</p>
            <form action="{{ route('settings.update') }}" method="POST" class="row g-3" id="mw-business-settings-form">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">Nombre del negocio</label>
                    <input type="text" name="business_name" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name', $setting->business_name ?? '') }}" required maxlength="255" autocomplete="organization">
                    @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">RUT del negocio</label>
                    <input type="text" name="rut" id="mw-settings-rut" class="form-control @error('rut') is-invalid @enderror" value="{{ old('rut', $setting->rut ?? '') }}" required maxlength="30" autocomplete="off" placeholder="77.123.456-7 o 771234567">
                    @error('rut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Dirección del negocio</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $setting->address ?? '') }}" required maxlength="255" autocomplete="street-address">
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono del negocio</label>
                    <input type="text" name="phone" id="mw-settings-phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $setting->phone ?? '') }}" required maxlength="20" inputmode="numeric" autocomplete="tel" placeholder="Ej.: 951466774 (9) o 21234567 (8)">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo del negocio</label>
                    <input type="text" class="form-control bg-light @error('email') is-invalid @enderror" value="{{ auth()->user()->email }}" readonly tabindex="-1" aria-readonly="true" autocomplete="email">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Coincide con el correo de tu cuenta de usuario.</div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary rounded-pill">Guardar configuración</button>
                </div>
            </form>
        @endif
    </div>
</div>

<div class="card mw-card">
    <div class="card-body">
        <p class="text-muted small mb-3 mb-md-2">Cambiar contraseña (opcional)</p>
        <form action="{{ route('settings.password') }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nueva contraseña</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Confirmar nueva contraseña</label>
                <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary rounded-pill">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

@if (empty($vendorBusinessLocked))
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rutInput = document.getElementById('mw-settings-rut');
    const phoneInput = document.getElementById('mw-settings-phone');
    const form = document.getElementById('mw-business-settings-form');

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
