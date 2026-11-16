<?php

namespace App\Http\Controllers\Erp;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Erp\ErpWorkItem;
use App\Models\Erp\Invoice;
use App\Models\Erp\InvoiceDetail;
use App\Models\Erp\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Customer invoices (money coming in): the invoice, its lines per WID and the
 * payments received. Amount and status are always recalculated, never typed in.
 */
class InvoiceController extends Controller
{
    private function authorizeTo(string $action): void
    {
        abort_unless(auth()->user()->hasPermission("invoices.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeTo('view');

        $invoices = Invoice::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when(in_array($request->query('status'), Invoice::STATUSES, true), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->where('code', 'like', $term)->orWhere('invoice_number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)->orWhere('top', 'like', $term));
            })
            ->latest('invoice_date')->latest('id')
            ->paginate(50)->withQueryString();

        $totals = Invoice::selectRaw('SUM(invoice_amount) amount, SUM(amount_paid) paid, SUM(outstanding) outstanding')->first();

        return view('erp.invoices.index', [
            'invoices' => $invoices,
            'companies' => $this->companies(),
            'totals' => $totals,
        ]);
    }

    public function create()
    {
        $this->authorizeTo('create');

        return view('erp.invoices.form', ['invoice' => new Invoice(['invoice_date' => now()]), 'companies' => $this->companies()]);
    }

    public function store(Request $request)
    {
        $this->authorizeTo('create');
        $data = $this->validateHeader($request);

        $invoice = DB::connection('tenant')->transaction(function () use ($data) {
            return Invoice::create($data + [
                'code' => $this->nextCode(Invoice::withTrashed(), 'INV-'),
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
            ]);
        });

        NotificationHelper::pushLive('invoice', $invoice->id, 'created');

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->code} dibuat. Tambahkan rincian per WID di bawah.");
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeTo('view');
        $invoice->load(['details.workItem', 'details.createdBy', 'payments.createdBy', 'createdBy']);

        return view('erp.invoices.show', [
            'invoice' => $invoice,
            'workItems' => ErpWorkItem::orderBy('wid_code')->get(['id', 'wid_code', 'name']),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $this->authorizeTo('update');

        return view('erp.invoices.form', ['invoice' => $invoice, 'companies' => $this->companies()]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizeTo('update');
        $invoice->update($this->validateHeader($request, $invoice) + ['updated_by_id' => auth()->id()]);

        NotificationHelper::pushLive('invoice', $invoice->id, 'updated');

        return redirect()->route('erp.invoices.show', $invoice)->with('success', 'Invoice diperbarui.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorizeTo('delete');

        // once money came in the invoice is part of the books; remove the payments first
        if ($invoice->payments()->exists()) {
            return redirect()->back()->with('error', 'Invoice yang sudah ada pembayarannya tidak bisa dihapus. Hapus dulu pembayarannya.');
        }

        $invoice->delete();
        NotificationHelper::pushLive('invoice', $invoice->id, 'deleted');

        return redirect()->route('erp.invoices.index')->with('success', "Invoice {$invoice->code} dihapus.");
    }

    public function storeDetail(Request $request, Invoice $invoice)
    {
        $this->authorizeTo('update');

        $data = $request->validate([
            'work_item_id' => ['nullable', Rule::exists('erp_work_items', 'id')],
            'project_code' => ['nullable', 'string', 'max:80'],
            'terms' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'po_date' => ['nullable', 'date'],
            'received_doc_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        DB::connection('tenant')->transaction(function () use ($invoice, $data) {
            $invoice->details()->create($data + [
                'code' => $this->nextCode(InvoiceDetail::query(), 'INVDET-'),
                'created_by_id' => auth()->id(),
            ]);
            $invoice->recalculate();
        });

        NotificationHelper::pushLive('invoice', $invoice->id, 'updated');

        return redirect()->back()->with('success', 'Rincian invoice ditambahkan.');
    }

    public function destroyDetail(InvoiceDetail $detail)
    {
        $this->authorizeTo('update');
        $invoice = $detail->invoice;

        // the invoice may not end up worth less than what the customer already paid
        $remaining = (float) $invoice->details()->where('id', '!=', $detail->id)->sum('amount');
        if ($remaining < (float) $invoice->amount_paid - 0.005) {
            return redirect()->back()->with('error', 'Rincian tidak bisa dihapus: nilai invoice akan lebih kecil dari yang sudah dibayar customer.');
        }

        DB::connection('tenant')->transaction(function () use ($detail, $invoice) {
            $detail->delete();
            $invoice->recalculate();
        });

        return redirect()->back()->with('success', "Rincian {$detail->code} dihapus.");
    }

    public function storePayment(Request $request, Invoice $invoice)
    {
        $this->authorizeTo('update');

        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['nullable', Rule::in(Invoice::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::connection('tenant')->transaction(function () use ($invoice, $data) {
            $invoice = Invoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail();
            if ((float) $invoice->invoice_amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Tambahkan rincian invoice dulu sebelum mencatat pembayaran.']);
            }
            if ((float) $data['amount'] > (float) $invoice->outstanding + 0.005) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran melebihi sisa tagihan (Rp '.number_format((float) $invoice->outstanding, 0, ',', '.').').',
                ]);
            }

            $invoice->payments()->create($data + ['created_by_id' => auth()->id()]);
            $invoice->recalculate();
        });

        NotificationHelper::pushLive('invoice', $invoice->id, 'updated');

        return redirect()->back()->with('success', 'Pembayaran dicatat.');
    }

    public function destroyPayment(InvoicePayment $payment)
    {
        $this->authorizeTo('update');
        $invoice = $payment->invoice;

        DB::connection('tenant')->transaction(function () use ($payment, $invoice) {
            $payment->delete();
            $invoice->recalculate();
        });

        return redirect()->back()->with('success', 'Pembayaran dihapus.');
    }

    private function validateHeader(Request $request, ?Invoice $invoice = null): array
    {
        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:120',
                Rule::unique('erp_invoices', 'invoice_number')->whereNull('deleted_at')->ignore($invoice?->id)],
            'invoice_date' => ['required', 'date'],
            'company_id' => ['nullable', Rule::exists('master.companies', 'id')],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'top' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
        ]);

        $data['company_name'] = !empty($data['company_id']) ? Company::find($data['company_id'])?->short_name : null;

        return $data;
    }

    private function companies()
    {
        return Company::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'short_name']);
    }

    /** Next "PREFIX-00001" code; numbers of deleted rows are not reused. */
    private function nextCode($query, string $prefix): string
    {
        $last = (clone $query)->where('code', 'like', $prefix.'%')->lockForUpdate()
            ->orderByRaw('CAST(SUBSTRING(code, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])->value('code');

        return $prefix.str_pad((string) ((int) substr((string) $last, strlen($prefix)) + 1), 5, '0', STR_PAD_LEFT);
    }
}
