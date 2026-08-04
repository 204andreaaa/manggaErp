<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erp_payment_advices')) {
            Schema::create('erp_payment_advices', function (Blueprint $table) {
                $table->id();
                $table->string('supplier_invoice_no', 100)->unique();
                $table->unsignedBigInteger('erp_purchase_order_id')->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('invoice_no', 150)->nullable();
                $table->string('contact_person', 150)->nullable();
                $table->date('due_date')->nullable();
                $table->decimal('total_invoice_amount', 18, 2)->default(0);
                $table->decimal('total_invoice_amount_with_tax', 18, 2)->default(0);
                $table->decimal('sum_payment_amount', 18, 2)->default(0);
                $table->decimal('sum_payment_amount_with_tax', 18, 2)->default(0);
                $table->decimal('outstanding', 18, 2)->default(0);
                $table->string('status', 50)->default('Draft');
                $table->string('approval_status', 50)->default('Draft');
                $table->boolean('payment_closed')->default(false);
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->timestamps();

                if (Schema::hasTable('erp_purchase_orders')) {
                    $table->foreign('erp_purchase_order_id')->references('id')->on('erp_purchase_orders')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_payment_advices');
    }
};
