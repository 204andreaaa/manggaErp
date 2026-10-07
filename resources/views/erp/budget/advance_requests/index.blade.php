@extends('layouts.home')

@section('title', 'Advance Requests (Kasbon)')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  <div class="card mb-3">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Advance Requests (Kasbon)</h5>
        <div class="text-muted small">Permintaan uang muka lapangan</div>
      </div>
      <a href="{{ route('erp.advance-requests.create') }}" class="btn btn-primary btn-sm px-3"><i class="bx bx-plus me-1"></i>Buat Kasbon Baru</a>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-uppercase fw-bold small">Code</th>
            <th class="text-uppercase fw-bold small">Budget Parent / Budget Line</th>
            <th class="text-uppercase fw-bold small">Work (WID)</th>
            <th class="text-uppercase fw-bold small">Requestor</th>
            <th class="text-uppercase fw-bold small text-end">Total</th>
            <th class="text-uppercase fw-bold small text-center">Status</th>
            <th class="text-uppercase fw-bold small text-center">Payment</th>
            <th class="text-uppercase fw-bold small text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($advanceRequests as $adv)
            <tr>
              <td class="fw-bold">{{ $adv->code }}</td>
              <td>
                {{ $adv->budgetPlanDetail?->monthlyBudgetPlan?->annualBudgetPlan?->budgetParent?->name ?? '-' }}
                <div class="text-muted small">{{ $adv->budgetPlanDetail?->expense_type }}</div>
              </td>
              <td>{{ $adv->work?->wid_code ?? '-' }}</td>
              <td>{{ $adv->requestBy?->name ?? '-' }}</td>
              <td class="text-end">{{ number_format($adv->total_amount_advance, 0, ',', '.') }}</td>
              <td class="text-center">
                @php $statusClass = match($adv->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' }; @endphp
                <span class="badge bg-label-{{ $statusClass }}">{{ $adv->status }}</span>
              </td>
              <td class="text-center"><span class="badge bg-label-{{ $adv->payment_status === 'Paid' ? 'success' : 'warning' }}">{{ $adv->payment_status }}</span></td>
              <td class="text-center"><a href="{{ route('erp.advance-requests.show', $adv) }}" class="btn btn-sm btn-primary"><i class="bx bx-show"></i></a></td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada Advance Request</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $advanceRequests->links() }}</div>
  </div>
</div>
@endsection
