@extends('layouts.home')

@section('title', 'Buat Advance Request')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="bx bx-wallet me-2 text-primary"></i>Buat Advance Request (Kasbon)</h4>
    <a href="{{ route('erp.advance-requests.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
  </div>

  <form method="POST" action="{{ route('erp.advance-requests.store') }}" class="card shadow-sm">
    @csrf
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-semibold">Budget Plan Detail <span class="text-danger">*</span></label>
        <select name="budget_plan_detail_id" class="form-select" required>
          <option value="">-- Pilih Budget Line --</option>
          @foreach($details as $d)
            <option value="{{ $d->id }}">
              {{ $d->code }} &mdash; {{ $d->monthlyBudgetPlan?->annualBudgetPlan?->budgetParent?->name }} /
              {{ $d->expense_type }} ({{ \Carbon\Carbon::create()->month($d->monthlyBudgetPlan?->month)->format('F') }} {{ $d->monthlyBudgetPlan?->year }})
            </option>
          @endforeach
        </select>
        <div class="form-text text-muted">Sisa plan di baris ini otomatis terlihat di halaman Monthly Budget Plan.</div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Work (WID) <span class="text-danger">*</span></label>
        <select name="work_id" class="form-select" required>
          <option value="">-- Pilih WID --</option>
          @foreach($workItems as $w)
            <option value="{{ $w->id }}">{{ $w->wid_code }} - {{ $w->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Budget Period <span class="text-danger">*</span></label>
          <input type="date" name="budget_period" class="form-control" required value="{{ now()->startOfMonth()->toDateString() }}">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Advance Request Date <span class="text-danger">*</span></label>
          <input type="date" name="advance_request_date" class="form-control" required value="{{ now()->toDateString() }}">
        </div>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Jumlah Site (opsional)</label>
          <input type="number" name="number_of_site" class="form-control" min="1" placeholder="Kosongkan jika tidak relevan">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Amount per Unit <span class="text-danger">*</span></label>
          <input type="number" step="0.01" name="amount_per_unit" class="form-control" required min="0" value="0">
          <div class="form-text text-muted">Jika Jumlah Site diisi, Total Kasbon = Amount per Unit &times; Jumlah Site.</div>
        </div>
      </div>

      <div class="mb-3 mt-3">
        <label class="form-label fw-semibold">Description</label>
        <textarea name="description" class="form-control" rows="2" placeholder="Contoh: BBM 1 team 200.000 6hari support u instalasi"></textarea>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('erp.advance-requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan sebagai Draft</button>
    </div>
  </form>
</div>
@endsection
