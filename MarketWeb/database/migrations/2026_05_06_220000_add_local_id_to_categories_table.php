<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('local_id')->nullable()->after('store_id');
        });

        $storeIds = DB::table('categories')
            ->select('store_id')
            ->whereNotNull('store_id')
            ->groupBy('store_id')
            ->pluck('store_id');

        foreach ($storeIds as $storeId) {
            $categories = DB::table('categories')
                ->where('store_id', $storeId)
                ->orderBy('id')
                ->get(['id']);

            $counter = 1;
            foreach ($categories as $category) {
                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['local_id' => $counter]);
                $counter++;
            }
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->unique(['store_id', 'local_id'], 'categories_store_id_local_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_store_id_local_id_unique');
            $table->dropColumn('local_id');
        });
    }
};
