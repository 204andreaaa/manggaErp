@extends('layouts.home')

@section('title', 'ERP Enterprise Dashboard')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Top Welcome & Global Filter Header --}}
  <div class="card bg-primary text-white mb-4 border-0 shadow-sm overflow-hidden position-relative">
    <div class="card-body p-4 position-relative" style="z-index: 2;">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <h3 class="text-white fw-bold mb-0">Selamat Datang, {{ auth()->user()->name }}! 👋</h3>
            <span class="badge bg-white text-primary fw-bold text-uppercase px-3 py-1 rounded-pill">{{ auth()->user()->primaryRole()?->name ?? auth()->user()->role ?? 'User' }}</span>
          </div>
          <p class="text-white text-opacity-75 mb-0 fs-6">
            Dashboard ERP Terpadu & Monitoring Kinerja Operasional Proyek
            @if(session('current_project_name'))
              <span class="badge bg-label-info bg-white bg-opacity-20 text-white ms-1"><i class="bx bx-buildings me-1"></i>{{ session('current_project_name') }}</span>
            @endif
          </p>
        </div>

        {{-- Filter Budget Parent, Bulan & Tahun Form --}}
        <div class="p-2 px-3 rounded-3 shadow-sm" style="background: rgba(255, 255, 255, 0.22); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.35);">
          <form action="{{ route('dashboard') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2 m-0">
            <div class="d-flex align-items-center gap-1 text-white small fw-bold">
              <i class="bx bx-filter-alt fs-5"></i> Filter:
            </div>
            
            {{-- Dropdown Budget Parent --}}
            <select name="budget_parent_id" class="form-select form-select-sm bg-white text-dark border-0 rounded-pill shadow-none fw-medium" style="min-width: 190px;" onchange="this.form.submit()">
              <option value="all" {{ $selectedBudgetParentId === 'all' ? 'selected' : '' }}>📁 Semua Budget Parent</option>
              @foreach($budgetParents as $bp)
                <option value="{{ $bp->id }}" {{ (string)$selectedBudgetParentId === (string)$bp->id ? 'selected' : '' }}>
                  📁 {{ $bp->name }} ({{ $bp->budget_code }})
                </option>
              @endforeach
            </select>

            {{-- Dropdown Bulan --}}
            <select name="month" class="form-select form-select-sm bg-white text-dark border-0 rounded-pill shadow-none fw-medium" style="min-width: 130px;" onchange="this.form.submit()">
              <option value="all" {{ $selectedMonth === 'all' ? 'selected' : '' }}>📅 Semua Bulan</option>
              @foreach([
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
              ] as $mNum => $mName)
                <option value="{{ $mNum }}" {{ (string)$selectedMonth === (string)$mNum ? 'selected' : '' }}>{{ $mName }}</option>
              @endforeach
            </select>

            {{-- Dropdown Tahun --}}
            <select name="year" class="form-select form-select-sm bg-white text-dark border-0 rounded-pill shadow-none fw-medium" style="min-width: 95px;" onchange="this.form.submit()">
              @foreach($years as $yr)
                <option value="{{ $yr }}" {{ (int)$selectedYear === (int)$yr ? 'selected' : '' }}>📆 {{ $yr }}</option>
              @endforeach
            </select>

            @if($selectedBudgetParentId !== 'all' || $selectedMonth !== 'all' || (int)$selectedYear !== (int)date('Y'))
              <a href="{{ route('dashboard') }}" class="btn btn-sm btn-light rounded-pill px-2 fw-semibold" title="Reset Filter">
                <i class="bx bx-reset me-1"></i>Reset
              </a>
            @endif
          </form>
        </div>
      </div>
    </div>
    {{-- Background Decorative Shape --}}
    <div class="position-absolute end-0 bottom-0 opacity-25 d-none d-md-block" style="transform: translate(10%, 20%); z-index: 1;">
      <i class="bx bx-pie-chart-alt text-white" style="font-size: 220px;"></i>
    </div>
  </div>

  {{-- ACTION NEEDED: PENDING APPROVALS ALERT --}}
  @if($pendingPoApprovals->isNotEmpty() || $pendingPaApprovals->isNotEmpty())
  <div class="card border-warning border mb-4 shadow-sm" style="background-color: #fffaf0;">
    <div class="card-body p-3">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-warning rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm">
            <i class="bx bx-bell-ring fs-3"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1 text-dark">Action Needed: Dokumen Menunggu Persetujuan Anda!</h6>
            <p class="text-muted small mb-0">
              Ada <strong>{{ $pendingPoApprovals->count() }} PO</strong> dan <strong>{{ $pendingPaApprovals->count() }} Payment Advice</strong> yang membutuhkan approval segera.
            </p>
          </div>
        </div>
        <div class="d-flex gap-2">
          @if($pendingPoApprovals->isNotEmpty())
            <a href="{{ route('erp.purchase-orders.index') }}" class="btn btn-sm btn-warning rounded-pill px-3 shadow-sm">
              <i class="bx bx-check-shield me-1"></i>Review PO ({{ $pendingPoApprovals->count() }})
            </a>
          @endif
          @if($pendingPaApprovals->isNotEmpty())
            <a href="{{ route('erp.payment-advices.index') }}" class="btn btn-sm btn-outline-warning rounded-pill px-3 shadow-sm">
              <i class="bx bx-wallet me-1"></i>Review PA ({{ $pendingPaApprovals->count() }})
            </a>
          @endif
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- TOP KPI SUMMARY CARDS --}}
  <div class="row g-3 mb-4">
    {{-- Card 1: Total PO Belanja --}}
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-label-primary rounded-pill p-2"><i class="bx bx-cart fs-4"></i></span>
            <span class="text-muted small fw-medium">Periode Terpilih</span>
          </div>
          <p class="text-muted small mb-1 fw-semibold text-uppercase">Total Belanja PO (Approved)</p>
          <h4 class="fw-bold mb-1 text-primary">Rp {{ number_format($totalSpendAmount, 0, ',', '.') }}</h4>
          <div class="d-flex align-items-center gap-1 small text-muted">
            <span class="badge bg-label-success px-2 py-0 rounded">{{ $approvedPoCount }} PO Approved</span>
            <span>dari {{ $totalPoCount }} Total PO</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Card 2: Realisasi Pembayaran / Kas Keluar --}}
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-label-success rounded-pill p-2"><i class="bx bx-check-double fs-4"></i></span>
            <span class="text-muted small fw-medium">Kas Keluar</span>
          </div>
          <p class="text-muted small mb-1 fw-semibold text-uppercase">Realisasi Pembayaran (Paid)</p>
          <h4 class="fw-bold mb-1 text-success">Rp {{ number_format($totalPaidAmount, 0, ',', '.') }}</h4>
          <div class="d-flex align-items-center gap-1 small text-muted">
            <i class="bx bx-shield-check text-success"></i>
            <span>Telah dibayar ke Vendor</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Card 3: Outstanding / Hutang Belum Lunas --}}
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-label-danger rounded-pill p-2"><i class="bx bx-time-five fs-4"></i></span>
            <span class="text-muted small fw-medium">Hutang Berjalan</span>
          </div>
          <p class="text-muted small mb-1 fw-semibold text-uppercase">Outstanding Tagihan (AP)</p>
          <h4 class="fw-bold mb-1 text-danger">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</h4>
          <div class="d-flex align-items-center gap-1 small text-muted">
            <i class="bx bx-error-circle text-danger"></i>
            <span>Sisa kewajiban pembayaran</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Card 4: Penyerapan Budget WID --}}
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-label-info rounded-pill p-2"><i class="bx bx-wallet-alt fs-4"></i></span>
            <span class="badge bg-info text-white rounded-pill px-2">{{ $budgetUtilizationRate }}% Terpakai</span>
          </div>
          <p class="text-muted small mb-1 fw-semibold text-uppercase">Budget Proyek (WID)</p>
          <h4 class="fw-bold mb-1 text-dark">Rp {{ number_format($totalRemainingBudget, 0, ',', '.') }}</h4>
          <div class="progress mt-2" style="height: 6px;">
            <div class="progress-bar bg-info" role="progressbar" style="width: {{ min(100, $budgetUtilizationRate) }}%" aria-valuenow="{{ $budgetUtilizationRate }}" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div class="d-flex justify-content-between small text-muted mt-1" style="font-size: 11px;">
            <span>Terpakai: Rp {{ number_format($totalUsedBudget, 0, ',', '.') }}</span>
            <span>Total: Rp {{ number_format($totalAllocatedBudget, 0, ',', '.') }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHARTS ROW --}}
  <div class="row g-4 mb-4">
    {{-- Main Monthly Trend Chart --}}
    <div class="col-12 col-lg-8">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-transparent border-bottom py-3">
          <div>
            <h5 class="card-title fw-bold mb-0 text-dark"><i class="bx bx-line-chart text-primary me-2"></i>Tren Belanja PO vs Pembayaran (Tahun {{ $selectedYear }})</h5>
            <small class="text-muted">Perbandingan nilai PO yang di-approve dengan realisasi pencairan kas per bulan</small>
          </div>
          <span class="badge bg-label-primary px-3 py-2 rounded-pill">12 Bulan (Jan - Des)</span>
        </div>
        <div class="card-body p-3">
          <div id="monthlyTrendChart" style="min-height: 320px;"></div>
        </div>
      </div>
    </div>

    {{-- Status PO & GR Breakdown --}}
    <div class="col-12 col-lg-4">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom py-3">
          <h5 class="card-title fw-bold mb-0 text-dark"><i class="bx bx-doughnut-chart text-info me-2"></i>Status Purchase Order</h5>
          <small class="text-muted">Distribusi status dokumen PO periode terpilih</small>
        </div>
        <div class="card-body p-3 d-flex flex-column justify-content-between">
          <div id="poStatusChart" style="min-height: 230px;"></div>
          
          <div class="border-top pt-3 mt-2">
            <div class="row g-2 text-center">
              <div class="col-4">
                <div class="bg-light p-2 rounded">
                  <small class="text-muted d-block">Approved</small>
                  <strong class="text-success fs-6">{{ $approvedPoCount }}</strong>
                </div>
              </div>
              <div class="col-4">
                <div class="bg-light p-2 rounded">
                  <small class="text-muted d-block">Submitted</small>
                  <strong class="text-warning fs-6">{{ $submittedPoCount }}</strong>
                </div>
              </div>
              <div class="col-4">
                <div class="bg-light p-2 rounded">
                  <small class="text-muted d-block">Draft</small>
                  <strong class="text-secondary fs-6">{{ $draftPoCount }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- DIVISIONAL HIGHLIGHTS / QUICK INSIGHT CARDS --}}
  <div class="row g-3 mb-4">
    {{-- Procurement Highlight --}}
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bx bx-cart text-primary fs-4"></i>
            <h6 class="fw-bold mb-0 text-dark">Procurement Insight</h6>
          </div>
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Pengajuan RF Baru (Submitted):</span>
              <strong class="text-warning">{{ $submittedRfCount }} RF</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>RF Siap Diproses (Approved):</span>
              <strong class="text-success">{{ $approvedRfCount }} RF</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Draft PO Belum Submit:</span>
              <strong class="text-secondary">{{ $draftPoCount }} PO</strong>
            </li>
          </ul>
          <a href="{{ route('erp.procurement.dashboard') }}" class="btn btn-sm btn-outline-primary w-100 mt-2 rounded-pill">
            Buka PO Request <i class="bx bx-right-arrow-alt"></i>
          </a>
        </div>
      </div>
    </div>

    {{-- Finance Highlight --}}
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bx bx-credit-card text-success fs-4"></i>
            <h6 class="fw-bold mb-0 text-dark">Finance & Invoicing</h6>
          </div>
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Total Payment Advice:</span>
              <strong>Rp {{ number_format($totalPaAmount, 0, ',', '.') }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Total Kas Telah Cair:</span>
              <strong class="text-success">Rp {{ number_format($totalPaidAmount, 0, ',', '.') }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Outstanding / Unpaid:</span>
              <strong class="text-danger">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</strong>
            </li>
          </ul>
          <a href="{{ route('erp.payment-advices.index') }}" class="btn btn-sm btn-outline-success w-100 mt-2 rounded-pill">
            Buka Payment Advice <i class="bx bx-right-arrow-alt"></i>
          </a>
        </div>
      </div>
    </div>

    {{-- Logistik & GA Highlight --}}
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bx bx-box text-info fs-4"></i>
            <h6 class="fw-bold mb-0 text-dark">Logistik & Gudang</h6>
          </div>
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Total Surat Jalan (GR):</span>
              <strong>{{ $totalGrCount }} DO</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Barang Telah Diterima:</span>
              <strong class="text-success">{{ number_format($totalReceivedQty, 0) }} Unit</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Total SKU Master Produk:</span>
              <strong class="text-primary">{{ $totalProductsCount }} Produk</strong>
            </li>
          </ul>
          <a href="{{ route('erp.stocks.index') }}" class="btn btn-sm btn-outline-info w-100 mt-2 rounded-pill">
            Buka Inventory Stok <i class="bx bx-right-arrow-alt"></i>
          </a>
        </div>
      </div>
    </div>

    {{-- Admin Project Highlight --}}
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bx bx-briefcase text-warning fs-4"></i>
            <h6 class="fw-bold mb-0 text-dark">Admin Project (WID)</h6>
          </div>
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Budget Total Proyek:</span>
              <strong>Rp {{ number_format($totalAllocatedBudget, 0, ',', '.') }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Penyerapan Realisasi:</span>
              <strong class="text-info">{{ $budgetUtilizationRate }}%</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span>Sisa Alokasi Budget:</span>
              <strong class="text-success">Rp {{ number_format($totalRemainingBudget, 0, ',', '.') }}</strong>
            </li>
          </ul>
          <a href="{{ route('erp.work-items.index') }}" class="btn btn-sm btn-outline-warning w-100 mt-2 rounded-pill">
            Buka Work Items <i class="bx bx-right-arrow-alt"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- RECENT TRANSACTIONS / ACTIVITY TABLES ROW --}}
  <div class="row g-4">
    {{-- Recent Purchase Orders --}}
    <div class="col-12 col-xl-6">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-transparent border-bottom py-3">
          <h5 class="card-title fw-bold mb-0 text-dark"><i class="bx bx-cart text-primary me-2"></i>5 Purchase Order Terbaru</h5>
          <a href="{{ route('erp.purchase-orders.index') }}" class="btn btn-sm btn-label-primary rounded-pill px-3">Lihat Semua PO</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light">
              <tr>
                <th>No. PO</th>
                <th>Supplier / Vendor</th>
                <th>Nilai Total</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentPos as $rPo)
                <tr>
                  <td>
                    <a href="{{ route('erp.purchase-orders.show', $rPo) }}" class="fw-bold text-primary">
                      {{ $rPo->po_no }}
                    </a>
                    <div class="text-muted" style="font-size: 11px;">{{ $rPo->created_at->format('d M Y') }}</div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark text-truncate" style="max-width: 150px;">{{ $rPo->supplier?->name ?? '-' }}</div>
                  </td>
                  <td class="fw-bold text-dark">
                    Rp {{ number_format($rPo->total_po_amount_with_tax, 0, ',', '.') }}
                  </td>
                  <td>
                    @if($rPo->status === 'Approved' || $rPo->status === 'Completed')
                      <span class="badge bg-label-success rounded-pill px-2 py-1">{{ $rPo->status }}</span>
                    @elseif($rPo->status === 'Submitted')
                      <span class="badge bg-label-warning rounded-pill px-2 py-1">{{ $rPo->status }}</span>
                    @elseif($rPo->status === 'Draft')
                      <span class="badge bg-label-secondary rounded-pill px-2 py-1">{{ $rPo->status }}</span>
                    @else
                      <span class="badge bg-label-danger rounded-pill px-2 py-1">{{ $rPo->status }}</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <a href="{{ route('erp.purchase-orders.show', $rPo) }}" class="btn btn-xs btn-outline-primary rounded-pill px-2">
                      <i class="bx bx-show"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">Belum ada data Purchase Order.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Recent Goods Receipts (DO/GR) --}}
    <div class="col-12 col-xl-6">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-transparent border-bottom py-3">
          <h5 class="card-title fw-bold mb-0 text-dark"><i class="bx bx-truck text-info me-2"></i>5 Penerimaan Barang (GR/DO) Terbaru</h5>
          <span class="badge bg-label-info rounded-pill px-3 py-1">Logistik & GA</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light">
              <tr>
                <th>No. GR / DO</th>
                <th>Referensi PO</th>
                <th>Supplier / Vendor</th>
                <th>Qty Terima</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentGrs as $rGr)
                <tr>
                  <td>
                    <a href="{{ route('erp.goods-receipts.show', $rGr) }}" class="fw-bold text-info">
                      {{ $rGr->gr_no }}
                    </a>
                    <div class="text-muted" style="font-size: 11px;">{{ $rGr->date ? \Carbon\Carbon::parse($rGr->date)->format('d M Y') : '-' }}</div>
                  </td>
                  <td>
                    <span class="badge bg-label-primary px-2 py-0">{{ $rGr->purchaseOrder?->po_no ?? '-' }}</span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark text-truncate" style="max-width: 130px;">{{ $rGr->supplier?->name ?? '-' }}</div>
                  </td>
                  <td class="fw-bold text-success">
                    {{ number_format($rGr->total_received_qty, 0) }} Unit
                  </td>
                  <td>
                    @if($rGr->status === 'Received')
                      <span class="badge bg-label-success rounded-pill px-2 py-1"><i class="bx bx-check me-1"></i>Received</span>
                    @else
                      <span class="badge bg-label-secondary rounded-pill px-2 py-1">{{ $rGr->status }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">Belum ada data Penerimaan Barang (GR).</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
{{-- ApexCharts CDN & Initialization --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // 1. Monthly Trend Spline / Area Chart
  const monthlySpendData = @json($monthlySpend);
  const monthlyPaidData = @json($monthlyPaid);

  const trendOptions = {
    series: [
      {
        name: 'Nilai Belanja PO (Approved)',
        data: monthlySpendData
      },
      {
        name: 'Realisasi Kas Keluar (Paid)',
        data: monthlyPaidData
      }
    ],
    chart: {
      height: 320,
      type: 'area',
      toolbar: { show: false },
      zoom: { enabled: false }
    },
    colors: ['#696cff', '#71dd37'],
    dataLabels: { enabled: false },
    stroke: {
      curve: 'smooth',
      width: [2.5, 2.5]
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.45,
        opacityTo: 0.05,
        stops: [0, 90, 100]
      }
    },
    xaxis: {
      categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
      labels: { style: { colors: '#a1acb8', fontSize: '12px' } }
    },
    yaxis: {
      labels: {
        formatter: function (val) {
          if (val >= 1000000000) return (val / 1000000000).toFixed(1) + ' M';
          if (val >= 1000000) return (val / 1000000).toFixed(0) + ' Jt';
          if (val >= 1000) return (val / 1000).toFixed(0) + ' Rb';
          return val;
        },
        style: { colors: '#a1acb8', fontSize: '12px' }
      }
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
        }
      }
    },
    grid: {
      borderColor: '#f1f1f1',
      strokeDashArray: 4
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right'
    }
  };

  const trendChart = new ApexCharts(document.querySelector("#monthlyTrendChart"), trendOptions);
  trendChart.render();

  // 2. PO Status Donut Chart
  const approvedCount = {{ (int) $approvedPoCount }};
  const submittedCount = {{ (int) $submittedPoCount }};
  const draftCount = {{ (int) $draftPoCount }};
  const rejectedCount = {{ (int) $rejectedPoCount }};

  const totalPoAll = approvedCount + submittedCount + draftCount + rejectedCount;

  const donutOptions = {
    series: totalPoAll > 0 ? [approvedCount, submittedCount, draftCount, rejectedCount] : [1],
    labels: totalPoAll > 0 ? ['Approved/Completed', 'Submitted', 'Draft', 'Rejected'] : ['No Data'],
    chart: {
      type: 'donut',
      height: 230
    },
    colors: totalPoAll > 0 ? ['#71dd37', '#ffab00', '#8592a3', '#ff3e1d'] : ['#e0e0e0'],
    legend: {
      position: 'bottom',
      fontSize: '12px'
    },
    dataLabels: {
      enabled: totalPoAll > 0
    },
    plotOptions: {
      pie: {
        donut: {
          size: '65%',
          labels: {
            show: true,
            total: {
              show: true,
              label: 'Total PO',
              fontSize: '13px',
              formatter: function () {
                return totalPoAll;
              }
            }
          }
        }
      }
    }
  };

  const poStatusChart = new ApexCharts(document.querySelector("#poStatusChart"), donutOptions);
  poStatusChart.render();
});
</script>
@endpush
