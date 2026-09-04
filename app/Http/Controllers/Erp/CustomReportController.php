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
        $limit      = (int) $request->input('limit', 300);

        try {
            $data = $this->fetchReportData($reportType, $columns, $dateField, $dateFrom, $dateTo, $status, $limit);

            return response()->json([
                'success'     => true,
                'total_rows'  => count($data['rows']),
                'columns'     => $data['columns'],
                'rows'        => $data['rows'],
                'summaries'   => $data['summaries'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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
        $allFieldsByKey = $allFields->keyBy('key');
        
        if (empty($selectedKeys)) {
            $selectedColumns = $allFields->where('default', true)->values()->all();
        } else {
            $selectedColumns = collect($selectedKeys)
                ->map(fn($k) => $allFieldsByKey->get($k))
                ->filter()
                ->values()
                ->all();
        }

        $rows = [];
        $summaries = [];

        switch ($reportType) {
            case 'purchase_orders':
                $q = DB::connection('tenant')->table('erp_purchase_orders as po')
                    ->leftJoin('erp_suppliers as sup', 'po.supplier_id', '=', 'sup.id')
                    ->select([
                        'po.id',
                        'po.po_no',
                        'po.date',
                        'sup.name as supplier_name',
                        'po.total_po_amount',
                        'po.tax',
                        'po.total_po_amount_with_tax',
                        'po.balance_amount',
                        'po.amount_paid',
                        'po.payment_terms',
                        'po.destination',
                        'po.attention_to',
                        'po.invoice_to',
                        'po.status',
                        'po.description',
                        'po.approved_date',
                        'po.created_at',
                    ]);

                if ($dateField && $dateFrom && $dateTo) {
                    $columnName = in_array($dateField, ['date', 'approved_date']) ? "po.$dateField" : 'po.created_at';
                    $q->whereBetween($columnName, [$dateFrom, $dateTo]);
                }
                if ($status) {
                    $q->where('po.status', $status);
                }

                $rawRows = $q->latest('po.id')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                
                $summaries['total_po_amount']          = $rawRows->sum('total_po_amount');
                $summaries['tax']                      = $rawRows->sum('tax');
                $summaries['total_po_amount_with_tax'] = $rawRows->sum('total_po_amount_with_tax');
                $summaries['balance_amount']           = $rawRows->sum('balance_amount');
                $summaries['amount_paid']              = $rawRows->sum('amount_paid');
                break;

            case 'request_forms':
                $q = DB::connection('tenant')->table('request_forms as rf')
                    ->leftJoin('erp_work_items as wi', 'rf.work_item_id', '=', 'wi.id')
                    ->select([
                        'rf.id',
                        'rf.rf_no',
                        'rf.rf_date',
                        'rf.requestor',
                        'rf.rf_type',
                        'wi.name as work_item_name',
                        'rf.total_amount',
                        'rf.status',
                        'rf.remark',
                        'rf.created_at',
                    ]);

                if ($dateField && $dateFrom && $dateTo) {
                    $q->whereBetween('rf.rf_date', [$dateFrom, $dateTo]);
                }
                if ($status) {
                    $q->where('rf.status', $status);
                }

                $rawRows = $q->latest('rf.id')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                $summaries['total_amount'] = $rawRows->sum('total_amount');
                break;

            case 'goods_receipts':
                $q = DB::connection('tenant')->table('erp_goods_receipts as gr')
                    ->leftJoin('erp_purchase_orders as po', 'gr.erp_purchase_order_id', '=', 'po.id')
                    ->leftJoin('erp_suppliers as sup', 'gr.supplier_id', '=', 'sup.id')
                    ->select([
                        'gr.id',
                        'gr.do_no',
                        'gr.date',
                        'po.po_no',
                        'sup.name as supplier_name',
                        'gr.supplier_do_no',
                        'gr.receiving_contact',
                        'gr.total_received_qty',
                        'gr.status',
                        'gr.remarks',
                        'gr.created_at',
                    ]);

                if ($dateField && $dateFrom && $dateTo) {
                    $q->whereBetween('gr.date', [$dateFrom, $dateTo]);
                }
                if ($status) {
                    $q->where('gr.status', $status);
                }

                $rawRows = $q->latest('gr.id')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                $summaries['total_received_qty'] = $rawRows->sum('total_received_qty');
                break;

            case 'payment_advices':
                $q = DB::connection('tenant')->table('erp_payment_advices as pa')
                    ->leftJoin('erp_purchase_orders as po', 'pa.erp_purchase_order_id', '=', 'po.id')
                    ->leftJoin('erp_suppliers as sup', 'pa.supplier_id', '=', 'sup.id')
                    ->select([
                        'pa.id',
                        'pa.supplier_invoice_no',
                        'po.po_no',
                        'sup.name as supplier_name',
                        'pa.due_date',
                        'pa.total_invoice_amount',
                        'pa.total_invoice_amount_with_tax',
                        'pa.outstanding',
                        'pa.status',
                    ]);

                if ($dateField && $dateFrom && $dateTo) {
                    $q->whereBetween('pa.due_date', [$dateFrom, $dateTo]);
                }
                if ($status) {
                    $q->where('pa.status', $status);
                }

                $rawRows = $q->latest('pa.id')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                $summaries['total_invoice_amount']          = $rawRows->sum('total_invoice_amount');
                $summaries['total_invoice_amount_with_tax'] = $rawRows->sum('total_invoice_amount_with_tax');
                $summaries['outstanding']                   = $rawRows->sum('outstanding');
                break;

            case 'work_items':
                $q = DB::connection('tenant')->table('erp_work_items as wi')
                    ->leftJoin('erp_sub_projects as sp', 'wi.sub_project_id', '=', 'sp.id')
                    ->leftJoin('erp_budget_parents as bp', 'sp.budget_parent_id', '=', 'bp.id')
                    ->select([
                        'wi.id',
                        'bp.name as budget_parent_name',
                        'sp.name as sub_project_name',
                        'wi.wid_code',
                        'wi.name as wid_name',
                        'wi.allocated_budget',
                        'wi.remaining_budget',
                        DB::raw('(wi.allocated_budget - wi.remaining_budget) as realized_budget'),
                    ]);

                $rawRows = $q->orderBy('wi.wid_code')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                $summaries['allocated_budget'] = $rawRows->sum('allocated_budget');
                $summaries['realized_budget']  = $rawRows->sum('realized_budget');
                $summaries['remaining_budget'] = $rawRows->sum('remaining_budget');
                break;

            case 'stocks':
                $q = DB::connection('tenant')->table('erp_stocks as st')
                    ->leftJoin('erp_products as p', 'st.erp_product_id', '=', 'p.id')
                    ->leftJoin('erp_warehouses as w', 'st.erp_warehouse_id', '=', 'w.id')
                    ->leftJoin('uoms as u', 'p.uom_id', '=', 'u.id')
                    ->select([
                        'st.id',
                        'p.product_code',
                        'p.name as product_name',
                        'p.part_number',
                        'u.name as uom_name',
                        'st.qty_on_hand',
                        'w.name as warehouse_name',
                    ]);

                $rawRows = $q->orderBy('p.name')->limit($limit)->get();
                $rows = $rawRows->map(fn($r) => (array) $r)->toArray();
                $summaries['qty_on_hand'] = $rawRows->sum('qty_on_hand');
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
