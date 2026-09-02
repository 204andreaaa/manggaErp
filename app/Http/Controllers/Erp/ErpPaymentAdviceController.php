<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpPaymentAdvice;
use App\Models\Erp\ErpPaymentAdviceDetail;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\ErpGoodsReceipt;
use App\Models\Erp\ErpApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ErpPaymentAdviceController extends Controller
{
    public function index()
    {
        if (!auth()->check()) abort(401);
        return view('erp.payment_advices.index');
    }

    public function datatable(Request $r)
    {
        if (!auth()->check()) abort(401);

        try {
            $draw   = (int) $r->input('draw', 1);
            $start  = (int) $r->input('start', 0);
            $length = (int) $r->input('length', 10);
            $search = trim((string) $r->input('search.value', ''));

            $query = ErpPaymentAdvice::query()->with(['purchaseOrder', 'supplier', 'owner']);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('supplier_invoice_no', 'like', "%{$search}%")
                      ->orWhere('invoice_no', 'like', "%{$search}%")
                      ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('purchaseOrder', fn($p) => $p->where('po_no', 'like', "%{$search}%"));
                });
            }

            $recordsTotal    = ErpPaymentAdvice::count();
            $recordsFiltered = (clone $query)->count();

            $rows = $query->orderBy('created_at', 'desc')
                ->skip($start)->take($length)->get()
                ->map(function ($pa, $i) use ($start) {
                    $appBadge = match($pa->approval_status) {
                        'Approved' => '<span class="badge bg-label-success fw-bold">Approved</span>',
                        'Submitted', 'Pending' => '<span class="badge bg-label-warning fw-bold">Submitted</span>',
                        'Rejected' => '<span class="badge bg-label-danger fw-bold">Rejected</span>',
                        default    => '<span class="badge bg-label-secondary fw-bold">Draft</span>',
                    };

                    $closedBadge = $pa->payment_closed
                        ? '<span class="badge bg-label-success">Closed</span>'
                        : '<span class="badge bg-label-warning">Not Closed</span>';
                    return [
                        'rownum' => $start + $i + 1,
                        'supplier_invoice_no' => '<a href="' . route('erp.payment-advices.show', $pa) . '" class="fw-bold text-primary">' . e($pa->supplier_invoice_no) . '</a>',
                        'po_no' => e($pa->purchaseOrder?->po_no ?? '-'),
                        'supplier_name' => e($pa->supplier?->name ?? '-'),
                        'invoice_no' => e($pa->invoice_no ?? '-'),
                        'total_amount' => 'IDR ' . number_format($pa->total_invoice_amount_with_tax, 0, ',', '.'),
                        'outstanding' => 'IDR ' . number_format($pa->outstanding, 0, ',', '.'),
                        'due_date' => $pa->due_date?->format('Y-m-d') ?? '-',
                        'approval_status' => $appBadge,
                        'payment_closed' => $closedBadge,
                        'action' => '<a href="' . route('erp.payment-advices.show', $pa) . '" class="btn btn-xs btn-primary"><i class="bx bx-show me-1"></i>View</a>',
                    ];
                });

            return response()->json([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $rows,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
            ]);
        }
    }

    public function create(Request $request)
    {
        $poId = $request->input('po_id');
        $grId = $request->input('gr_id');

        $purchaseOrder = $poId ? ErpPurchaseOrder::with('supplier', 'goodsReceipts')->find($poId) : null;
        $goodsReceipt = $grId ? ErpGoodsReceipt::with('purchaseOrder.supplier')->find($grId) : null;

        if ($goodsReceipt && !$purchaseOrder) {
            $purchaseOrder = $goodsReceipt->purchaseOrder;
        }

        return view('erp.payment_advices.create', compact('purchaseOrder', 'goodsReceipt'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'erp_purchase_order_id' => 'required|exists:erp_purchase_orders,id',
            'erp_goods_receipt_id' => 'nullable|exists:erp_goods_receipts,id',
            'invoice_no' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:150',
            'due_date' => 'nullable|date',
            'total_invoice_amount' => 'required|numeric|min:0',
            'initial_payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:100',
            'payment_type' => 'required|string|max:100',
            'remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $po = ErpPurchaseOrder::findOrFail($data['erp_purchase_order_id']);
            $gr = !empty($data['erp_goods_receipt_id']) ? ErpGoodsReceipt::find($data['erp_goods_receipt_id']) : $po->goodsReceipts()->first();

            $pa = new ErpPaymentAdvice();
            $pa->supplier_invoice_no = $this->generateSiNo();
            $pa->erp_purchase_order_id = $po->id;
            $pa->supplier_id = $po->supplier_id;
            $pa->invoice_no = $data['invoice_no'];
            $pa->contact_person = $data['contact_person'] ?? $po->contact_person;
            $pa->due_date = $data['due_date'] ?? now()->addDays(30);
            $pa->total_invoice_amount = $data['total_invoice_amount'];
            $pa->total_invoice_amount_with_tax = $data['total_invoice_amount'];
            $pa->status = 'Draft';
            $pa->approval_status = 'Draft';
            $pa->payment_closed = false;
            $pa->owner_id = auth()->id();
            $pa->save();

            // Create initial Payment Advice Detail (Termin 1 / DP 1)
            $pad = new ErpPaymentAdviceDetail();
            $pad->supplier_detail_no = $this->generateSidNo();
            $pad->erp_payment_advice_id = $pa->id;
            $pad->erp_purchase_order_id = $po->id;
            $pad->erp_goods_receipt_id = $gr?->id;
            $pad->gr_date = $gr?->date;
            $pad->created_date_sid = now();
            $pad->payment_amount = $data['initial_payment_amount'];
            $pad->payment_amount_with_tax = $data['initial_payment_amount'];
            $pad->payment_method = $data['payment_method'];
            $pad->payment_type = $data['payment_type'];
            $pad->remark = $data['remark'];
            $pad->days_invoice_overdue = '< 30';
            $pad->days_overdue = 1;
            $pad->approval_status = 'Draft';
            $pad->save();

            $this->recalculateTotals($pa);

            DB::commit();

            return redirect()->route('erp.payment-advices.show', $pa)->with('success', 'Payment Advice Header & Termin 1 berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuat Payment Advice: ' . $e->getMessage())->withInput();
        }
    }

    public function storeDetail(Request $request, ErpPaymentAdvice $paymentAdvice)
    {
        if ($paymentAdvice->payment_closed || $paymentAdvice->outstanding <= 0) {
            return redirect()->back()->with('error', 'Payment Advice ini sudah Lunas, tidak dapat menambah rincian termin lagi.');
        }

        $allocatedSum = $paymentAdvice->details()->sum('payment_amount_with_tax');
        $unallocated = max(0, $paymentAdvice->total_invoice_amount_with_tax - $allocatedSum);

        if ($unallocated <= 0) {
            return redirect()->back()->with('error', 'Seluruh total invoice (100%) sudah teralokasi ke dalam jadwal termin yang ada. Harap hapus termin Draft yang ada terlebih dahulu jika ingin membuat termin kustom baru.');
        }

        $data = $request->validate([
            'payment_amount' => 'required|numeric|min:1|max:' . $unallocated,
            'payment_method' => 'required|string|max:100',
            'payment_type' => 'required|string|max:100',
            'erp_goods_receipt_id' => 'nullable|exists:erp_goods_receipts,id',
            'remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $gr = !empty($data['erp_goods_receipt_id']) ? ErpGoodsReceipt::find($data['erp_goods_receipt_id']) : $paymentAdvice->purchaseOrder?->goodsReceipts()->first();

            $pad = new ErpPaymentAdviceDetail();
            $pad->supplier_detail_no = $this->generateSidNo();
            $pad->erp_payment_advice_id = $paymentAdvice->id;
            $pad->erp_purchase_order_id = $paymentAdvice->erp_purchase_order_id;
            $pad->erp_goods_receipt_id = $gr?->id;
            $pad->gr_date = $gr?->date;
            $pad->created_date_sid = now();
            $pad->payment_amount = $data['payment_amount'];
            $pad->payment_amount_with_tax = $data['payment_amount'];
            $pad->payment_method = $data['payment_method'];
            $pad->payment_type = $data['payment_type'];
            $pad->remark = $data['remark'];
            $pad->days_invoice_overdue = '< 30';
            $pad->days_overdue = 1;
            $pad->approval_status = 'Draft';
            $pad->save();

            $this->recalculateTotals($paymentAdvice);

            DB::commit();

            return redirect()->route('erp.payment-advices.show', $paymentAdvice)->with('success', 'Termin pembayaran baru (' . $pad->supplier_detail_no . ') berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuat termin pembayaran: ' . $e->getMessage());
        }
    }

    public function destroyDetail(ErpPaymentAdviceDetail $paymentAdviceDetail)
    {
        if (!auth()->user()->hasRole('superadmin') && !auth()->user()->hasRole('finance')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus termin pembayaran.');
        }

        if ($paymentAdviceDetail->approval_status === 'Approved' || $paymentAdviceDetail->date_paid) {
            return redirect()->back()->with('error', 'Termin yang sudah di-Approve atau sudah dibayar tidak dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            $pa = $paymentAdviceDetail->paymentAdvice;
            $paymentAdviceDetail->delete();

            if ($pa) {
                $this->recalculateTotals($pa);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Termin pembayaran berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus termin: ' . $e->getMessage());
        }
    }

    private function recalculateTotals(ErpPaymentAdvice $paymentAdvice)
    {
        $sum = $paymentAdvice->details()
            ->whereNotIn('approval_status', ['Draft', 'Rejected', 'Cancelled'])
            ->sum('payment_amount_with_tax');
        $total = $paymentAdvice->total_invoice_amount_with_tax;
        $outstanding = max(0, $total - $sum);
        $isClosed = ($outstanding <= 0 && $total > 0);

        $paymentAdvice->update([
            'sum_payment_amount' => $sum,
            'sum_payment_amount_with_tax' => $sum,
            'outstanding' => $outstanding,
            'payment_closed' => $isClosed,
            'status' => $isClosed ? 'Completed' : ($paymentAdvice->approval_status === 'Approved' ? 'Approved' : $paymentAdvice->status)
        ]);

        if ($isClosed && $paymentAdvice->purchaseOrder) {
            $paymentAdvice->purchaseOrder->update(['payment_closed' => true]);
        }
    }

    public function show(ErpPaymentAdvice $paymentAdvice)
    {
        $paymentAdvice->load([
            'purchaseOrder',
            'supplier',
            'owner',
            'details.goodsReceipt',
            'details.approvals.assignedRole',
            'details.approvals.assignedUser',
            'details.approvals.actualApprover',
            'approvals'
        ]);

        return view('erp.payment_advices.show', compact('paymentAdvice'));
    }

    public function showDetail(ErpPaymentAdviceDetail $paymentAdviceDetail)
    {
        $paymentAdviceDetail->load([
            'paymentAdvice.purchaseOrder',
            'paymentAdvice.supplier',
            'paymentAdvice.owner',
            'goodsReceipt',
            'approvals.assignedRole',
            'approvals.assignedUser',
            'approvals.actualApprover'
        ]);

        $approvals = $paymentAdviceDetail->approvals;

        return view('erp.payment_advice_details.show', compact('paymentAdviceDetail', 'approvals'));
    }

    public function updateInvoice(Request $request, ErpPaymentAdviceDetail $paymentAdviceDetail)
    {
        abort_unless(auth()->user()->hasRole(['finance', 'superadmin']), 403, 'Hanya tim Finance yang berhak mengedit data invoice.');

        $request->validate([
            'invoice_no' => 'required|string|max:100',
            'invoice_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $updateData = [
            'invoice_no' => $request->invoice_no,
        ];

        if ($request->hasFile('invoice_attachment')) {
            $file = $request->file('invoice_attachment');
            $filename = 'inv_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/invoices'), $filename);
            $updateData['invoice_attachment'] = 'uploads/invoices/' . $filename;
        }

        $paymentAdviceDetail->update($updateData);

        return redirect()->back()->with('success', 'Data Invoice Vendor (' . $paymentAdviceDetail->invoice_no . ') berhasil disimpan.');
    }

    public function submitDetail(Request $request, ErpPaymentAdviceDetail $paymentAdviceDetail)
    {
        abort_unless(auth()->user()->hasRole(['finance', 'superadmin']), 403, 'Hanya tim Finance yang berhak mengajukan tagihan untuk approval.');

        $invoiceNo = $request->invoice_no ?: $paymentAdviceDetail->invoice_no;
        if (empty($invoiceNo) || $invoiceNo === '-') {
            return redirect()->back()->with('error', 'Nomor Invoice Vendor wajib diisi sebelum submit approval.');
        }

        $request->validate([
            'invoice_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($paymentAdviceDetail->approvals()->count() > 0) {
            return redirect()->back()->with('error', 'Payment Advice Detail is already submitted for approval.');
        }

        // Sequential validation check
        $unapprovedPrev = $paymentAdviceDetail->previousUnapprovedDetail();
        if ($unapprovedPrev) {
            return redirect()->back()->with('error', 'Termin pembayaran harus diajukan secara berurutan. Harap selesaikan dan setujui (Approved) termin sebelumnya (' . $unapprovedPrev->payment_type . ' - ' . $unapprovedPrev->supplier_detail_no . ') terlebih dahulu.');
        }

        DB::beginTransaction();
        try {
            $grId = $request->input('erp_goods_receipt_id') ?: ($paymentAdviceDetail->erp_goods_receipt_id ?: $paymentAdviceDetail->purchaseOrder?->goodsReceipts?->first()?->id);

            $updateData = [
                'invoice_no' => $invoiceNo,
                'erp_goods_receipt_id' => $grId,
                'approval_status' => 'Submitted'
            ];

            if ($request->hasFile('invoice_attachment')) {
                $file = $request->file('invoice_attachment');
                $filename = 'inv_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/invoices'), $filename);
                $updateData['invoice_attachment'] = 'uploads/invoices/' . $filename;
            }

            // Update the detail with invoice no, attachment and linked GR
            $paymentAdviceDetail->update($updateData);

            // Create Approval Flow
            $amount = $paymentAdviceDetail->payment_amount_with_tax;
            $configs = \App\Models\Erp\ErpApprovalConfig::where('record_type', 'payment_advice')
                ->where(function($q) use ($amount) {
                    $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount);
                })
                ->where(function($q) use ($amount) {
                    $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
                })
                ->orderBy('level')
                ->get();

            if ($configs->isEmpty()) {
                // If no dynamic configs, fallback to 1-level CEO/Finance
                \App\Models\Erp\ErpApproval::create([
                    'payment_advice_detail_id' => $paymentAdviceDetail->id,
                    'level' => 1,
                    'status' => 'Pending',
                ]);
            } else {
                $isFirst = true;
                foreach ($configs as $config) {
                    \App\Models\Erp\ErpApproval::create([
                        'payment_advice_detail_id' => $paymentAdviceDetail->id,
                        'level' => $config->level,
                        'assigned_to_role_id' => $config->role_id,
                        'assigned_to_user_id' => $config->user_id,
                        'status' => $isFirst ? 'Pending' : 'Waiting',
                    ]);
                    $isFirst = false;
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Rincian termin berhasil disubmit untuk approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal submit approval: ' . $e->getMessage());
        }
    }

    public function approveDetail(Request $request, ErpPaymentAdviceDetail $paymentAdviceDetail)
    {
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $activeApproval = $paymentAdviceDetail->approvals()->where('status', 'Pending')->first();

            if ($activeApproval) {
                $isAuthorized = false;
                if ($user->hasRole('superadmin')) {
                    $isAuthorized = true;
                } elseif ($activeApproval->assigned_to_user_id) {
                    if ($user->id == $activeApproval->assigned_to_user_id) {
                        $isAuthorized = true;
                    }
                } elseif ($activeApproval->assigned_to_role_id) {
                    $hasRole = \Illuminate\Support\Facades\DB::connection('master')
                        ->table('role_user')
                        ->where('user_id', $user->id)
                        ->where('role_id', $activeApproval->assigned_to_role_id)
                        ->exists();
                    if ($hasRole) {
                        $isAuthorized = true;
                    }
                }

                if (!$isAuthorized) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menyetujui termin ini.');
                }

                $activeApproval->update([
                    'status' => 'Approved',
                    'comments' => $request->input('comments', 'Approved by ' . $user->name),
                    'actual_approver_id' => $user->id,
                    'approved_at' => now(),
                ]);

                // Promote next step if any
                $nextApproval = $paymentAdviceDetail->approvals()->where('status', 'Waiting')->orderBy('level')->first();
                if ($nextApproval) {
                    $nextApproval->update(['status' => 'Pending']);
                } else {
                    // Fully approved
                    $paymentAdviceDetail->update([
                        'approval_status' => 'Approved',
                        'approved_date' => now()
                    ]);
                    
                    // Recalculate PA Header
                    if ($paymentAdviceDetail->paymentAdvice) {
                        $this->recalculateTotals($paymentAdviceDetail->paymentAdvice);
                    }
                }

                DB::commit();
                return redirect()->back()->with('success', 'Approval Rincian Termin berhasil disetujui.');
            }

            // FALLBACK RULES (No active dynamic approval, use legacy flow)
            if (!$user->hasRole('superadmin') && !$user->hasRole('finance') && !$user->hasRole('ceo')) {
                return redirect()->back()->with('error', 'Hanya Finance / CEO / Superadmin yang berhak menyetujui Termin ini.');
            }

            $paymentAdviceDetail->update([
                'approval_status' => 'Approved',
                'approved_date' => now()
            ]);

            \App\Models\Erp\ErpApproval::create([
                'payment_advice_detail_id' => $paymentAdviceDetail->id,
                'level' => 1,
                'status' => 'Approved',
                'assigned_to_user_id' => $user->id,
                'actual_approver_id' => $user->id,
                'approved_at' => now(),
                'comments' => 'Approved by ' . $user->name,
            ]);

            if ($paymentAdviceDetail->paymentAdvice) {
                $this->recalculateTotals($paymentAdviceDetail->paymentAdvice);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Rincian Termin berhasil disetujui (Approved).');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyetujui Rincian Termin: ' . $e->getMessage());
        }
    }

    public function rejectDetail(Request $request, ErpPaymentAdviceDetail $paymentAdviceDetail)
    {
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $activeApproval = $paymentAdviceDetail->approvals()->where('status', 'Pending')->first();

            if ($activeApproval) {
                $isAuthorized = false;
                if ($user->hasRole('superadmin')) {
                    $isAuthorized = true;
                } elseif ($activeApproval->assigned_to_user_id) {
                    if ($user->id == $activeApproval->assigned_to_user_id) {
                        $isAuthorized = true;
                    }
                } elseif ($activeApproval->assigned_to_role_id) {
                    $hasRole = \Illuminate\Support\Facades\DB::connection('master')
                        ->table('role_user')
                        ->where('user_id', $user->id)
                        ->where('role_id', $activeApproval->assigned_to_role_id)
                        ->exists();
                    if ($hasRole) {
                        $isAuthorized = true;
                    }
                }

                if (!$isAuthorized) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menolak termin ini.');
                }

                $activeApproval->update([
                    'status' => 'Rejected',
                    'comments' => $request->input('reason', 'Rejected by ' . $user->name),
                    'actual_approver_id' => $user->id,
                    'approved_at' => now(),
                ]);

                // Cancel subsequent steps
                $paymentAdviceDetail->approvals()->where('status', 'Waiting')->update(['status' => 'Cancelled']);

                $paymentAdviceDetail->update([
                    'approval_status' => 'Rejected'
                ]);

                DB::commit();
                return redirect()->back()->with('success', 'Rincian Termin berhasil ditolak.');
            }

            // FALLBACK RULES
            if (!$user->hasRole('superadmin') && !$user->hasRole('finance') && !$user->hasRole('ceo')) {
                return redirect()->back()->with('error', 'Hanya Finance / CEO / Superadmin yang berhak menolak (Reject) Termin ini.');
            }

            $paymentAdviceDetail->update([
                'approval_status' => 'Rejected'
            ]);

            \App\Models\Erp\ErpApproval::create([
                'payment_advice_detail_id' => $paymentAdviceDetail->id,
                'level' => 1,
                'status' => 'Rejected',
                'assigned_to_user_id' => $user->id,
                'actual_approver_id' => $user->id,
                'approved_at' => now(),
                'comments' => $request->input('reason', 'Rejected by ' . $user->name),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Rincian Termin telah ditolak (Rejected).');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menolak Termin: ' . $e->getMessage());
        }
    }

    public function markPaidDetail(Request $request, ErpPaymentAdviceDetail $paymentAdviceDetail)
    {
        $user = auth()->user();
        if (!$user->hasRole('superadmin') && !$user->hasRole('finance')) {
            return redirect()->back()->with('error', 'Hanya Tim Finance / Superadmin yang berhak memproses pembayaran termin.');
        }

        $request->validate([
            'payment_receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $updateData = [
                'date_paid' => now()
            ];

            if ($request->hasFile('payment_receipt')) {
                $file = $request->file('payment_receipt');
                $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/receipts'), $filename);
                $updateData['payment_receipt'] = 'uploads/receipts/' . $filename;
            }

            $paymentAdviceDetail->update($updateData);

            DB::commit();

            return redirect()->back()->with('success', 'Pembayaran termin berhasil dicatat & ditandai Lunas.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    public function destroy(ErpPaymentAdvice $paymentAdvice)
    {
        if (!auth()->user()->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Hanya Superadmin yang berhak menghapus Payment Advice.');
        }

        DB::beginTransaction();
        try {
            $paymentAdvice->details()->delete();
            $paymentAdvice->approvals()->delete();
            $paymentAdvice->delete();

            DB::commit();

            return redirect()->route('erp.payment-advices.index')->with('success', 'Payment Advice berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus Payment Advice: ' . $e->getMessage());
        }
    }

    private function generateSiNo()
    {
        $prefix = 'SI' . now()->format('Y-m') . '-';
        $latest = ErpPaymentAdvice::where('supplier_invoice_no', 'like', $prefix . '%')
            ->orderBy('supplier_invoice_no', 'desc')
            ->first();

        if ($latest) {
            $number = intval(substr($latest->supplier_invoice_no, -5));
            return $prefix . str_pad($number + 1, 5, '0', STR_PAD_LEFT);
        }

        return $prefix . '26978';
    }

    private function generateSidNo()
    {
        $prefix = 'SID-' . now()->format('Y-m') . '-';
        $latest = ErpPaymentAdviceDetail::where('supplier_detail_no', 'like', $prefix . '%')
            ->orderBy('supplier_detail_no', 'desc')
            ->first();

        if ($latest) {
            $number = intval(substr($latest->supplier_detail_no, -5));
            return $prefix . str_pad($number + 1, 5, '0', STR_PAD_LEFT);
        }

        return $prefix . '63503';
    }
}
