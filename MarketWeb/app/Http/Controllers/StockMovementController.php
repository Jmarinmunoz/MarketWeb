<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function index(): View
    {
        $movements = StockMovement::with(['product', 'user'])
            ->where('store_id', $this->currentStoreId())
            ->latest()
            ->get();
        $today = Carbon::now();

        return view('stock-movements.index', compact('movements', 'today'));
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $storeId = $this->currentStoreId();

        $products = Product::query()
            ->where('store_id', $storeId)
            ->where('status', true)
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'stock', 'barcode']);

        $payload = $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'stock' => (int) $product->stock,
            'barcode' => (string) ($product->barcode ?? ''),
        ])->values();

        return response()->json(['data' => $payload]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'reason' => trim((string) $request->input('reason', '')),
        ]);

        $storeId = $this->currentStoreId();

        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(
                    fn ($query) => $query->where('store_id', $storeId)->where('status', true)
                ),
            ],
            'type' => ['required', 'in:ENTRADA,SALIDA,AJUSTE'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required_if:type,SALIDA,AJUSTE', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
        ], [
            'reason.required_if' => 'El motivo es obligatorio para salidas y ajustes.',
        ]);

        DB::transaction(function () use ($validated, $storeId): void {
            $product = Product::query()
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->findOrFail($validated['product_id']);

            $previousStock = (int) $product->stock;
            $quantity = (int) $validated['quantity'];
            $newStock = $this->calculateNewStock($validated['type'], $previousStock, $quantity);

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock insuficiente para realizar esta salida.',
                ]);
            }

            $product->update(['stock' => $newStock]);

            StockMovement::create([
                'store_id' => $storeId,
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $validated['reason'] !== '' ? $validated['reason'] : null,
                'reference' => $validated['reference'] ?? null,
            ]);
        });

        return redirect()
            ->route('stock-movements.index')
            ->with('status', 'Movimiento de stock registrado correctamente.');
    }

    private function calculateNewStock(string $type, int $previousStock, int $quantity): int
    {
        return match ($type) {
            'ENTRADA' => $previousStock + $quantity,
            'SALIDA' => $previousStock - $quantity,
            'AJUSTE' => $quantity,
        };
    }
}
