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
        Schema::create('erp_purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('erp_purchase_requests')->cascadeOnDelete();
            $table->foreignId('request_form_item_id')->constrained('request_form_items')->cascadeOnDelete();
            
            // These might be copied from the RF item for historical tracking or allowed to be edited
            $table->decimal('required_qty', 15, 2);
            $table->decimal('pr_requested_qty', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_purchase_request_items');
    }
};
