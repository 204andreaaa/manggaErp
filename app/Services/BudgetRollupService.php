<?php

namespace App\Services;

use App\Models\Erp\AnnualBudgetPlan;
use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\MonthlyBudgetPlan;
use Illuminate\Support\Facades\DB;

/**
 * Recomputes the "dihitung" (plan/rollup) columns across the budget hierarchy.
 * Called after any Advance Request / Expense Declaration is created, changed,
 * or has its status changed — never computed on the fly in a view/report, so
 * every level always has a plain stored number to read.
 *
 * Only 'Approved' transactions count toward actual_* columns. Draft/Submitted/
 * Rejected kasbon or declarations haven't actually committed real money yet.
 * This default isn't explicitly confirmed by the business (the source spec
 * itself flags this as an open question) — change the status filter here if
 * the business decides otherwise.
 */
class BudgetRollupService
{
    public static function recalculateDetail(int $detailId): void
    {
        DB::transaction(function () use ($detailId) {
            $detail = BudgetPlanDetail::where('id', $detailId)->lockForUpdate()->firstOrFail();
            $monthly = MonthlyBudgetPlan::where('id', $detail->monthly_budget_plan_id)->lockForUpdate()->firstOrFail();

            $detail->total_expense_plan = $detail->amount * $monthly->qty;

            $detail->actual_advance_request = $detail->advanceRequests()
                ->where('status', 'Approved')
                ->sum('total_amount_advance');

            $detail->actual_expense_with_advance = $detail->expenseDeclarations()
                ->where('record_type', 'with_advance')
                ->where('status', 'Approved')
                ->sum('converted_amount_idr');

            $detail->actual_expense_without_advance = $detail->expenseDeclarations()
                ->where('record_type', 'without_advance')
                ->where('status', 'Approved')
                ->sum('converted_amount_idr');

            $detail->actual_expense_amount = $detail->actual_expense_with_advance + $detail->actual_expense_without_advance;

            $detail->save();

            self::recalculateMonthly($monthly->id);
        });
    }

    public static function recalculateMonthly(int $monthlyId): void
    {
        DB::transaction(function () use ($monthlyId) {
            $monthly = MonthlyBudgetPlan::where('id', $monthlyId)->lockForUpdate()->firstOrFail();
            $details = $monthly->budgetPlanDetails()->get();

            $direct = (float) $details->where('is_indirect', false)->sum('amount');
            $indirect = (float) $details->where('is_indirect', true)->sum('amount');
            $subtotal = $direct + $indirect;

            $monthly->total_direct_expense_plan = $direct;
            $monthly->total_indirect_expense_plan = $indirect;
            $monthly->subtotal_expense_plan = $subtotal;
            $monthly->total_expense_plan = $subtotal * $monthly->qty;
            $monthly->total_revenue_plan = $monthly->revenue_plan * $monthly->qty;
            $monthly->total_actual_expense = $details->sum('actual_expense_amount');
            $monthly->save();

            self::recalculateAnnual($monthly->annual_budget_plan_id);
        });
    }

    /**
     * outstanding = kasbon yang sudah disetujui (actual_amount) − total deklarasi
     * with_advance yang sudah Approved terhadap kasbon ini. 0 berarti lunas,
     * negatif berarti karyawan lebih bayar (perlu reimburse selisih).
     */
    public static function recalculateAdvanceRequest(int $advanceRequestId): void
    {
        DB::transaction(function () use ($advanceRequestId) {
            $advanceRequest = \App\Models\Erp\AdvanceRequest::where('id', $advanceRequestId)->lockForUpdate()->firstOrFail();

            $declaredApproved = $advanceRequest->expenseDeclarations()
                ->where('record_type', 'with_advance')
                ->where('status', 'Approved')
                ->sum('converted_amount_idr');

            $advanceRequest->advance_outstanding = (float) $advanceRequest->actual_amount - (float) $declaredApproved;
            $advanceRequest->save();
        });
    }

    public static function recalculateAnnual(int $annualId): void
    {
        DB::transaction(function () use ($annualId) {
            $annual = AnnualBudgetPlan::where('id', $annualId)->lockForUpdate()->firstOrFail();
            $monthlies = $annual->monthlyBudgetPlans()->get();

            $annual->total_expense_plan = $monthlies->sum('total_expense_plan');
            $annual->total_actual_expense = $monthlies->sum('total_actual_expense');
            $annual->save();
        });
    }
}
