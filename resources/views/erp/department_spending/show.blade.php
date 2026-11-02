@extends('layouts.home')

@section('title', 'Pengeluaran - ' . $department->name)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $statusBadge = fn ($s) => match ($s) {
      'Approved', 'Completed', 'Paid' => 'bg-label-success',
      'Rejected', 'Cancelled' => 'bg-label-danger',
      'Submitted', 'Pending' => 'bg-label-warning',
      default => 'bg-label-secondary',
  };
  $left = [
    ['Departemen', '<b>'.e($department->name).'</b>'],
    ['Kode', e($department->code)],
    ['Periode', e($year === 'all' ? 'Semua Tahun' : 'Tahun '.$year)],
  ];
  $right = [
    ['Nilai RF Approved', '<b>'.e($money($rfApprovedTotal)).'</b>'],
    ['Terpakai (PO Approved/Completed)', '<b class="text-primary">'.e($money($poSpentTotal)).'</b>'],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #3a8ee0">

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-buildings"></i></span>
      <div><div class="sf-object">Pengeluaran Departemen</div><div class="sf-name">{{ $department->name }}</div></div>
    </div>
    <div class="d-flex align-items-center gap-2">
      @include('erp.department_spending.partials.year-filter', ['action' => route('erp.department-spending.show', $department)])
      <a href="{{ route('erp.department-spending.index', ['year' => $year]) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
    </div>
  </div>

  @include('erp.budget.partials.jump-bar', ['sections' => [
    'rf' => ['Request Forms', $requestForms->count()],
    'po' => ['Purchase Orders', $purchaseOrders->count()],
  ]])

  <div class="sf-card">
    <div class="sf-card-head"><h6>Ringkasan</h6></div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  <div class="sf-card" id="rel-rf" style="--sf-accent: #9b6bd3">
    <div class="sf-card-head"><h6><i class="bx bx-file me-1"></i>Request Forms (Non Project)</h6></div>
    @if($requestForms->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr><th>RF No</th><th>Date</th><th>Requestor</th><th>Remark</th><th class="text-end">Total Amount</th><th>Status</th></tr>
          </thead>
          <tbody>
            @foreach($requestForms as $rf)
              <tr>
                <td><a class="sf-link" href="{{ route('erp.request-form.show', $rf) }}">{{ $rf->rf_no }}</a></td>
                <td>{{ $rf->rf_date?->format('Y/m/d') }}</td>
                <td>{{ $rf->requestor ?: '-' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($rf->remark ?? '-', 60) }}</td>
                <td class="text-end">{{ $money($rf->total_amount) }}</td>
                <td><span class="badge {{ $statusBadge($rf->status) }}">{{ $rf->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <div class="sf-card" id="rel-po" style="--sf-accent: #3a8ee0">
    <div class="sf-card-head"><h6><i class="bx bx-cart me-1"></i>Purchase Orders</h6></div>
    @if($purchaseOrders->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr><th>PO No</th><th>Date</th><th>Supplier</th><th>RF No</th><th class="text-end">PO Total (incl. tax)</th><th>Status</th></tr>
          </thead>
          <tbody>
            @foreach($purchaseOrders as $po)
              <tr>
                <td><a class="sf-link" href="{{ route('erp.purchase-orders.show', $po) }}">{{ $po->po_no }}</a></td>
                <td>{{ $po->date ? \Carbon\Carbon::parse($po->date)->format('Y/m/d') : '-' }}</td>
                <td>{{ $po->supplier->name ?? '-' }}</td>
                <td>@if($po->requestForm)<a class="sf-link" href="{{ route('erp.request-form.show', $po->requestForm) }}">{{ $po->requestForm->rf_no }}</a>@else - @endif</td>
                <td class="text-end">{{ $money($po->total_po_amount_with_tax) }}</td>
                <td><span class="badge {{ $statusBadge($po->status) }}">{{ $po->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

@push('scripts')
<script>
  // Another user changing an RF or PO refreshes the page.
  window.addEventListener('erp-live-update', function (e) {
    if (['request_form', 'purchase_order'].includes(e.detail.record_type)) {
      location.reload();
    }
  });
</script>
@endpush
@endsection
