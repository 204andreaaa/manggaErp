@extends('layouts.home')

@section('title', 'Expense Declarations (Realisasi)')

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
        <h5 class="mb-1 fw-bold">Expense Declarations (Realisasi)</h5>
        <div class="text-muted small">Pertanggungjawaban biaya, dengan atau tanpa kasbon</div>
      </div>
      <a href="{{ route('erp.expense-declarations.create') }}" class="btn btn-primary btn-sm px-3"><i class="bx bx-plus me-1"></i>Buat Deklarasi Baru</a>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-uppercase fw-bold small">Code</th>
            <th class="text-uppercase fw-bold small text-center">Tipe</th>
            <th class="text-uppercase fw-bold small">Budget Line</th>
            <th class="text-uppercase fw-bold small">Kasbon</th>
            <th class="text-uppercase fw-bold small">Requestor</th>
            <th class="text-uppercase fw-bold small text-end">Amount (IDR)</th>
            <th class="text-uppercase fw-bold small text-center">Status</th>
            <th class="text-uppercase fw-bold small text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($declarations as $d)
            <tr>
              <td class="fw-bold">{{ $d->code }}</td>
              <td class="text-center"><span class="badge bg-label-{{ $d->record_type === 'with_advance' ? 'info' : 'secondary' }}">{{ $d->record_type === 'with_advance' ? 'With Advance' : 'Without Advance' }}</span></td>
              <td>{{ $d->budgetPlanDetail?->code }}<div class="text-muted small">{{ $d->budgetPlanDetail?->expense_type }}</div></td>
              <td>{{ $d->advanceRequest?->code ?? '-' }}</td>
              <td>{{ $d->requestBy?->name ?? '-' }}</td>
              <td class="text-end">{{ number_format($d->converted_amount_idr, 0, ',', '.') }}</td>
              <td class="text-center">
                @php $statusClass = match($d->status) { 'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'info', default => 'secondary' }; @endphp
                <span class="badge bg-label-{{ $statusClass }}">{{ $d->status }}</span>
              </td>
              <td class="text-center"><a href="{{ route('erp.expense-declarations.show', $d) }}" class="btn btn-sm btn-primary"><i class="bx bx-show"></i></a></td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada Expense Declaration</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $declarations->links() }}</div>
  </div>
</div>
@endsection
