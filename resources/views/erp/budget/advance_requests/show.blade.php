@extends('layouts.home')

@section('title', 'Advance Request - ' . $advanceRequest->code)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  @php
    $detail = $advanceRequest->budgetPlanDetail;
    $budgetParent = $detail?->monthlyBudgetPlan?->annualBudgetPlan?->budgetParent;
    $statusClass = match($advanceRequest->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' };
  @endphp

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bx bx-wallet me-2 text-primary"></i>{{ $advanceRequest->code }}</h4>
      <div class="text-muted small">{{ $budgetParent->name ?? '-' }} &middot; {{ $detail?->expense_type }}</div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-label-{{ $statusClass }} fs-7">{{ $advanceRequest->status }}</span>
      <a href="{{ route('erp.advance-requests.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0 fw-bold">Detail Kasbon</h6></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4 text-muted">Account</dt><dd class="col-sm-8">{{ $budgetParent->name ?? '-' }}</dd>
            <dt class="col-sm-4 text-muted">Area</dt><dd class="col-sm-8">{{ $detail?->monthlyBudgetPlan?->area ?? '-' }}</dd>
            <dt class="col-sm-4 text-muted">Work Type / Subtype</dt><dd class="col-sm-8">{{ $detail?->monthlyBudgetPlan?->work_type ?? '-' }}{{ $detail?->monthlyBudgetPlan?->work_subtype ? ' / '.$detail->monthlyBudgetPlan->work_subtype : '' }}</dd>
            <dt class="col-sm-4 text-muted">Expense Type</dt><dd class="col-sm-8 fw-semibold">{{ $detail?->expense_type }}</dd>
            <dt class="col-sm-4 text-muted">Budget Plan Detail</dt><dd class="col-sm-8">{{ $detail?->code }}</dd>
            <dt class="col-sm-4 text-muted">Work (WID)</dt><dd class="col-sm-8">{{ $advanceRequest->work?->wid_code }} - {{ $advanceRequest->work?->name }}</dd>
            <dt class="col-sm-4 text-muted">Requestor</dt><dd class="col-sm-8">{{ $advanceRequest->requestBy?->name }}</dd>
            <dt class="col-sm-4 text-muted">Tanggal Pengajuan</dt><dd class="col-sm-8">{{ $advanceRequest->advance_request_date?->format('d M Y') }}</dd>
            <dt class="col-sm-4 text-muted">Budget Period</dt><dd class="col-sm-8">{{ $advanceRequest->budget_period?->format('d M Y') }}</dd>
            @if($advanceRequest->number_of_site)
              <dt class="col-sm-4 text-muted">Jumlah Site</dt><dd class="col-sm-8">{{ $advanceRequest->number_of_site }}</dd>
            @endif
            <dt class="col-sm-4 text-muted">Amount per Unit</dt><dd class="col-sm-8">{{ number_format($advanceRequest->amount_per_unit, 0, ',', '.') }}</dd>
            <dt class="col-sm-4 text-muted">Total Kasbon</dt><dd class="col-sm-8 fw-bold fs-5 text-primary">{{ number_format($advanceRequest->total_amount_advance, 0, ',', '.') }}</dd>
            @if($advanceRequest->status === 'Approved')
              <dt class="col-sm-4 text-muted">Actual Amount</dt><dd class="col-sm-8">{{ number_format($advanceRequest->actual_amount, 0, ',', '.') }}</dd>
              <dt class="col-sm-4 text-muted">Outstanding</dt><dd class="col-sm-8 {{ $advanceRequest->advance_outstanding > 0 ? 'text-warning fw-bold' : 'text-success' }}">{{ number_format($advanceRequest->advance_outstanding, 0, ',', '.') }}</dd>
            @endif
            @if($advanceRequest->description)
              <dt class="col-sm-4 text-muted">Description</dt><dd class="col-sm-8">{{ $advanceRequest->description }}</dd>
            @endif
          </dl>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Status Pembayaran</h6></div>
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <span class="badge bg-label-{{ $advanceRequest->payment_status === 'Paid' ? 'success' : 'warning' }} fs-7">{{ $advanceRequest->payment_status }}</span>
            @if($advanceRequest->payment_date)
              <span class="text-muted small ms-2">dibayar {{ $advanceRequest->payment_date->format('d M Y') }} oleh {{ $advanceRequest->paidBy?->name }}</span>
            @endif
          </div>
          @if($advanceRequest->status === 'Approved' && $advanceRequest->payment_status === 'Unpaid' && auth()->user()->hasRole(['finance','superadmin']))
            <form method="POST" action="{{ route('erp.advance-requests.pay', $advanceRequest) }}">
              @csrf
              <button class="btn btn-success btn-sm" onclick="return confirm('Tandai kasbon ini sudah dibayar?')"><i class="bx bx-dollar-circle me-1"></i>Tandai Sudah Dibayar</button>
            </form>
          @endif
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">Expense Declaration</h6>
          @if($advanceRequest->status === 'Approved' && $advanceRequest->payment_status === 'Paid' && auth()->user()->hasPermission('expense_declarations.create'))
            <a href="{{ route('erp.expense-declarations.create', ['advance_request_id' => $advanceRequest->id]) }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Expense Declaration</a>
          @endif
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr><th>Code</th><th>Expense Type</th><th>Work Id</th><th>Description</th><th class="text-end">Amount</th><th class="text-center">Status</th></tr>
            </thead>
            <tbody>
              @forelse($advanceRequest->expenseDeclarations as $decl)
                <tr>
                  <td><a href="{{ route('erp.expense-declarations.show', $decl) }}" class="fw-semibold">{{ $decl->code }}</a></td>
                  <td>{{ $decl->budgetPlanDetail?->expense_type }}</td>
                  <td>{{ $decl->work?->wid_code }}</td>
                  <td class="small">{{ $decl->description }}</td>
                  <td class="text-end">{{ number_format($decl->converted_amount_idr, 0, ',', '.') }}</td>
                  <td class="text-center"><span class="badge bg-label-{{ match($decl->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' } }}">{{ $decl->status }}</span></td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-3">
                  @if($advanceRequest->status === 'Approved' && $advanceRequest->payment_status === 'Paid')
                    Belum ada deklarasi untuk kasbon ini.
                  @else
                    Expense Declaration bisa dibuat setelah kasbon Approved dan sudah dibayar.
                  @endif
                </td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">Approval Timeline</h6>
          @if($advanceRequest->status === 'Draft')
            <form method="POST" action="{{ route('erp.advance-requests.submit', $advanceRequest) }}">
              @csrf
              <button class="btn btn-primary btn-sm"><i class="bx bx-paper-plane me-1"></i>Submit for Approval</button>
            </form>
          @endif
        </div>
        <div class="card-body">
          @forelse($advanceRequest->approvals as $step)
            @php
              $stepClass = match($step->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Pending' => 'warning', default => 'secondary' };
              $canAct = $step->status === 'Pending' && (auth()->user()->hasRole('superadmin') || $step->assigned_to_user_id == auth()->id());
            @endphp
            <div class="d-flex align-items-start gap-2 mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
              <span class="badge bg-{{ $stepClass }} rounded-circle p-2">{{ $step->level }}</span>
              <div class="flex-grow-1">
                <div class="fw-semibold">Level {{ $step->level }} &mdash; <span class="badge bg-label-{{ $stepClass }}">{{ $step->status }}</span> @if($step->is_override)<span class="badge bg-label-dark">Override</span>@endif</div>
                <div class="text-muted small">Assigned: {{ $step->assignedUser?->name ?? ($step->assignedRole?->name ?? '-') }}</div>
                @if($step->actualApprover)
                  <div class="text-muted small">Diproses oleh {{ $step->actualApprover->name }} &middot; {{ $step->approved_at?->format('d M Y H:i') }}</div>
                @endif
                @if($step->comments)
                  <div class="small fst-italic">"{{ $step->comments }}"</div>
                @endif
                @if($canAct)
                  <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-success btn-sm" onclick="openApprove({{ $step->id }})"><i class="bx bx-check"></i> Approve</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="openReject({{ $step->id }})"><i class="bx bx-x"></i> Reject</button>
                  </div>
                @endif
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4">Belum disubmit untuk approval.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
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
