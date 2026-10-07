<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_monthly_budget_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('annual_budget_plan_id')->constrained('erp_annual_budget_plans')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->string('unit')->nullable();
            $table->unsignedInteger('qty')->default(0);
            $table->decimal('revenue_plan', 20, 2)->default(0);

            // Rollup (dihitung)
            $table->decimal('total_revenue_plan', 20, 2)->default(0);
            $table->decimal('total_direct_expense_plan', 20, 2)->default(0);
            $table->decimal('total_indirect_expense_plan', 20, 2)->default(0);
            $table->decimal('subtotal_expense_plan', 20, 2)->default(0);
            $table->decimal('total_expense_plan', 20, 2)->default(0);
            $table->decimal('total_actual_expense', 20, 2)->default(0);

            $table->text('description')->nullable();
            $table->string('approval_status')->default('Draft');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['annual_budget_plan_id', 'month'], 'monthly_plan_unique_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_monthly_budget_plans');
    }
};
