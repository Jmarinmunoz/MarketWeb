<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['store_id', 'sold_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['store_id', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['store_id', 'status']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'status']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'created_at']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'sold_at']);
        });
    }
};
