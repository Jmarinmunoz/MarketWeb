@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
    <div>
        <h3 class="mb-1">¡Bienvenido, {{ auth()->user()->name }}!</h3>
        <p class="text-muted mb-0">Resumen general de tu negocio</p>
    </div>
    <div class="text-muted small text-end">
        <div><i class="fa-regular fa-calendar me-2"></i>{{ $adminNow->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</div>
        <div><i class="fa-regular fa-clock me-2"></i>{{ $adminNow->format('h:i a') }}</div>
    </div>
</div>

<div class="row g-3 mb-1">
    <div class="col-md-4">
        <div class="card mw-card mw-kpi-card">
            <div class="card-body d-flex justify-content-between">
                <div>
                    <div class="text-muted small">Productos</div>
                    <h4 class="mb-0">{{ number_format($adminMetrics['activeProducts']) }}</h4>
                    <small class="text-muted">activos</small>
                </div>
                <div class="mw-kpi-icon text-primary"><i class="fa-solid fa-cube"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mw-card mw-kpi-card">
            <div class="card-body d-flex justify-content-between">
                <div>
                    <div class="text-muted small">Ventas Hoy</div>
                    <h4 class="mb-0">{{ number_format($adminMetrics['todayTickets']) }}</h4>
                    <small class="text-muted">ventas</small>
                </div>
                <div class="mw-kpi-icon text-success"><i class="fa-solid fa-cart-shopping"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mw-card mw-kpi-card">
            <div class="card-body d-flex justify-content-between">
                <div>
                    <div class="text-muted small">Ingresos Hoy</div>
                    <h4 class="mb-0">${{ number_format($adminMetrics['todayIncome'], 0, ',', '.') }}</h4>
                </div>
                <div class="mw-kpi-icon text-purple"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card mw-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Ventas de los últimos 7 días</span>
                <small class="text-muted">Últimos 7 días</small>
            </div>
            <div class="card-body">
                <canvas id="admin-sales-chart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mw-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Movimientos recientes</span>
                <a href="{{ route('stock-movements.index') }}" class="small text-decoration-none">Ver todos</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Producto</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMovements as $movement)
                                <tr>
                                    <td>{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{
                                            $movement->type === 'ENTRADA' ? 'text-bg-success' :
                                            ($movement->type === 'SALIDA' ? 'text-bg-danger' :
                                            ($movement->type === 'AJUSTE' ? 'text-bg-primary' :
                                            ($movement->type === 'VENTA' ? 'text-bg-secondary' : 'text-bg-light border')))
                                        }}">
                                            {{ $movement->type }}
                                        </span>
                                    </td>
                                    <td>{{ $movement->product?->name }}</td>
                                    <td>{{ $movement->user?->name }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Sin movimientos recientes</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mw-card">
            <div class="card-header bg-white">Ventas por categoría (este mes)</div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-5 text-center">
                        <canvas id="admin-category-chart" height="150"></canvas>
                    </div>
                    <div class="col-md-7">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                @forelse($categorySales as $category)
                                    <tr>
                                        <td>{{ $category->category_name }}</td>
                                        <td class="text-end">${{ number_format($category->total, 0, ',', '.') }}</td>
                                        <td class="text-end text-muted">
                                            {{ $categoryTotal > 0 ? number_format(($category->total / $categoryTotal) * 100, 0) : 0 }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-muted">Sin ventas por categoría este mes.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">${{ number_format($categoryTotal, 0, ',', '.') }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mw-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Últimas ventas</span>
                <a href="{{ route('sales-history.index') }}" class="small text-decoration-none">Ver todas</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Vendedor</th>
                                <th>Items</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td>{{ $sale->folio }}</td>
                                    <td>{{ $sale->sold_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $sale->user?->name }}</td>
                                    <td>{{ $sale->details_count }}</td>
                                    <td class="text-end">${{ number_format($sale->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Sin ventas recientes</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const salesChartElement = document.getElementById('admin-sales-chart');
        if (!salesChartElement) {
            return;
        }

        new Chart(salesChartElement, {
            type: 'line',
            data: {
                labels: @json($adminChart['labels']),
                datasets: [{
                    label: 'Ventas',
                    data: @json($adminChart['series']),
                    borderColor: '#032145',
                    backgroundColor: 'rgba(3,33,69,0.10)',
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                }
            }
        });

        const categoryChartElement = document.getElementById('admin-category-chart');
        if (!categoryChartElement) return;

        new Chart(categoryChartElement, {
            type: 'doughnut',
            data: {
                labels: @json($categoryChart['labels']),
                datasets: [{
                    data: @json($categoryChart['series']),
                    backgroundColor: ['#3766d8', '#f59e0b', '#16a34a', '#8b5cf6', '#ef4444', '#06b6d4']
                }]
            },
            options: {
                plugins: {
                    legend: { display: false }
                }
            }
        });
    });
</script>
@endsection
