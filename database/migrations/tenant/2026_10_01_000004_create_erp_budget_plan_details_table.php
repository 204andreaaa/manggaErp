<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_budget_plan_details', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('monthly_budget_plan_id')->constrained('erp_monthly_budget_plans')->cascadeOnDelete();

            $table->string('expense_type');
            $table->boolean('is_indirect')->default(false);
            $table->decimal('amount', 20, 2)->default(0);

            // Rollup (dihitung)
            $table->decimal('total_expense_plan', 20, 2)->default(0);
            $table->decimal('actual_advance_request', 20, 2)->default(0);
            $table->decimal('actual_expense_with_advance', 20, 2)->default(0);
            $table->decimal('actual_expense_without_advance', 20, 2)->default(0);
            $table->decimal('actual_expense_amount', 20, 2)->default(0);

            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['monthly_budget_plan_id', 'expense_type', 'is_indirect'], 'budget_detail_unique_expense_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_budget_plan_details');
    }
};
