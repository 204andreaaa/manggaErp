<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\RequestForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Spending of non-project RFs and their POs per department (record only, no budget cut).
 * RFs count by RF date, POs by PO date; "Terpakai" is the POs that went through approval.
 */
class DepartmentSpendingController extends Controller
{
    /** PO statuses counted as spent: approved (budget-cut point for WIDs) and completed. */
    public const SPENT_PO_STATUSES = ['Approved', 'Completed'];

    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('budgets.view'), 403);
        $year = $this->year($request);

        $rfs = $this->requestForms($year)->where('status', 'Approved')
            ->selectRaw('department_id, COUNT(*) as rf_count, SUM(total_amount) as rf_total')
            ->groupBy('department_id')->get()->keyBy('department_id');

        $pos = $this->purchaseOrders($year)->whereIn('erp_purchase_orders.status', self::SPENT_PO_STATUSES)
            ->join('request_forms', 'request_forms.id', '=', 'erp_purchase_orders.request_form_id')
            ->selectRaw('request_forms.department_id, COUNT(*) as po_count, SUM(erp_purchase_orders.total_po_amount_with_tax) as po_total')
            ->groupBy('request_forms.department_id')->get()->keyBy('department_id');

        $rows = Department::orderBy('name')->get()
            ->filter(fn (Department $d) => $d->status === 'active' || isset($rfs[$d->id]) || isset($pos[$d->id]))
            ->map(fn (Department $d) => (object) [
                'department' => $d,
                'rf_count' => (int) ($rfs[$d->id]->rf_count ?? 0),
                'rf_total' => (float) ($rfs[$d->id]->rf_total ?? 0),
                'po_count' => (int) ($pos[$d->id]->po_count ?? 0),
                'po_total' => (float) ($pos[$d->id]->po_total ?? 0),
            ])->values();

        $unassigned = RequestForm::where('record_type', 'non_project')->whereNull('department_id')->count();

        return view('erp.department_spending.index', [
            'rows' => $rows,
            'year' => $year,
            'years' => $this->years(),
            'unassigned' => $unassigned,
        ]);
    }

    public function show(Request $request, Department $department)
    {
        abort_unless(auth()->user()->hasPermission('budgets.view'), 403);
        $year = $this->year($request);

        $requestForms = $this->requestForms($year)->where('department_id', $department->id)
            ->latest('rf_date')->latest('id')->get();
        $purchaseOrders = $this->purchaseOrders($year)
            ->whereHas('requestForm', fn ($q) => $q->where('department_id', $department->id))
            ->with(['supplier', 'requestForm'])->latest('date')->latest('id')->get();

        return view('erp.department_spending.show', [
            'department' => $department,
            'year' => $year,
            'years' => $this->years(),
            'requestForms' => $requestForms,
            'purchaseOrders' => $purchaseOrders,
            'rfApprovedTotal' => $requestForms->where('status', 'Approved')->sum('total_amount'),
            'poSpentTotal' => $purchaseOrders->whereIn('status', self::SPENT_PO_STATUSES)->sum('total_po_amount_with_tax'),
        ]);
    }

    /** "all" or a four-digit year; defaults to this year. */
    private function year(Request $request): string
    {
        $year = (string) $request->query('year', now()->year);

        return $year === 'all' || preg_match('/^\d{4}$/', $year) ? $year : (string) now()->year;
    }

    private function years(): array
    {
        return RequestForm::where('record_type', 'non_project')->whereNotNull('rf_date')
            ->selectRaw('DISTINCT YEAR(rf_date) as y')->pluck('y')
            ->push(now()->year)->map(fn ($y) => (int) $y)->unique()->sortDesc()->values()->all();
    }

    private function requestForms(string $year): Builder
    {
        return RequestForm::where('record_type', 'non_project')->whereNotNull('department_id')
            ->when($year !== 'all', fn ($q) => $q->whereYear('rf_date', $year));
    }

    private function purchaseOrders(string $year): Builder
    {
        return ErpPurchaseOrder::query()
            ->whereHas('requestForm', fn ($q) => $q->where('record_type', 'non_project')->whereNotNull('department_id'))
            ->when($year !== 'all', fn ($q) => $q->whereYear('erp_purchase_orders.date', $year));
    }
}
