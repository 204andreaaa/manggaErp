<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpGoodsReceipt;
use App\Models\Erp\ErpGoodsReceiptItem;
use App\Models\Erp\ErpPurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ErpGoodsReceiptController extends Controller
{
    public function create(ErpPurchaseOrder $purchaseOrder)
    {
        abort_unless(auth()->user()->hasRole(['logistik', 'warehouse', 'ga', 'admin_project', 'superadmin']), 403);

        $purchaseOrder->load(['items.requestFormItem.erpProduct.uom', 'supplier', 'warehouse', 'goodsReceipts.items']);

        if ($purchaseOrder->is_gr_completed) {
            return redirect()->back()->with('error', 'Semua barang dalam PO ini sudah diterima lengkap (100% GR Completed).');
        }

        if ($purchaseOrder->status !== 'Approved' && $purchaseOrder->status !== 'Completed') {
            return redirect()->back()->with('error', 'Hanya PO dengan status Approved yang dapat dibuatkan Goods Receipt.');
        }

        return view('erp.goods_receipts.create', compact('purchaseOrder'));
    }

    public function store(Request $request, ErpPurchaseOrder $purchaseOrder)
    {
        abort_unless(auth()->user()->hasRole(['logistik', 'warehouse', 'ga', 'admin_project', 'superadmin']), 403);

        $purchaseOrder->load(['items.requestFormItem.erpProduct', 'goodsReceipts.items']);

        if ($purchaseOrder->is_gr_completed) {
            return redirect()->back()->with('error', 'Semua barang dalam PO ini sudah diterima lengkap (100% GR Completed).');
        }

        if ($purchaseOrder->status !== 'Approved' && $purchaseOrder->status !== 'Completed') {
            return redirect()->back()->with('error', 'Hanya PO dengan status Approved yang dapat dibuatkan Goods Receipt.');
        }

        $data = $request->validate([
            'date' => 'required|date',
            'supplier_do_no' => 'nullable|string|max:100',
            'sending_contact' => 'nullable|string|max:150',
            'receiving_contact' => 'nullable|string|max:150',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
            'items.*.po_item_id' => 'required|exists:erp_purchase_order_items,id',
            'items.*.delivered_qty' => 'required|numeric|min:0',
            'items.*.received_qty' => 'required|numeric|min:0',
            'items.*.remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $gr = new ErpGoodsReceipt();
            $gr->do_no = $this->generateGrNo();
            $gr->erp_purchase_order_id = $purchaseOrder->id;
            $gr->supplier_id = $purchaseOrder->supplier_id;
            $gr->warehouse_id = $purchaseOrder->erp_warehouse_id;
            $gr->date = $data['date'];
            $gr->sending_contact = $data['sending_contact'] ?? ($purchaseOrder->contact_person ?: ($purchaseOrder->supplier?->contact_person ?: 'Vendor Courier'));
            $gr->receiving_contact = $data['receiving_contact'] ?? (auth()->user()->name ?: ($purchaseOrder->warehouse?->pic ?: 'Staff Penerima Gudang'));
            if (\Illuminate\Support\Facades\Schema::hasColumn('erp_goods_receipts', 'supplier_do_no')) {
                $gr->supplier_do_no = $data['supplier_do_no'] ?? null;
            }
            $gr->remarks = $data['remarks'] ?? null;
            $gr->owner_id = auth()->id();
            $gr->status = 'Draft';
            $gr->save();

            $totalDelivered = 0;
            $totalReceived = 0;

            foreach ($data['items'] as $item) {
                $poItem = \App\Models\Erp\ErpPurchaseOrderItem::find($item['po_item_id']);
                if (!$poItem) continue;

                $delQty = floatval($item['delivered_qty']);
                $recQty = floatval($item['received_qty']);

                // Fallback to PO item qty if 0 was passed
                if ($delQty == 0 && $recQty == 0 && $poItem->qty > 0) {
                    $delQty = $poItem->qty;
                    $recQty = $poItem->qty;
                }

                $gr->items()->create([
                    'do_detail_no' => $this->generateDoDetailNo(),
                    'erp_purchase_order_item_id' => $poItem->id,
                    'request_form_item_id' => $poItem->request_form_item_id,
                    'delivered_qty' => $delQty,
                    'received_qty' => $recQty,
                    'remark' => $item['remark'] ?? $poItem->remarks,
                ]);

                $totalDelivered += $delQty;
                $totalReceived += $recQty;
            }

            $gr->update([
                'total_delivered_qty' => $totalDelivered,
                'total_received_qty' => $totalReceived,
            ]);

            DB::commit();
            return redirect()->route('erp.goods-receipts.show', $gr)->with('success', 'Goods Receipt / DO created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create Goods Receipt: ' . $e->getMessage());
        }
    }

    public function show(ErpGoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load([
            'purchaseOrder',
            'supplier',
            'warehouse',
            'owner',
            'verifiedBy',
            'items.purchaseOrderItem',
            'items.requestFormItem.erpProduct.brand',
            'items.requestFormItem.erpProduct.productModel',
            'items.requestFormItem.erpProduct.uom'
        ]);

        $users = \App\Models\User::all();

        return view('erp.goods_receipts.show', compact('goodsReceipt', 'users'));
    }

    public function print(ErpGoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load([
            'purchaseOrder',
            'supplier',
            'verifiedBy',
            'items.requestFormItem.erpProduct'
        ]);

        return view('erp.goods_receipts.print', compact('goodsReceipt'));
    }

    public function receive(Request $request, ErpGoodsReceipt $goodsReceipt)
    {
        $user = auth()->user();
        $grVerifConfig = \App\Models\Erp\ErpApprovalConfig::where('record_type', 'gr_verification')->first();
        
        $isAuthorized = false;
        if ($user->hasRole('superadmin')) {
            $isAuthorized = true;
        } elseif ($grVerifConfig) {
            if ($grVerifConfig->user_id && $user->id == $grVerifConfig->user_id) {
                $isAuthorized = true;
            } elseif ($grVerifConfig->role_id && $user->hasRole($grVerifConfig->role?->name)) {
                $isAuthorized = true;
            }
        } else {
            $isAuthorized = $user->hasRole(['logistik', 'warehouse', 'superadmin']) || $user->email === 'nikmal@example.com';
        }

        abort_unless($isAuthorized, 403, 'Anda tidak memiliki hak akses untuk memverifikasi penerimaan fisik barang (GR) ini.');

        if ($goodsReceipt->status === 'Received') {
            return redirect()->back()->with('error', 'Goods Receipt is already received.');
        }

        $verifiedById = $request->input('verified_by_id', auth()->id());

        $goodsReceipt->update([
            'status' => 'Received',
            'status_receive_date' => now(),
            'document_complete_date' => now(),
            'verified_by_id' => $verifiedById,
            'verification_timestamp' => now(),
            'remarks' => $request->input('remarks', $goodsReceipt->remarks),
        ]);

        $po = $goodsReceipt->purchaseOrder;
        if ($po) {
            $po->load('items');
            $isCompleted = $po->is_gr_completed;
            $po->update([
                'gr' => $isCompleted,
                'status' => ($isCompleted && $po->payment_closed) ? 'Completed' : 'Approved',
            ]);
        }

        $warehouseId = $goodsReceipt->warehouse_id 
            ?: ($po?->erp_warehouse_id 
                ?: \Illuminate\Support\Facades\DB::table('erp_warehouses')->value('id'));
        $supplierId = $po?->supplier_id;

        foreach ($goodsReceipt->items as $grItem) {
            $rfItem = $grItem->requestFormItem;
            if ($rfItem) {
                $poItem = $grItem->purchaseOrderItem;
                if ($poItem && $poItem->remaining_qty <= 0) {
                    $rfItem->update(['status' => 'Completed']);
                    foreach ($rfItem->purchaseRequestItems as $prItem) {
                        $prItem->update(['status' => 'Completed']);
                    }
                }

                $product = $rfItem->erpProduct;
                $receivedQty = $grItem->received_qty > 0 ? $grItem->received_qty : $grItem->delivered_qty;
                if ($product && ($product->is_physical ?? true) && $warehouseId && $receivedQty > 0) {
                    $stock = \App\Models\Erp\ErpStock::firstOrCreate(
                        [
                            'erp_product_id' => $product->id,
                            'erp_warehouse_id' => $warehouseId,
                            'erp_supplier_id' => $supplierId,
                        ],
                        ['qty_on_hand' => 0]
                    );
                    $stock->increment('qty_on_hand', $receivedQty);
                }
            }
        }

        return redirect()->back()->with('success', 'Goods Receipt / DO successfully verified and marked as Received. Physical inventory stock updated.');
    }

    public function destroy(ErpGoodsReceipt $goodsReceipt)
    {
        if (!auth()->user()->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Hanya Superadmin yang berhak menghapus Goods Receipt (DO).');
        }

        DB::beginTransaction();
        try {
            $po = $goodsReceipt->purchaseOrder;
            $warehouseId = $goodsReceipt->warehouse_id 
                ?: ($po?->erp_warehouse_id 
                    ?: \Illuminate\Support\Facades\DB::table('erp_warehouses')->value('id'));
            $supplierId = $po?->supplier_id;

            if ($goodsReceipt->status === 'Received') {
                foreach ($goodsReceipt->items as $grItem) {
                    $product = $grItem->requestFormItem?->erpProduct;
                    $receivedQty = $grItem->received_qty > 0 ? $grItem->received_qty : $grItem->delivered_qty;
                    if ($product && ($product->is_physical ?? true) && $warehouseId && $receivedQty > 0) {
                        $stock = \App\Models\Erp\ErpStock::where('erp_product_id', $product->id)
                            ->where('erp_warehouse_id', $warehouseId)
                            ->where('erp_supplier_id', $supplierId)
                            ->first();
                        if ($stock) {
                            $newQty = max(0, $stock->qty_on_hand - $receivedQty);
                            $stock->update(['qty_on_hand' => $newQty]);
                        }
                    }

                    if ($grItem->requestFormItem) {
                        $grItem->requestFormItem->update(['status' => 'Approved']);
                    }
                }
            }

            $goodsReceipt->items()->delete();
            $goodsReceipt->delete();

            if ($po) {
                $po->load('items');
                $isCompleted = $po->is_gr_completed;
                $po->update([
                    'gr' => $isCompleted,
                    'status' => 'Approved'
                ]);
            }

            DB::commit();

            $redirectUrl = $po ? route('erp.purchase-orders.show', $po) : route('erp.procurement.dashboard');
            return redirect($redirectUrl)->with('success', 'Goods Receipt (DO) berhasil dihapus. Stok fisik telah dikurangi kembali dan PO dikembalikan ke status Approved.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus Goods Receipt: ' . $e->getMessage());
        }
    }

    private function generateGrNo()
    {
        $prefix = 'GR-' . now()->format('Y-m') . '-';
        $latest = \App\Models\Erp\ErpGoodsReceipt::where('do_no', 'like', $prefix . '%')
            ->orderBy('do_no', 'desc')
            ->first();
        
        if ($latest) {
            $number = intval(substr($latest->do_no, -5));
            return $prefix . str_pad($number + 1, 5, '0', STR_PAD_LEFT);
        }
        
        return $prefix . '35150';
    }

    private function generateDoDetailNo()
    {
        $prefix = 'DOIN-' . now()->format('Y-m') . '-';
        $latest = \App\Models\Erp\ErpGoodsReceiptItem::where('do_detail_no', 'like', $prefix . '%')
            ->orderBy('do_detail_no', 'desc')
            ->first();
        
        if ($latest) {
            $number = intval(substr($latest->do_detail_no, -6));
            return $prefix . str_pad($number + 1, 6, '0', STR_PAD_LEFT);
        }

        return $prefix . '137041';
    }
}
