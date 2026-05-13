<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(): View
    {
        $storeId = $this->currentStoreId();
        $products = Product::with('category')->where('store_id', $storeId)->latest()->get();
        $categories = Category::where('store_id', $storeId)->where('status', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function panel(Product $product): JsonResponse
    {
        abort_unless((int) $product->store_id === (int) $this->currentStoreId(), 404);
        $product->load('category');

        return response()->json([
            'id' => $product->id,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'sale_price' => (float) $product->sale_price,
            'stock' => $product->stock,
            'status' => $product->status,
            'can_edit' => false,
            'update_url' => route('products.update', $product),
        ]);
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('products.index', ['panel' => $product->id]);
    }

    public function create(): View
    {
        $this->ensureCanCreateProducts();
        $storeId = $this->currentStoreId();
        $categories = Category::where('store_id', $storeId)->where('status', true)->orderBy('name')->get();
        if ($categories->isEmpty()) {
            Category::create([
                'store_id' => $storeId,
                'name' => 'General',
                'status' => true,
            ]);
            $categories = Category::where('store_id', $storeId)->where('status', true)->orderBy('name')->get();
        }

        return view('products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanCreateProducts();
        $storeId = $this->currentStoreId();
        $validated = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('store_id', $storeId)),
            ],
            'barcode' => [
                'required',
                'string',
                'max:100',
                'regex:/^[0-9]+$/',
                Rule::unique('products', 'barcode')->where(fn ($q) => $q->where('store_id', $storeId)),
            ],
            'name' => ['required', 'string', 'max:150'],
            'status' => ['required', 'boolean'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
        ]);

        Product::create([
            'store_id' => $storeId,
            'category_id' => $validated['category_id'],
            'barcode' => $validated['barcode'],
            'name' => $validated['name'],
            'description' => null,
            'purchase_price' => $validated['sale_price'],
            'sale_price' => $validated['sale_price'],
            'stock' => $validated['stock'],
            'minimum_stock' => 0,
            'status' => (bool) $validated['status'],
        ]);

        return redirect()->route('products.index')->with('status', 'Producto creado correctamente.');
    }

    public function edit(Product $product): View
    {
        $this->ensureAdmin();
        $categories = Category::where('store_id', $this->currentStoreId())->where('status', true)->orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin();
        $validated = $this->validateProduct($request, $product->id, $this->currentStoreId());
        $product->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Producto actualizado correctamente.']);
        }

        return redirect()->route('products.index')->with('status', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->ensureAdmin();
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Producto eliminado correctamente.');
    }

    public function updatePrice(Request $request, Product $product): RedirectResponse
    {
        $this->ensureCanUpdatePrice();
        abort_unless((int) $product->store_id === (int) $this->currentStoreId(), 404);

        $validated = $request->validate([
            'sale_price' => ['required', 'numeric', 'min:0'],
        ]);

        $oldPrice = (float) $product->sale_price;
        $newPrice = (float) $validated['sale_price'];

        if ($oldPrice !== $newPrice) {
            ProductPriceHistory::query()->create([
                'store_id' => $this->currentStoreId(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
            ]);
        }

        $product->update(['sale_price' => $newPrice]);

        return redirect()->route('products.index')->with('status', 'Precio actualizado correctamente.');
    }

    private function validateProduct(Request $request, ?int $productId, int $storeId): array
    {
        $barcodeUnique = Rule::unique('products', 'barcode')
            ->where(fn ($q) => $q->where('store_id', $storeId));

        if ($productId) {
            $barcodeUnique = $barcodeUnique->ignore($productId);
        }

        return $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('store_id', $storeId)),
            ],
            'barcode' => ['nullable', 'string', 'max:100', 'regex:/^[0-9]+$/', $barcodeUnique],
            'name' => ['required', 'string', 'max:150'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'boolean'],
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->role?->name === 'Administrador', 403);
    }

    private function ensureCanCreateProducts(): void
    {
        $role = auth()->user()?->role?->name;
        abort_unless(in_array($role, ['Administrador', 'Vendedor'], true), 403);
    }

    private function ensureCanUpdatePrice(): void
    {
        $role = auth()->user()?->role?->name;
        abort_unless(in_array($role, ['Administrador', 'Vendedor'], true), 403);
    }
}
