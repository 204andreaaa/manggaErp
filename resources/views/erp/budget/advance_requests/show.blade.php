@extends('layouts.home')

@section('title', 'Advance Request - ' . $advanceRequest->code)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $detail = $advanceRequest->budgetPlanDetail;
  $monthly = $detail?->monthlyBudgetPlan;
  $budgetParent = $monthly?->annualBudgetPlan?->budgetParent;
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $statusClass = match($advanceRequest->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' };
  $canDeclare = $advanceRequest->status === 'Approved' && $advanceRequest->payment_status === 'Paid' && auth()->user()->hasPermission('expense_declarations.create');
  $canPay = $advanceRequest->status === 'Approved' && $advanceRequest->payment_status === 'Unpaid' && auth()->user()->hasRole(['finance','superadmin']);

  $left = [
    ['Account ID', e($budgetParent->name ?? '-')],
    ['Budget Period', e($advanceRequest->budget_period?->format('Y/m/d') ?? '-')],
    ['Budget Plan Detail', $detail ? '<a class="sf-link" href="'.e(route('erp.monthly-budget-plans.show', $detail->monthly_budget_plan_id)).'">'.e($detail->code).'</a>' : '-'],
    ['Advance Payment Status', e($advanceRequest->advance_payment_status ?? '-')],
    ['Advance Request Date', e($advanceRequest->advance_request_date?->format('Y/m/d') ?? '-')],
    ['Request By', e($advanceRequest->requestBy?->name ?? '-')],
    ['Work Id', e($advanceRequest->work?->wid_code ?? '-').($advanceRequest->work ? ' <span class="text-muted small">'.e($advanceRequest->work->name).'</span>' : '')],
    ['Is Active', $advanceRequest->is_active ? '<i class="bx bx-check fs-5 text-success"></i>' : ''],
    ['Created', e($advanceRequest->created_at?->format('Y/m/d H:i') ?? '-')],
    ['Advance Outstanding', e($money($advanceRequest->advance_outstanding))],
    ['Payment Status', '<span class="badge bg-label-'.($advanceRequest->payment_status === 'Paid' ? 'success' : 'warning').'">'.e($advanceRequest->payment_status).'</span>'],
    ['Payment Date', e($advanceRequest->payment_date?->format('Y/m/d') ?? '').($advanceRequest->paidBy ? ' <span class="text-muted small">oleh '.e($advanceRequest->paidBy->name).'</span>' : '')],
  ];
  $right = [
    ['Area', e($monthly?->area ?? '-')],
    ['Work Type', e($monthly?->work_type ?? '-')],
    ['Work subtype', e($monthly?->work_subtype ?? '-')],
    ['Expense Type', '<b>'.e($detail?->expense_type ?? '-').'</b>'],
    ['Amount per unit', e($money($advanceRequest->amount_per_unit))],
    ['Number of Site', e($advanceRequest->number_of_site ?? '')],
    ['Actual Amount', e($money($advanceRequest->actual_amount))],
    ['Total amount advance', '<b>'.e($money($advanceRequest->total_amount_advance)).'</b>'],
    ['Description', nl2br(e($advanceRequest->description ?? ''))],
    ['Status', '<span class="badge bg-label-'.$statusClass.'">'.e($advanceRequest->status).'</span>'],
    ['Last Modified', e($advanceRequest->updated_at?->format('Y/m/d H:i') ?? '-')],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #d27d5b">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-wallet"></i></span>
      <div><div class="sf-object">Advance Request</div><div class="sf-name">{{ $advanceRequest->code }}</div></div>
    </div>
    <a href="{{ route('erp.advance-requests.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
  </div>

  <div class="sf-card">
    <div class="sf-card-head">
      <h6>Advance Request Detail</h6>
      <div class="sf-actions">
        @if(in_array($advanceRequest->status, ['Draft', 'Rejected'], true))
          <form method="POST" action="{{ route('erp.advance-requests.submit', $advanceRequest) }}">@csrf<button class="btn btn-primary btn-sm"><i class="bx bx-paper-plane me-1"></i>{{ $advanceRequest->status === 'Rejected' ? 'Ajukan Ulang' : 'Submit for Approval' }}</button></form>
        @endif
        @if($canPay)
          <form method="POST" action="{{ route('erp.advance-requests.pay', $advanceRequest) }}">@csrf<button class="btn btn-success btn-sm" onclick="return confirm('Tandai kasbon ini sudah dibayar?')"><i class="bx bx-dollar-circle me-1"></i>Mark as Paid</button></form>
        @endif
      </div>
    </div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  <div class="sf-card" style="--sf-accent: #6fb1ad">
    <div class="sf-card-head">
      <h6>Expense Declaration</h6>
      <div class="sf-actions">
        @if($canDeclare)
          <a href="{{ route('erp.expense-declarations.create', ['advance_request_id' => $advanceRequest->id]) }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Expense Declaration</a>
        @endif
      </div>
    </div>
    @if($advanceRequest->expenseDeclarations->isEmpty())
      <div class="sf-empty">
        No records to display
        @unless($canDeclare)<span class="ms-1">(Expense Declaration bisa dibuat setelah kasbon Approved dan sudah dibayar.)</span>@endunless
      </div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr><th>Expense Declaration Detail Id</th><th>Account ID</th><th>Work Id</th><th>Expense Type</th><th>Area</th><th class="text-center">Is InDirect</th><th>Work Subtype WID</th><th class="text-end">Amount declaration</th><th>Description</th><th>Status</th></tr>
          </thead>
          <tbody>
            @foreach($advanceRequest->expenseDeclarations as $decl)
              <tr>
                <td><a class="sf-link" href="{{ route('erp.expense-declarations.show', $decl) }}">{{ $decl->code }}</a></td>
                <td>{{ $budgetParent->name ?? '-' }}</td>
                <td>{{ $decl->work?->wid_code }}</td>
                <td>{{ $decl->expense_type ?? $decl->budgetPlanDetail?->expense_type }}</td>
                <td>{{ $monthly?->area }}</td>
                <td class="text-center">@if($decl->is_indirect)<i class="bx bx-check"></i>@endif</td>
                <td>{{ $monthly?->work_subtype }}</td>
                <td class="text-end">{{ $money($decl->converted_amount_idr) }}</td>
                <td class="small">{{ $decl->description }}</td>
                <td><span class="badge bg-label-{{ match($decl->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' } }}">{{ $decl->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  @include('erp.budget.partials.sf-approval', [
    'approvals' => $advanceRequest->approvals,
    'submittedBy' => $advanceRequest->requestBy?->name,
    'submitForm' => in_array($advanceRequest->status, ['Draft', 'Rejected'], true)
        ? '<form method="POST" action="'.e(route('erp.advance-requests.submit', $advanceRequest)).'"><input type="hidden" name="_token" value="'.e(csrf_token()).'"><button class="btn btn-primary btn-sm">'.($advanceRequest->status === 'Rejected' ? 'Ajukan Ulang' : 'Submit for Approval').'</button></form>'
        : '',
  ])
</div>

<div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formApprove">
      @csrf
      <div class="modal-header"><h5 class="modal-title fw-bold">Setujui Tahap Ini</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><label class="form-label">Komentar (opsional)</label><textarea name="comments" class="form-control" rows="2"></textarea></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Approve</button></div>
    </form>
  </div>
</div>
<div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formReject">
      @csrf
      <div class="modal-header"><h5 class="modal-title fw-bold">Tolak Tahap Ini</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><label class="form-label">Alasan <span class="text-danger">*</span></label><textarea name="reason" class="form-control" rows="2" required></textarea></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Reject</button></div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function openApprove(id) {
  document.getElementById('formApprove').action = "{{ route('erp.advance-requests.approvals.approve', ['approval' => '__ID__']) }}".replace('__ID__', id);
  new bootstrap.Modal(document.getElementById('modalApprove')).show();
}
function openReject(id) {
  document.getElementById('formReject').action = "{{ route('erp.advance-requests.approvals.reject', ['approval' => '__ID__']) }}".replace('__ID__', id);
  new bootstrap.Modal(document.getElementById('modalReject')).show();
}

// Live refresh: this exact Advance Request got submitted/approved/rejected/paid
// by someone else while we had it open — reload so status stays current.
window.addEventListener('erp-live-update', function (e) {
  if (e.detail.record_type === 'advance_request' && e.detail.id == {{ $advanceRequest->id }}) {
    location.reload();
  }
});
</script>
@endpush
@endsection
