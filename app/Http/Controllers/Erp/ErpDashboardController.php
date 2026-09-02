<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\ErpPaymentAdvice;
use App\Models\Erp\ErpPaymentAdviceDetail;
use App\Models\Erp\ErpGoodsReceipt;
use App\Models\Erp\RequestForm;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\ErpSupplier;
use App\Models\Erp\ErpStock;
use App\Models\Erp\ErpProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ErpDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Year, Month & Budget Parent Filter
        $currentYear = (int) date('Y');
        $selectedYear = (int) $request->input('year', $currentYear);
        $selectedMonth = $request->input('month', 'all'); // 'all' or 1..12
        $selectedBudgetParentId = $request->input('budget_parent_id', 'all');

        $budgetParents = \App\Models\Erp\ErpBudgetParent::orderBy('name')->get();

        $workItemIds = null;
        if ($selectedBudgetParentId !== 'all' && is_numeric($selectedBudgetParentId)) {
            $workItemIds = ErpWorkItem::whereHas('subProject', function($q) use ($selectedBudgetParentId) {
                $q->where('budget_parent_id', (int) $selectedBudgetParentId);
            })->pluck('id')->toArray();
        }

        // Years list for dropdown (e.g. from 2024 to currentYear + 1)
        $years = range(max(2024, $currentYear - 2), $currentYear + 1);
        rsort($years);

        // Base date query builder helper
        $applyDateFilter = function ($query, $dateColumn = 'created_at') use ($selectedYear, $selectedMonth) {
            $query->whereYear($dateColumn, $selectedYear);
            if ($selectedMonth !== 'all' && is_numeric($selectedMonth)) {
                $query->whereMonth($dateColumn, (int) $selectedMonth);
            }
            return $query;
        };

        // 1. PO Metrics
        $poQuery = ErpPurchaseOrder::query();
        $poQuery = $applyDateFilter($poQuery, 'created_at');
        if ($workItemIds !== null) {
            $poQuery->whereHas('requestForm.items', function($q) use ($workItemIds) {
                $q->whereIn('work_item_id', $workItemIds);
            });
        }
        
        $totalPoCount = (clone $poQuery)->count();
        $approvedPoCount = (clone $poQuery)->whereIn('status', ['Approved', 'Completed'])->count();
        $draftPoCount = (clone $poQuery)->where('status', 'Draft')->count();
        $submittedPoCount = (clone $poQuery)->where('status', 'Submitted')->count();
        $rejectedPoCount = (clone $poQuery)->where('status', 'Rejected')->count();

        $totalSpendAmount = (clone $poQuery)->whereIn('status', ['Approved', 'Completed'])->sum('total_po_amount_with_tax');
        $totalSubmittedAmount = (clone $poQuery)->where('status', 'Submitted')->sum('total_po_amount_with_tax');

        // 2. Finance & Payment Metrics
        $paQuery = ErpPaymentAdvice::query();
        $paQuery = $applyDateFilter($paQuery, 'created_at');
        if ($workItemIds !== null) {
            $paQuery->whereHas('purchaseOrder.requestForm.items', function($q) use ($workItemIds) {
                $q->whereIn('work_item_id', $workItemIds);
            });
        }

        $totalPaAmount = (clone $paQuery)->sum('sum_payment_amount_with_tax');
        $totalOutstanding = (clone $paQuery)->sum('outstanding');
        
        // Paid amount in period
        $paidDetailsQuery = ErpPaymentAdviceDetail::whereNotNull('date_paid');
        if ($selectedMonth !== 'all' && is_numeric($selectedMonth)) {
            $paidDetailsQuery->whereYear('date_paid', $selectedYear)->whereMonth('date_paid', (int) $selectedMonth);
        } else {
            $paidDetailsQuery->whereYear('date_paid', $selectedYear);
        }
        if ($workItemIds !== null) {
            $paidDetailsQuery->whereHas('paymentAdvice.purchaseOrder.requestForm.items', function($q) use ($workItemIds) {
                $q->whereIn('work_item_id', $workItemIds);
            });
        }
        $totalPaidAmount = (float) $paidDetailsQuery->sum('payment_amount_with_tax');
        if ($totalPaidAmount <= 0) {
            $totalPaidAmount = max(0, $totalSpendAmount - $totalOutstanding);
        }

        // 3. Goods Receipt (Logistik / GA) Metrics
        $grQuery = ErpGoodsReceipt::query();
        $grQuery = $applyDateFilter($grQuery, 'date');
        if ($workItemIds !== null) {
            $grQuery->whereHas('purchaseOrder.requestForm.items', function($q) use ($workItemIds) {
                $q->whereIn('work_item_id', $workItemIds);
            });
        }

        $totalGrCount = (clone $grQuery)->count();
        $receivedGrCount = (clone $grQuery)->where('status', 'Received')->count();
        $draftGrCount = (clone $grQuery)->where('status', 'Draft')->count();
        $totalDeliveredQty = (clone $grQuery)->sum('total_delivered_qty');
        $totalReceivedQty = (clone $grQuery)->sum('total_received_qty');

        // 4. Request Form (RF) Metrics
        $rfQuery = RequestForm::query();
        $rfQuery = $applyDateFilter($rfQuery, 'rf_date');
        if ($workItemIds !== null) {
            $rfQuery->whereHas('items', function($q) use ($workItemIds) {
                $q->whereIn('work_item_id', $workItemIds);
            });
        }

        $totalRfCount = (clone $rfQuery)->count();
        $approvedRfCount = (clone $rfQuery)->where('status', 'Approved')->count();
        $submittedRfCount = (clone $rfQuery)->where('status', 'Submitted')->count();
        $draftRfCount = (clone $rfQuery)->where('status', 'Draft')->count();

        // 5. Budgeting & Work Items (WID)
        $widQuery = ErpWorkItem::query();
        if ($workItemIds !== null) {
            $widQuery->whereIn('id', $workItemIds);
        }
        $totalAllocatedBudget = (float) (clone $widQuery)->sum('allocated_budget');
        $totalRemainingBudget = (float) (clone $widQuery)->sum('remaining_budget');
        $totalUsedBudget = max(0, $totalAllocatedBudget - $totalRemainingBudget);
        $budgetUtilizationRate = $totalAllocatedBudget > 0 ? round(($totalUsedBudget / $totalAllocatedBudget) * 100, 1) : 0;

        // 6. Action Center: Pending Approvals for Current User
        $pendingPoApprovals = collect();
        $pendingPaApprovals = collect();

        // Check user PO approvals
        $pendingPoQuery = ErpPurchaseOrder::where('status', 'Submitted');
        if ($workItemIds !== null) {
            $pendingPoQuery->whereHas('requestForm.items', function($q) use ($workItemIds) {
                $q->whereIn('work_item_id', $workItemIds);
            });
        }

        if ($user->hasRole('superadmin')) {
            $pendingPoApprovals = (clone $pendingPoQuery)
                ->with(['supplier', 'requestForm'])
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();
        } else {
            $pendingPoApprovals = (clone $pendingPoQuery)
                ->whereHas('approvals', function($q) use ($user) {
                    $q->where('status', 'Pending')
                      ->where(function($sq) use ($user) {
                          $sq->where('assigned_to_user_id', $user->id)
                             ->orWhereIn('assigned_to_role_id', $user->roles->pluck('id'));
                      });
                })
                ->with(['supplier', 'requestForm'])
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();
        }

        // Check user PA approvals
        if ($user->hasRole('superadmin') || $user->hasRole('ceo') || $user->hasRole('finance')) {
            $pendingPaQuery = ErpPaymentAdviceDetail::whereIn('approval_status', ['Submitted', 'Pending', 'Waiting']);
            if ($workItemIds !== null) {
                $pendingPaQuery->whereHas('paymentAdvice.purchaseOrder.requestForm.items', function($q) use ($workItemIds) {
                    $q->whereIn('work_item_id', $workItemIds);
                });
            }
            $pendingPaApprovals = $pendingPaQuery
                ->with(['paymentAdvice.purchaseOrder', 'paymentAdvice.supplier'])
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();
        }

        // 7. Monthly Trend Chart Data for Selected Year (Jan - Dec)
        $monthlySpend = [];
        $monthlyPaid = [];
        $monthlyPoCount = [];

        for ($m = 1; $m <= 12; $m++) {
            $mSpendQuery = ErpPurchaseOrder::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $m)
                ->whereIn('status', ['Approved', 'Completed']);
            if ($workItemIds !== null) {
                $mSpendQuery->whereHas('requestForm.items', function($q) use ($workItemIds) {
                    $q->whereIn('work_item_id', $workItemIds);
                });
            }
            $mSpend = $mSpendQuery->sum('total_po_amount_with_tax');

            $mPaidQuery = ErpPaymentAdviceDetail::whereYear('date_paid', $selectedYear)
                ->whereMonth('date_paid', $m);
            if ($workItemIds !== null) {
                $mPaidQuery->whereHas('paymentAdvice.purchaseOrder.requestForm.items', function($q) use ($workItemIds) {
                    $q->whereIn('work_item_id', $workItemIds);
                });
            }
            $mPaid = $mPaidQuery->sum('payment_amount_with_tax');

            $mPoCountQuery = ErpPurchaseOrder::whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $m);
            if ($workItemIds !== null) {
                $mPoCountQuery->whereHas('requestForm.items', function($q) use ($workItemIds) {
                    $q->whereIn('work_item_id', $workItemIds);
                });
            }
            $mPoCount = $mPoCountQuery->count();

            $monthlySpend[] = round((float) $mSpend, 2);
            $monthlyPaid[] = round((float) $mPaid, 2);
            $monthlyPoCount[] = $mPoCount;
        }

        // 8. Top 5 Suppliers by PO Volume in Period
        $topSuppliersQuery = ErpSupplier::query();
        if ($workItemIds !== null) {
            $topSuppliersQuery->whereHas('purchaseOrders.requestForm.items', function($q) use ($workItemIds) {
                $q->whereIn('work_item_id', $workItemIds);
            });
        }
        $topSuppliers = $topSuppliersQuery
            ->withCount(['purchaseOrders' => function($q) use ($applyDateFilter) {
                $applyDateFilter($q, 'created_at');
            }])
            ->withSum(['purchaseOrders' => function($q) use ($applyDateFilter) {
                $applyDateFilter($q, 'created_at');
            }], 'total_po_amount_with_tax')
            ->orderByDesc('purchase_orders_sum_total_po_amount_with_tax')
            ->limit(5)
            ->get();

        // 9. Recent Activity Feeds (5 POs, 5 GRs, 5 PAs)
        $recentPos = ErpPurchaseOrder::with(['supplier', 'requestForm'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $recentGrs = ErpGoodsReceipt::with(['purchaseOrder', 'supplier', 'warehouse'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $recentPas = ErpPaymentAdvice::with(['purchaseOrder', 'supplier', 'details'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        // 10. Inventory Alert (Low stock / Total SKUs)
        $totalProductsCount = ErpProduct::count();
        $totalPhysicalStockQty = (float) ErpStock::sum('qty_on_hand');

        return view('erp.dashboard.index', compact(
            'selectedYear',
            'selectedMonth',
            'selectedBudgetParentId',
            'budgetParents',
            'years',
            'totalSpendAmount',
            'totalSubmittedAmount',
            'totalPaAmount',
            'totalPaidAmount',
            'totalOutstanding',
            'totalPoCount',
            'approvedPoCount',
            'draftPoCount',
            'submittedPoCount',
            'rejectedPoCount',
            'totalGrCount',
            'receivedGrCount',
            'draftGrCount',
            'totalDeliveredQty',
            'totalReceivedQty',
            'totalRfCount',
            'approvedRfCount',
            'submittedRfCount',
            'draftRfCount',
            'totalAllocatedBudget',
            'totalRemainingBudget',
            'totalUsedBudget',
            'budgetUtilizationRate',
            'pendingPoApprovals',
            'pendingPaApprovals',
            'monthlySpend',
            'monthlyPaid',
            'monthlyPoCount',
            'topSuppliers',
            'recentPos',
            'recentGrs',
            'recentPas',
            'totalProductsCount',
            'totalPhysicalStockQty'
        ));
    }
}
