<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    @php
        $isAdmin = Auth::check() && Auth::user()->role?->name === 'Administrador';
        $canAccessBusinessSettings = Auth::check() && Auth::user()->role?->name === 'Vendedor';
        $isLoginPage = request()->routeIs('login');
        $adminStores = collect();
        $adminSelectedStoreId = null;
        if ($isAdmin) {
            $adminStores = \App\Models\Store::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name']);
            $adminSelectedStoreId = (int) session('admin_selected_store_id', 0);
            if ($adminSelectedStoreId <= 0 || ! $adminStores->contains('id', $adminSelectedStoreId)) {
                $adminSelectedStoreId = (int) (Auth::user()->store_id ?? 0);
            }
        }
    @endphp
    <div id="app" class="container-fluid">
        <div class="row">
            @auth
                <aside class="col-md-3 col-lg-2 p-3 mw-sidebar d-flex flex-column min-vh-100">
                    <h5 class="text-white mb-4 fw-bold">Market Web</h5>
                    <ul class="nav flex-column gap-1 mb-auto">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}" href="{{ route('home') }}"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><i class="fa-solid fa-boxes-stacked me-2"></i>Productos</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}"><i class="fa-solid fa-tags me-2"></i>Categorías</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}"><i class="fa-solid fa-cash-register me-2"></i>Ventas / POS</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}"><i class="fa-solid fa-warehouse me-2"></i>Inventario</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('stock-movements.*') ? 'active' : '' }}" href="{{ route('stock-movements.index') }}"><i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Movimientos stock</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('sales-history.*') ? 'active' : '' }}" href="{{ route('sales-history.index') }}"><i class="fa-solid fa-receipt me-2"></i>Historial</a></li>
                        @if ($isAdmin)
                            <li class="nav-item"><a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="fa-solid fa-users me-2"></i>Usuarios</a></li>
                            <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="fa-solid fa-chart-line me-2"></i>Reportes</a></li>
                        @endif
                        @if ($canAccessBusinessSettings)
                            <li class="nav-item"><a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><i class="fa-solid fa-gear me-2"></i>Configuración</a></li>
                        @endif
                    </ul>

                    <div class="mt-3 pt-3 border-top border-light-subtle">
                        <div class="dropdown dropup">
                            <button class="btn text-start text-white w-100 d-flex align-items-center gap-2 p-2 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-primary fw-bold" style="width:48px;height:48px;">
                                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 2)) }}
                                </span>
                                <span class="flex-grow-1 overflow-hidden">
                                    <span class="d-block fw-semibold text-truncate">{{ Auth::user()->name }}</span>
                                    <span class="d-block small text-white-50 text-truncate">{{ Auth::user()->role?->name }}</span>
                                </span>
                                <i class="fa-solid fa-chevron-down small"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark w-100 shadow">
                                <li>
                                    <button type="button" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
                                        Cerrar sesión
                                    </button>
                                </li>
                            </ul>
                            <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                        </div>
                    </div>
                </aside>
            @endauth

            <div class="@auth col-md-9 col-lg-10 @else col-12 @endauth px-0">
                @unless($isLoginPage && auth()->guest())
                    <nav class="navbar navbar-expand-md bg-white shadow-sm">
                        <div class="container-fluid px-4">
                            <span class="navbar-brand mb-0 h1">{{ config('app.name', 'Market Web') }}</span>
                            @if ($isAdmin)
                                <form method="POST" action="{{ route('admin.store-context.update') }}" class="mx-auto w-100 px-3" style="max-width: 520px;">
                                    @csrf
                                    @method('PUT')
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fa-solid fa-store text-muted"></i>
                                        </span>
                                        <input
                                            type="text"
                                            id="admin-store-search"
                                            class="form-control border-start-0"
                                            list="admin-store-options"
                                            placeholder="Buscar local..."
                                            autocomplete="off"
                                            value="{{ optional($adminStores->firstWhere('id', $adminSelectedStoreId))->name }}"
                                        >
                                        <input type="hidden" name="store_id" id="admin-store-id" value="{{ $adminSelectedStoreId }}">
                                        <button class="btn btn-outline-primary" type="submit">Ver</button>
                                    </div>
                                    <datalist id="admin-store-options">
                                        @foreach ($adminStores as $store)
                                            <option value="{{ $store->name }}" data-id="{{ $store->id }}"></option>
                                        @endforeach
                                    </datalist>
                                </form>
                            @endif
                            <ul class="navbar-nav ms-auto">
                                @guest
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('login') }}">Iniciar sesión</a>
                                    </li>
                                @endguest
                            </ul>
                        </div>
                    </nav>
                @endunless
                <main class="{{ $isLoginPage && auth()->guest() ? 'p-0' : 'p-3' }}">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>
    @if ($isAdmin)
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('admin-store-search');
        const hiddenId = document.getElementById('admin-store-id');
        const options = Array.from(document.querySelectorAll('#admin-store-options option'));
        if (!input || !hiddenId || options.length === 0) return;

        function syncStoreId() {
            const selected = options.find((opt) => opt.value === input.value);
            hiddenId.value = selected?.dataset.id || '';
        }

        input.addEventListener('input', syncStoreId);
        input.addEventListener('change', syncStoreId);
    });
    </script>
    @endif
</body>
</html>
