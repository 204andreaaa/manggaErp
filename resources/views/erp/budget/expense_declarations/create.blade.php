@extends('layouts.home')

@section('title', 'Buat Expense Declaration')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="bx bx-receipt me-2 text-primary"></i>Buat Expense Declaration (Realisasi)</h4>
    <a href="{{ route('erp.expense-declarations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
  </div>

  <form method="POST" action="{{ route('erp.expense-declarations.store') }}" class="card shadow-sm" id="declForm">
    @csrf
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-semibold">Tipe Deklarasi <span class="text-danger">*</span></label>
        <select name="record_type" id="recordType" class="form-select" required>
          <option value="with_advance">With Advance (ada kasbon-nya)</option>
          <option value="without_advance">Without Advance (biaya langsung, boleh lewat PO)</option>
        </select>
      </div>

      <div class="mb-3" id="advanceRequestCol">
        <label class="form-label fw-semibold">Kasbon (Advance Request) <span class="text-danger">*</span></label>
        <select name="advance_request_id" id="advanceRequestId" class="form-select">
          <option value="">-- Pilih Kasbon (harus sudah Approved & Paid) --</option>
          @foreach($advanceRequests as $adv)
            <option value="{{ $adv->id }}" data-detail="{{ $adv->budget_plan_detail_id }}" data-work="{{ $adv->work_id }}">
              {{ $adv->code }} &mdash; {{ number_format($adv->total_amount_advance, 0, ',', '.') }} ({{ $adv->work?->wid_code }})
            </option>
          @endforeach
        </select>
        <div class="form-text text-muted">Cuma kasbon berstatus Approved &amp; Paid yang muncul di sini.</div>
      </div>

      <div class="mb-3" id="poNumberCol" style="display:none;">
        <label class="form-label fw-semibold">Nomor PO (opsional)</label>
        <input name="po_number" class="form-control" placeholder="Kalau biaya ini lewat Purchase Order">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Budget Plan Detail <span class="text-danger">*</span></label>
        <select name="budget_plan_detail_id" id="budgetPlanDetailId" class="form-select" required>
          <option value="">-- Pilih Budget Line --</option>
          @foreach($details as $d)
            <option value="{{ $d->id }}">
              {{ $d->code }} &mdash; {{ $d->monthlyBudgetPlan?->annualBudgetPlan?->budgetParent?->name }} / {{ $d->expense_type }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Work (WID) <span class="text-danger">*</span></label>
          <select name="work_id" id="workId" class="form-select" required>
            <option value="">-- Pilih WID --</option>
            @foreach($workItems as $w)
              <option value="{{ $w->id }}">{{ $w->wid_code }} - {{ $w->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Site Name</label>
          <input name="site_name" class="form-control" placeholder="Contoh: TANJUNG PATI">
        </div>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Request Date <span class="text-danger">*</span></label>
          <input type="date" name="request_date" class="form-control" required value="{{ now()->toDateString() }}">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Amount Declaration <span class="text-danger">*</span></label>
          <input type="number" step="0.01" name="amount_declaration" class="form-control" required min="0" value="0">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Currency</label>
          <input name="currency" class="form-control" value="IDR">
        </div>
      </div>

      <div class="mb-3 mt-3 form-check">
        <input type="checkbox" name="is_indirect" value="1" class="form-check-input" id="isIndirect">
        <label class="form-check-label" for="isIndirect">Biaya Tidak Langsung (Indirect)</label>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Description</label>
        <textarea name="description" class="form-control" rows="2"></textarea>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('erp.expense-declarations.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Simpan sebagai Draft</button>
    </div>
  </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const recordType = document.getElementById('recordType');
  const advCol = document.getElementById('advanceRequestCol');
  const advSelect = document.getElementById('advanceRequestId');
  const poCol = document.getElementById('poNumberCol');
  const detailSelect = document.getElementById('budgetPlanDetailId');
  const workSelect = document.getElementById('workId');

  function toggle() {
    const isWith = recordType.value === 'with_advance';
    advCol.style.display = isWith ? 'block' : 'none';
    poCol.style.display = isWith ? 'none' : 'block';
    advSelect.required = isWith;
    if (!isWith) advSelect.value = '';
  }

  recordType.addEventListener('change', toggle);
  toggle();

  advSelect.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.value) {
      if (opt.dataset.detail) detailSelect.value = opt.dataset.detail;
      if (opt.dataset.work) workSelect.value = opt.dataset.work;
    }
  });
});
</script>
@endpush
@endsection
