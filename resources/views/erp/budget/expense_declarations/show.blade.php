@extends('layouts.home')

@section('title', 'Expense Declaration - ' . $expenseDeclaration->code)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $detail = $expenseDeclaration->budgetPlanDetail;
  $monthly = $detail?->monthlyBudgetPlan;
  $budgetParent = $monthly?->annualBudgetPlan?->budgetParent;
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $statusClass = match($expenseDeclaration->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' };
  $advance = $expenseDeclaration->advanceRequest;

  $left = [
    ['Expense Declaration Detail Id', '<b>'.e($expenseDeclaration->code).'</b>'],
    ['Record Type', e($expenseDeclaration->record_type === 'with_advance' ? 'with advance' : 'without advance')],
    ['Advance Request Id', $advance ? '<a class="sf-link" href="'.e(route('erp.advance-requests.show', $advance)).'">'.e($advance->code).'</a>' : ''],
    ['Account ID', e($budgetParent->name ?? '-')],
    ['Area', e($monthly?->area ?? '-')],
    ['Work Id', e($expenseDeclaration->work?->wid_code ?? '-').($expenseDeclaration->work ? ' <span class="text-muted small">'.e($expenseDeclaration->work->name).'</span>' : '')],
    ['Work Subtype WID', e($monthly?->work_subtype ?? '-')],
    ['Request By', e($expenseDeclaration->requestBy?->name ?? '-')],
    ['Request Date', e($expenseDeclaration->request_date?->format('Y/m/d') ?? '-')],
    ['Vehicle', e($expenseDeclaration->vehicle_info ?? '')],
  ];
  $right = [
    ['Expense Type', '<b>'.e($expenseDeclaration->expense_type ?? $detail?->expense_type ?? '-').'</b>'],
    ['Budget yang Dipakai', e(($detail?->code ?? '-').' ('.($detail?->expense_type ?? '-').')')],
    ['Is InDirect', $expenseDeclaration->is_indirect ? '<i class="bx bx-check fs-5"></i>' : ''],
    ['Amount declaration', e($expenseDeclaration->currency.' '.number_format((float) $expenseDeclaration->amount_declaration, 0, ',', '.'))],
    ['Converted Amount (IDR)', '<b>'.e($money($expenseDeclaration->converted_amount_idr)).'</b>'],
    ['PO Number', e($expenseDeclaration->po_number ?? '')],
    ['Site Name', e($expenseDeclaration->site_name ?? '')],
    ['Description', nl2br(e($expenseDeclaration->description ?? ''))],
    ['Budget Plan Detail', $detail ? '<a class="sf-link" href="'.e(route('erp.monthly-budget-plans.show', $detail->monthly_budget_plan_id)).'">'.e($detail->code).'</a>' : '-'],
    ['Status', '<span class="badge bg-label-'.$statusClass.'">'.e($expenseDeclaration->status).'</span>'],
    ['Last Modified', e($expenseDeclaration->updated_at?->format('Y/m/d H:i') ?? '-')],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #6fb1ad">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-receipt"></i></span>
      <div><div class="sf-object">Expense Declaration</div><div class="sf-name">{{ $expenseDeclaration->code }}</div></div>
    </div>
    <div class="d-flex gap-2">
      @if($advance)
        <a href="{{ route('erp.advance-requests.show', $advance) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-wallet me-1"></i>Back to {{ $advance->code }}</a>
      @endif
      <a href="{{ route('erp.expense-declarations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
    </div>
  </div>

  <div class="sf-card">
    <div class="sf-card-head">
      <h6>Expense Declaration Detail</h6>
      <div class="sf-actions">
        @if(in_array($expenseDeclaration->status, ['Draft', 'Rejected'], true))
          <form method="POST" action="{{ route('erp.expense-declarations.submit', $expenseDeclaration) }}">@csrf<button class="btn btn-primary btn-sm"><i class="bx bx-paper-plane me-1"></i>{{ $expenseDeclaration->status === 'Rejected' ? 'Ajukan Ulang' : 'Submit for Approval' }}</button></form>
        @endif
      </div>
    </div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  @include('erp.budget.partials.sf-approval', [
    'approvals' => $expenseDeclaration->approvals,
    'submittedBy' => $expenseDeclaration->requestBy?->name,
  ])
</div>

<div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formApprove">
      @csrf
      <div class="modal-header"><h5 class="modal-title fw-bold">Setujui Deklarasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><label class="form-label">Komentar (opsional)</label><textarea name="comments" class="form-control" rows="2"></textarea></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Approve</button></div>
    </form>
  </div>
</div>
<div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formReject">
      @csrf
      <div class="modal-header"><h5 class="modal-title fw-bold">Tolak Deklarasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><label class="form-label">Alasan <span class="text-danger">*</span></label><textarea name="reason" class="form-control" rows="2" required></textarea></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Reject</button></div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function openApprove(id) {
  document.getElementById('formApprove').action = "{{ route('erp.expense-declarations.approvals.approve', ['approval' => '__ID__']) }}".replace('__ID__', id);
  new bootstrap.Modal(document.getElementById('modalApprove')).show();
}
function openReject(id) {
  document.getElementById('formReject').action = "{{ route('erp.expense-declarations.approvals.reject', ['approval' => '__ID__']) }}".replace('__ID__', id);
  new bootstrap.Modal(document.getElementById('modalReject')).show();
}

// Live refresh: this exact Expense Declaration got submitted/approved/rejected
// by someone else while we had it open — reload so status stays current.
window.addEventListener('erp-live-update', function (e) {
  if (e.detail.record_type === 'expense_declaration' && e.detail.id == {{ $expenseDeclaration->id }}) {
    location.reload();
  }
});
</script>
@endpush
@endsection
