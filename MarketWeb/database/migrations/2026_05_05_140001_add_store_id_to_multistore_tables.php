<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $storeId = DB::table('stores')->orderBy('id')->value('id');

        if ($storeId === null) {
            $storeId = DB::table('stores')->insertGetId([
                'name' => 'Local principal',
                'slug' => 'local-principal',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) use ($storeId) {
            $table->foreignId('store_id')
                ->after('id')
                ->default($storeId)
                ->constrained('stores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) use ($storeId) {
            $table->foreignId('store_id')
                ->after('id')
                ->default($storeId)
                ->constrained('stores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('products', function (Blueprint $table) use ($storeId) {
            $table->foreignId('store_id')
                ->after('id')
                ->default($storeId)
                ->constrained('stores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable()
                ->after('id')
                ->constrained('stores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        foreach (DB::table('sales')->select('id', 'user_id')->cursor() as $sale) {
            $sid = DB::table('users')->where('id', $sale->user_id)->value('store_id');
            DB::table('sales')->where('id', $sale->id)->update(['store_id' => $sid]);
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable()
                ->after('id')
                ->constrained('stores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        foreach (DB::table('stock_movements')->select('id', 'product_id')->cursor() as $row) {
            $sid = DB::table('products')->where('id', $row->product_id)->value('store_id');
            DB::table('stock_movements')->where('id', $row->id)->update(['store_id' => $sid]);
        }

        Schema::table('business_settings', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable()
                ->after('id')
                ->constrained('stores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::table('business_settings')->whereNull('store_id')->update(['store_id' => $storeId]);
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });
    }
};
