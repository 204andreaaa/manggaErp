<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No production rows reference account_id yet (confirmed empty before this
        // migration was written) — straight swap, no data migration needed.
        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropUnique('annual_plan_unique_combo');
        });

        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->dropColumn('account_id');
        });

        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->foreignId('budget_parent_id')->nullable()->after('code')->constrained('erp_budget_parents');
            $table->unique(['budget_parent_id', 'year', 'area', 'work_type', 'work_subtype'], 'annual_plan_unique_combo');
        });
    }

    public function down(): void
    {
        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->dropForeign(['budget_parent_id']);
            $table->dropUnique('annual_plan_unique_combo');
            $table->dropColumn('budget_parent_id');
        });

        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('code')->constrained('erp_accounts');
            $table->unique(['account_id', 'year', 'area', 'work_type', 'work_subtype'], 'annual_plan_unique_combo');
        });
    }
};
