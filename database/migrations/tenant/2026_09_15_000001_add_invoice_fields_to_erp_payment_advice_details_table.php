<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('erp_payment_advice_details')) {
            Schema::table('erp_payment_advice_details', function (Blueprint $table) {
                if (!Schema::hasColumn('erp_payment_advice_details', 'payment_receipt')) {
                    $table->string('payment_receipt', 255)->nullable()->after('date_paid');
                }
                if (!Schema::hasColumn('erp_payment_advice_details', 'invoice_no')) {
                    $table->string('invoice_no', 100)->nullable()->after('payment_receipt');
                }
                if (!Schema::hasColumn('erp_payment_advice_details', 'invoice_attachment')) {
                    $table->string('invoice_attachment', 255)->nullable()->after('invoice_no');
                }
                if (!Schema::hasColumn('erp_payment_advice_details', 'submitted_by_id')) {
                    $table->unsignedBigInteger('submitted_by_id')->nullable()->after('invoice_attachment');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('erp_payment_advice_details')) {
            Schema::table('erp_payment_advice_details', function (Blueprint $table) {
                if (Schema::hasColumn('erp_payment_advice_details', 'invoice_attachment')) {
                    $table->dropColumn('invoice_attachment');
                }
                if (Schema::hasColumn('erp_payment_advice_details', 'invoice_no')) {
                    $table->dropColumn('invoice_no');
                }
                if (Schema::hasColumn('erp_payment_advice_details', 'payment_receipt')) {
                    $table->dropColumn('payment_receipt');
                }
            });
        }
    }
};
