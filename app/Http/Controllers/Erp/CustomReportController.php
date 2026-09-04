<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\CustomReport;
use App\Models\Employee;
use App\Services\ReportSchemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomReportController extends Controller
{
    public function index()
    {
        $reportTypes  = ReportSchemaService::getReportTypes();
        $savedReports = CustomReport::on('master')->with('user')->latest()->get();

        return view('erp.reports.index', compact('reportTypes', 'savedReports'));
    }

    public function builder(Request $request)
    {
        $reportTypes = ReportSchemaService::getReportTypes();
        $reportType  = $request->query('type', 'purchase_orders');

        if (!array_key_exists($reportType, $reportTypes)) {
            $reportType = 'purchase_orders';
        }

        $savedReport = null;
        if ($request->has('report_id')) {
            $savedReport = CustomReport::on('master')->find($request->query('report_id'));
            if ($savedReport) {
                $reportType = $savedReport->report_type;
            }
        }

        $allFields = ReportSchemaService::getFields($reportType);
        $activeTypeConfig = $reportTypes[$reportType];

        return view('erp.reports.builder', compact('reportTypes', 'reportType', 'allFields', 'activeTypeConfig', 'savedReport'));
    }

    public function preview(Request $request)
    {
        $reportType = $request->input('report_type', 'purchase_orders');
        $columns    = $request->input('columns', []);
        $dateField  = $request->input('date_field');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $status     = $request->input('status');
        $limit      = (int) $request->input('limit', 200);

        $data = $this->fetchReportData($reportType, $columns, $dateField, $dateFrom, $dateTo, $status, $limit);

        return response()->json([
            'success'     => true,
            'total_rows'  => count($data['rows']),
            'columns'     => $data['columns'],
            'rows'        => $data['rows'],
            'summaries'   => $data['summaries'],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $reportType = $request->input('report_type', 'purchase_orders');
        $columns    = $request->input('columns', []);
        $dateField  = $request->input('date_field');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $status     = $request->input('status');

        $data = $this->fetchReportData($reportType, $columns, $dateField, $dateFrom, $dateTo, $status, 5000);

        $filename = "Report_" . ucfirst($reportType) . "_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');
            // Add BOM for Excel UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write Headers
            $headerRow = [];
            foreach ($data['columns'] as $col) {
                $headerRow[] = $col['label'];
            }
            fputcsv($handle, $headerRow);

            // Write Data Rows
            foreach ($data['rows'] as $row) {
                $rowValues = [];
                foreach ($data['columns'] as $col) {
                    $key = $col['key'];
                    $val = $row[$key] ?? '';
                    if ($col['type'] === 'currency' && is_numeric($val)) {
                        $val = number_format($val, 2, ',', '.');
                    }
                    $rowValues[] = $val;
                }
                fputcsv($handle, $rowValues);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'             => 'required|string|max:150',
            'description'       => 'nullable|string|max:500',
            'report_type'       => 'required|string|max:50',
            'selected_columns'  => 'required|array|min:1',
            'date_field'        => 'nullable|string',
            'date_range_preset' => 'nullable|string',
            'date_from'         => 'nullable|date',
            'date_to'           => 'nullable|date',
        ]);

        $data['user_id'] = auth()->id();

        $report = CustomReport::on('master')->create($data);

        return response()->json([
            'success'   => true,
            'message'   => 'Template laporan berhasil disimpan!',
            'report_id' => $report->id,
        ]);
    }

    public function destroy(CustomReport $customReport)
    {
        $customReport->delete();
        return response()->json(['success' => true, 'message' => 'Template laporan berhasil dihapus.']);
    }

    /**
     * Core Data Aggregator Query Engine
     */
    protected function fetchReportData(string $reportType, array $selectedKeys, ?string $dateField, ?string $dateFrom, ?string $dateTo, ?string $status, int $limit): array
    {
        $allFields = collect(ReportSchemaService::getFields($reportType));
        
        // If no columns specified, use default active fields
        if (empty($selectedKeys)) {
            $selectedColumns = $allFields->where('default', true)->values()->all();
        } else {
            $selectedColumns = $allFields->filter(fn($f) => in_array($f['key'], $selectedKeys))->values()->all();
        }

        $rows = [];
        $summaries = [];

        switch ($reportType) {
            case 'purchase_orders':
                $q = DB::connection('tenant')->table('erp_purchase_orders as po')
                    ->leftJoin('erp_suppliers as sup', 'po.supplier_id', '=', 'sup.id')
                    ->leftJoin('erp_work_items as wi', 'po.work_item_id', '=', 'wi.id')
                    ->leftJoin('erp_sub_projects as sp', 'wi.sub_project_id', '=', 'sp.id')
                    ->leftJoin('erp_payment_terms as pt', 'po.payment_term_id', '=', 'pt.id')
                    ->select([
                        'po.id',
                        'po.po_number',
                        'po.po_date',
                        'sup.name as supplier_name',
                        'sp.name as sub_project_name',
                        'wi.name as work_item_name',
                        'po.subtotal',
                        'po.tax_amount',
                        'po.grand_total',
                        'pt.name as payment_term',
                        'po.delivery_location',
                        'po.status',
                        'po.notes',
                        'po.created_at',
                    ]);

                if ($dateField && $dateFrom && $dateTo) {
                    $columnName = ($dateField === 'po_date') ? 'po.po_date' : 'po.created_at';
                    $q->whereBetween($columnName, [$dateFrom, $dateTo]);
                }
                if ($status) {
                    $q->where('po.status', $status);
                }

                $rawRows = $q->latest('po.id')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                
                $summaries['grand_total'] = $rawRows->sum('grand_total');
                $summaries['subtotal']    = $rawRows->sum('subtotal');
                $summaries['tax_amount']  = $rawRows->sum('tax_amount');
                break;

            case 'request_forms':
                $q = DB::connection('tenant')->table('request_forms as rf')
                    ->leftJoin('erp_work_items as wi', 'rf.work_item_id', '=', 'wi.id')
                    ->select([
                        'rf.id',
                        'rf.form_number as rf_number',
                        'rf.request_date as rf_date',
                        'rf.department',
                        'rf.requestor_name',
                        'wi.name as work_item_name',
                        'rf.total_estimated_cost',
                        'rf.status',
                        'rf.notes',
                        'rf.created_at',
                    ]);

                if ($dateField && $dateFrom && $dateTo) {
                    $q->whereBetween('rf.request_date', [$dateFrom, $dateTo]);
                }
                if ($status) {
                    $q->where('rf.status', $status);
                }

                $rawRows = $q->latest('rf.id')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                $summaries['total_estimated_cost'] = $rawRows->sum('total_estimated_cost');
                break;

            case 'goods_receipts':
                $q = DB::connection('tenant')->table('erp_goods_receipts as gr')
                    ->leftJoin('erp_purchase_orders as po', 'gr.purchase_order_id', '=', 'po.id')
                    ->leftJoin('erp_suppliers as sup', 'po.supplier_id', '=', 'sup.id')
                    ->select([
                        'gr.id',
                        'gr.grn_number',
                        'gr.received_date',
                        'po.po_number',
                        'sup.name as supplier_name',
                        'gr.delivery_order_number',
                        'gr.receiver_name as received_by',
                        'gr.status',
                        'gr.notes',
                        'gr.created_at',
                    ]);

                if ($dateField && $dateFrom && $dateTo) {
                    $q->whereBetween('gr.received_date', [$dateFrom, $dateTo]);
                }
                if ($status) {
                    $q->where('gr.status', $status);
                }

                $rawRows = $q->latest('gr.id')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                break;

            case 'payment_advices':
                $q = DB::connection('tenant')->table('erp_payment_advice_details as pad')
                    ->leftJoin('erp_payment_advices as pa', 'pad.payment_advice_id', '=', 'pa.id')
                    ->leftJoin('erp_purchase_orders as po', 'pad.purchase_order_id', '=', 'po.id')
                    ->leftJoin('erp_suppliers as sup', 'po.supplier_id', '=', 'sup.id')
                    ->select([
                        'pad.id',
                        'pad.invoice_number',
                        'po.po_number',
                        'sup.name as supplier_name',
                        'pad.due_date',
                        'pad.amount_to_pay',
                        'pad.payment_type',
                        'pa.advice_number as pa_number',
                        'pad.status',
                    ]);

                if ($dateField && $dateFrom && $dateTo) {
                    $q->whereBetween('pad.due_date', [$dateFrom, $dateTo]);
                }
                if ($status) {
                    $q->where('pad.status', $status);
                }

                $rawRows = $q->latest('pad.id')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                $summaries['amount_to_pay'] = $rawRows->sum('amount_to_pay');
                break;

            case 'work_items':
                $q = DB::connection('tenant')->table('erp_work_items as wi')
                    ->leftJoin('erp_sub_projects as sp', 'wi.sub_project_id', '=', 'sp.id')
                    ->leftJoin('erp_budget_parents as bp', 'sp.budget_parent_id', '=', 'bp.id')
                    ->select([
                        'wi.id',
                        'bp.name as budget_parent_name',
                        'sp.name as sub_project_name',
                        'wi.code as wid_code',
                        'wi.name as wid_name',
                        'wi.allocated_budget',
                        'wi.realized_budget',
                        'wi.remaining_budget',
                        'wi.status',
                    ]);

                $rawRows = $q->orderBy('wi.code')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                $summaries['allocated_budget'] = $rawRows->sum('allocated_budget');
                $summaries['realized_budget']  = $rawRows->sum('realized_budget');
                $summaries['remaining_budget'] = $rawRows->sum('remaining_budget');
                break;

            case 'stocks':
                $q = DB::connection('tenant')->table('erp_stocks as st')
                    ->leftJoin('erp_products as p', 'st.product_id', '=', 'p.id')
                    ->leftJoin('erp_warehouses as w', 'st.warehouse_id', '=', 'w.id')
                    ->leftJoin('uoms as u', 'p.uom_id', '=', 'u.id')
                    ->select([
                        'st.id',
                        'p.code as product_code',
                        'p.name as product_name',
                        'p.type as category_name',
                        'u.name as uom_name',
                        'st.current_stock',
                        'st.min_stock',
                        'w.name as warehouse_name',
                    ]);

                $rawRows = $q->orderBy('p.name')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                $summaries['current_stock'] = $rawRows->sum('current_stock');
                break;

            case 'employees':
                $q = Employee::on('master')->select([
                    'id',
                    'nik',
                    'name',
                    'department',
                    'position',
                    'employment_status',
                    'email',
                    'phone',
                    'join_date',
                    'status',
                ]);

                if ($dateFrom && $dateTo) {
                    $q->whereBetween('join_date', [$dateFrom, $dateTo]);
                }
                if ($status) {
                    $q->where('status', $status);
                }

                $rawRows = $q->orderBy('nik')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                break;
        }

        return [
            'columns'   => $selectedColumns,
            'rows'      => $rows,
            'summaries' => $summaries,
        ];
    }
}
