@extends('layouts.home')

@section('title', 'Invoice')

@include('erp.hr.partials.style')

@section('content')
@php
  $money = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
  $billed = (float) ($totals->amount ?? 0);
  $cards = [
    ['bx-receipt', 'primary', 'Total Ditagih', $money($billed), 'Semua invoice'],
    ['bx-wallet', 'success', 'Sudah Dibayar', $money($totals->paid ?? 0), $billed > 0 ? round(($totals->paid ?? 0) / $billed * 100).'% dari tagihan' : '-'],
    ['bx-hourglass', 'warning', 'Belum Dibayar', $money($totals->outstanding ?? 0), 'Sisa piutang customer'],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y">

  @foreach(['success' => 'bx-check-circle', 'error' => 'bx-error-circle'] as $key => $icon)
    @if(session($key))
      <div class="alert alert-{{ $key === 'error' ? 'danger' : 'success' }} alert-dismissible d-flex align-items-center border-0 shadow-sm" role="alert">
        <i class="bx {{ $icon }} fs-4 me-2"></i>{{ session($key) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
  @endforeach

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="avatar avatar-lg"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-receipt fs-3"></i></span></div>
      <div>
        <h4 class="fw-bold mb-0">Invoice</h4>
        <small class="text-muted">Tagihan ke customer dan pembayarannya</small>
      </div>
    </div>
    @if(auth()->user()->hasPermission('invoices.create'))
      <a href="{{ route('erp.invoices.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Invoice Baru</a>
    @endif
  </div>

  <div class="row g-3 mb-4">
    @foreach($cards as [$icon, $color, $label, $value, $note])
      <div class="col-md-4">
        <div class="card hr-stat card-border-shadow-{{ $color }} shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
              <div class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }}"></i></span></div>
              <span class="hr-stat-label">{{ $label }}</span>
            </div>
            <p class="hr-stat-value text-{{ $color }}">{{ $value }}</p>
            <small class="text-muted">{{ $note }}</small>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header">
      <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
        <div class="input-group input-group-sm" style="max-width:300px">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nomor, customer, TOP...">
        </div>
        <select name="company_id" class="form-select form-select-sm" style="max-width:190px" onchange="this.form.submit()">
          <option value="">Semua perusahaan</option>
          @foreach($companies as $c)
            <option value="{{ $c->id }}" @selected((string) request('company_id') === (string) $c->id)>{{ $c->short_name ?: $c->name }}</option>
          @endforeach
        </select>
        <div class="btn-group btn-group-sm" role="group">
          @foreach(['' => 'Semua', 'New' => 'New', 'Partial Paid' => 'Partial', 'Paid' => 'Paid'] as $value => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $value ?: null, 'page' => null]) }}"
               class="btn {{ (string) request('status') === $value ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
          @endforeach
        </div>
        @if(request()->hasAny(['q', 'company_id', 'status']))
          <a href="{{ route('erp.invoices.index') }}" class="btn btn-sm btn-text-secondary"><i class="bx bx-x me-1"></i>Reset</a>
        @endif
      </form>
    </div>

    @if($invoices->isEmpty())
      @include('erp.hr.partials.empty', ['icon' => 'bx-receipt', 'title' => 'Belum ada invoice', 'text' => 'Invoice yang dibuat akan muncul di sini.'])
    @else
      <div class="table-responsive">
        <table class="table table-hover hr-table mb-0">
          <thead>
            <tr><th>Invoice</th><th>Customer</th><th>Tanggal</th><th>Status</th><th class="text-end">Nilai</th><th style="min-width:170px">Pembayaran</th></tr>
          </thead>
          <tbody>
            @foreach($invoices as $inv)
              @php $pct = $inv->invoice_amount > 0 ? min(100, round($inv->amount_paid / $inv->invoice_amount * 100)) : 0; @endphp
              <tr style="cursor:pointer" onclick="window.location='{{ route('erp.invoices.show', $inv) }}'">
                <td>
                  <a href="{{ route('erp.invoices.show', $inv) }}" class="fw-semibold hr-code">{{ $inv->code }}</a>
                  <div class="small text-muted text-truncate" style="max-width:260px">{{ $inv->invoice_number }}</div>
                </td>
                <td>{{ $inv->customer_name ?: '-' }}<div class="small text-muted">{{ $inv->company_name }}@if($inv->top) · {{ $inv->top }}@endif</div></td>
                <td class="text-nowrap">{{ $inv->invoice_date?->format('d M Y') }}
                  <div class="small {{ $inv->isOverdue() ? 'text-danger fw-semibold' : 'text-muted' }}">Jatuh tempo {{ $inv->due_date?->format('d M Y') ?? '-' }}</div></td>
                <td><span class="badge bg-label-{{ $inv->statusColor() }}">{{ $inv->status }}</span></td>
                <td class="text-end fw-semibold text-nowrap">{{ $money($inv->invoice_amount) }}</td>
                <td>
                  <div class="progress" style="height:6px"><div class="progress-bar bg-success" style="width: {{ $pct }}%"></div></div>
                  <div class="small text-muted mt-1">{{ $pct }}% · sisa {{ $money($inv->outstanding) }}</div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer py-2">{{ $invoices->links() }}</div>
    @endif
  </div>
</div>

<script>
  window.addEventListener('erp-live-update', function (e) {
    if (e.detail.record_type === 'invoice') location.reload();
  });
</script>
@endsection
