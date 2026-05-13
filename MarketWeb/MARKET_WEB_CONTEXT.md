# Market Web — Contexto de negocio y técnico

Documento pensado para transferir contexto a otra persona o IA sin adjuntar todo el repositorio. Última actualización orientativa: revisar `composer.json`, `package.json` y `.env` para versiones exactas en tu máquina.

---

## 1. Idea de negocio

**Market Web** es una aplicación web para un negocio tipo **minimarket** (tienda pequeña): permite llevar **catálogo**, **stock**, **ventas en punto de venta (POS)**, **historial**, **movimientos de inventario** y **datos del negocio**, con usuarios diferenciados por rol.

### Roles

| Rol | Descripción breve |
|-----|-------------------|
| **Administrador** | Acceso completo: categorías, alta/edición/baja de productos, usuarios, reportes, configuración del negocio, inventario, movimientos de stock, POS e historial global. |
| **Vendedor** | POS, consulta de productos e inventario, **movimientos de stock** (mismo uso funcional que el admin en ese módulo), historial **de sus propias ventas** (y detalle por AJAX cuando aplica). Sin gestión de usuarios, categorías CRUD completo, reportes administrativos ni configuración avanzada. |

La autorización por rutas está en `routes/web.php` con el middleware personalizado `role` (alias de `EnsureUserHasRole`). El menú lateral en `resources/views/layouts/app.blade.php` oculta enlaces según el rol.

### Módulos principales (negocio)

- **Dashboard**: métricas distintas para admin (negocio global) vs vendedor (ventas propias + vistas alineadas visualmente).
- **Productos**: listado con filtros y panel lateral de detalle; el vendedor no crea/edita/elimina como el admin.
- **Ventas / POS**: carrito por sesión, lectura por código de barra, métodos de pago; **sin descuento** (total sin línea de descuento).
- **Inventario**: vista de stock con KPIs y filtros.
- **Movimientos de stock**: entradas, salidas y ajustes; **motivo obligatorio** para **salida** y **ajuste**; búsqueda de producto por AJAX (solo activos).
- **Historial de ventas**: tabla sin columna folio en listado; detalle en modal (folio, fecha, método, total, productos, estado).
- **Reportes** (solo admin): KPIs, gráfico de movimientos por tipo (**últimos 30 días**), ventas por método de pago, tabla de movimientos recientes.

### Reglas de negocio destacadas (implementadas)

- Zona horaria de la aplicación: **`America/Santiago`** (`APP_TIMEZONE` en `.env`).
- Idioma UI: **español** (`APP_LOCALE=es`, traducciones en `lang/` cuando existan).
- **Ventas**: al confirmar se registran venta, detalles, descuento en **0**, actualización de stock y movimiento tipo **VENTA** con motivo/referencia acorde al folio según la lógica actual en `SaleController`.
- **Efectivo en POS**: campos “recibido” / “cambio” solo cuando el método es efectivo.
- **Stock**: los movimientos manuales actualizan `products.stock` dentro de transacción; validación de stock insuficiente en salidas.

---

## 2. Stack tecnológico

### Lenguajes y runtime

| Tecnología | Uso |
|------------|-----|
| **PHP 8.3+** | Backend (`composer.json`: `"php": "^8.3"`). |
| **JavaScript (ES modules)** | Frontend empaquetado con Vite (`resources/js/app.js`). |
| **Sass (SCSS)** | Estilos propios (`resources/sass/app.scss`). |
| **HTML** | Vistas Blade (`resources/views/**/*.blade.php`). |

### Framework y backend (Composer)

| Paquete | Uso |
|---------|-----|
| **Laravel** (^13) | Framework MVC, rutas, Eloquent, middleware, etc. |
| **laravel/ui** | Auth con scaffolding Bootstrap (login/register deshabilitado en rutas según proyecto). |
| **laravel/tinker** | Consola REPL (desarrollo). |

Herramientas de desarrollo habituales en el proyecto: PHPUnit, Laravel Pint, Pail, Faker, etc. (ver `composer.json` → `require-dev`).

### Frontend y librerías (npm)

| Librería | Uso en Market Web |
|----------|-------------------|
| **Vite** | Build y dev server de assets. |
| **Bootstrap 5** | Layout, grid, componentes (modales, offcanvas, navbar). |
| **DataTables** (`datatables.net` + `datatables.net-bs5`) | Tablas con búsqueda, orden y paginación. |
| **Chart.js** | Gráficos en dashboard y reportes. |
| **Font Awesome** (`@fortawesome/fontawesome-free`) | Iconografía. |
| **SweetAlert2** | Alertas en POS y validaciones cliente. |
| **jQuery** | Dependencia habitual de DataTables; expuesto en `resources/js/app.js` junto con DataTables. |
| **Axios** | Cliente HTTP (disponible en el bundle; uso según vistas). |

Notas:

- En `package.json` también aparecen **Tailwind CSS** y **@tabler/core**; la interfaz principal del panel está construida sobre **Bootstrap + SCSS propio** (`resources/sass`). Si algo no se importa en `app.scss`/`app.js`, puede estar instalado pero sin uso activo.

---

## 3. Extensiones PHP recomendadas

Para Laravel y este tipo de proyecto suele hacer falta (según instalación):

**Siempre útiles**

- `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `pdo`

**Según base de datos**

- **MySQL / MariaDB**: `pdo_mysql`, `mysqli` (opcional según tooling)
- **SQLite** (desarrollo local): `pdo_sqlite`, `sqlite3`

**Opcional**

- `curl` (Composer, HTTP externo)

Lista oficial actualizada: documentación de requisitos de Laravel para tu versión.

---

## 4. Base de datos

### Motor previsto en producción

En **`.env.example`** la conexión por defecto es **MySQL**:

- `DB_CONNECTION=mysql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

Nombre sugerido de base: `market_web` (ajustable).

### Desarrollo local

Es válido usar **SQLite** (`DB_CONNECTION=sqlite` y `DB_DATABASE` apuntando a `database/database.sqlite`) si no tienes MySQL levantado; las migraciones están pensadas para SQL estándar compatible con ambos en este proyecto.

### Tablas de dominio (migraciones)

Definidas bajo `database/migrations/` (además de tablas estándar Laravel `cache`, `jobs`, etc., si están habilitadas):

- `roles`
- `users` (+ campos de rol y estado según migraciones “market”)
- `categories`
- `products`
- `payment_methods`
- `sales`
- `sale_details`
- `stock_movements`
- `business_settings`

Relaciones y campos editables en `app/Models/*.php`.

---

## 5. Archivos clave para profundizar (sin peso del repo completo)

| Archivo | Para qué sirve |
|---------|----------------|
| `routes/web.php` | Mapa de URLs y permisos por rol. |
| `app/Http/Middleware/EnsureUserHasRole.php` | Lógica del middleware `role`. |
| `app/Http/Controllers/SaleController.php` | POS, carrito, checkout, historial y detalle JSON. |
| `app/Http/Controllers/StockMovementController.php` | Movimientos y búsqueda de productos. |
| `app/Http/Controllers/ProductController.php` | CRUD y panel JSON de productos. |
| `database/migrations/*.php` | Esquema de datos. |
| `database/seeders/*` | Datos iniciales (roles, admin demo, métodos de pago, etc.). |

---

## 6. Cómo arrancar (recordatorio breve)

1. Copiar `.env.example` → `.env`, configurar `APP_KEY`, BD y `APP_TIMEZONE`.
2. `composer install`
3. `php artisan migrate --seed` (o migraciones sin seed según necesidad).
4. `npm install` y `npm run build` (o `npm run dev` en desarrollo).
5. Servidor: `php artisan serve` o el comando que uses en tu entorno.

Credenciales de demo dependen de los seeders (por ejemplo usuario administrador definido en `AdminUserSeeder` si existe).

---

## 7. Migraciones — código completo

Copia fiel de los archivos en `database/migrations/` al momento de documentar. El orden de ejecución depende de las fechas en el nombre del archivo; **`roles`** debe existir antes de **`add_market_fields_to_users`** (FK `role_id`).

### `0001_01_01_000000_create_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
```

### `0001_01_01_000001_create_cache_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
```

### `0001_01_01_000002_create_jobs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
```

### `2026_04_28_165309_create_roles_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
```

### `2026_04_28_165310_create_categories_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

### `2026_04_28_165310_add_market_fields_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->after('id')->constrained('roles');
            $table->boolean('status')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['status', 'last_login_at']);
        });
    }
};
```

### `2026_04_28_165311_create_payment_methods_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
```

### `2026_04_28_165312_create_products_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('barcode', 100)->unique()->nullable();
            $table->string('sku', 100)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('sale_price', 12, 2);
            $table->integer('stock')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### `2026_04_28_165312_create_sales_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 30)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('status', 20)->default('COMPLETADA');
            $table->timestamp('sold_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
```

### `2026_04_28_165313_create_sale_details_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
```

### `2026_04_28_165313_create_stock_movements_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('user_id')->constrained('users');
            $table->string('type', 30);
            $table->integer('quantity');
            $table->integer('previous_stock');
            $table->integer('new_stock');
            $table->string('reason')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
```

### `2026_04_28_165314_create_business_settings_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('rut', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('currency', 10)->default('CLP');
            $table->string('logo_path')->nullable();
            $table->text('receipt_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
```

---

## 8. Modelos Eloquent — código completo

Archivos en `app/Models/`.

### `User.php`

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role_id', 'name', 'email', 'password', 'status', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'status' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
```

### `Role.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
```

### `Category.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
```

### `Product.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'barcode',
        'sku',
        'name',
        'description',
        'purchase_price',
        'sale_price',
        'stock',
        'minimum_stock',
        'status',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
```

### `PaymentMethod.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['name', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
```

### `Sale.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'folio',
        'user_id',
        'payment_method_id',
        'subtotal',
        'discount',
        'total',
        'status',
        'sold_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }
}
```

### `SaleDetail.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

### `StockMovement.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'previous_stock',
        'new_stock',
        'reason',
        'reference',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### `BusinessSetting.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'business_name',
        'rut',
        'address',
        'phone',
        'email',
        'currency',
        'logo_path',
        'receipt_message',
    ];
}
```

---

### Nota sobre mantenimiento de este documento

Las secciones **7** y **8** son una **réplica en texto** del código en el repositorio. Si cambias migraciones o modelos, conviene **actualizar también este archivo** (o regenerarlo desde los archivos fuente) para que quien lo lea no trabaje sobre un esquema desactualizado.

Las secciones **1–6** resumen negocio, stack y BD; **7–8** amplían con el detalle de esquema y relaciones Eloquent.
