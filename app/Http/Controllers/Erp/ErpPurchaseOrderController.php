<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\RequestForm;
use App\Models\Erp\RequestFormItem;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\ErpPurchaseOrderItem;
use App\Models\Erp\ErpSupplier;
use App\Models\Erp\ErpWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ErpPurchaseOrderController extends Controller
{
    public function dashboard()
    {
        // Display approved RFs that have completed PRs
        $requestForms = RequestForm::where('status', 'Approved')
            ->whereHas('purchaseRequests', function ($q) {
                $q->whereIn('status', ['Submitted', 'Completed']);
            })
            ->with(['items', 'purchaseRequests', 'purchaseOrders'])
            ->latest()
            ->get();

        return view('erp.purchase_orders.dashboard', compact('requestForms'));
    }

    public function index()
    {
        $purchaseOrders = ErpPurchaseOrder::with(['supplier', 'warehouse'])->latest()->get();
        return view('erp.purchase_orders.index', compact('purchaseOrders'));
    }

    public function create(RequestForm $requestForm)
    {
        // Cek dulu apakah ada item yang belum di-PO
        $outstandingItemsCount = $requestForm->items()->whereDoesntHave('purchaseOrderItems')->count();
        if ($outstandingItemsCount === 0) {
            return redirect()->back()->with('error', 'Semua barang di Request Form ini sudah dibuatkan PO.');
        }

        $requestForm->load(['items' => function($query) {
            $query->whereDoesntHave('purchaseOrderItems')->with('erpProduct.uom');
        }, 'purchaseRequests.items']);
        
        $suppliers = ErpSupplier::with(['paymentTerm', 'contacts'])->get();
        $warehouses = ErpWarehouse::where('is_active', true)->get();
        $paymentTerms = \App\Models\Erp\ErpPaymentTerm::where('is_active', true)->get();
        
        $projectId = session('current_project');
        $usersQuery = \App\Models\User::orderBy('name');
        if ($projectId) {
            $usersQuery->whereHas('projects', function ($q) use ($projectId) {
                $q->where('projects.id', $projectId);
            });
        }
        $users = $usersQuery->get();
        if ($users->isEmpty()) {
            $users = \App\Models\User::orderBy('name')->get();
        }

        $poNo = $this->generatePoNo();

        return view('erp.purchase_orders.create', compact('requestForm', 'suppliers', 'warehouses', 'paymentTerms', 'users', 'poNo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'request_form_id' => 'required|exists:request_forms,id',
            'erp_warehouse_id' => 'nullable|exists:erp_warehouses,id',
            'supplier_id' => 'required',
            'destination' => 'nullable|string',
            'contact_person' => 'nullable|string',
            'address' => 'nullable|string',
            'bank_account' => 'nullable|string',
            'date' => 'required|date',
            'eta' => 'nullable|date',
            'payment_method' => 'nullable|string',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            
            // Print related
            'project' => 'nullable|string',
            'invoice_to' => 'nullable|string',
            'attention_to' => 'nullable|string',
            'transfer_to' => 'nullable|string',
            'other_instructions' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'signature' => 'nullable|string',
            
            // Items
            'items' => 'required|array',
            'items.*.request_form_item_id' => 'required|exists:request_form_items,id',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax' => 'required|numeric|min:0',
            'items.*.remarks' => 'nullable|string',
        ]);

        $rf = RequestForm::findOrFail($data['request_form_id']);

        DB::beginTransaction();
        try {
            $po = new ErpPurchaseOrder();
            $po->po_no = $this->generatePoNo();
            $po->request_form_id = $rf->id;
            $warehouse = ErpWarehouse::find($data['erp_warehouse_id'] ?? null);
            $po->erp_warehouse_id = $data['erp_warehouse_id'] ?? null;
            $po->supplier_id = $data['supplier_id'];
            $po->destination = $warehouse ? $warehouse->name : null;
            $po->contact_person = $data['contact_person'];
            $po->address = $data['address'];
            $po->bank_account = $data['bank_account'];
            $po->date = $data['date'];
            $po->eta = $data['eta'];
            $po->payment_method = $data['payment_method'];
            $po->description = $data['description'];
            
            // Pre-fill expense types from Request Form
            $po->expense_material_equipment = $rf->expense_material_equipment;
            $po->expense_material_subcon = $rf->expense_material_subcon;
            $po->expense_personnel = $rf->expense_personnel;
            $po->expense_transportation = $rf->expense_transportation;
            $po->expense_utilities = $rf->expense_utilities;
            $po->expense_office = $rf->expense_office;
            $po->expense_other = $rf->expense_other;

            // Print related
            $po->project = $data['project'] ?? $rf->project_code;
            $po->invoice_to = $data['invoice_to'];
            $po->attention_to = $data['attention_to'];
            $po->transfer_to = $data['transfer_to'];
            $po->other_instructions = $data['other_instructions'];
            $po->payment_terms = $data['payment_terms'];
            $po->signature = $data['signature'];

            $po->status = 'Draft';
            $po->owner_id = auth()->id();
            $po->save();

            $totalAmount = 0;
            $totalTax = 0;

            foreach ($data['items'] as $itemData) {
                if ($itemData['qty'] > 0) {
                    $itemTotal = ($itemData['qty'] * $itemData['unit_cost']) + $itemData['tax'];
                    
                    $po->items()->create([
                        'request_form_item_id' => $itemData['request_form_item_id'],
                        'qty' => $itemData['qty'],
                        'unit_cost' => $itemData['unit_cost'],
                        'tax' => $itemData['tax'],
                        'total_cost' => $itemTotal,
                        'remarks' => $itemData['remarks'],
                    ]);

                    \App\Models\Erp\RequestFormItem::where('id', $itemData['request_form_item_id'])->update([
                        'status' => 'Ordered'
                    ]);

                    $totalAmount += ($itemData['qty'] * $itemData['unit_cost']);
                    $totalTax += $itemData['tax'];
                }
            }

            $po->total_po_amount = $totalAmount;
            $po->tax = $totalTax;
            $po->total_po_amount_with_tax = $totalAmount + $totalTax;
            $po->balance_amount = $totalAmount + $totalTax;
            $po->save();

            // Save attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('erp/attachments', 'public');
                    $po->notesAttachments()->create([
                        'type' => 'attachment',
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('erp.purchase-orders.show', $po)->with('success', 'PO Request created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create PO Request: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(ErpPurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Draft' && $purchaseOrder->status !== 'Rejected') {
            return redirect()->route('erp.purchase-orders.show', $purchaseOrder)->with('error', 'Only Draft or Rejected PO Requests can be edited.');
        }

        $purchaseOrder->load(['requestForm.items.erpProduct.uom', 'items']);
        $requestForm = $purchaseOrder->requestForm;
        
        $suppliers = ErpSupplier::with(['paymentTerm', 'contacts'])->get();
        $warehouses = ErpWarehouse::where('is_active', true)->get();
        $paymentTerms = \App\Models\Erp\ErpPaymentTerm::where('is_active', true)->get();

        $projectId = session('current_project');
        $usersQuery = \App\Models\User::orderBy('name');
        if ($projectId) {
            $usersQuery->whereHas('projects', function ($q) use ($projectId) {
                $q->where('projects.id', $projectId);
            });
        }
        $users = $usersQuery->get();
        if ($users->isEmpty()) {
            $users = \App\Models\User::orderBy('name')->get();
        }

        return view('erp.purchase_orders.edit', compact('purchaseOrder', 'requestForm', 'suppliers', 'warehouses', 'paymentTerms', 'users'));
    }

    public function update(Request $request, ErpPurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Draft' && $purchaseOrder->status !== 'Rejected') {
            return redirect()->route('erp.purchase-orders.show', $purchaseOrder)->with('error', 'Only Draft or Rejected PO Requests can be edited.');
        }

        $data = $request->validate([
            'supplier_id' => 'required',
            'erp_warehouse_id' => 'nullable|exists:erp_warehouses,id',
            'contact_person' => 'nullable|string',
            'address' => 'nullable|string',
            'bank_account' => 'nullable|string',
            'date' => 'required|date',
            'eta' => 'nullable|date',
            'payment_method' => 'required|string',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            
            // Print Related
            'project' => 'nullable|string',
            'invoice_to' => 'nullable|string',
            'attention_to' => 'nullable|string',
            'transfer_to' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'other_instructions' => 'nullable|string',
            'remarks_print' => 'nullable|string',
            
            // Items
            'items' => 'required|array',
            'items.*.request_form_item_id' => 'required|exists:request_form_items,id',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax' => 'required|numeric|min:0',
            'items.*.remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $warehouse = ErpWarehouse::find($data['erp_warehouse_id'] ?? null);
            
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'erp_warehouse_id' => $data['erp_warehouse_id'] ?? null,
                'destination' => $warehouse ? $warehouse->name : null,
                'contact_person' => $data['contact_person'] ?? null,
                'address' => $data['address'] ?? null,
                'bank_account' => $data['bank_account'] ?? null,
                'date' => $data['date'],
                'eta' => $data['eta'] ?? null,
                'payment_method' => $data['payment_method'],
                'description' => $data['description'] ?? null,
                
                // Print Related
                'project' => $data['project'] ?? null,
                'invoice_to' => $data['invoice_to'] ?? null,
                'attention_to' => $data['attention_to'] ?? null,
                'transfer_to' => $data['transfer_to'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'other_instructions' => $data['other_instructions'] ?? null,
                'remarks_print' => $data['remarks_print'] ?? null,
                
                // Reset status to Draft if it was Rejected
                'status' => 'Draft',
                'rejected_date' => null,
                'approved_date' => null,
                'submitted_date' => null,
            ]);

            // Clear existing items and recreate
            $purchaseOrder->items()->delete();

            $totalAmount = 0;
            $totalTax = 0;

            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $itemTotal = ($itemData['qty'] * $itemData['unit_cost']) + $itemData['tax'];
                    
                    $purchaseOrder->items()->create([
                        'request_form_item_id' => $itemData['request_form_item_id'],
                        'qty' => $itemData['qty'],
                        'unit_cost' => $itemData['unit_cost'],
                        'tax' => $itemData['tax'],
                        'total_cost' => $itemTotal,
                        'remarks' => $itemData['remarks'] ?? null,
                    ]);

                    $totalAmount += ($itemData['qty'] * $itemData['unit_cost']);
                    $totalTax += $itemData['tax'];
                }
            }

            $purchaseOrder->update([
                'total_po_amount' => $totalAmount,
                'tax' => $totalTax,
                'total_po_amount_with_tax' => $totalAmount + $totalTax,
                'balance_amount' => $totalAmount + $totalTax,
            ]);

            // Save attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('erp/attachments', 'public');
                    $purchaseOrder->notesAttachments()->create([
                        'type' => 'attachment',
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('erp.purchase-orders.show', $purchaseOrder)->with('success', 'PO Request updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update PO Request: ' . $e->getMessage())->withInput();
        }
    }

    public function show(ErpPurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'requestForm', 
            'supplier', 
            'warehouse',
            'owner', 
            'items.requestFormItem.erpProduct.brand',
            'items.requestFormItem.erpProduct.productModel',
            'items.requestFormItem.erpProduct.uom',
            'approvals.assignedUser',
            'approvals.actualApprover',
            'approvals.assignedRole',
            'goodsReceipts.owner'
        ]);

        return view('erp.purchase_orders.show', compact('purchaseOrder'));
    }

    public function print(ErpPurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier', 
            'warehouse',
            'items.requestFormItem.erpProduct.uom',
            'approvals.assignedUser',
            'approvals.actualApprover'
        ]);

        return view('erp.purchase_orders.print', compact('purchaseOrder'));
    }

    public function submit(ErpPurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Draft') {
            return redirect()->back()->with('error', 'Only Draft PO Requests can be submitted for approval.');
        }

        if (!$purchaseOrder->verified_by_id) {
            return redirect()->back()->with('error', 'PO must be verified by Finance before it can be submitted for approval.');
        }

        // Ambil data level yang sudah di-approve sebelum ada perubahan status
        $previouslyApprovedLevels = $purchaseOrder->approvals()
            ->where('level', '>', 0)
            ->where('status', 'Approved')
            ->get()
            ->keyBy('level');

        // Batalkan langkah aktif (Pending / Waiting) yang tersisa dari putaran sebelumnya
        $purchaseOrder->approvals()
            ->whereIn('status', ['Pending', 'Waiting'])
            ->update(['status' => 'Cancelled']);

        // Buat baris riwayat "Submitted" (Level 0)
        \App\Models\Erp\ErpApproval::create([
            'purchase_order_id' => $purchaseOrder->id,
            'level' => 0,
            'status' => 'Approved', // Set Approved agar baris ini dianggap selesai
            'comments' => 'PO Submitted for approval',
            'assigned_to_user_id' => auth()->id(),
            'actual_approver_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        $totalCost = $purchaseOrder->total_po_amount_with_tax;
        $recordType = $totalCost <= 1000000 ? 'purchase_order_low' : 'purchase_order_high';

        $configs = \App\Models\Erp\ErpApprovalConfig::where('record_type', $recordType)
            ->orderBy('level')
            ->get();

        if ($configs->isNotEmpty()) {
            $setPending = false;
            foreach ($configs as $config) {
                // Cari dari data yang disimpan di memori
                $previousApproved = $previouslyApprovedLevels->get($config->level);

                if ($previousApproved) {
                    // Jika level ini sudah pernah di-approve sebelumnya, kita TIDAK perlu membuat baris baru lagi.
                    continue;
                } else {
                    \App\Models\Erp\ErpApproval::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'level' => $config->level,
                        'assigned_to_role_id' => $config->role_id,
                        'assigned_to_user_id' => $config->user_id,
                        'status' => !$setPending ? 'Pending' : 'Waiting',
                    ]);
                    $setPending = true;
                }
            }

            if (!$setPending) {
                $purchaseOrder->update([
                    'status' => 'Approved',
                    'approved_date' => now(),
                ]);
                return redirect()->back()->with('success', 'PO submitted and automatically approved.');
            }
        }

        $purchaseOrder->update([
            'status' => 'Submitted',
            'submitted_date' => now(),
        ]);
        return redirect()->back()->with('success', 'PO submitted for approval.');
    }

    public function approve(ErpPurchaseOrder $purchaseOrder, Request $request)
    {
        $user = auth()->user();

        // Check if there are dynamic approvals
        $activeApproval = $purchaseOrder->approvals()->where('status', 'Pending')->first();

        if ($activeApproval) {
            $isAuthorized = false;
            if ($user->hasRole('superadmin')) {
                $isAuthorized = true;
            } elseif ($activeApproval->assigned_to_user_id) {
                if ($user->id == $activeApproval->assigned_to_user_id) {
                    $isAuthorized = true;
                }
            } elseif ($activeApproval->assigned_to_role_id) {
                $hasRole = \Illuminate\Support\Facades\DB::connection('tenant')
                    ->table('role_user')
                    ->where('user_id', $user->id)
                    ->where('role_id', $activeApproval->assigned_to_role_id)
                    ->exists();
                if ($hasRole) {
                    $isAuthorized = true;
                }
            }

            if (!$isAuthorized) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menyetujui tahap ini.');
            }

            $activeApproval->update([
                'status' => 'Approved',
                'comments' => $request->input('comments'),
                'actual_approver_id' => $user->id,
                'approved_at' => now(),
            ]);

            $nextApproval = $purchaseOrder->approvals()->where('status', 'Waiting')->orderBy('level')->first();
            if ($nextApproval) {
                $nextApproval->update(['status' => 'Pending']);
            } else {
                $purchaseOrder->update([
                    'status' => 'Approved',
                    'approved_date' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Approval PO berhasil disetujui.');
        }

        // FALLBACK RULES (If no dynamic approval configs were set up)
        $totalCost = $purchaseOrder->total_po_amount_with_tax;

        if ($totalCost <= 1000000) {
            // Must be procurement or superadmin
            if (!$user->hasRole('procurement') && !$user->hasRole('superadmin')) {
                return redirect()->back()->with('error', 'Only Procurement Manager (Febri Saputra) can approve POs <= 1,000,000 IDR.');
            }
        } else {
            // Must be ceo or superadmin
            if (!$user->hasRole('ceo') && !$user->hasRole('superadmin')) {
                return redirect()->back()->with('error', 'Only CEO (Barry Japadermawan) can approve POs > 1,000,000 IDR.');
            }
        }

        $purchaseOrder->update([
            'status' => 'Approved',
            'approved_date' => now(),
        ]);
        return redirect()->back()->with('success', 'PO approved successfully.');
    }

    public function reject(ErpPurchaseOrder $purchaseOrder, Request $request)
    {
        $user = auth()->user();

        // Check if there are dynamic approvals
        $activeApproval = $purchaseOrder->approvals()->where('status', 'Pending')->first();

        if ($activeApproval) {
            $isAuthorized = false;
            if ($user->hasRole('superadmin')) {
                $isAuthorized = true;
            } elseif ($activeApproval->assigned_to_user_id) {
                if ($user->id == $activeApproval->assigned_to_user_id) {
                    $isAuthorized = true;
                }
            } elseif ($activeApproval->assigned_to_role_id) {
                $hasRole = \Illuminate\Support\Facades\DB::connection('tenant')
                    ->table('role_user')
                    ->where('user_id', $user->id)
                    ->where('role_id', $activeApproval->assigned_to_role_id)
                    ->exists();
                if ($hasRole) {
                    $isAuthorized = true;
                }
            }

            if (!$isAuthorized) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menolak tahap ini.');
            }

            $request->validate([
                'comments' => 'required|string',
            ]);

            $activeApproval->update([
                'status' => 'Rejected',
                'comments' => $request->input('comments'),
                'actual_approver_id' => $user->id,
                'approved_at' => now(),
            ]);

            // Cancel subsequent steps
            $purchaseOrder->approvals()->where('status', 'Waiting')->update(['status' => 'Cancelled']);

            $purchaseOrder->update([
                'status' => 'Rejected',
                'rejected_date' => now(),
            ]);

            return redirect()->back()->with('success', 'PO berhasil ditolak.');
        }

        // FALLBACK RULES (If no dynamic approval configs were set up)
        $totalCost = $purchaseOrder->total_po_amount_with_tax;

        if ($totalCost <= 1000000) {
            if (!$user->hasRole('procurement') && !$user->hasRole('superadmin')) {
                return redirect()->back()->with('error', 'Only Procurement Manager (Febri Saputra) can reject POs <= 1,000,000 IDR.');
            }
        } else {
            if (!$user->hasRole('ceo') && !$user->hasRole('superadmin')) {
                return redirect()->back()->with('error', 'Only CEO (Barry Japadermawan) can reject POs > 1,000,000 IDR.');
            }
        }

        $request->validate([
            'comments' => 'required|string',
        ]);

        $purchaseOrder->update([
            'status' => 'Rejected',
            'rejected_date' => now(),
            'description' => $purchaseOrder->description . "\nRejection Reason: " . $request->input('comments'),
        ]);

        return redirect()->back()->with('success', 'PO rejected successfully.');
    }

    public function destroy(ErpPurchaseOrder $purchaseOrder)
    {
        if (!auth()->user()->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Hanya Admin/Superadmin yang berhak menghapus PO.');
        }

        // Rollback any received physical stocks associated with this PO's Goods Receipts
        $purchaseOrder->load('goodsReceipts.items.requestFormItem.erpProduct');
        foreach ($purchaseOrder->goodsReceipts as $gr) {
            if ($gr->status === 'Received') {
                $warehouseId = $gr->warehouse_id ?: ($purchaseOrder->erp_warehouse_id ?: \Illuminate\Support\Facades\DB::table('erp_warehouses')->value('id'));
                foreach ($gr->items as $grItem) {
                    $product = $grItem->requestFormItem?->erpProduct;
                    if ($product && ($product->is_physical ?? true) && $warehouseId && $grItem->received_qty > 0) {
                        $stock = \App\Models\Erp\ErpStock::where('erp_product_id', $product->id)
                            ->where('erp_warehouse_id', $warehouseId)
                            ->first();
                        if ($stock) {
                            $newQty = max(0, $stock->qty_on_hand - $grItem->received_qty);
                            $stock->update(['qty_on_hand' => $newQty]);
                        }
                    }
                }
            }
            $gr->items()->delete();
            $gr->delete();
        }

        // Kembalikan status PR Item (RequestFormItem) ke 'Requested' biar bisa di-PO ulang
        $requestFormItemIds = $purchaseOrder->items()->pluck('request_form_item_id')->toArray();
        if (!empty($requestFormItemIds)) {
            \App\Models\Erp\RequestFormItem::whereIn('id', $requestFormItemIds)->update([
                'status' => 'Requested'
            ]);
        }

        $purchaseOrder->items()->delete();
        $purchaseOrder->approvals()->delete();
        $purchaseOrder->delete();

        return redirect()->route('erp.procurement.dashboard')->with('success', 'PO dihapus. Stok fisik yang pernah diterima telah dikurangi kembali, dan status barang (RF Item) dikembalikan agar dapat dipesan ulang.');
    }

    public function verify(ErpPurchaseOrder $purchaseOrder)
    {
        $user = auth()->user();
        if (!$user->hasRole('finance') && !$user->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Only Finance team can verify this PO.');
        }

        $purchaseOrder->update([
            'verified_by_id' => $user->id,
            'verification_timestamp' => now(),
        ]);

        return redirect()->back()->with('success', 'PO has been verified successfully by Finance.');
    }

    private function generatePoNo()
    {
        $prefix = 'PO-' . now()->format('Y') . '-';
        $count = ErpPurchaseOrder::where('po_no', 'like', $prefix . '%')->count();
        return $prefix . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }
}
