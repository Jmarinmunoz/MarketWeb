<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Cada vendedor debe tener su propio local (store_id) para no compartir
     * configuración ni catálogo con el administrador u otros vendedores.
     */
    public function up(): void
    {
        $vendedorRoleId = DB::table('roles')->where('name', 'Vendedor')->value('id');

        if ($vendedorRoleId === null) {
            return;
        }

        foreach (DB::table('users')->where('role_id', $vendedorRoleId)->get() as $user) {
            $baseSlug = Str::slug($user->name . '-' . $user->id);
            if ($baseSlug === '') {
                $baseSlug = 'local-vendedor-' . $user->id;
            }

            $slug = $baseSlug;
            $n = 1;
            while (DB::table('stores')->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $n;
                $n++;
            }

            $now = now();
            $storeId = DB::table('stores')->insertGetId([
                'name' => 'Local ' . $user->name,
                'slug' => $slug,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('users')->where('id', $user->id)->update(['store_id' => $storeId]);
        }
    }

    public function down(): void
    {
        // No revertir asignaciones de tienda sin riesgo de pérdida de datos.
    }
};
