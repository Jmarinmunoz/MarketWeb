<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Efectivo', 'Tarjeta débito', 'Tarjeta crédito', 'Transferencia'] as $method) {
            PaymentMethod::updateOrCreate(['name' => $method], ['status' => true]);
        }
    }
}
