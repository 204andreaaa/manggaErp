@extends('layouts.home')

@section('title', 'Advance Requests (Kasbon)')

@include('erp.budget.partials.sf-style')

@section('content')
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
      <div><div class="sf-object">Advance Requests</div><div class="sf-name">Kasbon</div></div>
    </div>
  </div>

  <div class="sf-card">
    <div class="sf-card-head">
      <h6>Advance Request</h6>
      <div class="sf-actions">
        <a href="{{ route('erp.advance-requests.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Advance Request</a>
      </div>
    </div>
    @if($advanceRequests->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr>
              <th>Expense Advance Request Id</th>
              <th>Account ID</th>
              <th>Advance Request Date</th>
              <th>Expense Plan id</th>
              <th>Expense Type</th>
              <th class="text-end">Number of Site</th>
              <th>Work Id</th>
              <th class="text-end">Amount per unit</th>
              <th class="text-end">Actual Amount</th>
              <th>Area</th>
              <th>Status</th>
              <th>Payment</th>
            </tr>
          </thead>
          <tbody>
            @foreach($advanceRequests as $adv)
              @php
                $m = $adv->budgetPlanDetail?->monthlyBudgetPlan;
                $statusClass = match($adv->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' };
              @endphp
              <tr>
                <td><a class="sf-link" href="{{ route('erp.advance-requests.show', $adv) }}">{{ $adv->code }}</a></td>
                <td>{{ $m?->annualBudgetPlan?->budgetParent?->name ?? '-' }}</td>
                <td>{{ $adv->advance_request_date?->format('Y/m/d') }}</td>
                <td>{{ $adv->budgetPlanDetail?->code }}</td>
                <td>{{ $adv->budgetPlanDetail?->expense_type }}</td>
                <td class="text-end">{{ $adv->number_of_site }}</td>
                <td>{{ $adv->work?->wid_code ?? '-' }}</td>
                <td class="text-end">IDR {{ number_format($adv->amount_per_unit, 0, ',', '.') }}</td>
                <td class="text-end">IDR {{ number_format($adv->actual_amount, 0, ',', '.') }}</td>
                <td>{{ $m?->area }}</td>
                <td><span class="badge bg-label-{{ $statusClass }}">{{ $adv->status }}</span></td>
                <td><span class="badge bg-label-{{ $adv->payment_status === 'Paid' ? 'success' : 'warning' }}">{{ $adv->payment_status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="px-3 py-2">{{ $advanceRequests->links() }}</div>
    @endif
  </div>
</div>

<script>
  // Live refresh: this is a server-rendered paginated list (no ajax source),
  // so the only way to reflect another user's change live is a full reload.
  window.addEventListener('erp-live-update', function (e) {
    if (e.detail.record_type === 'advance_request') {
      location.reload();
    }
  });
</script>
@endsection
