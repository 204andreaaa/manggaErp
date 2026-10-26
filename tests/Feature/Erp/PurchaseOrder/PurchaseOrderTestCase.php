<?php

namespace Tests\Feature\Erp\PurchaseOrder;

use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\ErpBudgetParent;
use App\Models\Erp\ErpProduct;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\ErpSubProject;
use App\Models\Erp\ErpSupplier;
use App\Models\Erp\ErpWarehouse;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\RequestForm;
use App\Models\Erp\RequestFormItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\ErpTestCase;

/**
 * Fixtures for the Purchase Order / Goods Receipt tests. Prerequisite data
 * (RF, WID, supplier, configs) is built with models; the flow under test is
 * driven through the HTTP routes.
 */
abstract class PurchaseOrderTestCase extends ErpTestCase
{
    protected ErpSupplier $supplier;

    protected ErpWarehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = ErpSupplier::create(['supplier_code' => 'SUP-'.Str::random(6), 'name' => 'PT Supplier Test']);
        $this->warehouse = ErpWarehouse::create(['warehouse_code' => 'WH-'.Str::random(6), 'name' => 'Gudang Utama', 'is_active' => true]);
    }

    protected function makeWorkItem(float $allocated = 10_000_000, ?float $remaining = null, string $code = 'WID-001'): ErpWorkItem
    {
        $parent = ErpBudgetParent::create(['budget_code' => 'BP-'.Str::random(6), 'name' => 'Budget Parent', 'total_budget' => $allocated, 'remaining_budget' => $allocated]);
        $sub = ErpSubProject::create(['budget_parent_id' => $parent->id, 'sub_project_code' => 'SP-'.Str::random(6), 'name' => 'Sub Project']);

        return ErpWorkItem::create([
            'sub_project_id' => $sub->id,
            'wid_code' => $code,
            'name' => 'Work Item '.$code,
            'allocated_budget' => $allocated,
            'remaining_budget' => $remaining ?? $allocated,
        ]);
    }

    /**
     * An approved RF. Each item: ['name' => .., 'qty' => .., 'unit_cost' => .., 'wid' => .., 'code' => ..].
     */
    protected function makeApprovedRf(array $items, array $attributes = []): RequestForm
    {
        $rf = RequestForm::create(array_merge([
            'rf_no' => 'RF-'.Str::random(8),
            'record_type' => 'project',
            'is_project' => true,
            'project_code' => 'PRJ-TEST',
            'requestor' => 'Requestor',
            'rf_date' => now()->toDateString(),
            'status' => 'Approved',
            'expense_material_equipment' => true,
            'expense_office' => false,
        ], $attributes));

        foreach ($items as $i => $item) {
            RequestFormItem::create([
                'request_form_id' => $rf->id,
                'rf_detail_no' => 'RFD-'.Str::random(6),
                'product_name' => $item['name'] ?? 'Item '.($i + 1),
                'product_id_text' => $item['code'] ?? null,
                'wid' => $item['wid'] ?? null,
                'qty' => $item['qty'] ?? 1,
                'unit_cost' => $item['unit_cost'] ?? 100_000,
                'status' => 'Requested',
            ]);
        }

        return $rf->fresh('items');
    }

    protected function makeProduct(string $code, string $name = 'Produk Fisik', bool $physical = true): ErpProduct
    {
        return ErpProduct::create(['product_code' => $code, 'name' => $name, 'is_physical' => $physical, 'is_active' => true]);
    }

    /** A purchase_order approval level assigned to a user or a role. */
    protected function poLevel(int $level, ?User $user = null, ?Role $role = null, array $extra = []): ErpApprovalConfig
    {
        return ErpApprovalConfig::create(array_merge([
            'record_type' => 'purchase_order',
            'level' => $level,
            'name' => 'Level '.$level,
            'user_id' => $user?->id,
            'role_id' => $role?->id,
        ], $extra));
    }

    protected function role(string $slug): Role
    {
        return Role::where('slug', $slug)->firstOrFail();
    }

    /** Store payload ordering every item of the RF at its RF qty / unit cost. */
    protected function storePayload(RequestForm $rf, array $overrides = [], ?array $items = null): array
    {
        return array_merge([
            'request_form_id' => $rf->id,
            'erp_warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'contact_person' => 'Budi',
            'address' => 'Jl. Test 1',
            'bank_account' => 'BCA 123',
            'date' => now()->toDateString(),
            'eta' => now()->addWeek()->toDateString(),
            'payment_method' => 'Transfer',
            'description' => 'PO test',
            'invoice_to' => 'PT Mandiri Daya Utama Nusantara',
            'attention_to' => 'Finance',
            'transfer_to' => 'Supplier',
            'other_instructions' => '-',
            'payment_terms' => null,
            'signature' => null,
            'items' => $items ?? $rf->items->map(fn ($item) => [
                'request_form_item_id' => $item->id,
                'qty' => (float) $item->qty,
                'unit_cost' => (float) $item->unit_cost,
                'tax' => 0,
                'remarks' => null,
            ])->all(),
        ], $overrides);
    }

    /** Creates a Draft PO through the store route as `$creator` and returns it. */
    protected function createPoViaHttp(User $creator, RequestForm $rf, array $overrides = [], ?array $items = null): ErpPurchaseOrder
    {
        $this->actingInProject($creator)
            ->post(route('erp.purchase-orders.store'), $this->storePayload($rf, $overrides, $items))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        return ErpPurchaseOrder::latest('id')->firstOrFail();
    }

    /**
     * An Approved PO built directly with models (for Goods Receipt tests):
     * one PO item per RF item at the RF qty / unit cost.
     */
    protected function makeApprovedPo(RequestForm $rf, array $attributes = []): ErpPurchaseOrder
    {
        $po = ErpPurchaseOrder::create(array_merge([
            'po_no' => 'PO-'.now()->format('Y').'-'.Str::random(5),
            'request_form_id' => $rf->id,
            'erp_warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'date' => now()->toDateString(),
            'status' => 'Approved',
            'approved_date' => now(),
            'payment_closed' => false,
            'gr' => false,
        ], $attributes));

        $total = 0;
        foreach ($rf->items as $rfItem) {
            $line = (float) $rfItem->qty * (float) $rfItem->unit_cost;
            $po->items()->create([
                'request_form_item_id' => $rfItem->id,
                'qty' => $rfItem->qty,
                'unit_cost' => $rfItem->unit_cost,
                'tax' => 0,
                'total_cost' => $line,
            ]);
            $rfItem->update(['status' => 'Ordered']);
            $total += $line;
        }
        $po->update(['total_po_amount' => $total, 'tax' => 0, 'total_po_amount_with_tax' => $total, 'balance_amount' => $total]);

        return $po->fresh('items');
    }
}
