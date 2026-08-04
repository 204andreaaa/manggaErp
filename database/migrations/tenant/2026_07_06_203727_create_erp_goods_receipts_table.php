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
        Schema::create('erp_goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('do_no')->unique(); // Format: GR-YYYY-MM-XXXXX
            $table->foreignId('erp_purchase_order_id')->nullable()->constrained('erp_purchase_orders')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('erp_suppliers')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('erp_warehouses')->nullOnDelete();
            $table->date('date')->nullable();
            $table->string('sending_contact')->nullable();
            $table->string('receiving_contact')->nullable();
            $table->string('status')->default('Draft'); // Draft, Received
            $table->decimal('total_delivered_qty', 15, 2)->default(0);
            $table->decimal('total_received_qty', 15, 2)->default(0);
            $table->string('record_type')->default('External');
            $table->text('remarks')->nullable();
            $table->boolean('bypass_verification')->default(false);
            $table->date('document_complete_date')->nullable();
            $table->date('status_receive_date')->nullable();
            
            // Biometric / Verification
            $table->unsignedBigInteger('verified_by_id')->nullable();
            $table->timestamp('verification_timestamp')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_goods_receipts');
    }
};
