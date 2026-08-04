<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('erp_products') && !Schema::hasColumn('erp_products', 'is_physical')) {
            Schema::table('erp_products', function (Blueprint $table) {
                $table->boolean('is_physical')->default(true)->after('buying_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('erp_products') && Schema::hasColumn('erp_products', 'is_physical')) {
            Schema::table('erp_products', function (Blueprint $table) {
                $table->dropColumn('is_physical');
            });
        }
    }
};
