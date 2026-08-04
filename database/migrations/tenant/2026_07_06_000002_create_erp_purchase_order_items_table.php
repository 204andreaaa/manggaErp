<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('erp_purchase_orders')->cascadeOnDelete();
            $table->foreignId('request_form_item_id')->constrained('request_form_items')->cascadeOnDelete();
            
            $table->decimal('qty', 15, 2);
            $table->decimal('unit_cost', 16, 2)->default(0);
            $table->decimal('tax', 16, 2)->default(0);
            $table->decimal('total_cost', 16, 2)->default(0);
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_purchase_order_items');
    }
};
