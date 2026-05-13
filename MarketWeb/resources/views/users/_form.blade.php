<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Rol</label>
        <input type="text" class="form-control bg-light" value="{{ isset($user) && strcasecmp(trim((string) ($user->email ?? '')), 'jmarinmun@gmail.com') === 0 ? 'Administrador (único)' : 'Vendedor' }}" readonly tabindex="-1">
        <div class="form-text">El sistema asigna el rol automáticamente.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Estado</label>
        <select name="status" class="form-select">
            <option value="1" @selected(old('status', (int) ($user->status ?? 1)) === 1)>Activo</option>
            <option value="0" @selected(old('status', (int) ($user->status ?? 1)) === 0)>Inactivo</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Contraseña {{ isset($user) ? '(opcional)' : '' }}</label>
        <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }}>
    </div>
</div>
