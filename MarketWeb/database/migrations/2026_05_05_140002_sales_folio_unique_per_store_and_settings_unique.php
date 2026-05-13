<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['folio']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unique(['store_id', 'folio']);
        });

        Schema::table('business_settings', function (Blueprint $table) {
            $table->unique('store_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable(false)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable(false)->change();
        });

        Schema::table('business_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->change();
        });

        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropUnique(['store_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'folio']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unique('folio');
        });
    }
};
