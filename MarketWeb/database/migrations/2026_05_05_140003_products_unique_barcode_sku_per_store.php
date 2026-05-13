<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropUnique(['sku']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['store_id', 'barcode']);
            $table->unique(['store_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'barcode']);
            $table->dropUnique(['store_id', 'sku']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('barcode');
            $table->unique('sku');
        });
    }
};
