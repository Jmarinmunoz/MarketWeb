<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $storeId = $this->currentStoreId();

        $products = Product::query()
            ->where('store_id', $storeId)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product): Product {
                $product->last_movement_at = StockMovement::query()
                    ->where('store_id', $product->store_id)
                    ->where('product_id', $product->id)
                    ->latest()
                    ->value('created_at');

                return $product;
            });

        $totalUnits = (int) $products->sum('stock');
        $outOfStockCount = (int) $products->where('stock', '<=', 0)->count();
        $lowStockCount = (int) $products->filter(fn (Product $product): bool => $product->stock > 0 && $product->stock <= 3)->count();
        $inventoryValue = (float) $products->sum(fn (Product $product): float => ((float) $product->sale_price) * (int) $product->stock);
        $categories = $products->pluck('category.name')->filter()->unique()->sort()->values();
        $now = Carbon::now();

        return view('inventory.index', compact(
            'products',
            'totalUnits',
            'outOfStockCount',
            'lowStockCount',
            'inventoryValue',
            'categories',
            'now'
        ));
    }
}
