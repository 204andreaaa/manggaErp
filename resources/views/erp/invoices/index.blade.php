@extends('layouts.home')

@section('title', 'Invoices')

@include('erp.budget.partials.sf-style')

@section('content')
@php $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.'); @endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #5b9bd2">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-receipt"></i></span>
      <div><div class="sf-object">Invoices</div><div class="sf-name">Tagihan ke Customer</div></div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card card-border-shadow-primary h-100"><div class="card-body py-3">
      <div class="small text-muted">Total ditagih</div><div class="fs-5 fw-bold">{{ $money($totals->amount ?? 0) }}</div></div></div></div>
    <div class="col-md-4"><div class="card card-border-shadow-success h-100"><div class="card-body py-3">
      <div class="small text-muted">Sudah dibayar</div><div class="fs-5 fw-bold text-success">{{ $money($totals->paid ?? 0) }}</div></div></div></div>
    <div class="col-md-4"><div class="card card-border-shadow-warning h-100"><div class="card-body py-3">
      <div class="small text-muted">Belum dibayar</div><div class="fs-5 fw-bold text-warning">{{ $money($totals->outstanding ?? 0) }}</div></div></div></div>
  </div>

  <div class="sf-card">
    <div class="sf-card-head">
      <h6>Invoices</h6>
      <div class="sf-actions">
        @if(auth()->user()->hasPermission('invoices.create'))
          <a href="{{ route('erp.invoices.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>New Invoice</a>
        @endif
      </div>
    </div>

    <form method="GET" class="d-flex flex-wrap gap-2 px-3 py-2 border-bottom">
      <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="max-width:260px" placeholder="Cari nomor invoice, customer, TOP...">
      <select name="company_id" class="form-select form-select-sm" style="max-width:200px">
        <option value="">Semua perusahaan</option>
        @foreach($companies as $c)
          <option value="{{ $c->id }}" @selected((string) request('company_id') === (string) $c->id)>{{ $c->short_name ?: $c->name }}</option>
        @endforeach
      </select>
      <select name="status" class="form-select form-select-sm" style="max-width:170px">
        <option value="">Semua status</option>
        @foreach(\App\Models\Erp\Invoice::STATUSES as $s)
          <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
        @endforeach
      </select>
      <button class="btn btn-sm btn-outline-primary"><i class="bx bx-filter-alt me-1"></i>Filter</button>
      @if(request()->hasAny(['q', 'company_id', 'status']))
        <a href="{{ route('erp.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
      @endif
    </form>

    @if($invoices->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr>
              <th>Invoice Id</th>
              <th>Invoice Number</th>
              <th>Company</th>
              <th>Customer</th>
              <th>Invoice Date</th>
              <th>Due Date</th>
              <th>Status</th>
              <th>TOP</th>
              <th class="text-end">Invoice Amount</th>
              <th class="text-end">Belum Dibayar</th>
            </tr>
          </thead>
          <tbody>
            @foreach($invoices as $inv)
              <tr>
                <td><a class="sf-link" href="{{ route('erp.invoices.show', $inv) }}">{{ $inv->code }}</a></td>
                <td>{{ $inv->invoice_number }}</td>
                <td>{{ $inv->company_name ?? '-' }}</td>
                <td>{{ $inv->customer_name ?? '-' }}</td>
                <td>{{ $inv->invoice_date?->format('Y/m/d') }}</td>
                <td class="{{ $inv->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ $inv->due_date?->format('Y/m/d') ?? '-' }}</td>
                <td><span class="badge bg-label-{{ $inv->statusColor() }}">{{ $inv->status }}</span></td>
                <td>{{ $inv->top ?? '-' }}</td>
                <td class="text-end">{{ $money($inv->invoice_amount) }}</td>
                <td class="text-end">{{ $money($inv->outstanding) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="px-3 py-2">{{ $invoices->links() }}</div>
    @endif
  </div>
</div>

<script>
  window.addEventListener('erp-live-update', function (e) {
    if (e.detail.record_type === 'invoice') location.reload();
  });
</script>
@endsection
