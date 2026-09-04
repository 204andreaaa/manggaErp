<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('report_type', 50); // purchase_orders, request_forms, goods_receipts, payment_advices, work_items, stocks, employees
            $table->json('selected_columns');   // array of column keys in order
            $table->json('filters')->nullable();         // filter conditions
            $table->string('date_field', 50)->nullable();
            $table->string('date_range_preset', 50)->nullable(); // all_time, today, this_month, this_year, custom
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_reports');
    }
};
