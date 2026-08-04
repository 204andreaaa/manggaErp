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
        Schema::create('erp_goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->string('do_detail_no')->unique(); // Format: DOIN-YYYY-MM-XXXXXX
            $table->foreignId('erp_goods_receipt_id')->constrained('erp_goods_receipts')->cascadeOnDelete();
            $table->foreignId('erp_purchase_order_item_id')->nullable()->constrained('erp_purchase_order_items')->nullOnDelete();
            $table->foreignId('request_form_item_id')->nullable()->constrained('request_form_items')->nullOnDelete();
            
            $table->decimal('delivered_qty', 15, 2)->default(0);
            $table->decimal('received_qty', 15, 2)->default(0);
            $table->text('remark')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_goods_receipt_items');
    }
};
