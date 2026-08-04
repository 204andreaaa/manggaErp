<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code');
            $table->string('part_number')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            
            $table->unsignedBigInteger('uom_id')->nullable(); // ERP UOM
            $table->bigInteger('buying_price')->default(0);
            
            $table->unsignedBigInteger('product_family_id')->nullable();
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('product_model_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('set null');
            $table->foreign('product_family_id')->references('id')->on('product_families')->onDelete('set null');
            $table->foreign('product_type_id')->references('id')->on('product_types')->onDelete('set null');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('product_model_id')->references('id')->on('product_models')->onDelete('set null');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_products');
    }
};
