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
        Schema::table('erp_payment_advices', function (Blueprint $table) {
            $table->string('termin_name')->nullable()->after('supplier_invoice_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_payment_advices', function (Blueprint $table) {
            $table->dropColumn('termin_name');
        });
    }
};
