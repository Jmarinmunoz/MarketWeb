@extends('layouts.app')

@section('content')
@php
    $roleName = auth()->user()?->role?->name;
    $isAdmin = $roleName === 'Administrador';
    $canCreateProducts = in_array($roleName, ['Administrador', 'Vendedor'], true);
@endphp

<div class="mw-products-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Gestión de productos</h3>
            <p class="text-muted mb-0 small">
                @if ($isAdmin)
                    Administra tu catálogo de productos, precios y existencias.
                @elseif ($roleName === 'Vendedor')
                    Agrega productos en tu local y consulta precios y stock disponible.
                @else
                    Consulta productos, precios y stock disponible.
                @endif
            </p>
        </div>
        @if ($canCreateProducts)
            <a href="{{ route('products.create') }}" class="btn btn-mw-orange rounded-pill px-4 fw-semibold">
                <i class="fa-solid fa-plus me-2"></i>Nuevo producto
            </a>
        @endif
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2">{{ session('status') }}</div>
    @endif

    <div class="card mw-products-toolbar-card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-center">
                <div class="col-lg-4">
                    <div class="position-relative">
                        <i class="fa-solid fa-magnifying-glass mw-products-toolbar-icon"></i>
                        <input type="search" id="mw-product-search" class="form-control form-control-sm mw-products-search-input ps-4"
                            placeholder="Buscar por nombre..." autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <select id="mw-filter-category" class="form-select form-select-sm">
                        <option value="">Todas las categorías</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <select id="mw-filter-status" class="form-select form-select-sm">
                        <option value="all">Estado: Todos</option>
                        <option value="activo">Activo</option>
                        <option value="bajo">Bajo stock</option>
                        <option value="agotado">Agotado</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="col-lg-2 text-lg-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="mw-products-reset-filters" title="Limpiar filtros">
                        <i class="fa-solid fa-filter me-1"></i>Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <p class="small text-secondary mb-2" id="mw-products-count-text">{{ number_format($products->count(), 0, ',', '.') }} productos encontrados</p>

    <div class="card mw-products-table-card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive mw-products-table-wrap">
            <table id="mw-products-table" class="table table-hover mb-0 mw-products-datatable align-middle">
                <thead>
                    <tr>
                        <th>Código de barra</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th class="text-end">Precio venta</th>
                        <th class="text-end">Stock</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end mw-products-actions-col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        @php
                            if (!$product->status) {
                                $stockStatus = 'inactivo';
                            } elseif ($product->stock <= 0) {
                                $stockStatus = 'agotado';
                            } elseif ($product->stock <= $product->minimum_stock) {
                                $stockStatus = 'bajo';
                            } else {
                                $stockStatus = 'activo';
                            }
                        @endphp
                        <tr
                            data-product-id="{{ $product->id }}"
                            data-stock-status="{{ $stockStatus }}"
                            data-category-name="{{ $product->category?->name ?? '' }}"
                            data-product-name="{{ $product->name }}"
                            data-sale-price="{{ (float) $product->sale_price }}"
                            data-price-url="{{ route('products.update-price', $product) }}"
                            class="mw-product-row"
                        >
                            <td><span class="text-muted">{{ $product->barcode ?: '—' }}</span></td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td class="text-end">${{ number_format($product->sale_price, 0, ',', '.') }}</td>
                            <td class="text-end">{{ $product->stock }}</td>
                            <td class="text-center">
                                @if ($stockStatus === 'activo')
                                    <span class="mw-badge-stock mw-badge-stock--active">Activo</span>
                                @elseif ($stockStatus === 'bajo')
                                    <span class="mw-badge-stock mw-badge-stock--warning">Bajo stock</span>
                                @elseif ($stockStatus === 'agotado')
                                    <span class="mw-badge-stock mw-badge-stock--danger">Agotado</span>
                                @else
                                    <span class="mw-badge-stock mw-badge-stock--inactive">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1 justify-content-end">
                                    @if ($isAdmin)
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-link btn-sm text-secondary p-1" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endif
                                    <div class="dropdown">
                                        <button class="btn btn-link btn-sm text-secondary p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Más">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <button type="button" class="dropdown-item mw-open-product-panel" data-product-id="{{ $product->id }}">
                                                    Ver detalle
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item mw-open-price-modal" data-product-id="{{ $product->id }}">
                                                    Cambiar precio
                                                </button>
                                            </li>
                                            @if ($isAdmin)
                                                <li>
                                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="mw-delete-product-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">Eliminar</button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Panel lateral derecho --}}
<div class="offcanvas offcanvas-end mw-product-drawer" tabindex="-1" id="productDetailDrawer" aria-labelledby="productDetailDrawerLabel">
    <div class="offcanvas-header border-bottom align-items-start">
        <div>
            <h5 class="offcanvas-title fw-bold" id="productDetailDrawerLabel">Detalle del producto</h5>
            <div class="mw-drawer-tab mt-2">Información</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div id="mw-drawer-loading" class="text-center py-5 text-muted d-none">
            <div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Cargando...</span></div>
        </div>
        <div id="mw-drawer-error" class="alert alert-danger d-none"></div>

        <form id="mw-drawer-form" class="flex-grow-1 d-flex flex-column d-none">
            @csrf

            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Código de barra</label>
                <div class="position-relative">
                    <input type="text" name="barcode" id="mw-drawer-barcode" class="form-control form-control-sm pe-5" maxlength="100">
                    <i class="fa-solid fa-barcode mw-drawer-input-icon"></i>
                </div>
            </div>

            <div class="mb-3 mw-drawer-barcode-preview-wrap">
                <label class="form-label small text-muted mb-1">Vista previa</label>
                <div class="mw-drawer-barcode-box bg-white border rounded p-3 text-center">
                    <svg id="mw-drawer-barcode-svg" xmlns="http://www.w3.org/2000/svg" class="mw-drawer-barcode-svg"></svg>
                    <div class="small text-muted mt-2 font-monospace" id="mw-drawer-barcode-text"></div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Nombre</label>
                <input type="text" name="name" id="mw-drawer-name" class="form-control form-control-sm" required maxlength="150">
            </div>

            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Categoría</label>
                <select name="category_id" id="mw-drawer-category" class="form-select form-select-sm"></select>
            </div>

            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Precio de venta</label>
                <input type="number" step="0.01" min="0" name="sale_price" id="mw-drawer-sale-price" class="form-control form-control-sm" required>
            </div>

            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Stock</label>
                <input type="number" min="0" step="1" name="stock" id="mw-drawer-stock" class="form-control form-control-sm" required>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="status" id="mw-drawer-status" value="1">
                <label class="form-check-label" for="mw-drawer-status">Producto activo</label>
            </div>

            <div class="mt-auto pt-3 border-top d-flex gap-2 mw-drawer-footer">
                <button type="button" class="btn btn-outline-secondary flex-grow-1 rounded-pill" data-bs-dismiss="offcanvas">Cancelar</button>
                <button type="submit" class="btn btn-mw-save flex-grow-1 rounded-pill fw-semibold d-none" id="mw-drawer-submit">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="mw-price-modal" tabindex="-1" aria-labelledby="mw-price-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="mw-price-form" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="mw-price-modal-label">Actualizar precio de venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong>Producto:</strong> <span id="mw-price-product-name">-</span></p>
                <div class="mb-3">
                    <label class="form-label">Precio actual</label>
                    <input type="text" class="form-control bg-light" id="mw-price-current" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nuevo precio</label>
                    <input type="number" step="0.01" min="0" name="sale_price" id="mw-price-new" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-mw-save">Guardar precio</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const isAdmin = @json($isAdmin);
    const categoriesOptions = @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]));
    const panelUrlTemplate = @json(route('products.panel', ['product' => '__PRODUCT__']));

    const tableEl = document.getElementById('mw-products-table');
    if (!tableEl || typeof window.DataTable === 'undefined') return;

    const table = new window.DataTable(tableEl, {
        dom: "<'row'<'col-sm-12'tr>><'row align-items-center gy-2 px-3 pb-3'<'col-md-6'l><'col-md-6 d-flex justify-content-md-end'p>>",
        pageLength: 12,
        lengthMenu: [[12, 24, 48], [12, 24, 48]],
        ordering: true,
        info: false,
        language: {
            search: '',
            lengthMenu: 'Mostrar _MENU_ por página',
            infoEmpty: 'Sin registros',
            zeroRecords: 'No hay productos que coincidan',
            paginate: { next: '›', previous: '‹' },
        },
        columnDefs: [
            { orderable: false, targets: [6] },
        ],
    });

    function updateCount() {
        const n = table.rows({ search: 'applied' }).count();
        const el = document.getElementById('mw-products-count-text');
        if (el) el.textContent = n.toLocaleString('es-CL') + ' productos encontrados';
    }

    table.on('draw', updateCount);
    updateCount();

    const searchInput = document.getElementById('mw-product-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', () => {
            table.search(searchInput.value).draw();
        });
    }

    window.DataTable.ext.search.push(function (settings, _filterData, rowIdx) {
        if (settings.nTable !== tableEl) return true;
        const row = settings.aoData[rowIdx]?.nTr;
        if (!row) return true;
        const catFilter = document.getElementById('mw-filter-category')?.value || '';
        const statusFilter = document.getElementById('mw-filter-status')?.value || 'all';

        if (catFilter && (row.getAttribute('data-category-name') || '') !== catFilter) {
            return false;
        }
        if (statusFilter !== 'all' && row.getAttribute('data-stock-status') !== statusFilter) {
            return false;
        }
        return true;
    });

    ['mw-filter-category', 'mw-filter-status'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => table.draw());
    });

    document.getElementById('mw-products-reset-filters')?.addEventListener('click', () => {
        document.getElementById('mw-filter-category').value = '';
        document.getElementById('mw-filter-status').value = 'all';
        if (searchInput) searchInput.value = '';
        table.search('').draw();
    });

    const drawerEl = document.getElementById('productDetailDrawer');
    const DrawerCtor = window.bootstrap?.Offcanvas;
    const drawer = drawerEl && DrawerCtor ? DrawerCtor.getOrCreateInstance(drawerEl) : null;
    const priceModalEl = document.getElementById('mw-price-modal');
    const ModalCtor = window.bootstrap?.Modal;
    const priceModal = priceModalEl && ModalCtor ? ModalCtor.getOrCreateInstance(priceModalEl) : null;
    const priceForm = document.getElementById('mw-price-form');
    const form = document.getElementById('mw-drawer-form');
    const loading = document.getElementById('mw-drawer-loading');
    const errBox = document.getElementById('mw-drawer-error');

    function fillCategorySelect(selectedId, canEdit) {
        const sel = document.getElementById('mw-drawer-category');
        sel.innerHTML = '';
        categoriesOptions.forEach((c) => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            if (String(c.id) === String(selectedId)) opt.selected = true;
            sel.appendChild(opt);
        });
        sel.disabled = !canEdit;
    }

    function renderBarcode(code) {
        const svg = document.getElementById('mw-drawer-barcode-svg');
        const txt = document.getElementById('mw-drawer-barcode-text');
        if (!svg || !txt) return;
        txt.textContent = code || '';
        svg.innerHTML = '';
        if (!code || typeof window.JsBarcode === 'undefined') return;
        try {
            window.JsBarcode(svg, code, {
                format: 'CODE128',
                width: 2,
                height: 56,
                displayValue: false,
                margin: 0,
            });
        } catch (e) {
            svg.innerHTML = '';
        }
    }

    function normalizeBarcode(value) {
        return String(value || '').replace(/\D/g, '');
    }

    async function openPanel(productId) {
        errBox?.classList.add('d-none');
        loading?.classList.remove('d-none');
        form?.classList.add('d-none');

        try {
            const panelUrl = panelUrlTemplate.replace('__PRODUCT__', String(productId));
            const res = await fetch(panelUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('No se pudo cargar el producto.');
            const data = await res.json();

            document.getElementById('mw-drawer-barcode').value = data.barcode || '';
            document.getElementById('mw-drawer-name').value = data.name || '';
            document.getElementById('mw-drawer-sale-price').value = data.sale_price ?? '';
            document.getElementById('mw-drawer-stock').value = data.stock ?? '';
            document.getElementById('mw-drawer-status').checked = !!data.status;

            fillCategorySelect(data.category_id, data.can_edit);

            ['mw-drawer-barcode', 'mw-drawer-name', 'mw-drawer-sale-price', 'mw-drawer-stock'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.readOnly = !data.can_edit;
            });
            const statusInput = document.getElementById('mw-drawer-status');
            if (statusInput) statusInput.disabled = !data.can_edit;
            document.getElementById('mw-drawer-submit')?.classList.toggle('d-none', !data.can_edit);

            renderBarcode(data.barcode || '');
            form.dataset.updateUrl = data.update_url || '';

            const bcInput = document.getElementById('mw-drawer-barcode');
            if (bcInput) {
                bcInput.oninput = () => {
                    bcInput.value = normalizeBarcode(bcInput.value);
                    renderBarcode(bcInput.value);
                };
            }

            loading?.classList.add('d-none');
            form?.classList.remove('d-none');

            drawer?.show();
        } catch (e) {
            loading?.classList.add('d-none');
            errBox.textContent = e.message || 'Error';
            errBox.classList.remove('d-none');
            drawer?.show();
        }
    }

    document.querySelectorAll('.mw-open-product-panel').forEach((btn) => {
        btn.addEventListener('click', () => openPanel(btn.getAttribute('data-product-id')));
    });

    function formatMoney(value) {
        return '$' + Number(value || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    document.querySelectorAll('.mw-open-price-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            const productId = btn.getAttribute('data-product-id');
            const row = document.querySelector(`tr.mw-product-row[data-product-id="${productId}"]`);
            if (!row || !priceForm) return;

            const name = row.getAttribute('data-product-name') || '-';
            const salePrice = row.getAttribute('data-sale-price') || '0';
            const priceUrl = row.getAttribute('data-price-url') || '';

            const nameEl = document.getElementById('mw-price-product-name');
            const currentEl = document.getElementById('mw-price-current');
            const newEl = document.getElementById('mw-price-new');

            if (nameEl) nameEl.textContent = name;
            if (currentEl) currentEl.value = formatMoney(salePrice);
            if (newEl) newEl.value = Number(salePrice);

            priceForm.setAttribute('action', priceUrl);
            priceModal?.show();
        });
    });

    document.querySelectorAll('.mw-product-row').forEach((row) => {
        row.addEventListener('dblclick', (e) => {
            if (e.target.closest('a, button, .dropdown-menu')) return;
            openPanel(row.getAttribute('data-product-id'));
        });
    });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const url = form.dataset.updateUrl;
        if (!url || !isAdmin) return;

        const fd = new FormData(form);
        fd.append('_method', 'PUT');
        fd.delete('status');
        fd.append('status', document.getElementById('mw-drawer-status').checked ? '1' : '0');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: fd,
                credentials: 'same-origin',
            });
            const payload = await res.json().catch(() => ({}));
            if (!res.ok) {
                let msg = payload.message || 'No se pudo guardar.';
                if (payload.errors) {
                    msg = Object.values(payload.errors).flat().join('\n');
                }
                if (window.Swal) window.Swal.fire({ icon: 'error', title: 'Error', text: msg });
                else alert(msg);
                return;
            }
            if (window.Swal) {
                window.Swal.fire({ icon: 'success', title: 'Listo', text: payload.message || 'Producto actualizado.', timer: 1600, showConfirmButton: false });
            }
            drawer?.hide();
            window.location.reload();
        } catch (err) {
            if (window.Swal) window.Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    });

    document.querySelectorAll('.mw-delete-product-form').forEach((f) => {
        f.addEventListener('submit', (ev) => {
            ev.preventDefault();
            if (!window.Swal) {
                if (confirm('¿Eliminar este producto?')) f.submit();
                return;
            }
            window.Swal.fire({
                title: '¿Eliminar producto?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
            }).then((r) => { if (r.isConfirmed) f.submit(); });
        });
    });

    const params = new URLSearchParams(window.location.search);
    const panelId = params.get('panel');
    if (panelId) {
        openPanel(panelId);
        window.history.replaceState({}, '', window.location.pathname);
    }
});
</script>
@endsection
