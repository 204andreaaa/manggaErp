@extends('layouts.home')

@section('title', 'Buat Expense Declaration')

@section('content')
@php
  $advanceInfo = $advanceRequests->mapWithKeys(fn ($a) => [$a->id => [
      'code' => $a->code,
      'total' => number_format($a->total_amount_advance, 0, ',', '.'),
      'left' => number_format($a->actual_amount - $a->expenseDeclarations->where('record_type', 'with_advance')->where('status', '!=', 'Rejected')->sum('converted_amount_idr'), 0, ',', '.'),
      'account' => $a->budgetPlanDetail?->monthlyBudgetPlan?->annualBudgetPlan?->budgetParent?->name ?? '-',
      'area' => $a->budgetPlanDetail?->monthlyBudgetPlan?->area ?? '-',
      'type' => $a->budgetPlanDetail?->monthlyBudgetPlan?->work_type ?? '-',
      'subtype' => $a->budgetPlanDetail?->monthlyBudgetPlan?->work_subtype ?? '-',
      'expense' => $a->budgetPlanDetail?->expense_type ?? '-',
      'wid' => $a->work?->wid_code ?? '-',
      'month' => $a->budgetPlanDetail?->monthlyBudgetPlan
          ? \Carbon\Carbon::create($a->budgetPlanDetail->monthlyBudgetPlan->year, $a->budgetPlanDetail->monthlyBudgetPlan->month, 1)->translatedFormat('F Y')
          : '-',
  ]]);
  $recordType = old('record_type', 'with_advance');
  $selectedAdvance = old('advance_request_id', $preselectedAdvance);
  // Opened from a kasbon page: the declaration is for that kasbon, full stop.
  $locked = (bool) $preselectedAdvance;
@endphp
<div class="container-xxl flex-grow-1 container-p-y">

  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="bx bx-receipt me-2 text-primary"></i>New Expense Declaration</h4>
    <a href="{{ $preselectedAdvance ? route('erp.advance-requests.show', $preselectedAdvance) : route('erp.expense-declarations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
  </div>

  <form method="POST" action="{{ route('erp.expense-declarations.store') }}" class="card shadow-sm">
    @csrf
    <div class="card-header py-2" style="background:#6fb1ad"><h6 class="mb-0 fw-bold text-white">Detail</h6></div>
    <div class="card-body pt-3">
      @if($locked)
        <input type="hidden" name="record_type" id="recordType" value="with_advance">
        <input type="hidden" name="advance_request_id" id="advanceRequestId" value="{{ $preselectedAdvance }}">
        <div class="small text-muted mb-1">Record Type: <b>with advance</b> (pertanggungjawaban kasbon)</div>
      @else
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Record Type <span class="text-danger">*</span></label>
          <select name="record_type" id="recordType" class="form-select" required>
            <option value="with_advance" @selected($recordType === 'with_advance')>with advance (pertanggungjawaban kasbon)</option>
            <option value="without_advance" @selected($recordType === 'without_advance')>without advance (tanpa kasbon / lewat PO)</option>
          </select>
        </div>
        <div class="col-md-6" id="advanceCol">
          <label class="form-label fw-semibold">Advance Request Id <span class="text-danger">*</span></label>
          <select name="advance_request_id" id="advanceRequestId" class="form-select">
            <option value="">-- Pilih Kasbon (Approved &amp; Paid) --</option>
            @foreach($advanceRequests as $adv)
              <option value="{{ $adv->id }}" @selected($selectedAdvance == $adv->id)>{{ $adv->code }} &mdash; {{ $adv->budgetPlanDetail?->expense_type }} &mdash; {{ number_format($adv->total_amount_advance, 0, ',', '.') }}</option>
            @endforeach
          </select>
        </div>
      </div>
      @endif

      {{-- with advance: everything below comes from the kasbon and can't be changed --}}
      <div id="advanceSummary" class="alert alert-info py-2 mt-3 mb-0 small d-none"></div>

      {{-- without advance: pick the budget the same way as a kasbon --}}
      <div id="manualBudget" class="row g-3 mt-0">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Account (Budget Parent) <span class="text-danger">*</span></label>
          <select name="budget_parent_id" id="budgetParent" class="form-select">
            <option value="">-- Pilih Account --</option>
            @foreach($budgetParents as $bp)
              <option value="{{ $bp->id }}" @selected(old('budget_parent_id') == $bp->id)>{{ $bp->name }} ({{ $bp->budget_code }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Work ID (WID) <span class="text-danger">*</span></label>
          <select name="work_id" id="workId" class="form-select"></select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
          <select name="area" id="area" class="form-select"></select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Work Type <span class="text-danger">*</span></label>
          <select name="work_type" id="workType" class="form-select"></select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Work SubType</label>
          <select name="work_subtype" id="workSubtype" class="form-select"></select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">PO Number</label>
          <input name="po_number" class="form-control" value="{{ old('po_number') }}" placeholder="Kalau biaya ini lewat Purchase Order">
        </div>
        <div class="col-12"><div id="planHint" class="alert alert-warning py-2 mb-0 d-none small"></div></div>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Request By <span class="text-danger">*</span></label>
          <select name="request_by_id" class="form-select" required>
            @foreach($users as $u)
              <option value="{{ $u->id }}" @selected(old('request_by_id', auth()->id()) == $u->id)>{{ $u->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Request Date <span class="text-danger">*</span></label>
          <input type="date" name="request_date" id="reqDate" class="form-control" required value="{{ old('request_date', now()->toDateString()) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Vehicle</label>
          <input name="vehicle_info" class="form-control" value="{{ old('vehicle_info') }}" placeholder="Contoh: B 1234 XYZ">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Site Name</label>
          <input name="site_name" class="form-control" value="{{ old('site_name') }}" placeholder="Contoh: TANJUNG PATI">
        </div>
      </div>
    </div>

    <div class="card-header py-2 border-top" style="background:#6fb1ad"><h6 class="mb-0 fw-bold text-white">Expense Type</h6></div>
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
              <tr class="expense-row" data-type="{{ $type }}">
                <td class="fw-semibold">{{ $type }} <span class="badge bg-label-info ms-1 kasbon-badge d-none">Expense Type kasbon</span></td>
                <td class="text-center"><input type="checkbox" class="form-check-input line-check" name="lines[{{ $i }}][checked]" value="1" @checked(old("lines.$i.checked"))></td>
                <td><span class="badge bg-label-secondary">IDR</span></td>
                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm line-amount" name="lines[{{ $i }}][amount]" value="{{ old("lines.$i.amount") }}"></td>
                <td><input type="text" class="form-control form-control-sm" name="lines[{{ $i }}][description]" value="{{ old("lines.$i.description") }}"></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="form-text mt-2">Tiap Expense Type yang diisi disimpan sebagai satu Expense Declaration tersendiri, masing-masing tercatat sebagai pengeluaran di Budget Plan Detail jenis biaya itu.</div>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2">
      <a href="{{ $preselectedAdvance ? route('erp.advance-requests.show', $preselectedAdvance) : route('erp.expense-declarations.index') }}" class="btn btn-outline-secondary">Cancel</a>
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

@push('styles')
<style>
  tr.row-locked td { opacity: .4; background: #f5f5f9; }
  tr.row-locked td:first-child::after { content: ' (tidak sesuai kasbon)'; font-weight: 400; font-size: .75rem; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const info = {!! json_encode($advanceInfo) !!};
  const recordType = document.getElementById('recordType');
  const advanceCol = document.getElementById('advanceCol');
  const advanceSel = document.getElementById('advanceRequestId');
  const summary = document.getElementById('advanceSummary');
  const manual = document.getElementById('manualBudget');
  const manualRequired = ['budgetParent', 'workId', 'area', 'workType'].map(id => document.getElementById(id));

  function showSummary() {
    const a = info[advanceSel.value];
    if (!a) { summary.classList.add('d-none'); return; }
    summary.innerHTML = '<b>' + a.code + '</b> &middot; Total kasbon Rp ' + a.total +
      ' &middot; <b>Sisa yang bisa dideklarasikan: Rp ' + a.left + '</b>' +
      '<br>Account: <b>' + a.account + '</b> &middot; Area: <b>' + a.area + '</b> &middot; Work Type: <b>' + a.type + '</b> / ' + a.subtype +
      '<br>WID: <b>' + a.wid + '</b> &middot; Budget bulan: <b>' + a.month + '</b> &middot; Expense Type kasbon: <b>' + a.expense + '</b>' +
      '<br><span class="text-muted">Account, Area, Work Type, WID, dan bulan budget otomatis mengikuti kasbon ini. Deklarasi dari kasbon langsung Approved, tanpa approval lagi.</span>' +
      '<br><b>' + (a.expense === OPEN_TYPE
        ? 'Kasbon Other Expense: boleh dipecah ke Expense Type mana saja, tiap baris memotong budget jenis biayanya sendiri.'
        : 'Deklarasi untuk kasbon ini hanya untuk Expense Type ' + a.expense + '.') + '</b>';
    summary.classList.remove('d-none');
  }

  // Specific-type kasbon -> only that row is usable; "Other Expense" kasbon -> all rows.
  const OPEN_TYPE = @json(\App\Http\Controllers\Erp\ExpenseDeclarationController::OPEN_KASBON_TYPE);
  const hadErrors = {{ $errors->any() ? 'true' : 'false' }};
  const rows = Array.from(document.querySelectorAll('.expense-row'));
  function restrictRows(prefill) {
    const a = recordType.value === 'with_advance' ? info[advanceSel.value] : null;
    const only = a && a.expense !== OPEN_TYPE ? a.expense : null;
    rows.forEach(row => {
      const type = row.dataset.type;
      const isKasbon = !!a && type === a.expense;
      const lockedOut = !!only && type !== only;
      row.classList.toggle('table-info', isKasbon);
      row.classList.toggle('row-locked', lockedOut);
      row.querySelector('.kasbon-badge').classList.toggle('d-none', !isKasbon);
      row.querySelectorAll('input').forEach(input => {
        input.disabled = lockedOut;
        if (lockedOut) { if (input.type === 'checkbox') input.checked = false; else input.value = ''; }
      });
      if (prefill && !hadErrors && only && type === only) row.querySelector('.line-check').checked = true;
    });
  }

  function toggle() {
    const withAdvance = recordType.value === 'with_advance';
    if (advanceCol) advanceCol.style.display = withAdvance ? '' : 'none';
    advanceSel.required = withAdvance;
    manual.style.display = withAdvance ? 'none' : '';
    manualRequired.forEach(x => { x.required = !withAdvance; });
    if (withAdvance) showSummary(); else summary.classList.add('d-none');
    restrictRows(true);
  }

  recordType.addEventListener('change', toggle);
  advanceSel.addEventListener('change', () => { showSummary(); restrictRows(true); });
  toggle();
});
</script>
@endpush
@endsection
