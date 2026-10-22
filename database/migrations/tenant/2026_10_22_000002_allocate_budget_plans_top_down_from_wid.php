<?php

use App\Services\BudgetRollupService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Budget dibagi dari atas ke bawah: WID -> Annual Budget Plan -> Monthly ->
 * Budget Plan Detail. Annual memotong Remaining WID saat dibuat, jadi kasbon
 * dan deklarasi tidak lagi memotong WID (itu dobel) — potongan WID dari
 * migration 2026_10_22_000001 dikembalikan di sini.
 *
 * Deklarasi dari kasbon sekarang selalu memotong Budget Plan Detail kasbonnya;
 * expense type deklarasi sendiri disimpan di kolom expense_type untuk laporan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The budget_parent_id foreign key may be leaning on the old unique index.
        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->index('budget_parent_id', 'annual_plan_budget_parent_idx');
        });
        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->dropUnique('annual_plan_unique_combo');
        });
        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->foreignId('work_item_id')->nullable()->after('budget_parent_id')->constrained('erp_work_items');
            $table->decimal('budget_amount', 20, 2)->default(0)->after('currency');
            $table->decimal('remaining_budget', 20, 2)->default(0)->after('budget_amount');
            $table->unique(['work_item_id', 'year', 'area', 'work_type', 'work_subtype'], 'annual_plan_unique_combo');
        });

        Schema::table('erp_monthly_budget_plans', function (Blueprint $table) {
            $table->decimal('budget_amount', 20, 2)->default(0)->after('revenue_plan');
            $table->decimal('remaining_budget', 20, 2)->default(0)->after('budget_amount');
        });

        Schema::table('erp_expense_declarations', function (Blueprint $table) {
            $table->string('expense_type')->nullable()->after('budget_plan_detail_id');
        });

        $db = DB::connection('tenant');

        // 1. Declarations remember their own expense type, then move onto their kasbon's detail.
        $db->statement('UPDATE erp_expense_declarations d JOIN erp_budget_plan_details bpd ON bpd.id = d.budget_plan_detail_id SET d.expense_type = bpd.expense_type');
        $db->statement("UPDATE erp_expense_declarations d JOIN erp_advance_requests a ON a.id = d.advance_request_id SET d.budget_plan_detail_id = a.budget_plan_detail_id WHERE d.record_type = 'with_advance'");

        // 2. Give back the WID cut that kasbon / without-advance declarations took in 000001.
        $giveBack = [];
        foreach ($db->table('erp_advance_requests')->whereNull('deleted_at')->where('status', 'Approved')->get(['work_id', 'actual_amount']) as $a) {
            $giveBack[$a->work_id] = ($giveBack[$a->work_id] ?? 0) + (float) $a->actual_amount;
        }
        foreach ($db->table('erp_expense_declarations')->whereNull('deleted_at')->where('status', 'Approved')->where('record_type', 'without_advance')->get(['work_id', 'converted_amount_idr']) as $d) {
            $giveBack[$d->work_id] = ($giveBack[$d->work_id] ?? 0) + (float) $d->converted_amount_idr;
        }
        foreach ($giveBack as $workId => $amount) {
            $db->table('erp_work_items')->where('id', $workId)->update(['remaining_budget' => DB::raw('remaining_budget + '.$amount)]);
        }

        // 3. Existing plans: link to the WID named like the plan's Area (same Budget Parent),
        //    and take today's planned totals as their allocation.
        foreach ($db->table('erp_monthly_budget_plans')->whereNull('deleted_at')->get(['id', 'total_expense_plan']) as $m) {
            $db->table('erp_monthly_budget_plans')->where('id', $m->id)->update(['budget_amount' => $m->total_expense_plan]);
        }
        foreach ($db->table('erp_annual_budget_plans')->whereNull('deleted_at')->get() as $annual) {
            $candidates = $db->table('erp_work_items as w')
                ->join('erp_sub_projects as sp', 'sp.id', '=', 'w.sub_project_id')
                ->whereNull('w.deleted_at')
                ->where('sp.budget_parent_id', $annual->budget_parent_id)
                ->where('w.name', trim($annual->area))
                ->pluck('w.id');
            $workId = $candidates->count() === 1 ? $candidates->first() : null;

            $amount = (float) $db->table('erp_monthly_budget_plans')
                ->where('annual_budget_plan_id', $annual->id)->whereNull('deleted_at')->sum('budget_amount');

            $db->table('erp_annual_budget_plans')->where('id', $annual->id)->update([
                'work_item_id' => $workId,
                'budget_amount' => $amount,
            ]);
            if ($workId) {
                $db->table('erp_work_items')->where('id', $workId)->update(['remaining_budget' => DB::raw('remaining_budget - '.$amount)]);
            }
        }

        // 4. Recompute every stored rollup with the new rules.
        foreach ($db->table('erp_advance_requests')->whereNull('deleted_at')->pluck('id') as $id) {
            BudgetRollupService::recalculateAdvanceRequest($id);
        }
        foreach ($db->table('erp_budget_plan_details')->whereNull('deleted_at')->pluck('id') as $id) {
            BudgetRollupService::recalculateDetail($id);
        }
        foreach ($db->table('erp_monthly_budget_plans')->whereNull('deleted_at')->pluck('id') as $id) {
            BudgetRollupService::recalculateMonthly($id);
        }
    }

    public function down(): void
    {
        Schema::table('erp_expense_declarations', function (Blueprint $table) {
            $table->dropColumn('expense_type');
        });
        Schema::table('erp_monthly_budget_plans', function (Blueprint $table) {
            $table->dropColumn(['budget_amount', 'remaining_budget']);
        });
        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->dropUnique('annual_plan_unique_combo');
            $table->dropForeign(['work_item_id']);
            $table->dropColumn(['work_item_id', 'budget_amount', 'remaining_budget']);
            $table->unique(['budget_parent_id', 'year', 'area', 'work_type', 'work_subtype'], 'annual_plan_unique_combo');
        });
        Schema::table('erp_annual_budget_plans', function (Blueprint $table) {
            $table->dropIndex('annual_plan_budget_parent_idx');
        });
    }
};
