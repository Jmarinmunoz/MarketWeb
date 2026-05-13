@extends('layouts.app')

@section('content')
<div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Inventario / Stock</h4>
        <p class="text-muted mb-0">Consulta y administración de existencias</p>
    </div>
    <div class="d-flex align-items-center gap-3 text-muted small">
        <span><i class="fa-regular fa-calendar me-2"></i>{{ $now->locale('es')->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</span>
        <span class="vr d-none d-md-inline-block"></span>
        <span><i class="fa-regular fa-clock me-2"></i>{{ $now->format('h:i a') }}</span>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm mw-inv-kpi-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="mw-inv-kpi-icon mw-inv-kpi-icon--blue"><i class="fa-regular fa-cube"></i></div>
                <div>
                    <div class="small text-muted">Stock total</div>
                    <div class="fs-3 fw-bold">{{ number_format($totalUnits, 0, ',', '.') }}</div>
                    <div class="small text-muted">unidades</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm mw-inv-kpi-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="mw-inv-kpi-icon mw-inv-kpi-icon--yellow"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <div class="small text-muted">Productos con stock bajo</div>
                    <div class="fs-3 fw-bold">{{ number_format($lowStockCount, 0, ',', '.') }}</div>
                    <div class="small text-muted">productos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm mw-inv-kpi-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="mw-inv-kpi-icon mw-inv-kpi-icon--red"><i class="fa-regular fa-box-open"></i></div>
                <div>
                    <div class="small text-muted">Sin stock</div>
                    <div class="fs-3 fw-bold">{{ number_format($outOfStockCount, 0, ',', '.') }}</div>
                    <div class="small text-muted">productos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm mw-inv-kpi-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="mw-inv-kpi-icon mw-inv-kpi-icon--green"><i class="fa-solid fa-dollar-sign"></i></div>
                <div>
                    <div class="small text-muted">Valor estimado del inventario</div>
                    <div class="fs-3 fw-bold">${{ number_format($inventoryValue, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mw-inv-table-card">
    <div class="card-body">
        <div class="row g-2 align-items-center mb-3">
            <div class="col-md-3">
                <label class="small text-muted">Categoría</label>
                <select id="mw-inv-filter-category" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="small text-muted">Estado</label>
                <select id="mw-inv-filter-status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="sin_stock">Sin stock</option>
                    <option value="stock_bajo">Stock bajo</option>
                    <option value="stock_ok">En stock</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="small text-muted">Buscar</label>
                <div class="position-relative">
                    <i class="fa-solid fa-magnifying-glass mw-inv-search-icon"></i>
                    <input type="search" id="mw-inv-search" class="form-control form-control-sm ps-4" placeholder="Buscar producto...">
                </div>
            </div>
            <div class="col-md-2 text-md-end">
                <a href="{{ route('stock-movements.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill mt-md-4">
                    <i class="fa-solid fa-plus me-1"></i>Movimiento
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0" id="mw-inventory-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-center">Stock actual</th>
                        <th>Último movimiento</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        @php
                            $stockLevel = (int) $product->stock <= 0 ? 'sin_stock' : ((int) $product->stock <= 3 ? 'stock_bajo' : 'stock_ok');
                        @endphp
                        <tr data-category="{{ $product->category?->name ?? '' }}" data-status="{{ $stockLevel }}" class="mw-inv-row-{{ $stockLevel }}">
                            <td>
                                <div class="fw-semibold">{{ $product->name }}</div>
                            </td>
                            <td>{{ $product->category?->name ?? '-' }}</td>
                            <td class="text-center fw-semibold">{{ $product->stock }}</td>
                            <td>{{ $product->last_movement_at ? \Carbon\Carbon::parse($product->last_movement_at)->format('d/m/Y H:i') : 'Sin movimientos' }}</td>
                            <td class="text-center">
                                @if ($stockLevel === 'sin_stock')
                                    <span class="mw-inv-badge mw-inv-badge--danger">Sin stock</span>
                                @elseif ($stockLevel === 'stock_bajo')
                                    <span class="mw-inv-badge mw-inv-badge--warning">Stock bajo</span>
                                @else
                                    <span class="mw-inv-badge mw-inv-badge--gray">En stock</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableEl = document.getElementById('mw-inventory-table');
    if (!tableEl || typeof window.DataTable === 'undefined') return;

    const table = new window.DataTable(tableEl, {
        dom: "<'row'<'col-sm-12'tr>><'row align-items-center gy-2 px-2 pt-2'<'col-md-6 small'l><'col-md-6 d-flex justify-content-md-end'p>>",
        pageLength: 10,
        lengthMenu: [[10, 20, 50], [10, 20, 50]],
        info: false,
        language: {
            lengthMenu: 'Mostrar _MENU_ por página',
            zeroRecords: 'No se encontraron productos',
            paginate: { next: '›', previous: '‹' },
        },
    });

    window.DataTable.ext.search.push((settings, _data, rowIdx) => {
        if (settings.nTable !== tableEl) return true;
        const row = settings.aoData[rowIdx]?.nTr;
        if (!row) return true;
        const category = document.getElementById('mw-inv-filter-category')?.value || '';
        const status = document.getElementById('mw-inv-filter-status')?.value || '';

        if (category && row.getAttribute('data-category') !== category) return false;
        if (status && row.getAttribute('data-status') !== status) return false;

        return true;
    });

    document.getElementById('mw-inv-filter-category')?.addEventListener('change', () => table.draw());
    document.getElementById('mw-inv-filter-status')?.addEventListener('change', () => table.draw());
    document.getElementById('mw-inv-search')?.addEventListener('keyup', (e) => {
        table.search(e.target.value).draw();
    });
});
</script>
@endsection
