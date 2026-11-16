<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice lines as Salesforce has them: each line names its Account (budget parent),
 * Project (sub project) and Work Id, carries a currency (IDR or USD) and an
 * "Update WID" flag. The invoice keeps a separate USD total, like Salesforce.
 */
return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('erp_invoice_details', function (Blueprint $table) {
            $table->unsignedBigInteger('budget_parent_id')->nullable()->index()->after('invoice_id');
            $table->unsignedBigInteger('sub_project_id')->nullable()->index()->after('budget_parent_id');
            $table->string('currency', 3)->default('IDR')->after('terms');
            $table->boolean('update_wid')->default(false)->after('received_doc_date');
        });

        Schema::connection('tenant')->table('erp_invoices', function (Blueprint $table) {
            $table->decimal('invoice_amount_usd', 18, 2)->default(0)->after('invoice_amount');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('erp_invoice_details', function (Blueprint $table) {
            $table->dropColumn(['budget_parent_id', 'sub_project_id', 'currency', 'update_wid']);
        });
        Schema::connection('tenant')->table('erp_invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_amount_usd');
        });
    }
};
