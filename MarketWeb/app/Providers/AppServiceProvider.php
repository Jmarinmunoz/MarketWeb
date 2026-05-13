<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $resolveStoreId = static function (): int {
            $auth = auth()->user();
            abort_if($auth === null, 403);

            $storeId = (int) $auth->store_id;

            if ($auth->role?->name === 'Administrador') {
                $selected = (int) session('admin_selected_store_id', 0);
                if ($selected > 0 && Store::query()->whereKey($selected)->where('status', true)->exists()) {
                    $storeId = $selected;
                }
            }

            abort_if($storeId <= 0, 403);

            return $storeId;
        };

        Route::bind('product', function (string $value) use ($resolveStoreId): Product {
            $storeId = $resolveStoreId();

            return Product::query()
                ->where('store_id', $storeId)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('category', function (string $value) use ($resolveStoreId): Category {
            $storeId = $resolveStoreId();

            return Category::query()
                ->where('store_id', $storeId)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('sale', function (string $value) use ($resolveStoreId): Sale {
            $storeId = $resolveStoreId();

            return Sale::query()
                ->where('store_id', $storeId)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('user', function (string $value): User {
            $auth = auth()->user();
            abort_if($auth === null, 403);

            if ($auth->role?->name === 'Administrador') {
                return User::query()->whereKey($value)->firstOrFail();
            }

            abort_if($auth->store_id === null, 403);

            return User::query()
                ->where('store_id', $auth->store_id)
                ->whereKey($value)
                ->firstOrFail();
        });
    }
}
