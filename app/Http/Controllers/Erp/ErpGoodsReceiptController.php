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
        if ($purchaseOrder->gr || $purchaseOrder->status === 'Completed' || $purchaseOrder->goodsReceipts()->where('status', 'Received')->exists()) {
            return redirect()->back()->with('error', 'PO ini sudah diterima / di-GR secara lengkap dan tidak dapat dibuatkan GR lagi.');
        }

        if ($purchaseOrder->status !== 'Approved') {
            return redirect()->back()->with('error', 'Hanya PO dengan status Approved yang dapat dibuatkan Goods Receipt.');
        }

        $purchaseOrder->load('items.requestFormItem.erpProduct.uom', 'supplier', 'warehouse');

        return view('erp.goods_receipts.create', compact('purchaseOrder'));
    }

    public function store(Request $request, ErpPurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->gr || $purchaseOrder->status === 'Completed' || $purchaseOrder->goodsReceipts()->where('status', 'Received')->exists()) {
            return redirect()->back()->with('error', 'PO ini sudah diterima / di-GR secara lengkap dan tidak dapat dibuatkan GR lagi.');
        }

        if ($purchaseOrder->status !== 'Approved') {
            return redirect()->back()->with('error', 'Hanya PO dengan status Approved yang dapat dibuatkan Goods Receipt.');
        }

        $data = $request->validate([
            'date' => 'required|date',
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
            $gr->remarks = $data['remarks'];
            $gr->owner_id = auth()->id();
            $gr->status = 'Draft';
            $gr->save();

            $totalDelivered = 0;
            $totalReceived = 0;

            foreach ($data['items'] as $item) {
                if ($item['delivered_qty'] > 0 || $item['received_qty'] > 0) {
                    $poItem = \App\Models\Erp\ErpPurchaseOrderItem::find($item['po_item_id']);
                    
                    $gr->items()->create([
                        'do_detail_no' => $this->generateDoDetailNo(),
                        'erp_purchase_order_item_id' => $poItem->id,
                        'request_form_item_id' => $poItem->request_form_item_id,
                        'delivered_qty' => $item['delivered_qty'],
                        'received_qty' => $item['received_qty'],
                        'remark' => $item['remark'],
                    ]);

                    $totalDelivered += $item['delivered_qty'];
                    $totalReceived += $item['received_qty'];
                }
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
        if ($goodsReceipt->status === 'Received') {
            return redirect()->back()->with('error', 'Goods Receipt is already received.');
        }

        $request->validate([
            'verified_by_id' => 'required|exists:master.users,id'
        ]);

        $goodsReceipt->update([
            'status' => 'Received',
            'status_receive_date' => now(),
            'document_complete_date' => now(),
            'verified_by_id' => $request->verified_by_id,
            'verification_timestamp' => now(),
        ]);

        // Option to mark PO as GR if all items are fully received could be implemented here
        $po = $goodsReceipt->purchaseOrder;
        if ($po && !$po->gr) {
            $po->update(['gr' => true]);
            $po->update(['status' => 'Completed']); // Optionally mark PO as Completed too
        }

        // Update associated RF items, PR items to Completed & Update Physical Inventory Stocks
        $warehouseId = $goodsReceipt->warehouse_id 
            ?: ($goodsReceipt->purchaseOrder?->erp_warehouse_id 
                ?: \Illuminate\Support\Facades\DB::table('erp_warehouses')->value('id'));
        foreach ($goodsReceipt->items as $grItem) {
            $rfItem = $grItem->requestFormItem;
            if ($rfItem) {
                $rfItem->update(['status' => 'Completed']);
                foreach ($rfItem->purchaseRequestItems as $prItem) {
                    $prItem->update(['status' => 'Completed']);
                }

                // Inventory Stock Update (Only for Physical products)
                $product = $rfItem->erpProduct;
                if ($product && $product->is_physical && $warehouseId && $grItem->received_qty > 0) {
                    $stock = \App\Models\Erp\ErpStock::firstOrCreate(
                        [
                            'erp_product_id' => $product->id,
                            'erp_warehouse_id' => $warehouseId,
                        ],
                        ['qty_on_hand' => 0]
                    );
                    $stock->increment('qty_on_hand', $grItem->received_qty);
                }
            }
        }

        return redirect()->back()->with('success', 'Goods Receipt marked as Received and physical inventory stock updated.');
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

            // Rollback stocks if GR was Received
            if ($goodsReceipt->status === 'Received') {
                foreach ($goodsReceipt->items as $grItem) {
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

                    // Reset RF Item status back to Approved
                    if ($grItem->requestFormItem) {
                        $grItem->requestFormItem->update(['status' => 'Approved']);
                    }
                }
            }

            // Reset PO status back to Approved
            if ($po) {
                $po->update([
                    'gr' => false,
                    'status' => 'Approved'
                ]);
            }

            $goodsReceipt->items()->delete();
            $goodsReceipt->delete();

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
