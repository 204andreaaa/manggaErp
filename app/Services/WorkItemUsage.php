<?php

namespace App\Services;

use App\Models\Erp\AdvanceRequest;
use App\Models\Erp\AnnualBudgetPlan;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\ExpenseDeclaration;
use App\Models\Erp\RequestForm;
use Illuminate\Support\Collection;

/**
 * Everything that points at a set of WIDs, for the WID / Sub Project / Budget
 * Parent detail pages: annual plans, RFs, POs, advance requests, declarations.
 */
class WorkItemUsage
{
    /** @param Collection<int, ErpWorkItem> $workItems */
    public static function for(Collection $workItems): array
    {
        $ids = $workItems->pluck('id')->all();
        $codes = $workItems->pluck('wid_code')->filter()->values()->all();

        return [
            'annual' => AnnualBudgetPlan::with('workItem')
                ->whereIn('work_item_id', $ids)
                ->orderByDesc('year')->orderBy('area')->get(),
            'requestForms' => self::requestForms($ids, $codes),
            'purchaseOrders' => self::purchaseOrders($ids, $codes),
            'advanceRequests' => AdvanceRequest::with(['budgetPlanDetail', 'work'])
                ->whereIn('work_id', $ids)->latest()->get(),
            'declarations' => ExpenseDeclaration::with(['advanceRequest', 'work'])
                ->whereIn('work_id', $ids)->latest()->get(),
        ];
    }

    /** Jump-bar entries for the related lists rendered by erp.budget.partials.wid-related. */
    public static function sections(array $usage): array
    {
        return [
            'annual' => ['Annual Budget Plans', $usage['annual']->count()],
            'rf' => ['Request Forms', $usage['requestForms']->count()],
            'po' => ['Purchase Orders', $usage['purchaseOrders']->count()],
            'advance' => ['Advance Request', $usage['advanceRequests']->count()],
            'declaration' => ['Expense Declaration', $usage['declarations']->count()],
        ];
    }

    /** RFs charged to one of the WIDs, on the header or on any line. Each gets ->wid_codes. */
    private static function requestForms(array $ids, array $codes): Collection
    {
        return RequestForm::with(['workItem', 'items:id,request_form_id,wid'])
            ->where(fn ($q) => $q->whereIn('work_item_id', $ids)
                ->orWhereIn('project_code', $codes)
                ->orWhereHas('items', fn ($i) => $i->whereIn('wid', $codes)))
            ->latest('id')->get()
            ->each(function (RequestForm $rf) use ($codes) {
                $rf->wid_codes = collect([$rf->workItem?->wid_code, $rf->project_code])
                    ->merge($rf->items->pluck('wid'))
                    ->filter(fn ($c) => in_array($c, $codes, true))
                    ->unique()->values();
            });
    }

    /**
     * POs with at least one line charged to one of the WIDs. A line is charged the
     * way PO approval cuts the budget: the RF line's WID, else the RF's WID, else the
     * RF's project code. Each PO gets ->charged_amount (its lines on these WIDs) and ->wid_codes.
     */
    private static function purchaseOrders(array $ids, array $codes): Collection
    {
        $codeById = ErpWorkItem::whereIn('id', $ids)->pluck('wid_code', 'id');

        return ErpPurchaseOrder::with(['supplier', 'requestForm', 'items.requestFormItem'])
            ->where(fn ($q) => $q->whereHas('items.requestFormItem', fn ($i) => $i->whereIn('wid', $codes))
                ->orWhereHas('requestForm', fn ($r) => $r->whereIn('work_item_id', $ids)->orWhereIn('project_code', $codes)))
            ->latest('id')->get()
            ->each(function (ErpPurchaseOrder $po) use ($codes, $codeById) {
                $rf = $po->requestForm;
                $charged = 0.0;
                $widCodes = collect();
                foreach ($po->items as $item) {
                    $rfItem = $item->requestFormItem;
                    $code = $rfItem?->wid
                        ?: ($rf?->work_item_id ? ($codeById[$rf->work_item_id] ?? null) : null)
                        ?: (! $rf?->work_item_id ? $rf?->project_code : null);
                    if ($code !== null && in_array($code, $codes, true)) {
                        $charged += (float) ($item->total_cost ?: ($item->qty * $item->unit_cost) + ($item->tax ?? 0));
                        $widCodes->push($code);
                    }
                }
                $po->charged_amount = $charged;
                $po->wid_codes = $widCodes->unique()->values();
            })
            ->filter(fn (ErpPurchaseOrder $po) => $po->wid_codes->isNotEmpty())
            ->values();
    }
}
