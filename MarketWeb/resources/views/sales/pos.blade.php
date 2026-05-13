@extends('layouts.app')

@section('content')
@php
    $todayText = \Carbon\Carbon::now()->locale('es')->translatedFormat('l, d \\d\\e F \\d\\e Y');
    $timeText = \Carbon\Carbon::now()->format('h:i a');
@endphp
<div class="mw-pos-page">
    <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-1">Ventas / Punto de venta</h4>
            <p class="text-muted mb-0">Realiza ventas rápidas y administra tu carrito</p>
        </div>
        <div class="d-flex align-items-center gap-3 text-muted small">
            <span><i class="fa-regular fa-calendar me-2"></i>{{ $todayText }}</span>
            <span class="vr d-none d-md-inline-block"></span>
            <span><i class="fa-regular fa-clock me-2"></i>{{ $timeText }}</span>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mw-pos-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="row g-2 mb-3">
                        <div class="col-lg-8">
                            <form action="{{ route('sales.add-by-barcode') }}" method="POST" id="barcode-form" class="position-relative">
                                @csrf
                                <div class="position-relative">
                                    <i class="fa-solid fa-barcode mw-pos-search-icon"></i>
                                    <input
                                        type="text"
                                        name="barcode"
                                        id="barcode-input"
                                        class="form-control mw-pos-search-input"
                                        placeholder="Escanear código de barra o buscar producto"
                                        autofocus
                                        required
                                    >
                                    <span class="mw-pos-keyboard-icon"><i class="fa-regular fa-keyboard"></i></span>
                                </div>
                                <div id="mw-pos-product-results" class="list-group position-absolute w-100 shadow-sm d-none mw-pos-autocomplete"></div>
                            </form>
                            <form action="{{ route('sales.add-by-product') }}" method="POST" id="add-product-form" class="d-none">
                                @csrf
                                <input type="hidden" name="product_id" id="mw-pos-product-id">
                            </form>
                        </div>
                        <div class="col-lg-4">
                            <div class="position-relative">
                                <i class="fa-solid fa-filter mw-pos-secondary-search-icon"></i>
                                <select id="mw-pos-category-filter" class="form-select mw-pos-secondary-search">
                                    <option value="">Todas las categorías</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-2">
                        <form method="POST" action="{{ route('sales.clear-cart') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                <i class="fa-regular fa-trash-can me-1"></i>Limpiar carrito
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mw-pos-table mb-2">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th width="180">Cantidad</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cart as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $item['name'] }}</div>
                                            <small class="text-muted">{{ $item['barcode'] ?: 'Sin código' }}</small>
                                        </td>
                                        <td>
                                            <div class="mw-pos-qty-wrap">
                                                <form action="{{ route('sales.update-item', $item['product_id']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="quantity" value="{{ max(1, $item['quantity'] - 1) }}">
                                                    <button class="btn btn-sm btn-light border" @disabled($item['quantity'] <= 1)>−</button>
                                                </form>
                                                <span class="mw-pos-qty-value">{{ $item['quantity'] }}</span>
                                                <form action="{{ route('sales.update-item', $item['product_id']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="quantity" value="{{ min($item['stock_available'], $item['quantity'] + 1) }}">
                                                    <button class="btn btn-sm btn-light border" @disabled($item['quantity'] >= $item['stock_available'])>+</button>
                                                </form>
                                            </div>
                                        </td>
                                        <td>${{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                                        <td>${{ number_format($item['quantity'] * $item['unit_price'], 0, ',', '.') }}</td>
                                        <td class="text-end">
                                            <form action="{{ route('sales.remove-item', $item['product_id']) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-link text-muted p-0" title="Quitar"><i class="fa-solid fa-xmark"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Aún no hay productos en el carrito.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="small fw-semibold text-secondary">{{ $summary['items'] }} productos</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mw-pos-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <form action="{{ route('sales.checkout') }}" method="POST" id="checkout-form">
                        @csrf
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <span class="fw-semibold fs-5">TOTAL</span>
                            <strong class="mw-pos-total">${{ number_format($summary['subtotal'], 0, ',', '.') }}</strong>
                        </div>

                        <div class="mb-3">
                            <div class="fw-semibold mb-2">Método de pago</div>
                            <div class="row g-2">
                                @foreach ($paymentMethods as $method)
                                    @php
                                        $isSelected = (string) old('payment_method_id', $paymentMethods->first()?->id) === (string) $method->id;
                                    @endphp
                                    <div class="col-6">
                                        <input class="btn-check mw-pos-payment-input" type="radio" name="payment_method_id" id="payment-{{ $method->id }}" value="{{ $method->id }}" @checked($isSelected) required>
                                        <label class="mw-pos-payment-option" for="payment-{{ $method->id }}" data-payment-name="{{ mb_strtolower($method->name) }}">
                                            <span>
                                                @if (str_contains(mb_strtolower($method->name), 'efectivo'))
                                                    <i class="fa-regular fa-money-bill-1"></i>
                                                @elseif (str_contains(mb_strtolower($method->name), 'tarjeta'))
                                                    <i class="fa-regular fa-credit-card"></i>
                                                @elseif (str_contains(mb_strtolower($method->name), 'transferencia'))
                                                    <i class="fa-solid fa-building-columns"></i>
                                                @else
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                @endif
                                                {{ $method->name }}
                                            </span>
                                            <i class="fa-solid fa-circle-check mw-pos-payment-check"></i>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mw-pos-cash-only mt-4 pt-3 border-top" id="cash-section">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Recibido</span>
                                <div class="mw-pos-money-input-wrap">
                                    <span>$</span>
                                    <input type="number" step="0.01" min="0" name="amount_received" id="amount-received" class="form-control form-control-sm" value="{{ old('amount_received', 0) }}">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Cambio</span>
                                <strong class="text-success fs-5" id="change-amount">$0</strong>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-mw-orange w-100 rounded-3 py-2 fw-semibold mb-2" @disabled($summary['items'] === 0)>
                            <i class="fa-solid fa-cart-shopping me-2"></i>Cobrar
                        </button>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-danger w-100 rounded-3" id="cancel-sale-btn">Cancelar</button>
                            <button type="button" class="btn btn-outline-primary w-100 rounded-3" disabled>Imprimir comprobante</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('sales.clear-cart') }}" id="cancel-sale-form" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const barcodeInput = document.getElementById('barcode-input');
    const barcodeForm = document.getElementById('barcode-form');
    const productResults = document.getElementById('mw-pos-product-results');
    const productIdInput = document.getElementById('mw-pos-product-id');
    const addProductForm = document.getElementById('add-product-form');
    const categoryFilter = document.getElementById('mw-pos-category-filter');
    const amountReceivedInput = document.getElementById('amount-received');
    const cancelSaleBtn = document.getElementById('cancel-sale-btn');
    const cancelSaleForm = document.getElementById('cancel-sale-form');
    const changeAmount = document.getElementById('change-amount');
    const cashSection = document.getElementById('cash-section');
    const paymentInputs = document.querySelectorAll('.mw-pos-payment-input');
    const total = {{ (float) $summary['subtotal'] }};
    let searchTimer = null;

    if (barcodeInput) {
        barcodeInput.focus();
    }

    function formatMoney(value) {
        return '$' + Number(value).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function isCashSelected() {
        const selected = document.querySelector('.mw-pos-payment-input:checked');
        if (!selected) return false;
        const label = document.querySelector(`label[for="${selected.id}"]`);
        const paymentName = label?.dataset.paymentName ?? '';

        return paymentName.includes('efectivo');
    }

    function updateCashFields() {
        const showCash = isCashSelected();
        if (cashSection) {
            cashSection.classList.toggle('d-none', !showCash);
        }
        if (amountReceivedInput) {
            amountReceivedInput.required = showCash;
            if (!showCash) {
                amountReceivedInput.value = '';
            }
        }
        updateChange();
    }

    function updateChange() {
        if (!changeAmount) return;
        if (!isCashSelected()) {
            changeAmount.textContent = formatMoney(0);
            return;
        }
        const received = Number(amountReceivedInput?.value || 0);
        const change = Math.max(received - total, 0);
        changeAmount.textContent = formatMoney(change);
    }

    paymentInputs.forEach((input) => input.addEventListener('change', updateCashFields));
    amountReceivedInput?.addEventListener('input', updateChange);
    cancelSaleBtn?.addEventListener('click', () => cancelSaleForm?.submit());
    updateCashFields();

    function clearAutocompleteResults() {
        if (!productResults) return;
        productResults.innerHTML = '';
        productResults.classList.add('d-none');
    }

    function renderAutocompleteResults(results) {
        if (!productResults) return;
        productResults.innerHTML = '';

        if (results.length === 0) {
            const empty = document.createElement('button');
            empty.type = 'button';
            empty.className = 'list-group-item list-group-item-action disabled';
            empty.textContent = 'Sin resultados';
            productResults.appendChild(empty);
        } else {
            results.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action';
                const barcodeText = item.barcode ? ` | Cód: ${item.barcode}` : '';
                btn.textContent = `${item.name}${barcodeText} | Stock: ${item.stock}`;
                btn.addEventListener('click', () => {
                    if (!productIdInput || !addProductForm || !barcodeInput) return;
                    productIdInput.value = String(item.id);
                    barcodeInput.value = item.name;
                    clearAutocompleteResults();
                    addProductForm.submit();
                });
                productResults.appendChild(btn);
            });
        }

        productResults.classList.remove('d-none');
    }

    async function fetchProducts(term) {
        const params = new URLSearchParams({
            q: term,
            category_id: categoryFilter?.value || '',
        });
        const response = await fetch(`{{ route('sales.search-products') }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('No se pudo buscar productos');
        }

        const payload = await response.json();

        return payload.data || [];
    }

    barcodeInput?.addEventListener('input', () => {
        const term = barcodeInput.value.trim();
        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        if (term.length < 2) {
            clearAutocompleteResults();
            return;
        }

        searchTimer = setTimeout(async () => {
            try {
                const results = await fetchProducts(term);
                renderAutocompleteResults(results);
            } catch (_error) {
                clearAutocompleteResults();
            }
        }, 250);
    });

    categoryFilter?.addEventListener('change', () => {
        const term = barcodeInput?.value.trim() || '';
        if (term.length < 2) {
            clearAutocompleteResults();
            return;
        }

        fetchProducts(term)
            .then((results) => renderAutocompleteResults(results))
            .catch(() => clearAutocompleteResults());
    });

    barcodeForm?.addEventListener('submit', () => {
        clearAutocompleteResults();
    });

    document.addEventListener('click', (event) => {
        if (!productResults || !barcodeInput) return;
        if (event.target === barcodeInput || productResults.contains(event.target)) return;
        clearAutocompleteResults();
    });

    @if (session('status'))
        Swal.fire({
            icon: 'success',
            title: 'Venta registrada',
            text: @json(session('status')),
            timer: 2200,
            showConfirmButton: false
        });
    @endif

    @if (session('pos_error'))
        Swal.fire({
            icon: 'error',
            title: 'No fue posible agregar',
            text: @json(session('pos_error'))
        });
    @endif
});
</script>
@endsection
