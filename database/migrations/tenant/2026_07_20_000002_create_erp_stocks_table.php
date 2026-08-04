<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erp_stocks')) {
            Schema::create('erp_stocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('erp_product_id');
                $table->unsignedBigInteger('erp_warehouse_id');
                $table->decimal('qty_on_hand', 15, 2)->default(0);
                $table->timestamps();

                $table->unique(['erp_product_id', 'erp_warehouse_id']);
                $table->foreign('erp_product_id')->references('id')->on('erp_products')->onDelete('cascade');
                $table->foreign('erp_warehouse_id')->references('id')->on('erp_warehouses')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_stocks');
    }
};
