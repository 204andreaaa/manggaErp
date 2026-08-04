<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_no', 50)->unique();
            $table->foreignId('request_form_id')->constrained('request_forms')->cascadeOnDelete();
            
            // Supplier
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            
            // Destinations/Info
            $table->string('destination', 100)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('bank_account', 150)->nullable();
            
            // Dates
            $table->date('date')->nullable();
            $table->date('eta')->nullable();
            
            // Amounts & Status
            $table->decimal('total_po_amount', 16, 2)->default(0);
            $table->decimal('tax', 16, 2)->default(0);
            $table->decimal('total_po_amount_with_tax', 16, 2)->default(0);
            $table->decimal('balance_amount', 16, 2)->default(0);
            $table->decimal('amount_paid', 16, 2)->default(0);
            $table->string('payment_method', 100)->nullable();
            $table->string('status', 50)->default('Draft'); // Draft, Approved, Submitted, Completed, Cancelled
            $table->boolean('over')->default(false);
            
            // Additional settings
            $table->text('description')->nullable();
            $table->boolean('bypass_verification')->default(false);
            $table->string('check_transfer_to', 100)->nullable();
            $table->integer('elapsed_time')->default(0);
            $table->boolean('payment_closed')->default(false);
            $table->boolean('gr')->default(false); // Goods Received checkbox

            // Approvals tracking
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->timestamp('submitted_date')->nullable();
            $table->timestamp('approved_date')->nullable();
            $table->timestamp('rejected_date')->nullable();

            // Expense Types
            $table->boolean('expense_material_equipment')->default(false);
            $table->boolean('expense_material_subcon')->default(false);
            $table->boolean('expense_personnel')->default(false);
            $table->boolean('expense_transportation')->default(false);
            $table->boolean('expense_utilities')->default(false);
            $table->boolean('expense_office')->default(false);
            $table->boolean('expense_other')->default(false);

            // Print Related Info
            $table->string('project', 150)->nullable();
            $table->string('invoice_to', 150)->nullable();
            $table->string('attention_to', 150)->nullable();
            $table->string('transfer_to', 150)->nullable();
            $table->text('other_instructions')->nullable();
            $table->string('payment_terms', 150)->nullable();
            $table->string('signature', 50)->nullable();

            // Biometric
            $table->unsignedBigInteger('verified_by_id')->nullable()->index();
            $table->timestamp('verification_timestamp')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_purchase_orders');
    }
};
