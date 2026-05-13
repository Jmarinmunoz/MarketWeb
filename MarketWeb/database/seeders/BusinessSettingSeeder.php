<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use App\Models\Store;
use Illuminate\Database\Seeder;

class BusinessSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->orderBy('id')->first();

        if (! $store) {
            return;
        }

        BusinessSetting::firstOrCreate(
            ['store_id' => $store->id],
            [
                'business_name' => 'Market Web',
                'currency' => 'CLP',
                'receipt_message' => 'Gracias por su compra',
            ]
        );
    }
}
