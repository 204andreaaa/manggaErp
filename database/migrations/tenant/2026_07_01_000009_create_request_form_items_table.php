<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_form_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_form_id')->constrained('request_forms')->cascadeOnDelete();
            $table->string('rf_detail_no')->nullable();
            $table->string('wid')->nullable();
            $table->string('product_id_text')->nullable();
            $table->string('product_name');
            $table->string('model')->nullable();
            $table->text('product_description')->nullable();
            $table->text('remark')->nullable();
            $table->decimal('qty', 15, 2)->default(1);
            $table->bigInteger('unit_cost')->default(0);
            $table->string('status')->default('Draft');
            $table->timestamps();

            $table->index('request_form_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_form_items');
    }
};
