@extends('layouts.app')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card mw-card"><div class="card-body"><span class="badge text-bg-primary">Ventas hoy</span><h4 class="mt-2 mb-0">${{ number_format($summary['todaySales'], 0, ',', '.') }}</h4></div></div>
    </div>
    <div class="col-md-3">
        <div class="card mw-card"><div class="card-body"><span class="badge text-bg-success">Ventas mes</span><h4 class="mt-2 mb-0">${{ number_format($summary['monthSales'], 0, ',', '.') }}</h4></div></div>
    </div>
    <div class="col-md-3">
        <div class="card mw-card"><div class="card-body"><span class="badge text-bg-info">Tickets mes</span><h4 class="mt-2 mb-0">{{ $summary['monthTickets'] }}</h4></div></div>
    </div>
    <div class="col-md-3">
        <div class="card mw-card"><div class="card-body"><span class="badge text-bg-warning">Stock bajo</span><h4 class="mt-2 mb-0">{{ $summary['lowStockProducts'] }}</h4></div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mw-card">
            <div class="card-header bg-white">Movimientos por tipo <span class="text-muted fw-normal small">(últimos 30 días)</span></div>
            <div class="card-body">
                <canvas id="report-movements-type-chart" height="90"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mw-card">
            <div class="card-header bg-white">Ventas por método de pago</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @forelse($salesByPaymentMethod as $row)
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>{{ $row->paymentMethod?->name ?? 'Sin método' }}</span>
                            <strong>${{ number_format($row->total, 0, ',', '.') }}</strong>
                        </li>
                    @empty
                        <li class="list-group-item px-0 text-muted">Sin ventas registradas este mes.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card mw-card">
            <div class="card-header bg-white">Últimos movimientos de stock</div>
            <div class="card-body">
                <table id="report-movements-table" data-manual-datatable="true" class="table table-striped align-middle js-datatable">
                    <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Usuario</th>
                        <th>Referencia</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($recentStockMovements as $movement)
                        <tr>
                            <td data-order="{{ $movement->created_at?->timestamp ?? 0 }}">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $movement->product?->name }}</td>
                            <td>{{ $movement->type }}</td>
                            <td>{{ $movement->quantity }}</td>
                            <td>{{ $movement->user?->name }}</td>
                            <td>{{ $movement->reference ?? '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartEl = document.getElementById('report-movements-type-chart');
    if (chartEl && typeof Chart !== 'undefined') {
        new Chart(chartEl, {
            type: 'bar',
            data: {
                labels: @json($movementChart['labels']),
                datasets: [{
                    label: 'Cantidad de movimientos',
                    data: @json($movementChart['series']),
                    backgroundColor: [
                        'rgba(22,163,74,0.85)',
                        'rgba(220,38,38,0.85)',
                        'rgba(37,99,235,0.85)',
                        'rgba(109,40,217,0.85)',
                    ],
                    borderColor: [
                        '#15803d',
                        '#b91c1c',
                        '#1d4ed8',
                        '#6d28d9',
                    ],
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const n = ctx.parsed.y ?? 0;
                                return n === 1 ? '1 movimiento' : `${n} movimientos`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    const movementsTable = document.getElementById('report-movements-table');
    if (!movementsTable || typeof window.DataTable === 'undefined') return;

    // Columna Fecha = índice 0
    new window.DataTable(movementsTable, {
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
});
</script>
@endsection
