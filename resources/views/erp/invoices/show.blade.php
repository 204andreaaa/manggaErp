@extends('layouts.home')

@section('title', 'Invoice - ' . $invoice->code)

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
  $canUpdate = auth()->user()->hasPermission('invoices.update');
  $date = fn ($d) => e($d?->format('Y/m/d') ?? '');

  $left = [
    ['Invoice Id', e($invoice->code)],
    ['Invoice Number', e($invoice->invoice_number)],
    ['Invoice Date', $date($invoice->invoice_date)],
    ['Company', e($invoice->company_name ?? '')],
    ['Customer', e($invoice->customer_name ?? '')],
    ['Invoice Status', '<span class="badge bg-label-'.$invoice->statusColor().'">'.e($invoice->status).'</span>'],
    ['Invoice Amount', '<b>'.e($money($invoice->invoice_amount)).'</b>'],
    ['Amount Payment Detail', e($money($invoice->amount_paid))],
    ['Belum Dibayar', '<b class="'.($invoice->outstanding > 0 ? 'text-warning' : 'text-success').'">'.e($money($invoice->outstanding)).'</b>'],
  ];
  $right = [
    ['TOP', e($invoice->top ?? '')],
    ['Remarks', nl2br(e($invoice->remarks ?? ''))],
    ['Invoice Due Date', $date($invoice->due_date).($invoice->isOverdue() ? ' <span class="badge bg-label-danger">Lewat jatuh tempo</span>' : '')],
    ['Partial Payment Date', $date($invoice->partial_payment_date)],
    ['Final Payment Date', $date($invoice->final_payment_date)],
    ['Created By', e($invoice->createdBy?->name ?? '-').' <span class="text-muted small">'.e($invoice->created_at?->format('Y/m/d H:i') ?? '').'</span>'],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #5b9bd2">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-receipt"></i></span>
      <div><div class="sf-object">Invoice</div><div class="sf-name">{{ $invoice->code }}</div></div>
    </div>
    <a href="{{ route('erp.invoices.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back to List</a>
  </div>

  <div class="sf-card">
    <div class="sf-card-head">
      <h6>Invoice Detail</h6>
      <div class="sf-actions">
        @if($canUpdate)
          <a href="{{ route('erp.invoices.edit', $invoice) }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-edit me-1"></i>Edit</a>
        @endif
        @if(auth()->user()->hasPermission('invoices.delete') && $invoice->payments->isEmpty())
          <form method="POST" action="{{ route('erp.invoices.destroy', $invoice) }}" onsubmit="return confirm('Hapus invoice {{ $invoice->code }} beserta rinciannya?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm"><i class="bx bx-trash me-1"></i>Delete</button>
          </form>
        @endif
      </div>
    </div>
    @include('erp.budget.partials.sf-fields', ['left' => $left, 'right' => $right])
  </div>

  {{-- Lines per WID --}}
  <div class="sf-card" style="--sf-accent: #6fb1ad">
    <div class="sf-card-head">
      <h6>Invoices Details <span class="text-muted small">[{{ $invoice->details->count() }}]</span></h6>
      <div class="sf-actions">
        @if($canUpdate)
          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal"><i class="bx bx-plus me-1"></i>New Invoice Detail</button>
        @endif
      </div>
    </div>
    @if($invoice->details->isEmpty())
      <div class="sf-empty">No records to display <span class="ms-1">(nilai invoice dihitung dari rincian ini)</span></div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr>
              <th>Invoice Detail Id</th><th>Work Id</th><th>Project Code</th><th>Terms</th>
              <th>PO Date</th><th>Received Doc</th><th class="text-end">Amount</th><th>Created By</th><th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($invoice->details as $d)
              <tr>
                <td>{{ $d->code }}</td>
                <td>
                  @if($d->workItem)
                    <a class="sf-link" href="{{ route('erp.work-items.show', $d->workItem) }}">{{ $d->workItem->wid_code }}</a>
                    <div class="small text-muted">{{ $d->workItem->name }}</div>
                  @else - @endif
                </td>
                <td>{{ $d->project_code ?? '-' }}</td>
                <td>{{ $d->terms ?? '-' }}</td>
                <td>{{ $d->po_date?->format('Y/m/d') ?? '-' }}</td>
                <td>{{ $d->received_doc_date?->format('Y/m/d') ?? '-' }}</td>
                <td class="text-end">{{ $money($d->amount) }}</td>
                <td>{{ $d->createdBy?->name ?? '-' }} <div class="small text-muted">{{ $d->created_at?->format('Y/m/d H:i') }}</div></td>
                <td class="text-end">
                  @if($canUpdate)
                    <form method="POST" action="{{ route('erp.invoice-details.destroy', $d) }}" onsubmit="return confirm('Hapus rincian {{ $d->code }}?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-icon btn-outline-danger" title="Hapus"><i class="bx bx-trash"></i></button>
                    </form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Payments received --}}
  <div class="sf-card" style="--sf-accent: #71c46f">
    <div class="sf-card-head">
      <h6>Payment Detail <span class="text-muted small">[{{ $invoice->payments->count() }}]</span></h6>
      <div class="sf-actions">
        @if($canUpdate && $invoice->outstanding > 0)
          <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="bx bx-plus me-1"></i>New Payment Detail</button>
        @endif
      </div>
    </div>
    @if($invoice->payments->isEmpty())
      <div class="sf-empty">No records to display</div>
    @else
      <div class="table-responsive">
        <table class="sf-table">
          <thead>
            <tr><th>Tanggal Bayar</th><th class="text-end">Amount</th><th>Metode</th><th>Referensi</th><th>Catatan</th><th>Dicatat Oleh</th><th></th></tr>
          </thead>
          <tbody>
            @foreach($invoice->payments->sortBy('payment_date') as $p)
              <tr>
                <td>{{ $p->payment_date?->format('Y/m/d') }}</td>
                <td class="text-end">{{ $money($p->amount) }}</td>
                <td>{{ $p->method ?? '-' }}</td>
                <td>{{ $p->reference ?? '-' }}</td>
                <td>{{ $p->note ?? '-' }}</td>
                <td>{{ $p->createdBy?->name ?? '-' }}</td>
                <td class="text-end">
                  @if($canUpdate)
                    <form method="POST" action="{{ route('erp.invoice-payments.destroy', $p) }}" onsubmit="return confirm('Hapus pembayaran ini? Status invoice akan dihitung ulang.')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-icon btn-outline-danger" title="Hapus"><i class="bx bx-trash"></i></button>
                    </form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

@if($canUpdate)
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="POST" action="{{ route('erp.invoices.details.store', $invoice) }}" class="modal-content">
      @csrf
      <div class="modal-header"><h5 class="modal-title">New Invoice Detail</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-6">
          <label class="form-label">Work Id (WID)</label>
          <select name="work_item_id" class="form-select">
            <option value="">-- Tanpa WID --</option>
            @foreach($workItems as $w)
              <option value="{{ $w->id }}">{{ $w->wid_code }} — {{ $w->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Amount <span class="text-danger">*</span></label>
          <input type="number" name="amount" class="form-control" min="1" step="0.01" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Terms</label>
          <input type="text" name="terms" class="form-control" maxlength="100" placeholder="mis. DP I">
        </div>
        <div class="col-md-4">
          <label class="form-label">Project Code</label>
          <input type="text" name="project_code" class="form-control" maxlength="80">
        </div>
        <div class="col-md-4">
          <label class="form-label">PO Date (customer)</label>
          <input type="date" name="po_date" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Received Doc</label>
          <input type="date" name="received_doc_date" class="form-control">
        </div>
        <div class="col-md-8">
          <label class="form-label">Keterangan</label>
          <input type="text" name="description" class="form-control" maxlength="500">
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="{{ route('erp.invoices.payments.store', $invoice) }}" class="modal-content">
      @csrf
      <div class="modal-header"><h5 class="modal-title">New Payment Detail</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><div class="alert alert-info small mb-0">Sisa tagihan: <b>{{ $money($invoice->outstanding) }}</b></div></div>
        <div class="col-md-6">
          <label class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
          <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Amount <span class="text-danger">*</span></label>
          <input type="number" name="amount" class="form-control" min="1" step="0.01" max="{{ (float) $invoice->outstanding }}" value="{{ (float) $invoice->outstanding }}" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Metode</label>
          <select name="method" class="form-select">
            <option value="">--</option>
            @foreach(\App\Models\Erp\Invoice::PAYMENT_METHODS as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Referensi</label>
          <input type="text" name="reference" class="form-control" maxlength="100" placeholder="no. transfer / giro">
        </div>
        <div class="col-12">
          <label class="form-label">Catatan</label>
          <input type="text" name="note" class="form-control" maxlength="500">
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Simpan</button></div>
    </form>
  </div>
</div>
@endif
@endsection
