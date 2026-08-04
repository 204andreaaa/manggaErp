<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\RequestForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RequestFormController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);

        return view('erp.request_form.index');
    }

    public function datatable(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderColIdx = (int) $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->input('search.value', ''));

        $query = RequestForm::query()->withCount('items');
        $recordsTotal = (clone $query)->count();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('rf_no', 'like', "%{$search}%")
                    ->orWhere('project_code', 'like', "%{$search}%")
                    ->orWhere('requestor', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $columnsMap = [
            1 => 'rf_no',
            2 => 'record_type',
            3 => 'project_code',
            4 => 'requestor',
            5 => 'rf_date',
            6 => 'status',
            7 => 'total_amount',
        ];
        $orderCol = $columnsMap[$orderColIdx] ?? 'created_at';

        $rows = $query->orderBy($orderCol, $orderDir)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function (RequestForm $rf, int $i) use ($start) {
                // Auto-sync status for legacy submitted RFs
                if ($rf->status === 'Draft' && $rf->approvals()->count() > 0) {
                    $allApproved = $rf->approvals()->where('status', '!=', 'Approved')->count() === 0;
                    $rf->status = $allApproved ? 'Approved' : 'Submitted';
                    $rf->update(['status' => $rf->status]);
                }

                $statusClass = match ($rf->status) {
                    'Approved' => 'success',
                    'Submitted' => 'info',
                    'Rejected' => 'danger',
                    default => 'secondary',
                };

                $viewUrl = route('erp.request-form.show', $rf);

                return [
                    'rownum' => $start + $i + 1,
                    'rf_no' => '<a class="fw-semibold" href="'.$viewUrl.'">'.e($rf->rf_no).'</a>',
                    'record_type' => e($rf->record_type_label),
                    'project_code' => e($rf->project_code ?? '-'),
                    'requestor' => e($rf->requestor ?? '-'),
                    'rf_date' => $rf->rf_date?->format('Y/m/d') ?? '-',
                    'status' => '<span class="badge bg-label-'.$statusClass.'">'.e($rf->status).'</span>',
                    'total_amount' => 'IDR '.number_format($rf->total_amount, 0, ',', '.'),
                    'items_count' => $rf->items_count,
                    'actions' => '<a href="'.$viewUrl.'" class="btn btn-sm btn-primary"><i class="bx bx-show"></i></a>',
                ];
            });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('products.create'), 403);

        $recordType = $request->query('type');
        abort_unless(in_array($recordType, ['project', 'non_project'], true), 404);

        $products = \App\Models\Erp\ErpProduct::orderBy('name')->get();
        $users = \App\Models\User::orderBy('name')->get();

        return view('erp.request_form.create', [
            'recordType' => $recordType,
            'recordTypeLabel' => $recordType === 'project' ? 'Project Based' : 'Non Project Based',
            'nextRfNo' => $this->generateNextCode(),
            'products' => $products,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('products.create'), 403);

        $data = $request->validate([
            'record_type' => ['required', Rule::in(['project', 'non_project'])],
            'project_code' => ['required_if:record_type,project', 'nullable', 'max:80'],
            'requestor' => ['nullable', 'max:120'],
            'owner' => ['nullable', 'max:120'],
            'priority' => ['required', 'max:40'],
            'remark' => ['nullable', 'string'],
            'long_remark' => ['nullable', 'string'],
            'recommend_supplier' => ['nullable', 'max:200'],
            'rf_date' => ['required', 'date'],
            'expense_material_equipment' => ['nullable', 'boolean'],
            'expense_material_subcon' => ['nullable', 'boolean'],
            'expense_transportation' => ['nullable', 'boolean'],
            'expense_personnel' => ['nullable', 'boolean'],
            'expense_office' => ['nullable', 'boolean'],
            'expense_other' => ['nullable', 'boolean'],
            'expense_utilities' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.product_name' => ['required', 'max:180'],
            'items.*.product_id_text' => ['nullable', 'max:80'],
            'items.*.wid' => ['nullable', 'max:80'],
            'items.*.currency' => ['required', 'max:10'],
            'items.*.original_total_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.actual_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.qty_fulfilled' => ['nullable', 'numeric', 'min:0'],
            'items.*.date_required' => ['nullable', 'date'],
            'items.*.pic' => ['nullable', 'max:120'],
            'items.*.within_budget' => ['nullable', 'boolean'],
            'items.*.status' => ['required', Rule::in(['Requested', 'Ordered', 'Completed', 'Cancelled'])],
            'items.*.remark' => ['nullable', 'string'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $rf = DB::transaction(function () use ($request, $data) {
            $items = collect($data['items'] ?? [])->map(function (array $item) {
                $qty = (float) $item['qty'];
                $unitCost = (int) ($item['unit_cost'] ?? 0);
                $originalTotalCost = (int) ($item['original_total_cost'] ?? 0);

                if ($originalTotalCost <= 0) {
                    $originalTotalCost = (int) round($qty * $unitCost);
                }

                return [
                    'product_name' => $item['product_name'],
                    'product_id_text' => $item['product_id_text'] ?? null,
                    'wid' => $item['wid'] ?? null,
                    'currency' => $item['currency'],
                    'original_total_cost' => $originalTotalCost,
                    'actual_cost' => (int) ($item['actual_cost'] ?? 0),
                    'unit_cost' => $unitCost,
                    'qty' => $qty,
                    'qty_fulfilled' => (float) ($item['qty_fulfilled'] ?? 0),
                    'date_required' => $item['date_required'] ?? null,
                    'pic' => $item['pic'] ?? null,
                    'within_budget' => ! empty($item['within_budget']),
                    'status' => $item['status'],
                    'remark' => $item['remark'] ?? null,
                ];
            });

            $rf = RequestForm::create([
                'rf_no' => $this->generateNextCode(),
                'record_type' => $data['record_type'],
                'project_code' => $data['record_type'] === 'project' ? $data['project_code'] : null,
                'requestor' => $data['requestor'] ?? auth()->user()->name ?? null,
                'owner' => $data['owner'] ?? auth()->user()->name ?? null,
                'priority' => $data['priority'],
                'remark' => $data['remark'] ?? null,
                'long_remark' => $data['long_remark'] ?? null,
                'recommend_supplier' => $data['recommend_supplier'] ?? null,
                'rf_date' => $data['rf_date'],
                'status' => 'Draft',
                'total_amount' => $items->sum('original_total_cost'),
                'expense_material_equipment' => $request->boolean('expense_material_equipment'),
                'expense_material_subcon' => $request->boolean('expense_material_subcon'),
                'expense_transportation' => $request->boolean('expense_transportation'),
                'expense_personnel' => $request->boolean('expense_personnel'),
                'expense_office' => $request->boolean('expense_office'),
                'expense_other' => $request->boolean('expense_other'),
                'expense_utilities' => $request->boolean('expense_utilities'),
            ]);

            $items->values()->each(function (array $item, int $index) use ($rf) {
                $rf->items()->create(array_merge($item, [
                    'rf_detail_no' => $this->generateItemCode($rf->rf_no, $index + 1),
                ]));
            });

            // Save notes
            if (!empty($data['notes'])) {
                foreach ($data['notes'] as $noteContent) {
                    if (trim($noteContent) !== '') {
                        $rf->notesAttachments()->create([
                            'type' => 'note',
                            'content' => $noteContent,
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            // Save attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('erp/attachments', 'public');
                    $rf->notesAttachments()->create([
                        'type' => 'attachment',
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            // Approval steps will be generated when user clicks "Submit for Approval"

            return $rf;
        });

        return redirect()
            ->route('erp.request-form.show', $rf)
            ->with('success', 'Request Form berhasil dibuat.');
    }

    public function edit(RequestForm $requestForm)
    {
        abort_unless(auth()->user()->hasPermission('products.update'), 403);
        abort_unless($requestForm->status === 'Draft', 403, 'Request Form hanya dapat di-edit saat berstatus Draft.');

        $requestForm->load(['items', 'notesAttachments']);
        $products = \App\Models\Erp\ErpProduct::orderBy('name')->get();
        $users = \App\Models\User::orderBy('name')->get();

        return view('erp.request_form.edit', [
            'rf' => $requestForm,
            'recordType' => $requestForm->record_type,
            'recordTypeLabel' => $requestForm->record_type === 'project' ? 'Project Based' : 'Non Project Based',
            'products' => $products,
            'users' => $users,
        ]);
    }

    public function update(Request $request, RequestForm $requestForm)
    {
        abort_unless(auth()->user()->hasPermission('products.update'), 403);
        abort_unless($requestForm->status === 'Draft', 403, 'Request Form hanya dapat di-edit saat berstatus Draft.');

        $data = $request->validate([
            'record_type' => ['required', Rule::in(['project', 'non_project'])],
            'project_code' => ['required_if:record_type,project', 'nullable', 'max:80'],
            'requestor' => ['nullable', 'max:120'],
            'owner' => ['nullable', 'max:120'],
            'priority' => ['required', 'max:40'],
            'remark' => ['nullable', 'string'],
            'long_remark' => ['nullable', 'string'],
            'recommend_supplier' => ['nullable', 'max:200'],
            'rf_date' => ['required', 'date'],
            'expense_material_equipment' => ['nullable', 'boolean'],
            'expense_material_subcon' => ['nullable', 'boolean'],
            'expense_transportation' => ['nullable', 'boolean'],
            'expense_personnel' => ['nullable', 'boolean'],
            'expense_office' => ['nullable', 'boolean'],
            'expense_other' => ['nullable', 'boolean'],
            'expense_utilities' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'exists:request_form_items,id'],
            'items.*.product_name' => ['required', 'max:180'],
            'items.*.product_id_text' => ['nullable', 'max:80'],
            'items.*.wid' => ['nullable', 'max:80'],
            'items.*.currency' => ['required', 'max:10'],
            'items.*.original_total_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.actual_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.qty_fulfilled' => ['nullable', 'numeric', 'min:0'],
            'items.*.date_required' => ['nullable', 'date'],
            'items.*.pic' => ['nullable', 'max:120'],
            'items.*.within_budget' => ['nullable', 'boolean'],
            'items.*.status' => ['required', Rule::in(['Requested', 'Ordered', 'Completed', 'Cancelled'])],
            'items.*.remark' => ['nullable', 'string'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $data, $requestForm) {
            $itemsData = collect($data['items'] ?? [])->map(function (array $item) {
                $qty = (float) $item['qty'];
                $unitCost = (int) ($item['unit_cost'] ?? 0);
                $originalTotalCost = (int) ($item['original_total_cost'] ?? 0);

                if ($originalTotalCost <= 0) {
                    $originalTotalCost = (int) round($qty * $unitCost);
                }

                return array_merge($item, [
                    'original_total_cost' => $originalTotalCost,
                    'actual_cost' => (int) ($item['actual_cost'] ?? 0),
                    'unit_cost' => $unitCost,
                    'qty' => $qty,
                    'qty_fulfilled' => (float) ($item['qty_fulfilled'] ?? 0),
                    'within_budget' => ! empty($item['within_budget']),
                ]);
            });

            $requestForm->update([
                'record_type' => $data['record_type'],
                'project_code' => $data['record_type'] === 'project' ? $data['project_code'] : null,
                'requestor' => $data['requestor'] ?? auth()->user()->name ?? null,
                'owner' => $data['owner'] ?? auth()->user()->name ?? null,
                'priority' => $data['priority'],
                'remark' => $data['remark'] ?? null,
                'long_remark' => $data['long_remark'] ?? null,
                'recommend_supplier' => $data['recommend_supplier'] ?? null,
                'rf_date' => $data['rf_date'],
                'total_amount' => $itemsData->sum('original_total_cost'),
                'expense_material_equipment' => $request->boolean('expense_material_equipment'),
                'expense_material_subcon' => $request->boolean('expense_material_subcon'),
                'expense_transportation' => $request->boolean('expense_transportation'),
                'expense_personnel' => $request->boolean('expense_personnel'),
                'expense_office' => $request->boolean('expense_office'),
                'expense_other' => $request->boolean('expense_other'),
                'expense_utilities' => $request->boolean('expense_utilities'),
            ]);

            // Delete removed items
            $existingItemIds = $requestForm->items()->pluck('id')->toArray();
            $submittedItemIds = $itemsData->pluck('id')->filter()->toArray();
            $itemsToDelete = array_diff($existingItemIds, $submittedItemIds);
            
            if (!empty($itemsToDelete)) {
                $requestForm->items()->whereIn('id', $itemsToDelete)->delete();
            }

            // Update or Create items
            $itemsData->values()->each(function (array $item, int $index) use ($requestForm) {
                $itemData = collect($item)->except('id')->toArray();
                if (empty($item['id'])) {
                    $requestForm->items()->create(array_merge($itemData, [
                        'rf_detail_no' => $this->generateItemCode($requestForm->rf_no, $requestForm->items()->count() + 1),
                    ]));
                } else {
                    $requestForm->items()->where('id', $item['id'])->update($itemData);
                }
            });

            // Update notes (simplest is to delete text notes and recreate, keeping attachments)
            $requestForm->notesAttachments()->where('type', 'note')->delete();
            if (!empty($data['notes'])) {
                foreach ($data['notes'] as $noteContent) {
                    if (trim($noteContent) !== '') {
                        $requestForm->notesAttachments()->create([
                            'type' => 'note',
                            'content' => $noteContent,
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            // Append new attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('erp/attachments', 'public');
                    $requestForm->notesAttachments()->create([
                        'type' => 'attachment',
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('erp.request-form.show', $requestForm)
            ->with('success', 'Request Form berhasil diperbarui.');
    }

    public function show(RequestForm $requestForm)
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);

        // Auto-fix status for RF records submitted prior to status bug fix
        if ($requestForm->status === 'Draft' && $requestForm->approvals()->count() > 0) {
            $allApproved = $requestForm->approvals()->where('status', '!=', 'Approved')->count() === 0;
            $newStatus = $allApproved ? 'Approved' : 'Submitted';
            $requestForm->update(['status' => $newStatus]);
            $requestForm->status = $newStatus;
        }

        $requestForm->load([
            'items',
            'notesAttachments.user',
            'purchaseRequests.items',
            'approvals.assignedRole',
            'approvals.assignedUser',
            'approvals.actualApprover',
        ]);

        return view('erp.request_form.show', [
            'rf' => $requestForm,
        ]);
    }

    public function unlock(RequestForm $requestForm)
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403, 'Hanya Superadmin yang dapat melakukan unlock.');

        if ($requestForm->status === 'Draft') {
            return redirect()->back()->with('error', 'Request Form sudah berstatus Draft.');
        }

        DB::transaction(function () use ($requestForm) {
            // Delete all existing approvals
            $requestForm->approvals()->delete();
            
            // Revert status to Draft
            $requestForm->update(['status' => 'Draft']);
        });

        return redirect()->back()->with('success', 'Request Form berhasil di-unlock. Approval telah direset dan status kembali menjadi Draft.');
    }

    private function generateNextCode(): string
    {
        $prefix = 'RF-'.now()->format('Y-m').'-';
        $latest = RequestForm::withTrashed()
            ->where('rf_no', 'like', $prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(rf_no, '.(strlen($prefix) + 1).') AS UNSIGNED) DESC')
            ->value('rf_no');

        $num = 0;
        if ($latest && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $latest, $match)) {
            $num = (int) $match[1];
        }

        return $prefix.str_pad($num + 1, 5, '0', STR_PAD_LEFT);
    }

    private function generateItemCode(string $rfNo, int $sequence): string
    {
        return 'RFIN-'.str_replace('RF-', '', $rfNo).'-'.str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}
