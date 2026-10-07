@extends('layouts.home')

@section('title', 'Expense Declaration - ' . $expenseDeclaration->code)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  @php
    $detail = $expenseDeclaration->budgetPlanDetail;
    $budgetParent = $detail?->monthlyBudgetPlan?->annualBudgetPlan?->budgetParent;
    $statusClass = match($expenseDeclaration->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' };
  @endphp

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bx bx-receipt me-2 text-primary"></i>{{ $expenseDeclaration->code }}</h4>
      <div class="text-muted small">{{ $budgetParent->name ?? '-' }} &middot; {{ $detail?->expense_type }}</div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-label-{{ $statusClass }} fs-7">{{ $expenseDeclaration->status }}</span>
      <a href="{{ route('erp.expense-declarations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Detail Deklarasi</h6></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4 text-muted">Tipe</dt><dd class="col-sm-8"><span class="badge bg-label-{{ $expenseDeclaration->record_type === 'with_advance' ? 'info' : 'secondary' }}">{{ $expenseDeclaration->record_type === 'with_advance' ? 'With Advance' : 'Without Advance' }}</span></dd>
            <dt class="col-sm-4 text-muted">Budget Line</dt><dd class="col-sm-8">{{ $detail?->code }}</dd>
            @if($expenseDeclaration->advanceRequest)
              <dt class="col-sm-4 text-muted">Kasbon</dt><dd class="col-sm-8"><a href="{{ route('erp.advance-requests.show', $expenseDeclaration->advanceRequest) }}">{{ $expenseDeclaration->advanceRequest->code }}</a></dd>
            @endif
            @if($expenseDeclaration->po_number)
              <dt class="col-sm-4 text-muted">PO Number</dt><dd class="col-sm-8">{{ $expenseDeclaration->po_number }}</dd>
            @endif
            <dt class="col-sm-4 text-muted">Work (WID)</dt><dd class="col-sm-8">{{ $expenseDeclaration->work?->wid_code }} - {{ $expenseDeclaration->work?->name }}</dd>
            @if($expenseDeclaration->site_name)
              <dt class="col-sm-4 text-muted">Site</dt><dd class="col-sm-8">{{ $expenseDeclaration->site_name }}</dd>
            @endif
            <dt class="col-sm-4 text-muted">Requestor</dt><dd class="col-sm-8">{{ $expenseDeclaration->requestBy?->name }}</dd>
            <dt class="col-sm-4 text-muted">Request Date</dt><dd class="col-sm-8">{{ $expenseDeclaration->request_date?->format('d M Y') }}</dd>
            <dt class="col-sm-4 text-muted">Amount Declaration</dt><dd class="col-sm-8">{{ $expenseDeclaration->currency }} {{ number_format($expenseDeclaration->amount_declaration, 0, ',', '.') }}</dd>
            <dt class="col-sm-4 text-muted">Converted (IDR)</dt><dd class="col-sm-8 fw-bold fs-5 text-primary">{{ number_format($expenseDeclaration->converted_amount_idr, 0, ',', '.') }}</dd>
            @if($expenseDeclaration->description)
              <dt class="col-sm-4 text-muted">Description</dt><dd class="col-sm-8">{{ $expenseDeclaration->description }}</dd>
            @endif
          </dl>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">Approval</h6>
          @if($expenseDeclaration->status === 'Draft')
            <form method="POST" action="{{ route('erp.expense-declarations.submit', $expenseDeclaration) }}">
              @csrf
              <button class="btn btn-primary btn-sm"><i class="bx bx-paper-plane me-1"></i>Submit for Approval</button>
            </form>
          @endif
        </div>
        <div class="card-body">
          @forelse($expenseDeclaration->approvals as $step)
            @php
              $stepClass = match($step->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Pending' => 'warning', default => 'secondary' };
              $canAct = $step->status === 'Pending' && (auth()->user()->hasRole('superadmin') || $step->assigned_to_user_id == auth()->id());
            @endphp
            <div class="d-flex align-items-start gap-2 mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
              <span class="badge bg-{{ $stepClass }} rounded-circle p-2">{{ $step->level }}</span>
              <div class="flex-grow-1">
                <div class="fw-semibold">Level {{ $step->level }} (KAM) &mdash; <span class="badge bg-label-{{ $stepClass }}">{{ $step->status }}</span> @if($step->is_override)<span class="badge bg-label-dark">Override</span>@endif</div>
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
</script>
@endpush
@endsection
