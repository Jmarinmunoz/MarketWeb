<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): \Illuminate\Http\RedirectResponse
    {
        $dashboardRoute = auth()->user()?->role?->name === 'Administrador'
            ? 'dashboard.admin'
            : 'dashboard.seller';

        return redirect()->route($dashboardRoute);
    }

    public function admin(): View
    {
        $storeId = $this->currentStoreId();
        $today = Carbon::today();
        $adminMetrics = [
            'activeProducts' => Product::where('store_id', $storeId)->where('status', true)->count(),
            'todayTickets' => Sale::where('store_id', $storeId)->whereDate('sold_at', $today)->count(),
            'todayIncome' => Sale::where('store_id', $storeId)->whereDate('sold_at', $today)->sum('total'),
        ];

        $last7DaysSales = Sale::query()
            ->where('store_id', $storeId)
            ->selectRaw('DATE(sold_at) as sale_date, COALESCE(SUM(total), 0) as total')
            ->whereDate('sold_at', '>=', Carbon::today()->subDays(6))
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        $labels = collect(range(0, 6))
            ->map(fn (int $offset) => Carbon::today()->subDays(6 - $offset)->toDateString());

        $adminChart = [
            'labels' => $labels->map(fn (string $date) => Carbon::parse($date)->format('d/m'))->all(),
            'series' => $labels->map(fn (string $date) => (float) ($last7DaysSales[$date]->total ?? 0))->all(),
        ];

        $recentMovements = StockMovement::with(['product', 'user'])
            ->where('store_id', $storeId)
            ->latest()
            ->limit(6)
            ->get();

        $recentSales = Sale::with('user')
            ->where('store_id', $storeId)
            ->withCount('details')
            ->latest('sold_at')
            ->limit(6)
            ->get();

        $categorySales = SaleDetail::query()
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sales.store_id', $storeId)
            ->whereMonth('sales.sold_at', now()->month)
            ->whereYear('sales.sold_at', now()->year)
            ->selectRaw('categories.name as category_name, SUM(sale_details.subtotal) as total')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $categoryTotal = (float) $categorySales->sum('total');
        $categoryChart = [
            'labels' => $categorySales->pluck('category_name')->all(),
            'series' => $categorySales->map(fn ($row) => (float) $row->total)->all(),
        ];

        $adminNow = Carbon::now();

        return view('dashboards.admin', compact(
            'adminMetrics',
            'adminChart',
            'recentMovements',
            'recentSales',
            'categorySales',
            'categoryTotal',
            'categoryChart',
            'adminNow'
        ));
    }

    public function seller(): View
    {
        $user = auth()->user();
        $storeId = (int) $user->store_id;
        $today = Carbon::today();

        $sellerMetrics = [
            'activeProducts' => Product::where('store_id', $storeId)->where('status', true)->count(),
            'todayTickets' => Sale::where('store_id', $storeId)->where('user_id', $user->id)->whereDate('sold_at', $today)->count(),
            'todayIncome' => Sale::where('store_id', $storeId)->where('user_id', $user->id)->whereDate('sold_at', $today)->sum('total'),
        ];

        $last7DaysSales = Sale::query()
            ->where('store_id', $storeId)
            ->selectRaw('DATE(sold_at) as sale_date, COALESCE(SUM(total), 0) as total')
            ->where('user_id', $user->id)
            ->whereDate('sold_at', '>=', Carbon::today()->subDays(6))
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        $labels = collect(range(0, 6))
            ->map(fn (int $offset) => Carbon::today()->subDays(6 - $offset)->toDateString());

        $sellerChart = [
            'labels' => $labels->map(fn (string $date) => Carbon::parse($date)->format('d/m'))->all(),
            'series' => $labels->map(fn (string $date) => (float) ($last7DaysSales[$date]->total ?? 0))->all(),
        ];

        $recentMovements = StockMovement::with(['product', 'user'])
            ->where('store_id', $storeId)
            ->latest()
            ->limit(6)
            ->get();

        $recentSales = Sale::query()
            ->where('store_id', $storeId)
            ->where('user_id', $user->id)
            ->with('user')
            ->withCount('details')
            ->latest('sold_at')
            ->limit(6)
            ->get();

        $categorySales = SaleDetail::query()
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->where('sales.store_id', $storeId)
            ->where('sales.user_id', $user->id)
            ->whereMonth('sales.sold_at', now()->month)
            ->whereYear('sales.sold_at', now()->year)
            ->selectRaw('categories.name as category_name, SUM(sale_details.subtotal) as total')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $categoryTotal = (float) $categorySales->sum('total');
        $categoryChart = [
            'labels' => $categorySales->pluck('category_name')->all(),
            'series' => $categorySales->map(fn ($row) => (float) $row->total)->all(),
        ];

        $sellerNow = Carbon::now();

        return view('dashboards.seller', compact(
            'sellerMetrics',
            'sellerChart',
            'recentMovements',
            'recentSales',
            'categorySales',
            'categoryTotal',
            'categoryChart',
            'sellerNow'
        ));
    }
}
