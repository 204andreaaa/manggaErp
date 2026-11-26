@extends('layouts.home')

@section('title', $invoice->exists ? 'Edit Invoice - '.$invoice->code : 'Invoice Baru')

@include('erp.hr.partials.style')

@section('content')
@php $back = $invoice->exists ? route('erp.invoices.show', $invoice) : route('erp.invoices.index'); @endphp
<div class="container-xxl flex-grow-1 container-p-y">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.invoices.index') }}">Invoice</a></li>
      @if($invoice->exists)<li class="breadcrumb-item"><a href="{{ $back }}">{{ $invoice->code }}</a></li>@endif
      <li class="breadcrumb-item active fw-semibold">{{ $invoice->exists ? 'Edit' : 'Invoice Baru' }}</li>
    </ol>
  </nav>

  @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ $invoice->exists ? route('erp.invoices.update', $invoice) : route('erp.invoices.store') }}">
    @csrf
    @if($invoice->exists) @method('PUT') @endif

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-receipt"></i></span></span>Data Invoice</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-12">
              <label class="form-label">Nomor Invoice <span class="text-danger">*</span></label>
              <input type="text" name="invoice_number" class="form-control" maxlength="120" required
                     value="{{ old('invoice_number', $invoice->invoice_number) }}" placeholder="mis. MDU04871/IJE-MANDAU/XI/2026">
            </div>
            <div class="col-md-6">
              <label class="form-label">Customer</label>
              <input type="text" name="customer_name" class="form-control" maxlength="150" value="{{ old('customer_name', $invoice->customer_name) }}" placeholder="mis. Indosat">
            </div>
            <div class="col-12">
              <label class="form-label">TOP</label>
              <input type="text" name="top" class="form-control" maxlength="100" value="{{ old('top', $invoice->top) }}" placeholder="mis. DP 30%, TERMIN 1 - 95%, FP 100%">
            </div>
            <div class="col-12">
              <label class="form-label">Remarks</label>
              <textarea name="remarks" class="form-control" rows="3" maxlength="2000">{{ old('remarks', $invoice->remarks) }}</textarea>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-info"><i class="bx bx-calculator"></i></span></span>Pajak</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-md-6">
              <label class="form-label">PPN <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" name="ppn_rate" class="form-control" min="0" max="100" step="0.01" required value="{{ old('ppn_rate', (float) $invoice->ppn_rate) }}">
                <span class="input-group-text">%</span>
              </div>
              <div class="form-text">Ditambahkan ke seluruh nilai invoice.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">PPh <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" name="pph_rate" class="form-control" min="0" max="100" step="0.01" required value="{{ old('pph_rate', (float) $invoice->pph_rate) }}">
                <span class="input-group-text">%</span>
              </div>
              <div class="form-text">Dipotong customer dari nilai invoice (isi 0% bila tidak kena PPh).</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header pb-2">
            <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-warning"><i class="bx bx-calendar"></i></span></span>Tanggal</h6>
          </div>
          <div class="card-body row g-3">
            <div class="col-12">
              <label class="form-label">Tanggal Invoice <span class="text-danger">*</span></label>
              <input type="date" name="invoice_date" class="form-control" required value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}">
            </div>
            <div class="col-12">
              <label class="form-label">Jatuh Tempo</label>
              <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
            </div>
          </div>
        </div>
        <div class="alert alert-info border-0 small">
          <i class="bx bx-info-circle me-1"></i>Nilai invoice (sebelum pajak) dihitung dari <b>rincian per WID</b>. Yang harus dibayar customer = nilai invoice + PPN − PPh; status (New / Partial Paid / Paid) mengikuti <b>pembayaran</b>.
        </div>
        <div class="d-flex gap-2">
          <a href="{{ $back }}" class="btn btn-label-secondary flex-fill">Batal</a>
          <button class="btn btn-primary flex-fill"><i class="bx bx-save me-1"></i>Simpan</button>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection
