<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        Store::firstOrCreate(
            ['slug' => 'local-principal'],
            [
                'name' => 'Local principal',
                'status' => true,
            ]
        );
    }
}
