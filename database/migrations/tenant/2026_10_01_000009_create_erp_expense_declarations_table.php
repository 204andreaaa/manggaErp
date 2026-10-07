<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_expense_declarations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('record_type'); // with_advance, without_advance

            // Stored explicitly (not derived) — business rule #2 in the spec admits
            // a declaration's budget line can legitimately differ from its kasbon's.
            $table->foreignId('budget_plan_detail_id')->constrained('erp_budget_plan_details');
            $table->foreignId('advance_request_id')->nullable()->constrained('erp_advance_requests');

            $table->foreignId('work_id')->constrained('erp_work_items');
            $table->string('site_name')->nullable();
            $table->boolean('is_indirect')->default(false);
            $table->string('po_number')->nullable();
            $table->string('vehicle_info')->nullable();

            $table->unsignedBigInteger('request_by_id');
            $table->date('request_date');
            $table->text('description')->nullable();

            $table->decimal('amount_declaration', 20, 2)->default(0);
            $table->string('currency')->default('IDR');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->decimal('converted_amount_idr', 20, 2)->default(0); // dihitung

            $table->string('rfin_ref')->nullable();
            $table->string('remark_rf_line')->nullable();
            $table->text('remark')->nullable();

            $table->string('status')->default('Draft');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_expense_declarations');
    }
};
