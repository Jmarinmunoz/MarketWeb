<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::where('store_id', $this->currentStoreId())
            ->orderBy('local_id')
            ->orderBy('id')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'boolean'],
        ]);

        $storeId = $this->currentStoreId();

        DB::transaction(function () use ($validated, $storeId): void {
            $nextLocalId = ((int) Category::query()
                ->where('store_id', $storeId)
                ->max('local_id')) + 1;

            Category::create([
                ...$validated,
                'store_id' => $storeId,
                'local_id' => $nextLocalId,
            ]);
        });

        return redirect()->route('categories.index')->with('status', 'Categoría creada correctamente.');
    }

    public function edit(Category $category): View
    {
        $this->ensureCategoryBelongsToCurrentStore($category);

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->ensureCategoryBelongsToCurrentStore($category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'boolean'],
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')->with('status', 'Categoría actualizada correctamente.');
    }

    private function ensureCategoryBelongsToCurrentStore(Category $category): void
    {
        abort_unless((int) $category->store_id === (int) $this->currentStoreId(), 404);
    }
}
