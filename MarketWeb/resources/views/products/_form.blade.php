<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Categoría</label>
        <select name="category_id" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Código de barra</label>
        <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $product->barcode ?? '') }}" inputmode="numeric" pattern="[0-9]*" maxlength="100">
    </div>
    <div class="col-md-8">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Estado</label>
        <select name="status" class="form-select" required>
            <option value="1" @selected(old('status', (int) ($product->status ?? 1)) === 1)>Activo</option>
            <option value="0" @selected(old('status', (int) ($product->status ?? 1)) === 0)>Inactivo</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Precio venta</label>
        <input type="number" step="0.01" min="0" name="sale_price" class="form-control" value="{{ old('sale_price', $product->sale_price ?? 0) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Stock</label>
        <input type="number" min="0" name="stock" class="form-control" value="{{ old('stock', $product->stock ?? 0) }}" required>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="small text-muted">
            Solo se editan campos operativos del producto.
        </div>
    </div>
</div>
