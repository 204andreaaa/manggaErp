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
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->unsignedBigInteger('request_form_id')->nullable()->change();
            $table->foreignId('purchase_order_id')->nullable()->after('request_form_id')->constrained('erp_purchase_orders')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
            $table->unsignedBigInteger('request_form_id')->nullable(false)->change();
        });
    }
};
