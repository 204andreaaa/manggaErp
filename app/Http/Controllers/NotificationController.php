<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\RequestForm;
use App\Models\Erp\ErpPaymentAdviceDetail;
use App\Models\Erp\ErpPaymentAdvice;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        $notifications = [];

        // ==========================================
        // 1. ACTIONABLE: Persetujuan Purchase Order (PO)
        // ==========================================
        $poApprovalsQuery = ErpPurchaseOrder::where('status', 'Submitted');
        if ($user->hasRole('superadmin')) {
            $pendingPos = $poApprovalsQuery->with(['supplier', 'requestForm'])->latest()->get();
        } else {
            $pendingPos = $poApprovalsQuery
                ->whereHas('approvals', function($q) use ($user) {
                    $q->where('status', 'Pending')
                      ->where(function($sq) use ($user) {
                          $sq->where('assigned_to_user_id', $user->id)
                             ->orWhereIn('assigned_to_role_id', $user->roles->pluck('id'));
                      });
                })
                ->with(['supplier', 'requestForm'])
                ->latest()
                ->get();
        }

        foreach ($pendingPos as $po) {
            $supplierName = $po->supplier?->name ?? 'Vendor';
            $amountFormatted = number_format($po->total_po_amount_with_tax, 0, ',', '.');
            $notifications[] = [
                'id'         => 'po_appr_' . $po->id,
                'type'       => 'po_approval_needed',
                'title'      => 'Persetujuan Purchase Order (PO)',
                'body'       => "PO {$po->po_no} ({$supplierName}) senilai Rp {$amountFormatted} menunggu approval Anda.",
                'url'        => route('erp.purchase-orders.show', $po),
                'created_at' => $po->created_at ? $po->created_at->diffForHumans() : 'Baru saja',
                'is_read'    => false,
            ];
        }

        // ==========================================
        // 1b. ACTIONABLE: Verifikasi Purchase Order (PO) - Head of Procurement (Febri)
        // ==========================================
        $isHeadProcurement = ($user->email === 'febri@local.com' || $user->username === 'febri' || $user->hasRole('head_procurement') || $user->hasRole('superadmin'));
        if ($isHeadProcurement) {
            $unverifiedPos = ErpPurchaseOrder::where('status', 'Draft')
                ->whereNull('verified_by_id')
                ->with(['supplier', 'owner'])
                ->latest()
                ->get();

            foreach ($unverifiedPos as $po) {
                $supplierName = $po->supplier?->name ?? 'Vendor';
                $creator = $po->owner?->name ?? 'Staff Procurement';
                $amountFormatted = number_format($po->total_po_amount_with_tax, 0, ',', '.');
                $notifications[] = [
                    'id'         => 'po_verif_' . $po->id,
                    'type'       => 'po_verification_needed',
                    'title'      => 'Verifikasi Purchase Order (PO)',
                    'body'       => "PO {$po->po_no} ({$supplierName}) senilai Rp {$amountFormatted} dibuat oleh {$creator} menunggu verifikasi Head of Procurement.",
                    'url'        => route('erp.purchase-orders.show', $po),
                    'created_at' => $po->created_at ? $po->created_at->diffForHumans() : 'Baru saja',
                    'is_read'    => false,
                ];
            }
        }

        // ==========================================
        // 2. ACTIONABLE: Persetujuan Request Form (RF)
        // ==========================================
        $rfApprovalsQuery = RequestForm::where('status', 'Submitted');
        if ($user->hasRole('superadmin')) {
            $pendingRfs = $rfApprovalsQuery->latest()->get();
        } else {
            $pendingRfs = $rfApprovalsQuery
                ->whereHas('approvals', function($q) use ($user) {
                    $q->where('status', 'Pending')
                      ->where(function($sq) use ($user) {
                          $sq->where('assigned_to_user_id', $user->id)
                             ->orWhereIn('assigned_to_role_id', $user->roles->pluck('id'));
                      });
                })
                ->latest()
                ->get();
        }

        foreach ($pendingRfs as $rf) {
            $requestor = $rf->requestor ?? 'User';
            $notifications[] = [
                'id'         => 'rf_appr_' . $rf->id,
                'type'       => 'rf_approval_needed',
                'title'      => 'Persetujuan Request Form (RF)',
                'body'       => "Pengajuan {$rf->rf_no} oleh {$requestor} menunggu approval Anda.",
                'url'        => route('erp.request-form.show', $rf),
                'created_at' => $rf->created_at ? $rf->created_at->diffForHumans() : 'Baru saja',
                'is_read'    => false,
            ];
        }

        // ==========================================
        // 2b. ACTIONABLE: RF Approved -> Buat Purchase Request (PR) (Logistik / Requestor)
        // ==========================================
        if ($user->hasRole('superadmin') || $user->hasRole('logistik') || $user->hasRole('warehouse')) {
            $approvedRfsWithoutPr = RequestForm::where('status', 'Approved')
                ->whereDoesntHave('purchaseRequests')
                ->latest()
                ->limit(10)
                ->get();

            foreach ($approvedRfsWithoutPr as $rf) {
                if ($user->hasRole('superadmin') || $user->hasRole('logistik') || $user->hasRole('warehouse') || strtolower($user->name) === strtolower($rf->requestor)) {
                    $notifications[] = [
                        'id'         => 'rf_approved_pr_' . $rf->id,
                        'type'       => 'rf_approved_create_pr',
                        'title'      => 'RF Approved — Buat Purchase Request (PR)',
                        'body'       => "RF {$rf->rf_no} telah disetujui sepenuhnya oleh CEO. Silakan buat Purchase Request (PR).",
                        'url'        => route('erp.request-form.show', $rf),
                        'created_at' => $rf->updated_at ? $rf->updated_at->diffForHumans() : 'Baru saja',
                        'is_read'    => false,
                    ];
                }
            }
        }

        // ==========================================
        // 2c. ACTIONABLE: RF Approved -> Buat Purchase Order (PO) (Procurement)
        // ==========================================
        if ($user->hasRole('superadmin') || $user->hasRole('procurement')) {
            $approvedRfsWithoutPo = RequestForm::where('status', 'Approved')
                ->whereDoesntHave('purchaseOrders')
                ->latest()
                ->limit(10)
                ->get();

            foreach ($approvedRfsWithoutPo as $rf) {
                $notifications[] = [
                    'id'         => 'rf_ready_po_' . $rf->id,
                    'type'       => 'rf_ready_po',
                    'title'      => 'RF Siap Diproses PO',
                    'body'       => "RF {$rf->rf_no} ({$rf->requestor}) telah Approved dan siap diterbitkan Purchase Order (PO).",
                    'url'        => route('erp.purchase-orders.create', $rf),
                    'created_at' => $rf->updated_at ? $rf->updated_at->diffForHumans() : 'Baru saja',
                    'is_read'    => false,
                ];
            }
        }

        // ==========================================
        // 3. ACTIONABLE: Notifikasi Keuangan / Payment Advice (PA) - Finance / Staff Finance / CEO
        // ==========================================
        if ($user->hasRole('superadmin') || $user->hasRole('ceo') || $user->hasRole('finance')) {
            // 3a. Tagihan Siap Diproses / Diajukan (Draft Termin) - Untuk Finance Staff (Lilu / Melvien)
            $draftPas = ErpPaymentAdviceDetail::where(function($q) {
                    $q->where('approval_status', 'Draft')
                      ->orWhereNull('approval_status');
                })
                ->whereNull('date_paid')
                ->whereHas('paymentAdvice', fn($q) => $q->where('payment_closed', false))
                ->with(['paymentAdvice.purchaseOrder', 'paymentAdvice.supplier'])
                ->latest()
                ->limit(5)
                ->get();

            foreach ($draftPas as $pad) {
                $paNo = $pad->paymentAdvice?->supplier_invoice_no ?? 'PA';
                $supplier = $pad->paymentAdvice?->supplier?->name ?? 'Vendor';
                $amount = number_format($pad->payment_amount_with_tax ?: $pad->payment_amount, 0, ',', '.');
                $termin = $pad->payment_type ?? 'Termin';
                
                if ($pad->approval_status === 'Draft' || empty($pad->approval_status)) {
                    $notifications[] = [
                        'id'         => 'pa_draft_' . $pad->id,
                        'type'       => 'pa_ready_to_process',
                        'title'      => 'Tagihan Vendor Siap Diproses (PA)',
                        'body'       => "Tagihan {$paNo} ({$supplier}) {$termin} senilai Rp {$amount} siap diajukan/diproses pencairan.",
                        'url'        => $pad->paymentAdvice ? route('erp.payment-advices.show', $pad->paymentAdvice) : route('erp.payment-advices.index'),
                        'created_at' => $pad->created_at ? $pad->created_at->diffForHumans() : 'Baru saja',
                        'is_read'    => false,
                    ];
                }
            }

            // 3b. Menunggu Approval Pencairan (Submitted / Pending)
            $pendingPas = ErpPaymentAdviceDetail::whereIn('approval_status', ['Submitted', 'Pending', 'Waiting'])
                ->with(['paymentAdvice.purchaseOrder', 'paymentAdvice.supplier'])
                ->latest()
                ->limit(5)
                ->get();

            foreach ($pendingPas as $pad) {
                $paNo = $pad->paymentAdvice?->supplier_invoice_no ?? 'PA';
                $supplier = $pad->paymentAdvice?->supplier?->name ?? 'Vendor';
                $amount = number_format($pad->payment_amount_with_tax ?: $pad->payment_amount, 0, ',', '.');
                $termin = $pad->payment_type ?? 'Termin';
                $notifications[] = [
                    'id'         => 'pa_appr_' . $pad->id,
                    'type'       => 'pa_approval_needed',
                    'title'      => 'Persetujuan Payment Advice (PA)',
                    'body'       => "Pencairan {$paNo} ({$supplier}) {$termin} senilai Rp {$amount} menunggu persetujuan.",
                    'url'        => $pad->paymentAdvice ? route('erp.payment-advices.show', $pad->paymentAdvice) : route('erp.payment-advices.index'),
                    'created_at' => $pad->created_at ? $pad->created_at->diffForHumans() : 'Baru saja',
                    'is_read'    => false,
                ];
            }

            // 3c. Pemberitahuan Barang Diterima Gudang (GR Received -> Info untuk Pelunasan)
            $receivedGrs = \App\Models\Erp\ErpGoodsReceipt::where('status', 'Received')
                ->whereHas('purchaseOrder.paymentAdvices', fn($q) => $q->where('payment_closed', false))
                ->with(['purchaseOrder.supplier'])
                ->latest()
                ->limit(3)
                ->get();

            foreach ($receivedGrs as $gr) {
                $poNo = $gr->purchaseOrder?->po_no ?? 'PO';
                $supplier = $gr->purchaseOrder?->supplier?->name ?? 'Vendor';
                $notifications[] = [
                    'id'         => 'gr_received_finance_' . $gr->id,
                    'type'       => 'gr_received_finance_info',
                    'title'      => 'Barang Diterima — Siap Pelunasan',
                    'body'       => "Barang untuk {$poNo} ({$supplier}) telah diterima di gudang oleh Logistik. Silakan proses pelunasan tagihan.",
                    'url'        => $gr->purchaseOrder ? route('erp.purchase-orders.show', $gr->purchaseOrder) : route('erp.payment-advices.index'),
                    'created_at' => $gr->updated_at ? $gr->updated_at->diffForHumans() : 'Baru saja',
                    'is_read'    => false,
                ];
            }
        }

        // ==========================================
        // 4. ACTIONABLE: Penerimaan Barang (GR/DO) - GA / Logistik
        // ==========================================
        if ($user->hasRole('superadmin') || $user->hasRole('ga') || $user->hasRole('logistik') || $user->hasRole('warehouse')) {
            $approvedPosWithoutGr = ErpPurchaseOrder::where('status', 'Approved')
                ->whereDoesntHave('goodsReceipts')
                ->with('supplier')
                ->latest()
                ->limit(5)
                ->get();

            foreach ($approvedPosWithoutGr as $po) {
                $supplier = $po->supplier?->name ?? 'Vendor';
                $notifications[] = [
                    'id'         => 'gr_ready_' . $po->id,
                    'type'       => 'gr_ready',
                    'title'      => 'PO Siap Diterima (GR/DO)',
                    'body'       => "PO {$po->po_no} ({$supplier}) telah Approved dan siap diproses Penerimaan Barang.",
                    'url'        => route('erp.purchase-orders.show', $po),
                    'created_at' => $po->created_at ? $po->created_at->diffForHumans() : 'Baru saja',
                    'is_read'    => false,
                ];
            }
        }

        // ==========================================
        // 4b. ACTIONABLE: Verifikasi Goods Receipt (GR/DO) - Logistik / Warehouse
        // ==========================================
        if ($user->hasRole('superadmin') || $user->hasRole('logistik') || $user->hasRole('warehouse')) {
            $unverifiedGrs = \App\Models\Erp\ErpGoodsReceipt::where('status', '!=', 'Received')
                ->with(['purchaseOrder.supplier', 'owner'])
                ->latest()
                ->limit(5)
                ->get();

            foreach ($unverifiedGrs as $gr) {
                $supplier = $gr->purchaseOrder?->supplier?->name ?? 'Vendor';
                $creator = $gr->owner?->name ?? 'Tim GA';
                $notifications[] = [
                    'id'         => 'gr_verif_' . $gr->id,
                    'type'       => 'gr_verification_needed',
                    'title'      => 'Verifikasi Fisik Barang (GR)',
                    'body'       => "DO {$gr->do_no} ({$supplier}) dibuat oleh {$creator} menunggu pengecekan fisik & verifikasi Logistik.",
                    'url'        => route('erp.goods-receipts.show', $gr),
                    'created_at' => $gr->created_at ? $gr->created_at->diffForHumans() : 'Baru saja',
                    'is_read'    => false,
                ];
            }
        }

        // ==========================================
        // 5. PERSISTENT: Notifikasi Riwayat dari Database
        // ==========================================
        $dbNotifs = Notification::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        foreach ($dbNotifs as $dn) {
            $notifications[] = [
                'id'         => (string)$dn->id,
                'type'       => $dn->type,
                'title'      => $dn->title,
                'body'       => $dn->body,
                'url'        => $dn->url ?: '#',
                'created_at' => $dn->created_at ? $dn->created_at->diffForHumans() : 'Baru saja',
                'is_read'    => (bool) $dn->is_read,
            ];
        }

        $unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    public function markAsRead($id)
    {
        if (is_numeric($id)) {
            Notification::where('id', $id)
                ->where('user_id', auth()->id())
                ->update(['is_read' => true, 'read_at' => Carbon::now()]);
        }
        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => Carbon::now()]);

        return response()->json(['success' => true]);
    }

    public function getBadge(Request $request)
    {
        $type = $request->query('type');
        $user = auth()->user();
        if (!$user) return response()->json(['count' => 0]);

        $count = 0;
        if ($type === 'po' || $type === 'purchase_orders') {
            if ($user->hasRole('superadmin')) {
                $count = ErpPurchaseOrder::where('status', 'Submitted')->count();
            } else {
                $count = ErpPurchaseOrder::where('status', 'Submitted')
                    ->whereHas('approvals', function($q) use ($user) {
                        $q->where('status', 'Pending')
                          ->where(function($sq) use ($user) {
                              $sq->where('assigned_to_user_id', $user->id)
                                 ->orWhereIn('assigned_to_role_id', $user->roles->pluck('id'));
                          });
                    })->count();
            }
        } elseif ($type === 'rf' || $type === 'request_forms') {
            if ($user->hasRole('superadmin') || $user->hasRole('ceo')) {
                $count = RequestForm::where('status', 'Submitted')->count();
            }
        } elseif ($type === 'pa' || $type === 'payment_advices') {
            if ($user->hasRole('superadmin') || $user->hasRole('ceo') || $user->hasRole('finance')) {
                $count = ErpPaymentAdviceDetail::whereIn('approval_status', ['Submitted', 'Pending', 'Waiting'])->count();
            }
        } elseif ($type === 'gr' || $type === 'goods_receipts') {
            if ($user->hasRole('superadmin') || $user->hasRole('ga') || $user->hasRole('logistik')) {
                $count = ErpPurchaseOrder::where('status', 'Approved')->whereDoesntHave('goodsReceipts')->count();
            }
        }

        return response()->json(['count' => $count]);
    }
}
