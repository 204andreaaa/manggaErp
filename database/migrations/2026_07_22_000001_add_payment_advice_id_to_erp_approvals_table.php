<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('erp_approvals') && !Schema::hasColumn('erp_approvals', 'payment_advice_id')) {
            Schema::table('erp_approvals', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_advice_id')->nullable()->after('purchase_order_id')->index();
                if (Schema::hasTable('erp_payment_advices')) {
                    $table->foreign('payment_advice_id')->references('id')->on('erp_payment_advices')->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('erp_approvals') && Schema::hasColumn('erp_approvals', 'payment_advice_id')) {
            Schema::table('erp_approvals', function (Blueprint $table) {
                $table->dropForeign(['payment_advice_id']);
                $table->dropColumn('payment_advice_id');
            });
        }
    }
};
