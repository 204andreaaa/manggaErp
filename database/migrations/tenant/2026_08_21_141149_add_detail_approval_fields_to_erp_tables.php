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
            $table->unsignedBigInteger('payment_advice_detail_id')->nullable()->after('payment_advice_id');
            $table->foreign('payment_advice_detail_id')->references('id')->on('erp_payment_advice_details')->nullOnDelete();
        });

        Schema::table('erp_payment_advice_details', function (Blueprint $table) {
            $table->string('invoice_no')->nullable()->after('date_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->dropForeign(['payment_advice_detail_id']);
            $table->dropColumn('payment_advice_detail_id');
        });

        Schema::table('erp_payment_advice_details', function (Blueprint $table) {
            $table->dropColumn('invoice_no');
        });
    }
};
