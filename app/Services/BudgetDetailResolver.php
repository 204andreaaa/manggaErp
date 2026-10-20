<?php

namespace App\Services;

use App\Models\Erp\AnnualBudgetPlan;
use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\MonthlyBudgetPlan;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Finds the Budget Plan Detail a kasbon / declaration line should draw from,
 * the way Salesforce does it: the user never picks a budget line directly,
 * they pick Account (Budget Parent) + Area + Work Type + Subtype + a date,
 * and the expense type of each line picks the detail inside that month.
 */
class BudgetDetailResolver
{
    public static function monthlyFor(int $budgetParentId, string $area, string $workType, ?string $workSubtype, string $date): MonthlyBudgetPlan
    {
        $when = Carbon::parse($date);

        $annual = AnnualBudgetPlan::where('budget_parent_id', $budgetParentId)
            ->where('year', $when->year)
            ->where('area', $area)
            ->where('work_type', $workType)
            ->where(function ($q) use ($workSubtype) {
                $workSubtype === null || $workSubtype === ''
                    ? $q->whereNull('work_subtype')->orWhere('work_subtype', '')
                    : $q->where('work_subtype', $workSubtype);
            })
            ->first();

        if (!$annual) {
            throw ValidationException::withMessages([
                'area' => "Annual Budget Plan tahun {$when->year} untuk {$area} / {$workType}".($workSubtype ? " / {$workSubtype}" : '').' belum dibuat.',
            ]);
        }

        $monthly = $annual->monthlyBudgetPlans()->where('month', $when->month)->first();

        if (!$monthly) {
            throw ValidationException::withMessages([
                'area' => "Monthly Budget Plan bulan {$when->translatedFormat('F Y')} di Annual Budget Plan {$annual->code} belum dibuat.",
            ]);
        }

        return $monthly;
    }

    /**
     * Direct expense lines are preferred; an indirect line is used only when
     * the month has no direct line for that expense type.
     */
    public static function detailFor(MonthlyBudgetPlan $monthly, string $expenseType): BudgetPlanDetail
    {
        $detail = $monthly->budgetPlanDetails()
            ->where('expense_type', $expenseType)
            ->orderBy('is_indirect')
            ->first();

        if (!$detail) {
            throw ValidationException::withMessages([
                'lines' => "Budget Plan Detail \"{$expenseType}\" belum ada di Monthly Budget Plan {$monthly->code}.",
            ]);
        }

        return $detail;
    }
}
