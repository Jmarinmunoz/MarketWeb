@extends('layouts.app')

@section('content')
<div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Movimientos de inventario</h4>
        <p class="text-muted mb-0">Consulta y administración de entradas, salidas y ajustes de stock</p>
    </div>
    <div class="d-flex align-items-center gap-3 text-muted small">
        <span><i class="fa-regular fa-calendar me-2"></i>{{ $today->locale('es')->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</span>
        <span class="vr d-none d-md-inline-block"></span>
        <span><i class="fa-regular fa-clock me-2"></i>{{ $today->format('h:i a') }}</span>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
@endif

@if (session('pos_error'))
    <div class="alert alert-danger py-2">{{ session('pos_error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger py-2">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 js-mw-set-type" data-type="ENTRADA">
                <i class="fa-solid fa-plus me-1"></i>Nueva entrada
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 js-mw-set-type" data-type="SALIDA">
                <i class="fa-solid fa-minus me-1"></i>Nueva salida
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 js-mw-set-type" data-type="AJUSTE">
                <i class="fa-solid fa-sliders me-1"></i>Ajuste
            </button>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="small text-muted">Tipo de movimiento</label>
                <select id="mw-mov-filter-type" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="ENTRADA">Entrada</option>
                    <option value="SALIDA">Salida</option>
                    <option value="AJUSTE">Ajuste</option>
                    <option value="VENTA">Venta</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small text-muted">Rango de fecha</label>
                <div class="d-flex align-items-center gap-1">
                    <input type="date" id="mw-mov-start-date" class="form-control form-control-sm">
                    <span class="small text-muted">a</span>
                    <input type="date" id="mw-mov-end-date" class="form-control form-control-sm">
                </div>
            </div>
            <div class="col-md-3">
                <label class="small text-muted">Producto</label>
                <input type="search" id="mw-mov-filter-product" class="form-control form-control-sm" placeholder="Todos los productos">
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Usuario</label>
                <input type="search" id="mw-mov-filter-user" class="form-control form-control-sm" placeholder="Todos los usuarios">
            </div>
            <div class="col-md-2 text-md-end">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="mw-mov-clear-filters">
                    <i class="fa-solid fa-filter-circle-xmark me-1"></i>Limpiar filtros
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm mw-mov-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0">Historial de movimientos</h6>
                    <span class="small text-muted" id="mw-mov-count">{{ $movements->count() }} movimientos</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="mw-mov-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-center">Stock anterior</th>
                                <th class="text-center">Stock nuevo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($movements as $movement)
                                @php
                                    $signedQty = in_array($movement->type, ['SALIDA', 'VENTA'], true)
                                        ? ('-' . $movement->quantity)
                                        : ('+' . $movement->quantity);
                                @endphp
                                <tr
                                    data-type="{{ $movement->type }}"
                                    data-product="{{ $movement->product?->name ?? '' }}"
                                    data-user="{{ $movement->user?->name ?? '' }}"
                                    data-date="{{ $movement->created_at?->format('Y-m-d') }}"
                                    data-datetime="{{ $movement->created_at?->format('d/m/Y H:i') }}"
                                    data-qty="{{ $signedQty }}"
                                    data-prev="{{ $movement->previous_stock }}"
                                    data-new="{{ $movement->new_stock }}"
                                    data-reason="{{ str_contains(strtolower($movement->reason ?? ''), 'venta') ? 'Venta' : ($movement->reason ?: '-') }}"
                                    class="mw-mov-row"
                                >
                                    <td data-order="{{ $movement->created_at?->timestamp ?? 0 }}">{{ $movement->created_at?->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="mw-mov-badge {{
                                            $movement->type === 'ENTRADA' ? 'mw-mov-badge--entrada' :
                                            ($movement->type === 'SALIDA' ? 'mw-mov-badge--salida' :
                                            ($movement->type === 'AJUSTE' ? 'mw-mov-badge--ajuste' : 'mw-mov-badge--venta'))
                                        }}">
                                            {{ ucfirst(strtolower($movement->type)) }}
                                        </span>
                                    </td>
                                    <td>{{ $movement->product?->name ?? '-' }}</td>
                                    <td class="text-center fw-semibold {{
                                        in_array($movement->type, ['SALIDA', 'VENTA'], true) ? 'text-danger' : 'text-success'
                                    }}">{{ $signedQty }}</td>
                                    <td class="text-center">{{ $movement->previous_stock }}</td>
                                    <td class="text-center">{{ $movement->new_stock }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end mw-mov-detail-drawer" tabindex="-1" id="mwMovementDetailDrawer" aria-labelledby="mwMovementDetailDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="mwMovementDetailDrawerLabel">Detalle del movimiento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body mw-mov-detail-body">
        <div class="mb-3">
            <small class="text-muted d-block mb-1">Fecha y hora</small>
            <div class="fw-semibold" id="mw-detail-datetime">-</div>
        </div>
        <div class="mb-3">
            <small class="text-muted d-block mb-2">Tipo</small>
            <div id="mw-detail-type-wrap"><span id="mw-detail-type" class="mw-mov-badge">-</span></div>
        </div>
        <div class="mw-mov-detail-feature mw-mov-detail-feature--product mb-3">
            <div class="mw-mov-detail-round mw-mov-detail-round--danger"><i class="fa-solid fa-box"></i></div>
            <div class="flex-grow-1 min-w-0">
                <small class="text-muted d-block mb-1">Producto</small>
                <div class="fw-bold text-break" id="mw-detail-product">-</div>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="mw-mov-detail-mini h-100">
                    <div id="mw-detail-qty-round" class="mw-mov-detail-round mw-mov-detail-round--danger"><i id="mw-detail-qty-icon" class="fa-solid fa-arrow-down"></i></div>
                    <small class="text-muted">Cantidad</small>
                    <div class="fw-bold fs-5" id="mw-detail-qty">-</div>
                </div>
            </div>
            <div class="col-6">
                <div class="mw-mov-detail-mini h-100">
                    <div class="mw-mov-detail-round mw-mov-detail-round--primary"><i class="fa-solid fa-layer-group"></i></div>
                    <small class="text-muted">Stock anterior</small>
                    <div class="fw-bold fs-5" id="mw-detail-prev">-</div>
                </div>
            </div>
            <div class="col-6">
                <div class="mw-mov-detail-mini h-100">
                    <div class="mw-mov-detail-round mw-mov-detail-round--success"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    <small class="text-muted">Stock nuevo</small>
                    <div class="fw-bold fs-5" id="mw-detail-new">-</div>
                </div>
            </div>
            <div class="col-6">
                <div class="mw-mov-detail-mini h-100">
                    <div class="mw-mov-detail-round mw-mov-detail-round--purple"><i class="fa-solid fa-user"></i></div>
                    <small class="text-muted">Usuario</small>
                    <div class="fw-bold text-break" id="mw-detail-user">-</div>
                </div>
            </div>
        </div>
        <div class="mw-mov-detail-feature mw-mov-detail-feature--reason">
            <div class="mw-mov-detail-round mw-mov-detail-round--warning"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="flex-grow-1 min-w-0">
                <small class="text-muted d-block mb-1">Motivo</small>
                <div class="fw-semibold text-break" id="mw-detail-reason">-</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mwMovementModal" tabindex="-1" aria-labelledby="mwMovementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow mw-mov-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="mwMovementModalLabel">Registrar nuevo movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <form action="{{ route('stock-movements.store') }}" method="POST" class="row g-3" id="mw-mov-form">
                    @csrf
                    <div class="col-12">
                        <label class="form-label small">Tipo de movimiento</label>
                        <select name="type" class="form-select form-select-sm" id="mw-mov-type" required>
                            <option value="ENTRADA" @selected(old('type', 'ENTRADA') === 'ENTRADA')>Entrada</option>
                            <option value="SALIDA" @selected(old('type') === 'SALIDA')>Salida</option>
                            <option value="AJUSTE" @selected(old('type') === 'AJUSTE')>Ajuste</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Producto</label>
                        <input type="hidden" name="product_id" id="mw-mov-product-id" value="{{ old('product_id') }}">
                        <div class="position-relative">
                            <input
                                type="search"
                                id="mw-mov-product-search"
                                class="form-control form-control-sm"
                                placeholder="Escriba al menos 2 letras..."
                                autocomplete="off"
                                value=""
                                required
                            >
                            <div id="mw-mov-product-results" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 20; max-height: 240px; overflow-y: auto;"></div>
                        </div>
                        <small id="mw-mov-product-selected" class="text-muted d-block mt-1"></small>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Cantidad</label>
                        <input type="number" min="1" name="quantity" class="form-control form-control-sm" value="{{ old('quantity', 1) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small" for="mw-mov-reason">Motivo <span id="mw-mov-reason-mark" class="text-danger d-none" aria-hidden="true">*</span></label>
                        <input type="text" name="reason" id="mw-mov-reason" class="form-control form-control-sm @error('reason') is-invalid @enderror" placeholder="Ej.: merma, corrección inventario…" value="{{ old('reason') }}">
                        @error('reason')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Usuario</label>
                        <input type="text" class="form-control form-control-sm" value="{{ auth()->user()?->name }}" readonly>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100 rounded-3">
                            <i class="fa-regular fa-floppy-disk me-1"></i>Guardar movimiento
                        </button>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border small text-muted mb-0 py-2">
                            <i class="fa-regular fa-circle-info me-1"></i>Los movimientos actualizan automáticamente el stock del producto.
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableEl = document.getElementById('mw-mov-table');
    if (!tableEl || typeof window.DataTable === 'undefined') return;

    const table = new window.DataTable(tableEl, {
        dom: "<'row'<'col-sm-12'tr>><'row align-items-center gy-2 px-2 pt-2'<'col-md-6 small'l><'col-md-6 d-flex justify-content-md-end'p>>",
        pageLength: 10,
        lengthMenu: [[10, 20, 50], [10, 20, 50]],
        stateSave: true,
        order: [[0, 'desc']],
        info: false,
        language: {
            lengthMenu: 'Mostrar _MENU_ por página',
            zeroRecords: 'No se encontraron movimientos',
            paginate: { next: '›', previous: '‹' },
        },
    });

    const typeFilter = document.getElementById('mw-mov-filter-type');
    const productFilter = document.getElementById('mw-mov-filter-product');
    const userFilter = document.getElementById('mw-mov-filter-user');
    const startDate = document.getElementById('mw-mov-start-date');
    const endDate = document.getElementById('mw-mov-end-date');
    const countEl = document.getElementById('mw-mov-count');

    window.DataTable.ext.search.push((settings, _data, rowIdx) => {
        if (settings.nTable !== tableEl) return true;
        const row = settings.aoData[rowIdx]?.nTr;
        if (!row) return true;

        const selectedType = typeFilter?.value || '';
        const selectedProduct = productFilter?.value || '';
        const userText = (userFilter?.value || '').trim().toLowerCase();
        const rowDate = row.getAttribute('data-date') || '';
        const fromDate = startDate?.value || '';
        const toDate = endDate?.value || '';

        if (selectedType && row.getAttribute('data-type') !== selectedType) return false;
        if (selectedProduct && !(row.getAttribute('data-product') || '').toLowerCase().includes(selectedProduct.toLowerCase())) return false;
        if (userText && !(row.getAttribute('data-user') || '').toLowerCase().includes(userText)) return false;
        if (fromDate && rowDate < fromDate) return false;
        if (toDate && rowDate > toDate) return false;

        return true;
    });

    function redrawAndCount() {
        table.draw();
        if (countEl) {
            const count = table.rows({ search: 'applied' }).count();
            countEl.textContent = count + ' movimientos';
        }
    }

    [typeFilter, startDate, endDate].forEach((el) => {
        el?.addEventListener('change', redrawAndCount);
    });
    productFilter?.addEventListener('input', redrawAndCount);
    userFilter?.addEventListener('input', redrawAndCount);

    document.getElementById('mw-mov-clear-filters')?.addEventListener('click', () => {
        if (typeFilter) typeFilter.value = '';
        if (productFilter) productFilter.value = '';
        if (userFilter) userFilter.value = '';
        if (startDate) startDate.value = '';
        if (endDate) endDate.value = '';
        redrawAndCount();
    });

    const movementModalEl = document.getElementById('mwMovementModal');
    const movementModal = movementModalEl && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(movementModalEl)
        : null;
    const detailDrawerEl = document.getElementById('mwMovementDetailDrawer');
    const detailDrawer = detailDrawerEl && window.bootstrap?.Offcanvas
        ? window.bootstrap.Offcanvas.getOrCreateInstance(detailDrawerEl)
        : null;

    document.querySelectorAll('.js-mw-set-type').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetType = btn.getAttribute('data-type');
            const select = document.getElementById('mw-mov-type');
            if (select && targetType) {
                select.value = targetType;
                select.dispatchEvent(new Event('change'));
                movementModal?.show();
                setTimeout(() => select.focus(), 150);
            }
        });
    });

    @if ($errors->any())
        movementModal?.show();
    @endif

    const movTypeSelect = document.getElementById('mw-mov-type');
    const reasonInputEl = document.getElementById('mw-mov-reason');
    const reasonMarkEl = document.getElementById('mw-mov-reason-mark');

    function syncReasonRequired() {
        const t = movTypeSelect?.value || '';
        const need = t === 'SALIDA' || t === 'AJUSTE';
        if (reasonInputEl) {
            reasonInputEl.required = need;
            reasonInputEl.setAttribute('aria-required', need ? 'true' : 'false');
        }
        reasonMarkEl?.classList.toggle('d-none', !need);
    }

    movTypeSelect?.addEventListener('change', syncReasonRequired);
    syncReasonRequired();

    const typeLabels = { ENTRADA: 'Entrada', SALIDA: 'Salida', AJUSTE: 'Ajuste', VENTA: 'Venta' };
    const typeBadgeClass = {
        ENTRADA: 'mw-mov-badge mw-mov-badge--entrada',
        SALIDA: 'mw-mov-badge mw-mov-badge--salida',
        AJUSTE: 'mw-mov-badge mw-mov-badge--ajuste',
        VENTA: 'mw-mov-badge mw-mov-badge--venta',
    };

    function fillMovementDetail(row) {
        const rawType = row.getAttribute('data-type') || '';
        const qtyEl = document.getElementById('mw-detail-qty');
        const typeEl = document.getElementById('mw-detail-type');
        document.getElementById('mw-detail-datetime').textContent = row.getAttribute('data-datetime') || '-';
        if (typeEl) {
            typeEl.textContent = typeLabels[rawType] || rawType || '-';
            typeEl.className = typeBadgeClass[rawType] || 'mw-mov-badge badge bg-secondary';
        }
        document.getElementById('mw-detail-product').textContent = row.getAttribute('data-product') || '-';
        const qtyText = row.getAttribute('data-qty') || '-';
        const qtyRound = document.getElementById('mw-detail-qty-round');
        const qtyIcon = document.getElementById('mw-detail-qty-icon');
        if (qtyEl) {
            qtyEl.textContent = qtyText;
            qtyEl.classList.remove('text-danger', 'text-success');
            if (qtyText.startsWith('-')) qtyEl.classList.add('text-danger');
            else if (qtyText.startsWith('+')) qtyEl.classList.add('text-success');
        }
        if (qtyRound && qtyIcon) {
            if (qtyText.startsWith('-')) {
                qtyRound.className = 'mw-mov-detail-round mw-mov-detail-round--danger';
                qtyIcon.className = 'fa-solid fa-arrow-down';
            } else if (qtyText.startsWith('+')) {
                qtyRound.className = 'mw-mov-detail-round mw-mov-detail-round--success';
                qtyIcon.className = 'fa-solid fa-arrow-up';
            } else {
                qtyRound.className = 'mw-mov-detail-round mw-mov-detail-round--primary';
                qtyIcon.className = 'fa-solid fa-hashtag';
            }
        }
        document.getElementById('mw-detail-prev').textContent = row.getAttribute('data-prev') || '-';
        document.getElementById('mw-detail-new').textContent = row.getAttribute('data-new') || '-';
        document.getElementById('mw-detail-user').textContent = row.getAttribute('data-user') || '-';
        document.getElementById('mw-detail-reason').textContent = row.getAttribute('data-reason') || '-';
    }

    tableEl.querySelector('tbody')?.addEventListener('click', (event) => {
        const row = event.target.closest('tr.mw-mov-row');
        if (!row || !tableEl.contains(row)) return;
        if (event.target.closest('a,button,input,select')) return;
        fillMovementDetail(row);
        detailDrawer?.show();
    });

    const productSearchInput = document.getElementById('mw-mov-product-search');
    const productIdInput = document.getElementById('mw-mov-product-id');
    const productResults = document.getElementById('mw-mov-product-results');
    const productSelected = document.getElementById('mw-mov-product-selected');
    let searchTimer = null;
    let selectedProductId = productIdInput?.value || '';

    function clearAutocompleteResults() {
        if (!productResults) return;
        productResults.innerHTML = '';
        productResults.classList.add('d-none');
    }

    function setSelectedProduct(item) {
        if (!productSearchInput || !productIdInput || !productSelected) return;
        selectedProductId = String(item.id);
        productIdInput.value = selectedProductId;
        productSearchInput.value = item.name;
        productSelected.textContent = `${item.name} (Stock: ${item.stock})`;
        clearAutocompleteResults();
    }

    async function fetchProducts(term) {
        const url = `{{ route('stock-movements.search-products') }}?q=${encodeURIComponent(term)}`;
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error('No se pudo buscar productos');
        }
        const payload = await response.json();

        return payload.data || [];
    }

    productSearchInput?.addEventListener('input', () => {
        const term = productSearchInput.value.trim();
        selectedProductId = '';
        if (productIdInput) productIdInput.value = '';
        if (productSelected) productSelected.textContent = '';

        if (searchTimer) {
            clearTimeout(searchTimer);
        }
        if (term.length < 2) {
            clearAutocompleteResults();
            return;
        }

        searchTimer = setTimeout(async () => {
            try {
                const results = await fetchProducts(term);
                if (!productResults) return;
                productResults.innerHTML = '';

                if (results.length === 0) {
                    const empty = document.createElement('button');
                    empty.type = 'button';
                    empty.className = 'list-group-item list-group-item-action disabled';
                    empty.textContent = 'Sin resultados';
                    productResults.appendChild(empty);
                } else {
                    results.forEach((item) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action';
                        btn.textContent = `${item.name} (Stock: ${item.stock})`;
                        btn.addEventListener('click', () => setSelectedProduct(item));
                        productResults.appendChild(btn);
                    });
                }
                productResults.classList.remove('d-none');
            } catch (_error) {
                clearAutocompleteResults();
            }
        }, 250);
    });

    document.addEventListener('click', (event) => {
        if (!productResults || !productSearchInput) return;
        if (event.target === productSearchInput || productResults.contains(event.target)) return;
        clearAutocompleteResults();
    });

    document.getElementById('mw-mov-form')?.addEventListener('submit', (event) => {
        const typeVal = movTypeSelect?.value || '';
        const reasonVal = (reasonInputEl?.value || '').trim();

        if (!selectedProductId) {
            event.preventDefault();
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Producto requerido',
                    text: 'Seleccione un producto desde los resultados.',
                });
            } else {
                alert('Seleccione un producto desde los resultados.');
            }
            return;
        }

        if ((typeVal === 'SALIDA' || typeVal === 'AJUSTE') && reasonVal === '') {
            event.preventDefault();
            reasonInputEl?.focus();
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Motivo requerido',
                    text: 'Debe indicar el motivo para salidas y ajustes.',
                });
            } else {
                alert('Debe indicar el motivo para salidas y ajustes.');
            }
        }
    });

    redrawAndCount();
});
</script>
@endsection
