<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('erp_goods_receipts') && !Schema::hasColumn('erp_goods_receipts', 'supplier_do_no')) {
            Schema::table('erp_goods_receipts', function (Blueprint $table) {
                $table->string('supplier_do_no')->nullable()->after('record_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('erp_goods_receipts') && Schema::hasColumn('erp_goods_receipts', 'supplier_do_no')) {
            Schema::table('erp_goods_receipts', function (Blueprint $table) {
                $table->dropColumn('supplier_do_no');
            });
        }
    }
};
