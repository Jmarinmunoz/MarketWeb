<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $storeId = $this->currentStoreId();
        $today = Carbon::today();

        $summary = [
            'todaySales' => Sale::where('store_id', $storeId)->whereDate('sold_at', $today)->sum('total'),
            'monthSales' => Sale::where('store_id', $storeId)->whereMonth('sold_at', now()->month)->sum('total'),
            'monthTickets' => Sale::where('store_id', $storeId)->whereMonth('sold_at', now()->month)->count(),
            'lowStockProducts' => Product::where('store_id', $storeId)->whereColumn('stock', '<=', 'minimum_stock')->count(),
        ];

        $salesByPaymentMethod = Sale::query()
            ->where('store_id', $storeId)
            ->selectRaw('payment_method_id, SUM(total) as total')
            ->with('paymentMethod')
            ->whereMonth('sold_at', now()->month)
            ->groupBy('payment_method_id')
            ->get();

        $recentStockMovements = StockMovement::with(['product', 'user'])
            ->where('store_id', $storeId)
            ->latest()
            ->limit(15)
            ->get();

        $movementCounts = StockMovement::query()
            ->where('store_id', $storeId)
            ->selectRaw('type, COUNT(*) as cnt')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('type')
            ->pluck('cnt', 'type');

        $movementChart = [
            'labels' => ['Entrada', 'Salida', 'Ajuste', 'Venta'],
            'series' => [
                (int) ($movementCounts['ENTRADA'] ?? 0),
                (int) ($movementCounts['SALIDA'] ?? 0),
                (int) ($movementCounts['AJUSTE'] ?? 0),
                (int) ($movementCounts['VENTA'] ?? 0),
            ],
        ];

        return view('reports.index', compact('summary', 'salesByPaymentMethod', 'recentStockMovements', 'movementChart'));
    }
}
