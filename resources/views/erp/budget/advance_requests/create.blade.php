@extends('layouts.home')

@section('title', 'Buat Advance Request')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="bx bx-wallet me-2 text-primary"></i>New Advance Request (Kasbon)</h4>
    <a href="{{ route('erp.advance-requests.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
  </div>

  <form method="POST" action="{{ route('erp.advance-requests.store') }}" class="card shadow-sm">
    @csrf
    <div class="card-header bg-label-primary py-2"><h6 class="mb-0 fw-bold">Detail</h6></div>
    <div class="card-body pt-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Advance Request Date <span class="text-danger">*</span></label>
          <input type="date" name="advance_request_date" id="reqDate" class="form-control" required value="{{ old('advance_request_date', now()->toDateString()) }}">
          <div class="form-text">Bulan dari tanggal ini menentukan Monthly Budget Plan yang dipotong.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Account (Budget Parent) <span class="text-danger">*</span></label>
          <select name="budget_parent_id" id="budgetParent" class="form-select" required>
            <option value="">-- Pilih Account --</option>
            @foreach($budgetParents as $bp)
              <option value="{{ $bp->id }}" @selected(old('budget_parent_id') == $bp->id)>{{ $bp->name }} ({{ $bp->budget_code }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
          <select name="area" id="area" class="form-select" required></select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Work Type <span class="text-danger">*</span></label>
          <select name="work_type" id="workType" class="form-select" required></select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Work SubType</label>
          <select name="work_subtype" id="workSubtype" class="form-select"></select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Work ID (WID) <span class="text-danger">*</span></label>
          <select name="work_id" id="workId" class="form-select" required></select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Request By <span class="text-danger">*</span></label>
          <select name="request_by_id" class="form-select" required>
            @foreach($users as $u)
              <option value="{{ $u->id }}" @selected(old('request_by_id', auth()->id()) == $u->id)>{{ $u->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Number of Site</label>
          <input type="number" name="number_of_site" class="form-control" min="1" value="{{ old('number_of_site') }}" placeholder="Kosongkan jika tidak relevan">
          <div class="form-text">Kalau diisi, Total Kasbon = Amount &times; Number of Site.</div>
        </div>
      </div>
      <div id="planHint" class="alert alert-warning py-2 mt-3 mb-0 d-none small"></div>
    </div>

    <div class="card-header bg-label-primary py-2 border-top"><h6 class="mb-0 fw-bold">Expense Type</h6></div>
    <div class="card-body pt-3">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>Expense Type</th>
              <th class="text-center" style="width:50px"></th>
              <th style="width:90px">Currency</th>
              <th style="width:200px">Amount</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            @foreach($expenseTypes as $i => $type)
              <tr>
                <td class="fw-semibold">{{ $type }}</td>
                <td class="text-center"><input type="checkbox" class="form-check-input line-check" name="lines[{{ $i }}][checked]" value="1" @checked(old("lines.$i.checked"))></td>
                <td><span class="badge bg-label-secondary">IDR</span></td>
                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm line-amount" name="lines[{{ $i }}][amount]" value="{{ old("lines.$i.amount") }}"></td>
                <td><input type="text" class="form-control form-control-sm" name="lines[{{ $i }}][description]" value="{{ old("lines.$i.description") }}"></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="form-text mt-2">Tiap Expense Type yang diisi disimpan sebagai satu Advance Request tersendiri, masing-masing memotong Budget Plan Detail jenis biaya itu.</div>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('erp.advance-requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Save</button>
    </div>
  </form>
</div>

@include('erp.budget.partials.budget-cascade', [
  'initial' => [
    'area' => old('area'),
    'workType' => old('work_type'),
    'workSubtype' => old('work_subtype'),
    'workId' => old('work_id'),
  ],
])
@endsection
