<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_warehouse_id')->nullable()->index()->after('request_form_id');
        });
    }

    public function down(): void
    {
        Schema::table('erp_purchase_orders', function (Blueprint $table) {
            $table->dropColumn('erp_warehouse_id');
        });
    }
};
