@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Historial</h4>
    <select id="mw-history-tab" class="form-select form-select-sm" style="max-width: 240px;">
        <option value="ventas" @selected($tab === 'ventas')>Historial / Ventas</option>
        <option value="precios" @selected($tab === 'precios')>Historial / Precios</option>
    </select>
</div>

@if ($tab === 'ventas')
    <div class="card mw-card">
        <div class="card-body">
            <table id="sales-history-table" data-manual-datatable="true" class="table table-striped table-hover align-middle js-datatable">
                <thead>
                    <tr>
                        <th class="d-none"><span class="visually-hidden">Orden por fecha</span></th>
                        <th>Fecha</th>
                        @if ($isAdmin)
                            <th>Vendedor</th>
                        @endif
                        <th>Método de pago</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                        <tr class="sale-history-row cursor-pointer" data-sale-id="{{ $sale->id }}" role="button" tabindex="0">
                            <td class="d-none">{{ $sale->sold_at?->getTimestamp() ?? 0 }}</td>
                            <td>{{ $sale->sold_at?->format('d/m/Y H:i') }}</td>
                            @if ($isAdmin)
                                <td>{{ $sale->user?->name }}</td>
                            @endif
                            <td>{{ $sale->paymentMethod?->name }}</td>
                            <td>${{ number_format($sale->total, 0, ',', '.') }}</td>
                            <td><span class="badge text-bg-success">{{ $sale->status }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card mw-card">
        <div class="card-body">
            <table id="price-history-table" data-manual-datatable="true" class="table table-striped table-hover align-middle js-datatable">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        @if ($isAdmin)
                            <th>Usuario</th>
                        @endif
                        <th class="text-end">Precio anterior</th>
                        <th class="text-end">Precio nuevo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($priceHistories as $history)
                        <tr>
                            <td>{{ $history->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $history->product?->name ?? 'Producto eliminado' }}</td>
                            @if ($isAdmin)
                                <td>{{ $history->user?->name ?? '-' }}</td>
                            @endif
                            <td class="text-end">${{ number_format((float) $history->old_price, 0, ',', '.') }}</td>
                            <td class="text-end">${{ number_format((float) $history->new_price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="modal fade" id="saleDetailModal" tabindex="-1" aria-labelledby="saleDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="saleDetailModalLabel">Detalle de venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="sale-detail-loading" class="text-muted py-4 text-center d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Cargando…
                </div>
                <div id="sale-detail-content">
                    <dl class="row mb-4 g-3">
                        <div class="col-sm-6">
                            <dt class="small text-muted mb-1">Folio</dt>
                            <dd class="mb-0 fw-semibold" id="sd-folio">—</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="small text-muted mb-1">Fecha</dt>
                            <dd class="mb-0 fw-semibold" id="sd-fecha">—</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="small text-muted mb-1">Método de pago</dt>
                            <dd class="mb-0 fw-semibold" id="sd-metodo">—</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="small text-muted mb-1">Total</dt>
                            <dd class="mb-0 fw-semibold" id="sd-total">—</dd>
                        </div>
                        <div class="col-12">
                            <dt class="small text-muted mb-1">Estado</dt>
                            <dd class="mb-0" id="sd-estado">—</dd>
                        </div>
                    </dl>
                    <h6 class="fw-bold mb-2">Productos</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">P. unitario</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="sd-productos"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const historyTab = document.getElementById('mw-history-tab');
    historyTab?.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', historyTab.value);
        window.location.href = url.toString();
    });

    const tableEl = document.getElementById('sales-history-table');
    if (typeof window.DataTable === 'undefined') return;

    const detailUrl = (id) => `{{ url('/historial-ventas') }}/${id}`;
    const modalEl = document.getElementById('saleDetailModal');
    const saleModal = modalEl && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
        : null;
    const loadingEl = document.getElementById('sale-detail-loading');
    const contentEl = document.getElementById('sale-detail-content');

    if (tableEl) {
        new window.DataTable(tableEl, {
            pageLength: 10,
            stateSave: true,
            order: [[1, 'desc']],
            columnDefs: [
                { targets: 0, visible: false, searchable: false },
                { targets: 1, orderData: [0] },
            ],
            stateLoadParams: function (_settings, data) {
                data.order = [[1, 'desc']];
            },
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_',
                info: 'Mostrando _START_ a _END_ de _TOTAL_',
                paginate: {
                    next: 'Siguiente',
                    previous: 'Anterior',
                },
            },
        });
    }

    const priceTableEl = document.getElementById('price-history-table');
    if (priceTableEl) {
        new window.DataTable(priceTableEl, {
            pageLength: 10,
            stateSave: true,
            order: [[0, 'desc']],
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_',
                info: 'Mostrando _START_ a _END_ de _TOTAL_',
                paginate: {
                    next: 'Siguiente',
                    previous: 'Anterior',
                },
            },
        });
    }

    function setDetailLoading(show) {
        if (!loadingEl || !contentEl) return;
        loadingEl.classList.toggle('d-none', !show);
        contentEl.classList.toggle('d-none', show);
    }

    async function openSaleDetail(id) {
        if (!saleModal) return;
        setDetailLoading(true);
        saleModal.show();

        try {
            const response = await fetch(detailUrl(id), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('HTTP error');
            }

            const data = await response.json();

            document.getElementById('sd-folio').textContent = data.folio ?? '—';
            document.getElementById('sd-fecha').textContent = data.sold_at ?? '—';
            document.getElementById('sd-metodo').textContent = data.payment_method ?? '—';
            document.getElementById('sd-total').textContent = data.total ?? '—';

            const estadoEl = document.getElementById('sd-estado');
            estadoEl.replaceChildren();
            const badge = document.createElement('span');
            badge.className = 'badge text-bg-success';
            badge.textContent = data.status ?? '';
            estadoEl.appendChild(badge);

            const tbody = document.getElementById('sd-productos');
            tbody.innerHTML = '';
            const items = data.items || [];
            if (items.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="4" class="text-muted small py-3 text-center">Sin líneas registradas.</td>';
                tbody.appendChild(tr);
            } else {
                items.forEach((item) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escapeHtml(item.name) + '</td>' +
                        '<td class="text-center">' + escapeHtml(String(item.quantity)) + '</td>' +
                        '<td class="text-end">' + escapeHtml(item.unit_price) + '</td>' +
                        '<td class="text-end">' + escapeHtml(item.subtotal) + '</td>';
                    tbody.appendChild(tr);
                });
            }

            setDetailLoading(false);
        } catch (_e) {
            setDetailLoading(false);
            saleModal.hide();
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el detalle de la venta.',
                });
            } else {
                alert('No se pudo cargar el detalle de la venta.');
            }
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    if (tableEl) {
        tableEl.querySelector('tbody')?.addEventListener('click', (event) => {
            const row = event.target.closest('tr[data-sale-id]');
            if (!row || !tableEl.contains(row)) return;
            const id = row.getAttribute('data-sale-id');
            if (id) openSaleDetail(id);
        });

        tableEl.querySelector('tbody')?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            const row = event.target.closest('tr[data-sale-id]');
            if (!row || !tableEl.contains(row)) return;
            event.preventDefault();
            const id = row.getAttribute('data-sale-id');
            if (id) openSaleDetail(id);
        });
    }
});
</script>
@endsection
