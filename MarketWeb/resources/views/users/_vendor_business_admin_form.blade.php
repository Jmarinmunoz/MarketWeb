{{--
    $vendorUser: User (vendedor)
    $businessSetting: BusinessSetting|null
--}}
@if ($vendorUser->store)
    @php $bs = $businessSetting; @endphp
    <div class="card mw-card mb-4">
        <div class="card-header bg-white">Datos del negocio del vendedor</div>
        <div class="card-body">
            <p class="text-muted small mb-3">Puedes editar estos datos cuando lo necesites. El correo del negocio coincide con el correo de la cuenta del vendedor.</p>
            <form action="{{ route('users.vendor-business.update', $vendorUser) }}" method="POST" class="row g-3" id="mw-admin-business-form">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">Nombre del negocio</label>
                    <input type="text" name="business_name" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name', $bs?->business_name ?? '') }}" required maxlength="255" autocomplete="organization">
                    @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">RUT del negocio</label>
                    <input type="text" name="rut" id="mw-admin-settings-rut" class="form-control @error('rut') is-invalid @enderror" value="{{ old('rut', $bs?->rut ?? '') }}" required maxlength="30" autocomplete="off" placeholder="77.123.456-7 o 771234567">
                    @error('rut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Dirección del negocio</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $bs?->address ?? '') }}" required maxlength="255" autocomplete="street-address">
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono del negocio</label>
                    <input type="text" name="phone" id="mw-admin-settings-phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $bs?->phone ?? '') }}" required maxlength="20" inputmode="numeric" autocomplete="tel" placeholder="Ej.: 951466774 (9) o 21234567 (8)">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo del negocio</label>
                    <input type="text" class="form-control bg-light" value="{{ $vendorUser->email }}" readonly tabindex="-1" autocomplete="email">
                    <div class="form-text">Coincide con el correo de la cuenta del vendedor.</div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary rounded-pill">Guardar datos del negocio</button>
                </div>
            </form>
        </div>
    </div>
@else
    <div class="alert alert-warning mb-4">Este usuario no tiene un local asociado.</div>
@endif
