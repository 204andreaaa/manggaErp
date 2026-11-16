@extends('layouts.home')

@section('title', $invoice->exists ? 'Edit Invoice - '.$invoice->code : 'New Invoice')

@include('erp.budget.partials.sf-style')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #5b9bd2">
  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-receipt"></i></span>
      <div><div class="sf-object">Invoice Edit</div><div class="sf-name">{{ $invoice->exists ? $invoice->code : 'New Invoice' }}</div></div>
    </div>
    <a href="{{ $invoice->exists ? route('erp.invoices.show', $invoice) : route('erp.invoices.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ $invoice->exists ? route('erp.invoices.update', $invoice) : route('erp.invoices.store') }}" class="sf-card">
    @csrf
    @if($invoice->exists) @method('PUT') @endif
    <div class="sf-card-head"><h6>Information</h6></div>
    <div class="row g-3 p-3">
      <div class="col-md-6">
        <label class="form-label">Invoice Number <span class="text-danger">*</span></label>
        <input type="text" name="invoice_number" class="form-control" maxlength="120" required
               value="{{ old('invoice_number', $invoice->invoice_number) }}" placeholder="mis. MDU04871/IJE-MANDAU/XI/2026">
      </div>
      <div class="col-md-3">
        <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
        <input type="date" name="invoice_date" class="form-control" required value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Invoice Due Date</label>
        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
      </div>
      <div class="col-md-6">
        <label class="form-label">Company</label>
        <select name="company_id" class="form-select">
          <option value="">-- None --</option>
          @foreach($companies as $c)
            <option value="{{ $c->id }}" @selected((string) old('company_id', $invoice->company_id) === (string) $c->id)>{{ $c->short_name ?: $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Customer</label>
        <input type="text" name="customer_name" class="form-control" maxlength="150" value="{{ old('customer_name', $invoice->customer_name) }}" placeholder="mis. Indosat">
      </div>
      <div class="col-md-6">
        <label class="form-label">TOP</label>
        <input type="text" name="top" class="form-control" maxlength="100" value="{{ old('top', $invoice->top) }}" placeholder="mis. DP 30%, TERMIN 1 - 95%, FP 100%">
      </div>
      <div class="col-md-6">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control" rows="2" maxlength="2000">{{ old('remarks', $invoice->remarks) }}</textarea>
      </div>
      <div class="col-12">
        <div class="form-text">Nilai invoice dihitung dari rincian per WID, dan status (New / Partial Paid / Paid) mengikuti pembayaran yang dicatat. Keduanya diisi di halaman invoice setelah disimpan.</div>
      </div>
    </div>
    <div class="d-flex justify-content-end gap-2 px-3 pb-3">
      <a href="{{ $invoice->exists ? route('erp.invoices.show', $invoice) : route('erp.invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <button class="btn btn-primary"><i class="bx bx-save me-1"></i>Save</button>
    </div>
  </form>
</div>
@endsection
