<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_annual_budget_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('account_id')->constrained('erp_accounts')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('area');
            $table->string('work_type');
            $table->string('work_subtype')->nullable();
            $table->string('currency')->default('IDR');

            // Rollup (dihitung) — diisi ulang oleh service, bukan diinput user.
            $table->decimal('total_expense_plan', 20, 2)->default(0);
            $table->decimal('total_actual_expense', 20, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'year', 'area', 'work_type', 'work_subtype'], 'annual_plan_unique_combo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_annual_budget_plans');
    }
};
