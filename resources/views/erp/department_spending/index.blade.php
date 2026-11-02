@extends('layouts.home')

@section('title', 'Pengeluaran Departemen')

@include('erp.budget.partials.sf-style')

@section('content')
@php
  $money = fn ($v) => 'IDR '.number_format((float) $v, 0, ',', '.');
@endphp
<div class="container-xxl flex-grow-1 container-p-y sf-wrap" style="--sf-accent: #3a8ee0">

  <div class="sf-head">
    <div class="d-flex align-items-center">
      <span class="sf-icon"><i class="bx bx-buildings"></i></span>
      <div><div class="sf-object">RF / PO Non Project</div><div class="sf-name">Pengeluaran per Departemen</div></div>
    </div>
    @include('erp.department_spending.partials.year-filter', ['action' => route('erp.department-spending.index')])
  </div>

  @if($unassigned > 0)
    <div class="alert alert-warning small">{{ $unassigned }} RF Non Project belum punya departemen (dibuat sebelum fitur ini dan data departemen pembuatnya belum lengkap), jadi belum masuk hitungan.</div>
  @endif

  <div class="sf-card">
    <div class="sf-card-head"><h6><i class="bx bx-bar-chart-alt-2 me-1"></i>Ringkasan {{ $year === 'all' ? 'Semua Tahun' : 'Tahun '.$year }}</h6></div>
    <div class="table-responsive">
      <table class="sf-table">
        <thead>
          <tr>
            <th>Departemen</th>
            <th class="text-end">RF Approved</th>
            <th class="text-end" title="Total RF Non Project yang sudah Approved (tanggal RF)">Nilai RF Approved</th>
            <th class="text-end">PO</th>
            <th class="text-end" title="Total PO (incl. tax) berstatus Approved/Completed (tanggal PO)">Terpakai (PO)</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $row)
            <tr>
              <td>
                <a class="sf-link" href="{{ route('erp.department-spending.show', ['department' => $row->department, 'year' => $year]) }}">{{ $row->department->name }}</a>
                @if($row->department->status !== 'active')<span class="badge bg-label-secondary ms-1">inactive</span>@endif
              </td>
              <td class="text-end">{{ $row->rf_count }}</td>
              <td class="text-end">{{ $money($row->rf_total) }}</td>
              <td class="text-end">{{ $row->po_count }}</td>
              <td class="text-end fw-semibold">{{ $money($row->po_total) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr class="fw-bold">
            <td>Total</td>
            <td class="text-end">{{ $rows->sum('rf_count') }}</td>
            <td class="text-end">{{ $money($rows->sum('rf_total')) }}</td>
            <td class="text-end">{{ $rows->sum('po_count') }}</td>
            <td class="text-end">{{ $money($rows->sum('po_total')) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection
