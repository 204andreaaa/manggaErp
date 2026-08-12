@extends('layouts.home')

@section('title', 'Edit Request Form')

@section('content')
<style>
  .rf-nav-tabs .nav-link {
    color: #64748b;
    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;
    transition: all 0.2s ease;
  }
  .rf-nav-tabs .nav-link:hover {
    color: #4f46e5;
    background: #f8fafc;
  }
  .rf-nav-tabs .nav-link.active {
    color: #4f46e5 !important;
    background: #ffffff !important;
    border-bottom-color: #4f46e5 !important;
    font-weight: 700 !important;
  }
  .expense-box {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    background-color: #f8fafc;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
  
  {{-- Header & Breadcrumb --}}
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-edit text-primary me-2"></i>Edit Request Form</h4>
      <div class="text-muted small">RF No: <span class="fw-bold text-primary">{{ $rf->rf_no }}</span> • Tipe: <span class="badge bg-label-primary fs-7">{{ $recordTypeLabel }}</span></div>
    </div>
    <a href="{{ route('erp.request-form.show', $rf) }}" class="btn btn-outline-secondary btn-sm">
      <i class="bx bx-arrow-back me-1"></i>Back to Detail
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger shadow-sm mb-4">
      <div class="fw-bold mb-1"><i class="bx bx-error-circle me-1"></i>Form belum lengkap:</div>
      <ul class="mb-0 small ps-3">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('erp.request-form.update', $rf) }}" class="card shadow-sm border-0 rounded-3 overflow-hidden" id="rfCreateForm" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="record_type" value="{{ $recordType }}">

    {{-- Top Header Widget --}}
    <div class="card-header bg-primary bg-opacity-10 py-3 border-bottom">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <span class="text-uppercase fw-bold text-primary small">Nomor RF</span>
          <h4 class="mb-0 fw-extrabold text-primary">{{ $rf->rf_no }}</h4>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary px-3 py-2 fs-7">{{ $recordTypeLabel }}</span>
        </div>
      </div>
    </div>

    {{-- Tab Navigation Bar --}}
    <div class="bg-white border-bottom">
      <ul class="nav nav-tabs nav-fill rf-nav-tabs border-0" id="rfTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active py-3" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
            <i class="bx bx-detail me-2 fs-5"></i>1. General & Supplier
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
            <i class="bx bx-package me-2 fs-5"></i>2. Line Items (Produk) <span class="badge bg-primary rounded-pill ms-1" id="lineItemsCountBadge">{{ $rf->items->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-notes-btn" data-bs-toggle="tab" data-bs-target="#tab-notes" type="button" role="tab">
            <i class="bx bx-paperclip me-2 fs-5"></i>3. Notes & Attachments
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3 text-muted" id="tab-approval-btn" data-bs-toggle="tab" data-bs-target="#tab-approval" type="button" role="tab">
            <i class="bx bx-shield-check me-2 fs-5"></i>4. Approval & PR
          </button>
        </li>
      </ul>
    </div>

    {{-- Tab Content Panes --}}
    <div class="card-body p-4 tab-content" id="rfTabContent">
      
      {{-- ================= TAB 1: GENERAL & SUPPLIER ================= --}}
      <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
        <div class="row g-4">
          {{-- Left Column --}}
          <div class="col-lg-6">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="bg-primary bg-opacity-10 rounded p-1">
                <i class="bx bx-info-circle text-primary fs-5"></i>
              </div>
              <h6 class="fw-bold mb-0 text-primary">Request Information</h6>
            </div>

            @if($recordType === 'project')
              <div class="mb-3">
                <label class="form-label fw-semibold small text-uppercase text-muted">Project Code <span class="text-danger">*</span></label>
                <input name="project_code" class="form-control" value="{{ old('project_code', $rf->project_code) }}" placeholder="e.g. MP05922" required>
              </div>
            @else
              <div class="alert alert-secondary py-2 small mb-3">Non Project RF tidak menggunakan project code.</div>
            @endif

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Requestor</label>
                <input name="requestor" class="form-control" value="{{ old('requestor', $rf->requestor) }}">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Owner</label>
                <input name="owner" class="form-control" value="{{ old('owner', $rf->owner) }}">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Priority</label>
                <select name="priority" class="form-select">
                  @foreach(['Normal', 'High', 'Urgent', 'Low'] as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $rf->priority) === $priority)>{{ $priority }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Date <span class="text-danger">*</span></label>
                <input type="date" name="rf_date" class="form-control" value="{{ old('rf_date', \Carbon\Carbon::parse($rf->rf_date)->format('Y-m-d')) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">RF Type</label>
                <select name="rf_type" class="form-select">
                  <option value="">--None--</option>
                  <option value="Fixed Asset" @selected(old('rf_type', $rf->rf_type) === 'Fixed Asset')>Fixed Asset</option>
                  <option value="Non Fixed Asset" @selected(old('rf_type', $rf->rf_type) === 'Non Fixed Asset')>Non Fixed Asset</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Status</label>
                <select name="status" class="form-select">
                  @foreach(['Draft', 'Submitted', 'Approved', 'Partial', 'Completed', 'Cancelled'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $rf->status) === $status)>{{ $status }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          {{-- Right Column --}}
          <div class="col-lg-6">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="bg-primary bg-opacity-10 rounded p-1">
                <i class="bx bx-message-square-detail text-primary fs-5"></i>
              </div>
              <h6 class="fw-bold mb-0 text-primary">Remarks & Supplier Information</h6>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Short Remark</label>
              <textarea name="remark" class="form-control" rows="2" placeholder="Ringkasan keperluan permohonan">{{ old('remark', $rf->remark) }}</textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Long Remark (Detail Request)</label>
              <textarea name="long_remark" class="form-control" rows="3" placeholder="Rincian lengkap dan latar belakang permohonan">{{ old('long_remark', $rf->long_remark) }}</textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Recommended Supplier</label>
              <input name="recommend_supplier" class="form-control" value="{{ old('recommend_supplier', $rf->recommend_supplier) }}" placeholder="Nama vendor / supplier rujukan">
            </div>
          </div>
        </div>

        {{-- Expense Types Section --}}
        <div class="mt-4 pt-3 border-top">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="bg-warning bg-opacity-10 rounded p-1">
              <i class="bx bx-purchase-tag text-warning fs-5"></i>
            </div>
            <h6 class="fw-bold mb-0 text-dark">Expense Categories / Jenis Pengeluaran</h6>
          </div>

          <div class="expense-box">
            <div class="row g-3">
              @foreach([
                'expense_material_equipment' => 'Material-Equipment',
                'expense_material_subcon' => 'Material-Subcon',
                'expense_transportation' => 'Transportation & Telecommunication',
                'expense_personnel' => 'Personnel',
                'expense_office' => 'Office',
                'expense_other' => 'Other Expense',
                'expense_utilities' => 'Utilities',
              ] as $field => $label)
                <div class="col-md-4 col-sm-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}" @checked(old($field, $rf->$field))>
                    <label class="form-check-label fw-medium text-secondary" for="{{ $field }}">{{ $label }}</label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      {{-- ================= TAB 2: LINE ITEMS (PRODUCTS) ================= --}}
      <div class="tab-pane fade" id="tab-items" role="tabpanel">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <h6 class="fw-bold text-primary mb-0"><i class="bx bx-list-plus me-1"></i>Daftar Barang / Produk (RF Line Items)</h6>
            <div class="text-muted small">Tambahkan item barang atau produk yang ingin diajukan dalam RF ini.</div>
          </div>
          <button type="button" class="btn btn-primary" id="btnOpenLineModal">
            <i class="bx bx-plus me-1"></i>Add Product / Item
          </button>
        </div>

        @php
          $oldItems = old('items', $rf->items->toArray());
        @endphp

        <div id="lineHiddenInputs"></div>

        <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
          <table class="table table-hover align-middle mb-0" id="lineItemsTable">
            <thead class="table-light">
              <tr>
                <th style="width:5%;" class="text-center">Action</th>
                <th>Product Name</th>
                <th>WID</th>
                <th>Currency</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Cost</th>
                <th class="text-end">Total Cost</th>
                <th>Date Required</th>
                <th>PIC</th>
                <th>Status</th>
                <th>Remark</th>
              </tr>
            </thead>
            <tbody id="lineItemsBody">
              <tr id="emptyLineRow">
                <td colspan="11" class="text-center text-muted py-5">
                  <i class="bx bx-package fs-1 text-muted opacity-50 mb-2"></i><br>
                  Belum ada item produk yang ditambahkan.<br>
                  <small>Klik tombol <strong>+ Add Product / Item</strong> di atas untuk menambah barang.</small>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      {{-- ================= TAB 3: NOTES & ATTACHMENTS ================= --}}
      <div class="tab-pane fade" id="tab-notes" role="tabpanel">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <h6 class="fw-bold text-primary mb-0"><i class="bx bx-paperclip me-1"></i>Catatan & Lampiran Berkas (Notes & Attachments)</h6>
            <div class="text-muted small">Tambahkan catatan tambahan atau upload dokumen pendukung (PDF/Gambar).</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddNote">
              <i class="bx bx-plus me-1"></i>New Note
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddAttachment">
              <i class="bx bx-upload me-1"></i>Attach File
            </button>
          </div>
        </div>

        <div class="border rounded-3 p-4 bg-light min-height-200" id="notesAttachmentsContainer">
          @foreach($rf->notesAttachments->where('type', 'attachment') as $attachment)
            <div class="d-flex align-items-center gap-2 mb-2 na-item bg-white p-2 rounded border shadow-sm">
              <i class="bx bx-file text-primary fs-5"></i>
              <a href="{{ Storage::url($attachment->file_path) }}" target="_blank" class="small text-dark fw-bold text-truncate" style="max-width:250px;">{{ $attachment->file_name }}</a>
              <span class="badge bg-secondary ms-auto">Existing</span>
            </div>
          @endforeach
          <div id="naEmptyMessage" class="text-center text-muted py-5" style="{{ $rf->notesAttachments->where('type', 'attachment')->count() > 0 ? 'display:none;' : '' }}">
            <i class="bx bx-paperclip fs-1 text-muted opacity-50 mb-2"></i><br>
            Belum ada catatan atau lampiran berkas baru.<br>
            <small>Gunakan tombol di atas untuk menambah Catatan Baru atau Lampirkan Berkas.</small>
          </div>
        </div>
      </div>

      {{-- ================= TAB 4: APPROVAL & PR ================= --}}
      <div class="tab-pane fade" id="tab-approval" role="tabpanel">
        <div class="row g-4">
          {{-- Approval History (Atas) --}}
          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="fw-bold text-danger mb-0"><i class="bx bx-check-shield me-1"></i>Approval History</h6>
            </div>
            <div class="border border-danger border-opacity-25 rounded-3 p-4 text-center text-muted bg-danger bg-opacity-10">
              <i class="bx bx-info-circle mb-2 fs-3 text-danger"></i><br>
              <strong>Approval History</strong> dapat diakses dari halaman detail Request Form.
            </div>
          </div>

          {{-- Purchase Request (PR) Status (Bawah) --}}
          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="fw-bold text-danger mb-0"><i class="bx bx-book me-1"></i>Purchase Request (PR) Status</h6>
            </div>
            <div class="border border-danger border-opacity-25 rounded-3 p-4 text-center text-muted bg-danger bg-opacity-10">
              <i class="bx bx-info-circle mb-2 fs-3 text-danger"></i><br>
              <strong>Purchase Request (PR)</strong> dikelola dari halaman detail Request Form.
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- Bottom Action Footer --}}
    <div class="card-footer border-top bg-light p-3 d-flex align-items-center justify-content-between">
      <button type="button" class="btn btn-outline-secondary" id="btnPrevTab" style="visibility: hidden;">
        <i class="bx bx-chevron-left me-1"></i>Previous Tab
      </button>

      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('erp.request-form.show', $rf) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="button" class="btn btn-primary" id="btnNextTab">
          Next Tab <i class="bx bx-chevron-right ms-1"></i>
        </button>
        <button type="submit" class="btn btn-success px-4" id="btnSubmitForm">
          <i class="bx bx-save me-1"></i>Update RF
        </button>
      </div>
    </div>
  </form>
</div>

{{-- Modal Add Line Item --}}
<div class="modal fade" id="modalLineItem" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">
          <i class="bx bx-list-plus me-2 text-primary"></i>RF Line Item
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="lineId" name="id">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold small text-uppercase text-muted">Product <span class="text-danger">*</span></label>
            <select id="lineProductName" class="form-select select2" required>
              <option value="">-- Select Product --</option>
              @foreach($products as $product)
                <option value="{{ $product->name }}" data-id="{{ $product->product_code ?: $product->part_number }}" data-cost="{{ $product->buying_price }}">
                  {{ $product->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">Product ID</label>
            <input id="lineProductId" class="form-control">
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">WID</label>
            <input id="lineWid" class="form-control">
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">Currency <span class="text-danger">*</span></label>
            <select id="lineCurrency" class="form-select">
              <option value="IDR">IDR</option>
              <option value="USD">USD</option>
              <option value="SGD">SGD</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">Status</label>
            <select id="lineStatus" class="form-select">
              <option value="Requested">Requested</option>
              <option value="Ordered">Ordered</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">Qty <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" id="lineQty" class="form-control" value="1">
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">Qty Fulfilled</label>
            <input type="number" step="0.01" min="0" id="lineQtyFulfilled" class="form-control bg-light" value="0" readonly>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">Unit Cost <span class="text-danger">*</span></label>
            <input type="number" min="0" id="lineUnitCost" class="form-control" value="0">
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">Original Total Cost</label>
            <input type="number" min="0" id="lineOriginalTotalCost" class="form-control" value="0">
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">Actual Cost</label>
            <input type="number" min="0" id="lineActualCost" class="form-control" value="0">
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold small text-uppercase text-muted">Date Required</label>
            <input type="date" id="lineDateRequired" class="form-control" value="{{ old('rf_date', \Carbon\Carbon::parse($rf->rf_date)->format('Y-m-d')) }}">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold small text-uppercase text-muted">PIC</label>
            <select id="linePic" class="form-select">
              <option value="">-- Select PIC --</option>
              @foreach($users as $user)
                <option value="{{ $user->name }}">{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="lineWithinBudget" value="1">
              <label class="form-check-label" for="lineWithinBudget">Within Budget</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold small text-uppercase text-muted">Remark</label>
            <textarea id="lineRemark" class="form-control" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnSaveLineItem">
          <i class="bx bx-save me-1"></i>Save Product
        </button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const initialItems = @json($oldItems);
  const lineItems = [];
  const body = document.getElementById('lineItemsBody');
  const emptyRow = document.getElementById('emptyLineRow');
  const hiddenInputs = document.getElementById('lineHiddenInputs');
  const modalEl = document.getElementById('modalLineItem');
  const lineModal = new bootstrap.Modal(modalEl);
  const lineItemsCountBadge = document.getElementById('lineItemsCountBadge');

  // Tab Navigation JS
  const tabList = ['tab-general', 'tab-items', 'tab-notes', 'tab-approval'];
  let currentTabIndex = 0;

  const btnPrevTab = document.getElementById('btnPrevTab');
  const btnNextTab = document.getElementById('btnNextTab');

  function updateTabButtons() {
    btnPrevTab.style.visibility = currentTabIndex === 0 ? 'hidden' : 'visible';
    btnNextTab.style.display = currentTabIndex === tabList.length - 1 ? 'none' : 'inline-flex';
  }

  btnNextTab.addEventListener('click', function() {
    if (currentTabIndex < tabList.length - 1) {
      currentTabIndex++;
      const triggerEl = document.querySelector(`#rfTab button[data-bs-target="#${tabList[currentTabIndex]}"]`);
      bootstrap.Tab.getInstance(triggerEl) ? bootstrap.Tab.getInstance(triggerEl).show() : new bootstrap.Tab(triggerEl).show();
      updateTabButtons();
    }
  });

  btnPrevTab.addEventListener('click', function() {
    if (currentTabIndex > 0) {
      currentTabIndex--;
      const triggerEl = document.querySelector(`#rfTab button[data-bs-target="#${tabList[currentTabIndex]}"]`);
      bootstrap.Tab.getInstance(triggerEl) ? bootstrap.Tab.getInstance(triggerEl).show() : new bootstrap.Tab(triggerEl).show();
      updateTabButtons();
    }
  });

  document.querySelectorAll('#rfTab button[data-bs-toggle="tab"]').forEach((tabBtn) => {
    tabBtn.addEventListener('shown.bs.tab', function (e) {
      const targetId = e.target.getAttribute('data-bs-target').replace('#', '');
      currentTabIndex = tabList.indexOf(targetId);
      updateTabButtons();
    });
  });

  const fields = {
    product_name: document.getElementById('lineProductName'),
    product_id_text: document.getElementById('lineProductId'),
    wid: document.getElementById('lineWid'),
    currency: document.getElementById('lineCurrency'),
    status: document.getElementById('lineStatus'),
    qty: document.getElementById('lineQty'),
    qty_fulfilled: document.getElementById('lineQtyFulfilled'),
    unit_cost: document.getElementById('lineUnitCost'),
    original_total_cost: document.getElementById('lineOriginalTotalCost'),
    actual_cost: document.getElementById('lineActualCost'),
    date_required: document.getElementById('lineDateRequired'),
    pic: document.getElementById('linePic'),
    within_budget: document.getElementById('lineWithinBudget'),
    remark: document.getElementById('lineRemark'),
  };

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[char]));
  }

  function formatNumber(value) {
    return new Intl.NumberFormat('id-ID').format(Number(value || 0));
  }

  function resetModal() {
    fields.product_name.value = '';
    fields.product_id_text.value = '';
    fields.wid.value = '';
    fields.currency.value = 'IDR';
    fields.status.value = 'Requested';
    fields.qty.value = '1';
    fields.qty_fulfilled.value = '0';
    fields.unit_cost.value = '0';
    fields.original_total_cost.value = '0';
    fields.actual_cost.value = '0';
    fields.date_required.value = document.querySelector('[name="rf_date"]').value || '{{ now()->toDateString() }}';
    fields.pic.value = '';
    fields.within_budget.checked = false;
    fields.remark.value = '';
  }

  function syncOriginalTotal() {
    const qty = Number(fields.qty.value || 0);
    const unitCost = Number(fields.unit_cost.value || 0);
    const actualCost = Number(fields.actual_cost.value || 0);
    const effectiveCost = actualCost > 0 ? actualCost : unitCost;

    if (unitCost > 0 && actualCost === 0) {
      fields.actual_cost.value = unitCost;
    }
    if (actualCost > 0 && unitCost === 0) {
      fields.unit_cost.value = actualCost;
    }

    fields.original_total_cost.value = Math.round(qty * effectiveCost);
  }

  fields.qty.addEventListener('input', syncOriginalTotal);
  fields.unit_cost.addEventListener('input', syncOriginalTotal);
  fields.actual_cost.addEventListener('input', syncOriginalTotal);

  function collectModalItem() {
    syncOriginalTotal();

    return {
      id: document.getElementById('lineId').value || '',
      product_name: fields.product_name.value.trim(),
      product_id_text: fields.product_id_text.value.trim(),
      wid: fields.wid.value.trim(),
      currency: fields.currency.value,
      status: fields.status.value,
      qty: fields.qty.value || '1',
      qty_fulfilled: fields.qty_fulfilled.value || '0',
      unit_cost: fields.unit_cost.value || '0',
      original_total_cost: fields.original_total_cost.value || '0',
      actual_cost: fields.actual_cost.value || '0',
      date_required: fields.date_required.value,
      pic: fields.pic.value.trim(),
      within_budget: fields.within_budget.checked ? '1' : '0',
      remark: fields.remark.value.trim(),
    };
  }

  function renderHiddenInputs() {
    hiddenInputs.innerHTML = lineItems.map((item, index) => Object.entries(item).map(([key, value]) => (
      `<input type="hidden" name="items[${index}][${key}]" value="${escapeHtml(value)}">`
    )).join('')).join('');
  }

  function renderTable() {
    emptyRow.classList.toggle('d-none', lineItems.length > 0);
    body.querySelectorAll('tr[data-line-index]').forEach((row) => row.remove());
    if (lineItemsCountBadge) lineItemsCountBadge.textContent = lineItems.length;

    lineItems.forEach((item, index) => {
      const row = document.createElement('tr');
      row.dataset.lineIndex = index;
      row.innerHTML = `
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line" data-index="${index}" title="Remove Item">
            <i class="bx bx-trash"></i>
          </button>
        </td>
        <td class="fw-bold text-dark">${escapeHtml(item.product_name)}</td>
        <td>${escapeHtml(item.wid || '-')}</td>
        <td>${escapeHtml(item.currency)}</td>
        <td class="text-end fw-semibold">${formatNumber(item.qty)}</td>
        <td class="text-end">${escapeHtml(item.currency)} ${formatNumber(item.unit_cost)}</td>
        <td class="text-end fw-bold text-primary">${escapeHtml(item.currency)} ${formatNumber(item.original_total_cost)}</td>
        <td>${escapeHtml(item.date_required || '-')}</td>
        <td>${escapeHtml(item.pic || '-')}</td>
        <td><span class="badge bg-label-info">${escapeHtml(item.status)}</span></td>
        <td>${escapeHtml(item.remark || '-')}</td>
      `;
      body.appendChild(row);
    });

    renderHiddenInputs();
  }

  document.getElementById('lineProductName').addEventListener('change', function (e) {
    const select = e.target;
    const option = select.options[select.selectedIndex];
    
    if (option && option.value !== "") {
      if (option.dataset.id) document.getElementById('lineProductId').value = option.dataset.id;
      if (option.dataset.cost) document.getElementById('lineUnitCost').value = option.dataset.cost;
      syncOriginalTotal();
    }
  });

  document.getElementById('btnOpenLineModal').addEventListener('click', function () {
    resetModal();
    lineModal.show();
  });

  document.getElementById('btnSaveLineItem').addEventListener('click', function () {
    const item = collectModalItem();

    if (!item.product_name) {
      Swal.fire({ icon: 'warning', title: 'Product wajib diisi' });
      return;
    }
    if (Number(item.qty) <= 0 || Number(item.unit_cost) < 0) {
      Swal.fire({ icon: 'warning', title: 'Qty dan Unit Cost belum valid' });
      return;
    }

    lineItems.push(item);
    renderTable();
    lineModal.hide();
  });

  body.addEventListener('click', function (event) {
    const button = event.target.closest('.btn-remove-line');
    if (!button) return;

    lineItems.splice(Number(button.dataset.index), 1);
    renderTable();
  });

  fields.qty.addEventListener('input', syncOriginalTotal);
  fields.unit_cost.addEventListener('input', syncOriginalTotal);

  initialItems.forEach((item) => {
    if (item.product_name) {
      lineItems.push({
        id: item.id || '',
        product_name: item.product_name || '',
        product_id_text: item.product_id_text || '',
        wid: item.wid || '',
        currency: item.currency || 'IDR',
        status: item.status || 'Requested',
        qty: item.qty || '1',
        qty_fulfilled: item.qty_fulfilled || '0',
        unit_cost: item.unit_cost || '0',
        original_total_cost: item.original_total_cost || '0',
        actual_cost: item.actual_cost || '0',
        date_required: item.date_required || '',
        pic: item.pic || '',
        within_budget: item.within_budget ? '1' : '0',
        remark: item.remark || '',
      });
    }
  });
  renderTable();

  // Notes & Attachments JS
  const naContainer = document.getElementById('notesAttachmentsContainer');
  const naEmptyMsg = document.getElementById('naEmptyMessage');

  document.getElementById('btnAddNote').addEventListener('click', function() {
    if (naEmptyMsg) naEmptyMsg.style.display = 'none';
    const wrapper = document.createElement('div');
    wrapper.className = 'd-flex align-items-start gap-2 mb-2 na-item';
    wrapper.innerHTML = `
      <textarea name="notes[]" class="form-control form-control-sm" rows="2" placeholder="Tulis catatan..." required></textarea>
      <button type="button" class="btn btn-sm btn-outline-danger btn-remove-na"><i class="bx bx-trash"></i></button>
    `;
    naContainer.appendChild(wrapper);
  });

  document.getElementById('btnAddAttachment').addEventListener('click', function() {
    if (naEmptyMsg) naEmptyMsg.style.display = 'none';
    const wrapper = document.createElement('div');
    wrapper.className = 'd-flex align-items-center gap-2 mb-2 na-item';
    wrapper.innerHTML = `
      <input type="file" name="attachments[]" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf" required>
      <button type="button" class="btn btn-sm btn-outline-danger btn-remove-na"><i class="bx bx-trash"></i></button>
    `;
    naContainer.appendChild(wrapper);
  });

  naContainer.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-remove-na');
    if (btn) {
      btn.parentElement.remove();
      if (naContainer.querySelectorAll('.na-item').length === 0 && naEmptyMsg) {
        naEmptyMsg.style.display = 'block';
      }
    }
  });
});
</script>
@endpush
@endsection
