<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Administrador')->first();
        $store = Store::query()->orderBy('id')->first();

        if (! $adminRole || ! $store) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@marketweb.local'],
            [
                'store_id' => $store->id,
                'role_id' => $adminRole->id,
                'name' => 'Administrador',
                'password' => Hash::make('Admin1234!'),
                'status' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
