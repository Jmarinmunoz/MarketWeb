<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    private function cartSessionKey(): string
    {
        return 'pos_cart_' . $this->currentStoreId();
    }

    public function index(): View
    {
        $storeId = $this->currentStoreId();

        return view('sales.pos', [
            'cart' => $this->getCart(),
            'categories' => Category::query()
                ->where('store_id', $storeId)
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::where('status', true)->orderBy('name')->get(),
            'summary' => $this->cartSummary(),
        ]);
    }

    public function addByBarcode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string'],
        ]);

        $product = Product::where('store_id', $this->currentStoreId())
            ->where('barcode', $validated['barcode'])
            ->where('status', true)
            ->first();

        if (! $product) {
            return redirect()->route('sales.index')->with('pos_error', 'Producto no encontrado por código de barra.');
        }

        $cart = $this->getCart();
        $existing = $cart[$product->id] ?? null;
        $newQty = ($existing['quantity'] ?? 0) + 1;

        if ($newQty > $product->stock) {
            return redirect()->route('sales.index')->with('pos_error', 'Stock insuficiente para agregar más unidades.');
        }

        $cart[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'unit_price' => (float) $product->sale_price,
            'stock_available' => (int) $product->stock,
            'quantity' => $newQty,
        ];

        $this->saveCart($cart);

        return redirect()->route('sales.index');
    }

    public function addByProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $product = Product::query()
            ->where('store_id', $this->currentStoreId())
            ->where('status', true)
            ->find($validated['product_id']);

        if (! $product) {
            return redirect()->route('sales.index')->with('pos_error', 'Producto no encontrado.');
        }

        $cart = $this->getCart();
        $existing = $cart[$product->id] ?? null;
        $newQty = ($existing['quantity'] ?? 0) + 1;

        if ($newQty > $product->stock) {
            return redirect()->route('sales.index')->with('pos_error', 'Stock insuficiente para agregar más unidades.');
        }

        $cart[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'unit_price' => (float) $product->sale_price,
            'stock_available' => (int) $product->stock,
            'quantity' => $newQty,
        ];

        $this->saveCart($cart);

        return redirect()->route('sales.index');
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $storeId = $this->currentStoreId();
        $categoryId = (int) $request->query('category_id', 0);

        $products = Product::query()
            ->where('store_id', $storeId)
            ->where('status', true)
            ->when($categoryId > 0, fn ($query) => $query->where('category_id', $categoryId))
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'barcode', 'stock', 'sale_price']);

        $payload = $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => (string) ($product->barcode ?? ''),
            'stock' => (int) $product->stock,
            'sale_price' => (float) $product->sale_price,
        ])->values();

        return response()->json(['data' => $payload]);
    }

    public function updateItem(Request $request, int $productId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->getCart();
        if (! isset($cart[$productId])) {
            return redirect()->route('sales.index');
        }

        $product = Product::where('store_id', $this->currentStoreId())->find($productId);
        if (! $product) {
            unset($cart[$productId]);
            $this->saveCart($cart);

            return redirect()->route('sales.index');
        }

        if ($validated['quantity'] > $product->stock) {
            return redirect()->route('sales.index')->with('pos_error', 'Cantidad supera el stock disponible.');
        }

        $cart[$productId]['quantity'] = $validated['quantity'];
        $cart[$productId]['stock_available'] = (int) $product->stock;
        $this->saveCart($cart);

        return redirect()->route('sales.index');
    }

    public function removeItem(int $productId): RedirectResponse
    {
        $cart = $this->getCart();
        unset($cart[$productId]);
        $this->saveCart($cart);

        return redirect()->route('sales.index');
    }

    public function clearCart(): RedirectResponse
    {
        session()->forget($this->cartSessionKey());

        return redirect()->route('sales.index');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'amount_received' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cart = $this->getCart();
        if (count($cart) === 0) {
            return redirect()->route('sales.index')->with('pos_error', 'El carrito está vacío.');
        }

        $subtotal = collect($cart)->sum(fn (array $item) => $item['quantity'] * $item['unit_price']);
        $discount = 0.0;
        $total = max($subtotal - $discount, 0);

        $paymentMethod = PaymentMethod::findOrFail((int) $validated['payment_method_id']);
        $isCashPayment = mb_strtolower($paymentMethod->name) === 'efectivo';

        if ($isCashPayment) {
            $receivedAmount = (float) ($validated['amount_received'] ?? 0);

            if ($receivedAmount < $total) {
                return redirect()
                    ->route('sales.index')
                    ->withInput()
                    ->with('pos_error', 'El monto recibido en efectivo debe ser mayor o igual al total.');
            }
        }

        $storeId = $this->currentStoreId();

        $sale = DB::transaction(function () use ($cart, $validated, $subtotal, $discount, $total, $storeId): Sale {
            $sale = Sale::create([
                'store_id' => $storeId,
                'folio' => $this->generateFolio(),
                'user_id' => auth()->id(),
                'payment_method_id' => $validated['payment_method_id'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => 'COMPLETADA',
                'sold_at' => now(),
            ]);

            foreach ($cart as $item) {
                $product = Product::query()
                    ->where('store_id', $storeId)
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);
                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'stock' => "Stock insuficiente para {$product->name}.",
                    ]);
                }

                $previousStock = (int) $product->stock;
                $newStock = $previousStock - (int) $item['quantity'];
                $product->update(['stock' => $newStock]);

                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'subtotal' => (float) $item['quantity'] * (float) $item['unit_price'],
                ]);

                StockMovement::create([
                    'store_id' => $storeId,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'VENTA',
                    'quantity' => (int) $item['quantity'],
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reason' => 'Venta en punto de venta',
                    'reference' => $sale->folio,
                ]);
            }

            return $sale;
        });

        session()->forget($this->cartSessionKey());

        return redirect()
            ->route('sales.index')
            ->with('status', "Venta {$sale->folio} registrada correctamente.");
    }

    public function history(): View
    {
        $tab = $requestTab = request()->query('tab', 'ventas');
        if (! in_array($requestTab, ['ventas', 'precios'], true)) {
            $tab = 'ventas';
        }

        $query = Sale::with(['user', 'paymentMethod', 'details.product'])
            ->where('store_id', $this->currentStoreId())
            ->latest('sold_at');
        $isAdmin = auth()->user()?->role?->name === 'Administrador';

        if (! $isAdmin) {
            $query->where('user_id', auth()->id());
        }

        $sales = $query->get();
        $priceHistories = ProductPriceHistory::query()
            ->with(['product', 'user'])
            ->where('store_id', $this->currentStoreId())
            ->latest()
            ->get();

        return view('sales.history', compact('sales', 'priceHistories', 'isAdmin', 'tab'));
    }

    public function historyDetail(Sale $sale): JsonResponse
    {
        $this->assertUserCanViewSale($sale);

        $sale->loadMissing(['paymentMethod', 'details.product']);

        return response()->json([
            'folio' => $sale->folio,
            'sold_at' => $sale->sold_at?->format('d/m/Y H:i'),
            'payment_method' => $sale->paymentMethod?->name ?? '-',
            'total' => '$'.number_format((float) $sale->total, 0, ',', '.'),
            'status' => $sale->status,
            'items' => $sale->details->map(fn (SaleDetail $detail): array => [
                'name' => $detail->product?->name ?? 'Producto',
                'quantity' => $detail->quantity,
                'unit_price' => '$'.number_format((float) $detail->unit_price, 0, ',', '.'),
                'subtotal' => '$'.number_format((float) $detail->subtotal, 0, ',', '.'),
            ])->values(),
        ]);
    }

    private function assertUserCanViewSale(Sale $sale): void
    {
        $isAdmin = auth()->user()?->role?->name === 'Administrador';

        if ($isAdmin) {
            return;
        }

        if ((int) $sale->user_id !== (int) auth()->id()) {
            abort(403);
        }
    }

    private function getCart(): array
    {
        return session()->get($this->cartSessionKey(), []);
    }

    private function saveCart(array $cart): void
    {
        session()->put($this->cartSessionKey(), $cart);
    }

    private function cartSummary(): array
    {
        $cart = $this->getCart();
        $subtotal = collect($cart)->sum(fn (array $item) => $item['quantity'] * $item['unit_price']);

        return [
            'items' => count($cart),
            'subtotal' => $subtotal,
        ];
    }

    private function generateFolio(): string
    {
        return 'VTA-' . Carbon::now()->format('Ymd-His') . '-' . random_int(100, 999);
    }
}
