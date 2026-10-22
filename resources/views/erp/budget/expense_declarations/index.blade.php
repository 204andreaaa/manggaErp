@extends('layouts.home')

@section('title', 'Expense Declarations')

@include('erp.budget.partials.sf-style')

@section('content')
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
      <div><div class="sf-object">Expense Declarations</div><div class="sf-name">Deklarasi Pengeluaran</div></div>
    </div>
  </div>

  <div class="sf-card">
    <div class="sf-card-head">
      <h6>Expense Declaration</h6>
      <div class="sf-actions">
        <a href="{{ route('erp.expense-declarations.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Expense Declaration</a>
      </div>
    </div>
    @if($declarations->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr>
              <th>Expense Declaration Detail Id</th>
              <th>Record Type</th>
              <th>Advance Request Id</th>
              <th>Expense Type</th>
              <th>Description</th>
              <th>PO Number</th>
              <th>Work Id</th>
              <th>Area</th>
              <th class="text-end">Amount declaration</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($declarations as $d)
              @php $statusClass = match($d->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' }; @endphp
              <tr>
                <td><a class="sf-link" href="{{ route('erp.expense-declarations.show', $d) }}">{{ $d->code }}</a></td>
                <td>{{ $d->record_type === 'with_advance' ? 'with advance' : 'without advance' }}</td>
                <td>@if($d->advanceRequest)<a class="sf-link" href="{{ route('erp.advance-requests.show', $d->advanceRequest) }}">{{ $d->advanceRequest->code }}</a>@endif</td>
                <td>{{ $d->expense_type ?? $d->budgetPlanDetail?->expense_type }}</td>
                <td class="small">{{ $d->description }}</td>
                <td>{{ $d->po_number }}</td>
                <td>{{ $d->work?->wid_code }}</td>
                <td>{{ $d->budgetPlanDetail?->monthlyBudgetPlan?->area }}</td>
                <td class="text-end">IDR {{ number_format($d->converted_amount_idr, 0, ',', '.') }}</td>
                <td><span class="badge bg-label-{{ $statusClass }}">{{ $d->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="px-3 py-2">{{ $declarations->links() }}</div>
    @endif
  </div>
</div>

<script>
  // Live refresh: server-rendered paginated list, no ajax source — a full
  // reload is the only way to reflect another user's change live.
  window.addEventListener('erp-live-update', function (e) {
    if (e.detail.record_type === 'expense_declaration') {
      location.reload();
    }
  });
</script>
@endsection
