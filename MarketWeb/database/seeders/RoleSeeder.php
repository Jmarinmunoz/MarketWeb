<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'Administrador'],
            ['description' => 'Acceso total al sistema']
        );

        Role::updateOrCreate(
            ['name' => 'Vendedor'],
            ['description' => 'Acceso a ventas, productos e inventario']
        );
    }
}
