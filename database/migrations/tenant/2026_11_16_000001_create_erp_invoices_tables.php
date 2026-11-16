<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer invoices (money coming in), modelled on the Salesforce "Invoice" object:
 * an invoice, its lines per WID (Invoice Detail) and the payments received for it.
 */
return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('erp_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                 // INV-00001
            $table->string('invoice_number', 120)->index();       // e.g. MDU04871/IJE-MANDAU/...
            $table->date('invoice_date');
            $table->unsignedBigInteger('company_id')->nullable(); // master.companies
            $table->string('company_name', 150)->nullable();
            $table->string('customer_name', 150)->nullable();
            $table->string('top', 100)->nullable();               // e.g. "DP 30%", "TERMIN 1 - 95%"
            $table->text('remarks')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('New');         // New / Partial Paid / Paid (calculated)
            $table->decimal('invoice_amount', 18, 2)->default(0); // sum of details
            $table->decimal('amount_paid', 18, 2)->default(0);    // sum of payments
            $table->decimal('outstanding', 18, 2)->default(0);
            $table->date('partial_payment_date')->nullable();     // first payment received
            $table->date('final_payment_date')->nullable();       // date it became fully paid
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('tenant')->create('erp_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                 // INVDET-00001
            $table->foreignId('invoice_id')->constrained('erp_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('work_item_id')->nullable()->index();
            $table->string('project_code', 80)->nullable();
            $table->string('terms', 100)->nullable();             // e.g. "DP I"
            $table->decimal('amount', 18, 2);
            $table->date('po_date')->nullable();                  // customer PO date
            $table->date('received_doc_date')->nullable();
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('erp_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('erp_invoices')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 18, 2);
            $table->string('method', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('erp_invoice_payments');
        Schema::connection('tenant')->dropIfExists('erp_invoice_details');
        Schema::connection('tenant')->dropIfExists('erp_invoices');
    }
};
