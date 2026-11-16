@extends('layouts.home')

@section('title', 'Invoice - ' . $invoice->code)

@include('erp.hr.partials.style')

@section('content')
@php
  $inv = $invoice;
  $money = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
  $date = fn ($d) => $d?->format('d M Y') ?? '-';
  $canUpdate = auth()->user()->hasPermission('invoices.update');
  $paidPct = $inv->invoice_amount > 0 ? min(100, round($inv->amount_paid / $inv->invoice_amount * 100)) : 0;
  $statusIcon = match ($inv->status) { 'Paid' => 'bx-check-circle', 'Partial Paid' => 'bx-time-five', default => 'bx-file' };
  $cards = [
    ['bx-receipt', 'primary', 'Nilai Invoice', $money($inv->invoice_amount), $inv->details->count().' rincian WID'],
    ['bx-wallet', 'success', 'Sudah Dibayar', $money($inv->amount_paid), $inv->payments->count().' kali pembayaran'],
    ['bx-hourglass', $inv->outstanding > 0 ? ($inv->isOverdue() ? 'danger' : 'warning') : 'success', 'Belum Dibayar', $money($inv->outstanding),
      $inv->outstanding <= 0 ? 'Lunas' : ($inv->isOverdue() ? 'Lewat jatuh tempo '.$date($inv->due_date) : 'Jatuh tempo '.$date($inv->due_date))],
  ];
@endphp
<style>
  .inv-progress { height: 8px; border-radius: 8px; background: rgba(105,108,255,.12); overflow: hidden; }
  .inv-progress > div { height: 100%; border-radius: 8px; background: linear-gradient(90deg, #71dd37, #2fb344); }
  .inv-wid { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .55rem; border-radius: 8px; background: rgba(105,108,255,.1); font-weight: 600; font-size: .82rem; }
  .inv-pay { display: flex; gap: .85rem; padding: .8rem 0; border-bottom: 1px dashed #eceef1; }
  .inv-pay:last-child { border-bottom: 0; }
  .inv-pay-dot { flex: 0 0 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
  html[data-theme="dark"] .inv-pay { border-bottom-color: #444564; }
</style>
<div class="container-xxl flex-grow-1 container-p-y">

  @foreach(['success' => 'bx-check-circle', 'error' => 'bx-error-circle'] as $key => $icon)
    @if(session($key))
      <div class="alert alert-{{ $key === 'error' ? 'danger' : 'success' }} alert-dismissible d-flex align-items-center border-0 shadow-sm" role="alert">
        <i class="bx {{ $icon }} fs-4 me-2"></i>{{ session($key) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
  @endforeach
  @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="{{ route('erp.invoices.index') }}">Invoice</a></li>
      <li class="breadcrumb-item active fw-semibold">{{ $inv->code }}</li>
    </ol>
  </nav>

  {{-- Header --}}
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center gap-3">
        <div class="avatar avatar-lg flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $inv->statusColor() }}"><i class="bx {{ $statusIcon }} fs-3"></i></span></div>
        <div class="flex-grow-1 min-w-0">
          <small class="text-muted text-uppercase fw-semibold" style="letter-spacing:.05em">Invoice · Tagihan ke Customer</small>
          <div class="d-flex flex-wrap align-items-center gap-2">
            <h4 class="fw-bold mb-0 hr-code" style="font-size:1.4rem">{{ $inv->code }}</h4>
            <span class="badge bg-label-{{ $inv->statusColor() }}">{{ $inv->status }}</span>
            @if($inv->isOverdue())<span class="badge bg-label-danger rounded-pill"><i class="bx bx-alarm-exclamation me-1"></i>Lewat jatuh tempo</span>@endif
          </div>
          <div class="hr-meta mt-1">
            <span><i class="bx bx-hash"></i>{{ $inv->invoice_number }}</span>
            @if($inv->customer_name)<span><i class="bx bx-user-circle"></i>{{ $inv->customer_name }}</span>@endif
            @if($inv->company_name)<span><i class="bx bx-buildings"></i>{{ $inv->company_name }}</span>@endif
            @if($inv->top)<span><i class="bx bx-calendar-event"></i>{{ $inv->top }}</span>@endif
          </div>
        </div>
        <div class="d-flex gap-2">
          @if($canUpdate)
            <a href="{{ route('erp.invoices.edit', $inv) }}" class="btn btn-primary btn-sm"><i class="bx bx-edit-alt me-1"></i>Edit</a>
          @endif
          @if(auth()->user()->hasPermission('invoices.delete') && $inv->payments->isEmpty())
            <form method="POST" action="{{ route('erp.invoices.destroy', $inv) }}" onsubmit="return confirm('Hapus invoice {{ $inv->code }} beserta rinciannya?')">
              @csrf @method('DELETE')
              <button class="btn btn-label-danger btn-sm"><i class="bx bx-trash me-1"></i>Hapus</button>
            </form>
          @endif
        </div>
      </div>
      <div class="mt-3">
        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Progres pembayaran</span><span class="fw-semibold">{{ $paidPct }}%</span></div>
        <div class="inv-progress"><div style="width: {{ $paidPct }}%"></div></div>
      </div>
    </div>
  </div>

  {{-- Amounts --}}
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

  <div class="row g-4">
    {{-- Info --}}
    <div class="col-lg-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header pb-2">
          <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-info"><i class="bx bx-info-circle"></i></span></span>Informasi</h6>
        </div>
        <div class="card-body pt-0">
          <ul class="hr-info-list">
            <li><span class="hr-info-icon bg-label-primary"><i class="bx bx-calendar"></i></span><div><div class="hr-info-label">Tanggal Invoice</div><div class="hr-info-value">{{ $date($inv->invoice_date) }}</div></div></li>
            <li><span class="hr-info-icon bg-label-{{ $inv->isOverdue() ? 'danger' : 'warning' }}"><i class="bx bx-alarm"></i></span><div><div class="hr-info-label">Jatuh Tempo</div><div class="hr-info-value">{{ $date($inv->due_date) }}</div></div></li>
            <li><span class="hr-info-icon bg-label-success"><i class="bx bx-calendar-check"></i></span><div><div class="hr-info-label">Bayar Pertama / Lunas</div><div class="hr-info-value">{{ $date($inv->partial_payment_date) }} · {{ $date($inv->final_payment_date) }}</div></div></li>
            <li><span class="hr-info-icon bg-label-secondary"><i class="bx bx-detail"></i></span><div class="min-w-0"><div class="hr-info-label">Remarks</div><div class="hr-info-value fw-normal">{!! nl2br(e($inv->remarks ?: '-')) !!}</div></div></li>
            <li><span class="hr-info-icon bg-label-secondary"><i class="bx bx-user"></i></span><div><div class="hr-info-label">Dibuat oleh</div><div class="hr-info-value">{{ $inv->createdBy?->name ?? '-' }}</div><small class="text-muted">{{ $inv->created_at?->format('d M Y H:i') }}</small></div></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      {{-- Lines per WID --}}
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center pb-2">
          <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-list-ul"></i></span></span>Rincian per WID</h6>
          @if($canUpdate)
            <button type="button" class="btn btn-sm btn-label-primary" data-bs-toggle="modal" data-bs-target="#detailModal"><i class="bx bx-plus me-1"></i>Tambah Rincian</button>
          @endif
        </div>
        @if($inv->details->isEmpty())
          @include('erp.hr.partials.empty', ['icon' => 'bx-list-plus', 'title' => 'Belum ada rincian', 'text' =>'Belum ada rincian. Nilai invoice dihitung dari rincian per WID.'])
        @else
          <div class="table-responsive">
            <table class="table table-hover hr-table mb-0">
              <thead><tr><th>WID</th><th>Terms</th><th>PO / Dokumen</th><th class="text-end">Nominal</th><th></th></tr></thead>
              <tbody>
                @foreach($inv->details as $d)
                  <tr>
                    <td>
                      @if($d->workItem)
                        <a href="{{ route('erp.work-items.show', $d->workItem) }}" class="inv-wid"><i class="bx bx-hard-hat"></i>{{ $d->workItem->wid_code }}</a>
                        <div class="small text-muted mt-1">{{ $d->workItem->name }}</div>
                      @else <span class="text-muted">Tanpa WID</span> @endif
                      @if($d->project_code)<div class="small text-muted">Project {{ $d->project_code }}</div>@endif
                    </td>
                    <td>{{ $d->terms ?: '-' }}<div class="small text-muted hr-code">{{ $d->code }}</div></td>
                    <td class="small">PO {{ $date($d->po_date) }}<div class="text-muted">Dok. {{ $date($d->received_doc_date) }}</div></td>
                    <td class="text-end fw-semibold text-nowrap">{{ $money($d->amount) }}</td>
                    <td class="text-end">
                      @if($canUpdate)
                        <form method="POST" action="{{ route('erp.invoice-details.destroy', $d) }}" onsubmit="return confirm('Hapus rincian {{ $d->code }}?')">
                          @csrf @method('DELETE')
                          <button class="btn btn-sm btn-icon btn-text-danger" title="Hapus"><i class="bx bx-trash"></i></button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end text-nowrap">{{ $money($inv->invoice_amount) }}</th><th></th></tr></tfoot>
            </table>
          </div>
        @endif
      </div>

      {{-- Payments --}}
      <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center pb-2">
          <h6 class="hr-section-title"><span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-success"><i class="bx bx-wallet"></i></span></span>Pembayaran Masuk</h6>
          @if($canUpdate && $inv->outstanding > 0)
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="bx bx-plus me-1"></i>Catat Pembayaran</button>
          @endif
        </div>
        <div class="card-body pt-0">
          @forelse($inv->payments->sortBy('payment_date') as $p)
            <div class="inv-pay">
              <span class="inv-pay-dot bg-label-success"><i class="bx bx-check"></i></span>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between gap-2">
                  <span class="fw-semibold">{{ $money($p->amount) }}</span>
                  <span class="small text-muted text-nowrap">{{ $date($p->payment_date) }}</span>
                </div>
                <div class="small text-muted">
                  {{ $p->method ?: 'Metode tidak diisi' }}@if($p->reference) · Ref {{ $p->reference }}@endif · dicatat {{ $p->createdBy?->name ?? '-' }}
                </div>
                @if($p->note)<div class="small">{{ $p->note }}</div>@endif
              </div>
              @if($canUpdate)
                <form method="POST" action="{{ route('erp.invoice-payments.destroy', $p) }}" onsubmit="return confirm('Hapus pembayaran ini? Status invoice dihitung ulang.')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-icon btn-text-danger" title="Hapus"><i class="bx bx-trash"></i></button>
                </form>
              @endif
            </div>
          @empty
            @include('erp.hr.partials.empty', ['icon' => 'bx-wallet', 'title' => 'Belum ada pembayaran', 'text' =>'Belum ada pembayaran dari customer.'])
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

@if($canUpdate)
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="POST" action="{{ route('erp.invoices.details.store', $inv) }}" class="modal-content">
      @csrf
      <div class="modal-header"><h5 class="modal-title"><i class="bx bx-list-plus me-1 text-primary"></i>Tambah Rincian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-7">
          <label class="form-label">WID</label>
          <select name="work_item_id" class="form-select">
            <option value="">-- Tanpa WID --</option>
            @foreach($workItems as $w)
              <option value="{{ $w->id }}">{{ $w->wid_code }} — {{ $w->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label">Nominal <span class="text-danger">*</span></label>
          <div class="input-group"><span class="input-group-text">Rp</span><input type="number" name="amount" class="form-control" min="1" step="0.01" required></div>
        </div>
        <div class="col-md-4"><label class="form-label">Terms</label><input type="text" name="terms" class="form-control" maxlength="100" placeholder="mis. DP I"></div>
        <div class="col-md-4"><label class="form-label">Project Code</label><input type="text" name="project_code" class="form-control" maxlength="80"></div>
        <div class="col-md-4"><label class="form-label">Tanggal PO Customer</label><input type="date" name="po_date" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Dokumen Diterima</label><input type="date" name="received_doc_date" class="form-control"></div>
        <div class="col-md-8"><label class="form-label">Keterangan</label><input type="text" name="description" class="form-control" maxlength="500"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="{{ route('erp.invoices.payments.store', $inv) }}" class="modal-content">
      @csrf
      <div class="modal-header"><h5 class="modal-title"><i class="bx bx-wallet me-1 text-success"></i>Catat Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><div class="alert alert-info small mb-0 border-0"><i class="bx bx-info-circle me-1"></i>Sisa tagihan <b>{{ $money($inv->outstanding) }}</b></div></div>
        <div class="col-md-6"><label class="form-label">Tanggal Bayar <span class="text-danger">*</span></label><input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
        <div class="col-md-6">
          <label class="form-label">Nominal <span class="text-danger">*</span></label>
          <div class="input-group"><span class="input-group-text">Rp</span><input type="number" name="amount" class="form-control" min="1" step="0.01" max="{{ (float) $inv->outstanding }}" value="{{ (float) $inv->outstanding }}" required></div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Metode</label>
          <select name="method" class="form-select"><option value="">--</option>@foreach(\App\Models\Erp\Invoice::PAYMENT_METHODS as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select>
        </div>
        <div class="col-md-6"><label class="form-label">Referensi</label><input type="text" name="reference" class="form-control" maxlength="100" placeholder="no. transfer / giro"></div>
        <div class="col-12"><label class="form-label">Catatan</label><input type="text" name="note" class="form-control" maxlength="500"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan</button></div>
    </form>
  </div>
</div>
@endif
@endsection
