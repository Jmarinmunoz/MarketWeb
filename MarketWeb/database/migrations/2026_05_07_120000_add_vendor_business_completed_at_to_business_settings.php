<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->timestamp('vendor_business_completed_at')->nullable()->after('updated_at');
        });

        DB::table('business_settings')
            ->whereNotNull('business_name')
            ->where('business_name', '!=', '')
            ->whereNull('vendor_business_completed_at')
            ->update(['vendor_business_completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('vendor_business_completed_at');
        });
    }
};
