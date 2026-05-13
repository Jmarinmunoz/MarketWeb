@extends('layouts.app')

@section('content')
<section class="mw-login-page">
    <div class="row g-0 min-vh-100">
        <div class="col-lg-6 d-none d-lg-flex mw-login-hero">
            <div class="mw-login-hero-content">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="mw-login-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <h1 class="mb-0">Market</h1>
                </div>
                <p class="h5 fw-normal mb-4">Sistema de inventario y ventas</p>
                <p class="mw-login-hero-text">Gestiona tus productos, controla tu inventario y aumenta tus ventas desde un solo lugar.</p>
                <div class="mw-login-illustration mt-4">
                    <img src="{{ asset('images/login-hero.png') }}" alt="Ilustración Market" class="img-fluid">
                </div>
                <div class="mw-login-feature-grid mt-5">
                    <div class="mw-login-feature-item">
                        <div class="mw-login-feature-title"><i class="fa-solid fa-box-open me-2"></i>Inventario</div>
                        <small>Controla tus productos y existencias en tiempo real.</small>
                    </div>
                    <div class="mw-login-feature-item">
                        <div class="mw-login-feature-title"><i class="fa-solid fa-cash-register me-2"></i>Ventas</div>
                        <small>Gestiona tus ventas y clientes de forma eficiente.</small>
                    </div>
                    <div class="mw-login-feature-item">
                        <div class="mw-login-feature-title"><i class="fa-solid fa-chart-line me-2"></i>Reportes</div>
                        <small>Toma mejores decisiones con reportes detallados.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 mw-login-form-wrapper">
            <div class="card mw-login-card border-0 shadow-sm w-100">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="mw-login-icon mx-auto mb-3">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Bienvenido a <span class="text-primary">Market</span></h3>
                        <p class="text-muted mb-0">Ingresa tus credenciales para continuar</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Usuario</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="Ingresa tu usuario" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Contraseña</label>
                            <div class="input-group">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="Ingresa tu contraseña" required autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">Recordarme</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-3 py-2">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    if (!toggleButton || !passwordInput) return;

    toggleButton.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        toggleButton.innerHTML = isPassword
            ? '<i class="fa-regular fa-eye-slash"></i>'
            : '<i class="fa-regular fa-eye"></i>';
    });
});
</script>
@endsection
