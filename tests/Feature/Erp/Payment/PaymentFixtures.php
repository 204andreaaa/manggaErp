<?php

namespace Tests\Feature\Erp\Payment;

use App\Models\Erp\ErpApprovalConfig;
use App\Models\Erp\ErpPaymentAdvice;
use App\Models\Erp\ErpPaymentAdviceDetail;
use App\Models\Erp\ErpPurchaseOrder;
use App\Models\Erp\ErpSupplier;
use App\Models\Erp\RequestForm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Prerequisite data for the Payment Advice tests, plus cleanup of the files the
 * controller moves into public/uploads/{invoices,receipts}.
 */
trait PaymentFixtures
{
    /** Relative public paths (e.g. uploads/invoices/inv_x.pdf) to delete after the test. */
    private array $uploadedPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->uploadedPaths as $path) {
            $full = public_path($path);
            if ($path && is_file($full)) {
                @unlink($full);
            }
        }
        $this->uploadedPaths = [];

        parent::tearDown();
    }

    /** Remember a stored upload path so tearDown removes the file. */
    protected function trackUpload(?string $path): void
    {
        if ($path) {
            $this->uploadedPaths[] = $path;
        }
    }

    protected function makeApprovedPo(float $total = 10_000_000, array $attributes = []): ErpPurchaseOrder
    {
        $supplier = ErpSupplier::create([
            'supplier_code' => 'SUP-'.Str::upper(Str::random(6)),
            'name' => 'PT Supplier '.Str::random(4),
        ]);

        $rf = RequestForm::create([
            'rf_no' => 'RF-'.Str::upper(Str::random(8)),
            'record_type' => 'non_project',
            'status' => 'Approved',
            'rf_date' => now()->toDateString(),
            'total_amount' => (int) $total,
        ]);

        return ErpPurchaseOrder::create(array_merge([
            'po_no' => 'PO-'.Str::upper(Str::random(8)),
            'request_form_id' => $rf->id,
            'supplier_id' => $supplier->id,
            'contact_person' => 'Budi Supplier',
            'date' => now()->toDateString(),
            'total_po_amount' => $total,
            'total_po_amount_with_tax' => $total,
            'balance_amount' => $total,
            'status' => 'Approved',
            'approved_date' => now(),
        ], $attributes));
    }

    /** Payload for POST erp.payment-advices.store. */
    protected function paPayload(ErpPurchaseOrder $po, array $overrides = []): array
    {
        return array_merge([
            'erp_purchase_order_id' => $po->id,
            'invoice_no' => 'INV-VENDOR-001',
            'contact_person' => 'Siti Finance',
            'due_date' => now()->addDays(14)->toDateString(),
            'total_invoice_amount' => 10_000_000,
            'initial_payment_amount' => 3_000_000,
            'payment_method' => 'Bank Transfer',
            'payment_type' => 'DP 1',
            'remark' => 'Termin pertama',
        ], $overrides);
    }

    /** A PA header + one Draft detail (termin), created directly with models. */
    protected function makePaymentAdvice(ErpPurchaseOrder $po, float $total, array $detailAmounts = [], ?User $owner = null): ErpPaymentAdvice
    {
        $pa = ErpPaymentAdvice::create([
            'supplier_invoice_no' => 'SI-TEST-'.Str::upper(Str::random(6)),
            'erp_purchase_order_id' => $po->id,
            'supplier_id' => $po->supplier_id,
            'invoice_no' => 'INV-X',
            'due_date' => now()->addDays(30),
            'total_invoice_amount' => $total,
            'total_invoice_amount_with_tax' => $total,
            'outstanding' => $total,
            'status' => 'Draft',
            'approval_status' => 'Draft',
            'payment_closed' => false,
            'owner_id' => $owner?->id,
        ]);

        foreach ($detailAmounts as $amount) {
            $this->makeDetail($pa, $amount);
        }

        return $pa->fresh();
    }

    protected function makeDetail(ErpPaymentAdvice $pa, float $amount, array $attributes = []): ErpPaymentAdviceDetail
    {
        return ErpPaymentAdviceDetail::create(array_merge([
            'supplier_detail_no' => 'SID-TEST-'.Str::upper(Str::random(8)),
            'erp_payment_advice_id' => $pa->id,
            'erp_purchase_order_id' => $pa->erp_purchase_order_id,
            'created_date_sid' => now(),
            'payment_amount' => $amount,
            'payment_amount_with_tax' => $amount,
            'payment_method' => 'Bank Transfer',
            'payment_type' => 'Termin',
            'approval_status' => 'Draft',
        ], $attributes));
    }

    /** One approval level per entry: ['role' => slug] or ['user' => User]. */
    protected function configureApprovalLevels(array $levels): void
    {
        foreach (array_values($levels) as $i => $level) {
            ErpApprovalConfig::create([
                'record_type' => 'payment_advice',
                'level' => $i + 1,
                'name' => 'Step '.($i + 1),
                'role_id' => isset($level['role']) ? Role::where('slug', $level['role'])->value('id') : null,
                'user_id' => isset($level['user']) ? $level['user']->id : null,
                'min_amount' => $level['min'] ?? null,
                'max_amount' => $level['max'] ?? null,
            ]);
        }
    }

    protected function finance(): User
    {
        return $this->makeUser('finance');
    }
}
