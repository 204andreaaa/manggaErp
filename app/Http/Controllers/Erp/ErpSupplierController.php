<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpSupplier;
use App\Models\Erp\ErpSupplierContact;
use App\Models\Erp\ErpSupplierAttachment;
use App\Models\Erp\ErpPaymentTerm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ErpSupplierController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole(['procurement', 'finance', 'superadmin', 'ceo', 'admin_project', 'logistik']) || auth()->user()->hasPermission('supplier.view'), 403);

        $nextSupplierCode = $this->generateNextCode();
        $paymentTerms = ErpPaymentTerm::where('is_active', true)->get();

        return view('erp.suppliers.index', compact('nextSupplierCode', 'paymentTerms'));
    }

    public function datatable(Request $request)
    {
        $orderable = [
            'id',
            'supplier_code',
            'name',
            'address',
            'phone',
            'note',
            'bank_name',
            'bank_account',
            'updated_at'
        ];

        $draw        = (int) $request->input('draw', 1);
        $start       = (int) $request->input('start', 0);
        $length      = (int) $request->input('length', 10);
        $orderColIdx = (int) $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $search      = trim((string) $request->input('search.value', ''));

        $orderCol = $orderable[$orderColIdx] ?? 'id';

        $recordsTotal = ErpSupplier::count();

        $base = ErpSupplier::query();

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('supplier_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('bank_account', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($s, $idx) use ($start) {

                $editBtn = auth()->user()->hasPermission('supplier.update')
                    ? '<button class="btn btn-sm btn-icon btn-outline-secondary js-edit"
                        data-id="'.$s->id.'"
                        data-supplier_code="'.e($s->supplier_code).'"
                        data-name="'.e($s->name).'"
                        data-parent_account="'.e($s->parent_account ?? '').'"
                        data-classification="'.e($s->classification ?? 'Unclassified').'"
                        data-industry="'.e($s->industry ?? '').'"
                        data-products_provided="'.e($s->products_provided ?? '').'"
                        data-services_provided="'.e($s->services_provided ?? '').'"
                        data-category="'.e($s->category ?? '').'"
                        data-products="'.e($s->products ?? '').'"
                        data-payment_terms_id="'.e($s->payment_terms_id ?? '').'"
                        data-address="'.e($s->address ?? '').'"
                        data-phone="'.e($s->phone ?? '').'"
                        data-fax="'.e($s->fax ?? '').'"
                        data-website="'.e($s->website ?? '').'"
                        data-note="'.e($s->note ?? '').'"
                        data-bank_name="'.e($s->bank_name ?? '').'"
                        data-bank_account="'.e($s->bank_account ?? '').'">
                        <i class="bx bx-edit-alt"></i>
                    </button>'
                    : '';

                $deleteBtn = auth()->user()->hasPermission('supplier.delete')
                    ? '<button class="btn btn-sm btn-icon btn-outline-danger js-del"
                        data-id="'.$s->id.'">
                        <i class="bx bx-trash"></i>
                    </button>'
                    : '';

                $showUrl = route('erp.suppliers.show', $s);

                return [
                    'rownum'        => $start + $idx + 1,
                    'supplier_code' => e($s->supplier_code),
                    'name'          => '<a href="'.$showUrl.'" class="fw-bold text-primary">'.e($s->name).'</a>',
                    'address'       => e($s->address ?? '-'),
                    'phone'         => e($s->phone ?? '-'),
                    'note'          => e($s->note ?? '-'),
                    'bank_name'     => e($s->bank_name ?? '-'),
                    'bank_account'  => e($s->bank_account ?? '-'),
                    'actions'       => '<div class="d-flex gap-1">'.$editBtn.$deleteBtn.'</div>',
                ];
            });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole(['procurement', 'superadmin']) || auth()->user()->hasPermission('supplier.create'), 403);

        $code = strtoupper(trim((string) $request->input('supplier_code', '')));
        if ($code === '') $code = $this->generateNextCode();

        $request->merge(['supplier_code' => $code]);

        $request->validate([
            'supplier_code' => ['required','max:50','unique:erp_suppliers,supplier_code'],
            'name'          => ['required','max:150'],
            'address'       => ['nullable'],
            'phone'         => ['nullable','max:50'],
            'note'          => ['nullable'],
            'bank_name'     => ['nullable','max:100'],
            'bank_account'  => ['nullable','max:100'],
            'parent_account'=> ['nullable','max:150'],
            'classification'=> ['nullable','max:100'],
            'industry'      => ['nullable','max:100'],
            'products_provided'=> ['nullable','max:255'],
            'services_provided'=> ['nullable','max:255'],
            'category'      => ['nullable','max:100'],
            'products'      => ['nullable','max:255'],
            'payment_terms_id'=> ['nullable','exists:erp_payment_terms,id'],
            'fax'           => ['nullable','max:50'],
            'website'       => ['nullable','max:150'],
        ]);

        ErpSupplier::create($request->all());

        return response()->json([
            'success' => 'Supplier created successfully.'
        ]);
    }

    public function show(ErpSupplier $supplier)
    {
        abort_unless(auth()->user()->hasRole(['procurement', 'finance', 'superadmin', 'ceo', 'admin_project', 'logistik']) || auth()->user()->hasPermission('supplier.view'), 403);

        $supplier->load(['contacts', 'attachments', 'paymentTerm']);
        $paymentTerms = ErpPaymentTerm::where('is_active', true)->get();
        $poCount = \App\Models\Erp\ErpPurchaseOrder::where('supplier_id', $supplier->id)->count();

        return view('erp.suppliers.show', compact('supplier', 'poCount', 'paymentTerms'));
    }

    public function update(Request $request, ErpSupplier $supplier)
    {
        abort_unless(auth()->user()->hasRole(['procurement', 'superadmin']) || auth()->user()->hasPermission('supplier.update'), 403);

        $code = strtoupper(trim((string) $request->input('supplier_code', '')));
        if ($code === '') $code = $supplier->supplier_code;

        $request->merge(['supplier_code' => $code]);

        $request->validate([
            'supplier_code' => [
                'required',
                'max:50',
                Rule::unique('erp_suppliers','supplier_code')->ignore($supplier->id)
            ],
            'name'          => ['required','max:150'],
            'address'       => ['nullable'],
            'phone'         => ['nullable','max:50'],
            'note'          => ['nullable'],
            'bank_name'     => ['nullable','max:100'],
            'bank_account'  => ['nullable','max:100'],
            'parent_account'=> ['nullable','max:150'],
            'classification'=> ['nullable','max:100'],
            'industry'      => ['nullable','max:100'],
            'products_provided'=> ['nullable','max:255'],
            'services_provided'=> ['nullable','max:255'],
            'category'      => ['nullable','max:100'],
            'products'      => ['nullable','max:255'],
            'payment_terms_id'=> ['nullable','exists:erp_payment_terms,id'],
            'fax'           => ['nullable','max:50'],
            'website'       => ['nullable','max:150'],
        ]);

        $supplier->update($request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => 'Supplier updated successfully.'
            ]);
        }

        return redirect()->route('erp.suppliers.show', $supplier)->with('success', 'Supplier updated successfully.');
    }

    public function destroy(ErpSupplier $supplier)
    {
        abort_unless(auth()->user()->hasRole(['procurement', 'superadmin']) || auth()->user()->hasPermission('supplier.delete'), 403);

        $supplier->delete();

        return response()->json([
            'success' => 'Supplier deleted successfully.'
        ]);
    }

    public function storeContact(Request $request, ErpSupplier $supplier)
    {
        $data = $request->validate([
            'contact_name' => 'required|string|max:150',
            'title' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
        ]);

        $supplier->contacts()->create($data);

        return redirect()->back()->with('success', 'Contact added successfully.');
    }

    public function destroyContact(ErpSupplierContact $contact)
    {
        $contact->delete();

        return redirect()->back()->with('success', 'Contact deleted successfully.');
    }

    public function storeAttachment(Request $request, ErpSupplier $supplier)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // Max 10MB
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('erp/attachments', 'public');

            $supplier->attachments()->create([
                'type' => 'Attachment',
                'title' => $request->input('title'),
                'file_path' => $path,
                'created_by' => auth()->user()->name,
            ]);

            return redirect()->back()->with('success', 'File attached successfully.');
        }

        return redirect()->back()->with('error', 'Failed to upload file.');
    }

    public function destroyAttachment(ErpSupplierAttachment $attachment)
    {
        // Delete physical file
        if ($attachment->file_path) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return redirect()->back()->with('success', 'Attachment deleted successfully.');
    }

    public function nextCode()
    {
        return response()->json([
            'next_code' => $this->generateNextCode()
        ]);
    }

    private function generateNextCode(): string
    {
        $latest = ErpSupplier::where('supplier_code', 'like', 'SUP-%')
            ->orderByRaw('CAST(SUBSTRING(supplier_code, 5) AS UNSIGNED) DESC')
            ->value('supplier_code');

        $num = 0;

        if ($latest && preg_match('/^SUP-(\d+)$/i', $latest, $m)) {
            $num = (int) $m[1];
        }

        return 'SUP-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}
