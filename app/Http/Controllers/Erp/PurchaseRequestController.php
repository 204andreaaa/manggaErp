<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Erp\RequestForm;
use App\Models\Erp\PurchaseRequest;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    public function store(Request $request, RequestForm $requestForm)
    {
        abort_unless(auth()->user()->hasRole(['logistik', 'superadmin']), 403, 'Hanya Logistik atau Super Admin yang dapat membuat PR.');

        $data = $request->validate([
            'expense_material_equipment' => 'nullable|boolean',
            'expense_material_subcon' => 'nullable|boolean',
            'expense_transportation' => 'nullable|boolean',
            'expense_personnel' => 'nullable|boolean',
            'expense_office' => 'nullable|boolean',
            'expense_other' => 'nullable|boolean',
            'expense_utilities' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.request_form_item_id' => 'required|exists:request_form_items,id',
            'items.*.pr_requested_qty' => 'required|numeric|min:0.01',
            'items.*.required_qty' => 'required|numeric',
        ]);

        DB::transaction(function () use ($data, $requestForm, $request) {
            $pr = PurchaseRequest::create([
                'pr_no' => $this->generatePrNo(),
                'request_form_id' => $requestForm->id,
                'requestor' => auth()->user()->name ?? 'System',
                'pr_date' => now(),
                'status' => 'Submitted',
                'expense_material_equipment' => $request->boolean('expense_material_equipment'),
                'expense_material_subcon' => $request->boolean('expense_material_subcon'),
                'expense_transportation' => $request->boolean('expense_transportation'),
                'expense_personnel' => $request->boolean('expense_personnel'),
                'expense_office' => $request->boolean('expense_office'),
                'expense_other' => $request->boolean('expense_other'),
                'expense_utilities' => $request->boolean('expense_utilities'),
            ]);

            foreach ($data['items'] as $item) {
                if ($item['pr_requested_qty'] > 0) {
                    $pr->items()->create([
                        'request_form_item_id' => $item['request_form_item_id'],
                        'required_qty' => $item['required_qty'],
                        'pr_requested_qty' => $item['pr_requested_qty'],
                    ]);

                    // Update qty_fulfilled and status on RF item
                    $rfItem = \App\Models\Erp\RequestFormItem::find($item['request_form_item_id']);
                    if ($rfItem) {
                        $newFulfilled = (float) $rfItem->qty_fulfilled + (float) $item['pr_requested_qty'];
                        $rfItem->update([
                            'qty_fulfilled' => $newFulfilled,
                            'status' => $newFulfilled >= (float) $rfItem->qty ? 'Completed' : 'Ordered',
                        ]);
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Purchase Request created successfully.');
    }
    
    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load([
            'requestForm',
            'items.requestFormItem.erpProduct.brand',
            'items.requestFormItem.erpProduct.productModel'
        ]);
        return view('erp.purchase_requests.show', compact('purchaseRequest'));
    }
    
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        DB::transaction(function() use ($purchaseRequest) {
            foreach ($purchaseRequest->items as $prItem) {
                $rfItem = $prItem->requestFormItem;
                if ($rfItem) {
                    $newFulfilled = max(0, (float) $rfItem->qty_fulfilled - (float) $prItem->pr_requested_qty);
                    $rfItem->update([
                        'qty_fulfilled' => $newFulfilled,
                        'status' => $newFulfilled > 0 ? 'Ordered' : 'Requested',
                    ]);
                }
            }
            $purchaseRequest->delete();
        });

        return redirect()->back()->with('success', 'Purchase Request deleted successfully.');
    }

    private function generatePrNo()
    {
        $prefix = 'PR-'.now()->format('Y-m').'-';
        $latest = PurchaseRequest::withTrashed()
            ->where('pr_no', 'like', $prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(pr_no, '.(strlen($prefix) + 1).') AS UNSIGNED) DESC')
            ->value('pr_no');

        $num = 0;
        if ($latest && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $latest, $match)) {
            $num = (int) $match[1];
        }

        return $prefix.str_pad($num + 1, 5, '0', STR_PAD_LEFT);
    }
}
