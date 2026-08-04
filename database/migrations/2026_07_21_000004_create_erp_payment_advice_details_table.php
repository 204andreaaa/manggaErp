<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erp_payment_advice_details')) {
            Schema::create('erp_payment_advice_details', function (Blueprint $table) {
                $table->id();
                $table->string('supplier_detail_no', 100)->unique();
                $table->unsignedBigInteger('erp_payment_advice_id');
                $table->unsignedBigInteger('erp_purchase_order_id')->nullable();
                $table->unsignedBigInteger('erp_goods_receipt_id')->nullable();
                $table->date('gr_date')->nullable();
                $table->date('created_date_sid')->nullable();
                $table->date('approved_date')->nullable();
                $table->date('date_paid')->nullable();
                $table->decimal('payment_amount', 18, 2)->default(0);
                $table->decimal('payment_amount_with_tax', 18, 2)->default(0);
                $table->string('payment_method', 100)->default('Bank Transfer');
                $table->string('payment_type', 100)->default('Final Payment');
                $table->text('remark')->nullable();
                $table->string('days_invoice_overdue', 50)->default('< 30');
                $table->integer('days_overdue')->default(0);
                $table->string('approval_status', 50)->default('Draft');
                $table->timestamps();

                $table->foreign('erp_payment_advice_id')->references('id')->on('erp_payment_advices')->onDelete('cascade');
                if (Schema::hasTable('erp_purchase_orders')) {
                    $table->foreign('erp_purchase_order_id')->references('id')->on('erp_purchase_orders')->onDelete('set null');
                }
                if (Schema::hasTable('erp_goods_receipts')) {
                    $table->foreign('erp_goods_receipt_id')->references('id')->on('erp_goods_receipts')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_payment_advice_details');
    }
};
