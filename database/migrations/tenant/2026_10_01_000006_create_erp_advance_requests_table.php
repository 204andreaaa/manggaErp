<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_advance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            // account_id / area / work_type / work_subtype / expense_type are NOT
            // stored here — they're always read through budget_plan_detail's own
            // chain (see BudgetPlanDetail -> MonthlyBudgetPlan -> AnnualBudgetPlan),
            // so they can never drift out of sync with the parent.
            $table->foreignId('budget_plan_detail_id')->constrained('erp_budget_plan_details');
            $table->foreignId('work_id')->constrained('erp_work_items');
            $table->foreignId('project_id')->constrained('erp_projects');

            $table->date('budget_period');
            $table->date('advance_request_date');
            $table->unsignedBigInteger('request_by_id');

            $table->unsignedInteger('number_of_site')->nullable();
            $table->decimal('amount_per_unit', 20, 2)->default(0);
            $table->decimal('actual_amount', 20, 2)->default(0);
            $table->decimal('total_amount_advance', 20, 2)->default(0);

            $table->text('description')->nullable();

            $table->string('status')->default('Draft');
            $table->string('advance_payment_status')->default('New');
            $table->string('payment_status')->default('Unpaid');
            $table->date('payment_date')->nullable();
            $table->unsignedBigInteger('paid_by_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_advance_requests');
    }
};
