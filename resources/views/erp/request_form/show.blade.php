@extends('layouts.home')

@section('title', $rf->rf_no)

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
  @if(session('success'))
    <div class="alert alert-success alert-dismissible shadow-sm mb-4" role="alert">
      <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible shadow-sm mb-4" role="alert">
      <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Header & Actions --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-file text-primary me-2"></i>Detail Request Form</h4>
      <div class="text-muted small">RF No: <span class="fw-bold text-primary">{{ $rf->rf_no }}</span> • Tipe: <span class="badge bg-label-primary fs-7">{{ $rf->record_type_label }}</span></div>
    </div>
    
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('erp.request-form.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bx bx-arrow-back me-1"></i>Back to List
      </a>

      @if(auth()->user()->hasRole('superadmin') && $rf->status !== 'Draft')
        <form action="{{ route('erp.request-form.unlock', $rf) }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Yakin ingin unlock RF ini? Semua approval akan dihapus dan status kembali ke Draft.')">
            <i class="bx bx-lock-open-alt me-1"></i>Unlock Record
          </button>
        </form>
      @endif

      @if($rf->status === 'Draft' && auth()->user()->hasPermission('products.update'))
        <a href="{{ route('erp.request-form.edit', $rf) }}" class="btn btn-sm btn-outline-primary">
          <i class="bx bx-edit me-1"></i>Edit RF
        </a>
      @endif

      @if($rf->status !== 'Approved')
        <form action="{{ route('erp.approvals.submit', $rf) }}" method="POST" class="d-inline">
          @csrf
          <button class="btn btn-sm btn-primary" {{ $rf->approvals->count() > 0 ? 'disabled' : '' }}>
            <i class="bx bx-paper-plane me-1"></i>Submit for Approval
          </button>
        </form>
      @endif
      
      @if(auth()->user()->hasRole(['logistik', 'superadmin']) && $rf->status === 'Approved')
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#createPrModal">
          <i class="bx bx-plus me-1"></i>Create PR
        </button>
      @endif

      @if(auth()->user()->hasRole(['procurement', 'superadmin']) && $rf->status === 'Approved' && $rf->purchaseRequests->where('status', 'Completed')->count() > 0)
        <a href="{{ route('erp.purchase-orders.create', $rf) }}" class="btn btn-sm btn-success">
          <i class="bx bx-cart me-1"></i>Create PO
        </a>
      @endif
    </div>
  </div>

  {{-- Main Tabbed Container --}}
  <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
    
    {{-- Top Header Widget --}}
    <div class="card-header bg-primary bg-opacity-10 py-3 border-bottom">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <span class="text-uppercase fw-bold text-primary small">Nomor RF</span>
          <h4 class="mb-0 fw-extrabold text-primary">{{ $rf->rf_no }}</h4>
        </div>
        <div class="d-flex align-items-center gap-3">
          <div class="text-end">
            <span class="text-muted small d-block">Total RF Amount</span>
            <span class="fw-extrabold text-primary fs-5">IDR {{ number_format($rf->total_amount, 0, ',', '.') }}</span>
          </div>
          <div>
            @if($rf->status === 'Approved')
              <span class="badge bg-success px-3 py-2 fs-7"><i class="bx bx-check-circle me-1"></i>Approved</span>
            @elseif($rf->status === 'Submitted')
              <span class="badge bg-warning px-3 py-2 fs-7"><i class="bx bx-time-five me-1"></i>Submitted</span>
            @elseif($rf->status === 'Rejected')
              <span class="badge bg-danger px-3 py-2 fs-7"><i class="bx bx-x-circle me-1"></i>Rejected</span>
            @else
              <span class="badge bg-secondary px-3 py-2 fs-7">{{ $rf->status }}</span>
            @endif
          </div>
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
            <i class="bx bx-package me-2 fs-5"></i>2. Line Items (Produk) <span class="badge bg-primary rounded-pill ms-1">{{ $rf->items->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-notes-btn" data-bs-toggle="tab" data-bs-target="#tab-notes" type="button" role="tab">
            <i class="bx bx-paperclip me-2 fs-5"></i>3. Notes & Attachments <span class="badge bg-secondary rounded-pill ms-1">{{ $rf->notesAttachments->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-approval-btn" data-bs-toggle="tab" data-bs-target="#tab-approval" type="button" role="tab">
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

            @if($rf->record_type === 'project')
              <div class="mb-3">
                <label class="form-label fw-semibold small text-uppercase text-muted">Project Code</label>
                <input class="form-control bg-white" value="{{ $rf->project_code ?: '-' }}" readonly>
              </div>
            @else
              <div class="alert alert-secondary py-2 small mb-3">Non Project RF tidak memakai project code.</div>
            @endif

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Requestor</label>
                <input class="form-control bg-white" value="{{ $rf->requestor ?: '-' }}" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Owner</label>
                <input class="form-control bg-white" value="{{ $rf->owner ?: '-' }}" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Priority</label>
                <input class="form-control bg-white" value="{{ $rf->priority }}" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Date</label>
                <input class="form-control bg-white" value="{{ $rf->rf_date?->format('Y-m-d') ?: '-' }}" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">RF Type</label>
                <input class="form-control bg-white" value="{{ $rf->rf_type ?: '-' }}" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-uppercase text-muted">Status</label>
                <input class="form-control bg-white fw-bold" value="{{ $rf->status }}" readonly>
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
              <textarea class="form-control bg-white" rows="2" readonly>{{ $rf->remark ?: '-' }}</textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Long Remark (Detail Request)</label>
              <textarea class="form-control bg-white" rows="3" readonly>{{ $rf->long_remark ?: '-' }}</textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Recommended Supplier</label>
              <input class="form-control bg-white" value="{{ $rf->recommend_supplier ?: '-' }}" readonly>
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
                    <input class="form-check-input" type="checkbox" disabled @checked($rf->{$field})>
                    <label class="form-check-label fw-medium text-secondary">{{ $label }}</label>
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
            <div class="text-muted small">Rincian item produk yang diajukan dalam RF ini.</div>
          </div>
        </div>

        <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
          <table class="table table-hover align-middle mb-0" id="lineItemsTable">
            <thead class="table-light">
              <tr>
                <th>RF Detail No</th>
                <th>Product Name</th>
                <th>WID</th>
                <th>Currency</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Qty Fulfilled</th>
                <th class="text-end">Unit Cost</th>
                <th class="text-end">Total Cost</th>
                <th>Date Required</th>
                <th>PIC</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rf->items as $item)
                <tr>
                  <td class="fw-semibold">
                    @if($item->rf_detail_no)
                      <a href="{{ route('erp.request-form-items.show', $item) }}" class="text-primary text-decoration-none fw-bold">{{ $item->rf_detail_no }}</a>
                    @else
                      -
                    @endif
                  </td>
                  <td class="fw-bold text-dark">{{ $item->product_name }}</td>
                  <td>{{ $item->wid ?: '-' }}</td>
                  <td>{{ $item->currency }}</td>
                  <td class="text-end fw-semibold">{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                  <td class="text-end text-muted">{{ number_format((float) $item->qty_fulfilled, 2, ',', '.') }}</td>
                  <td class="text-end">{{ $item->currency }} {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                  <td class="text-end fw-bold text-primary">{{ $item->currency }} {{ number_format($item->original_total_cost, 0, ',', '.') }}</td>
                  <td>{{ $item->date_required?->format('Y-m-d') ?: '-' }}</td>
                  <td>{{ $item->pic ?: '-' }}</td>
                  <td><span class="badge bg-label-info">{{ $item->status }}</span></td>
                </tr>
              @empty
                <tr><td colspan="11" class="text-center text-muted py-5">Belum ada item produk dalam RF ini.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- ================= TAB 3: NOTES & ATTACHMENTS ================= --}}
      <div class="tab-pane fade" id="tab-notes" role="tabpanel">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <h6 class="fw-bold text-primary mb-0"><i class="bx bx-paperclip me-1"></i>Catatan & Lampiran Berkas (Notes & Attachments)</h6>
            <div class="text-muted small">Catatan internal dan berkas lampiran yang diunggah.</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newNoteModal">
              <i class="bx bx-plus me-1"></i>New Note
            </button>
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#attachFileModal">
              <i class="bx bx-upload me-1"></i>Attach File
            </button>
          </div>
        </div>

        <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:10%;">Tipe</th>
                <th>Konten / Nama Berkas</th>
                <th>Pengunggah</th>
                <th>Tanggal</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rf->notesAttachments as $na)
                <tr>
                  <td>
                    @if($na->type === 'note')
                      <span class="badge bg-label-info"><i class="bx bx-note me-1"></i>Note</span>
                    @else
                      <span class="badge bg-label-success"><i class="bx bx-file me-1"></i>File</span>
                    @endif
                  </td>
                  <td>
                    @if($na->type === 'note')
                      <div class="text-dark">{{ $na->content }}</div>
                    @else
                      <a href="javascript:void(0);" onclick="showAttachmentModal('{{ Storage::url($na->file_path) }}', '{{ $na->file_name }}')" class="text-primary fw-bold text-decoration-none d-inline-flex align-items-center">
                        <i class="bx bx-paperclip me-2 fs-5"></i> {{ $na->file_name }}
                      </a>
                    @endif
                  </td>
                  <td>{{ $na->user?->name ?: '-' }}</td>
                  <td>{{ $na->created_at->format('Y-m-d H:i') }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-5">Belum ada catatan atau lampiran berkas.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- ================= TAB 4: APPROVAL & PR (STACKED VERTICALLY) ================= --}}
      <div class="tab-pane fade" id="tab-approval" role="tabpanel">
        <div class="row g-4">
          
          {{-- 1. Approval History (Atas) --}}
          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 rounded p-1">
                  <i class="bx bx-check-shield text-primary fs-5"></i>
                </div>
                <h6 class="fw-bold mb-0 text-primary">Approval History & Workflow</h6>
              </div>
              @if($rf->status !== 'Approved')
                <form action="{{ route('erp.approvals.submit', $rf) }}" method="POST" class="d-inline">
                  @csrf
                  <button class="btn btn-sm btn-primary" {{ $rf->approvals->count() > 0 ? 'disabled' : '' }}>
                    <i class="bx bx-paper-plane me-1"></i>Submit for Approval
                  </button>
                </form>
              @endif
            </div>

            <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
              <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                  <tr class="text-muted text-uppercase small">
                    <th style="width: 110px;">ACTION</th>
                    <th>DATE</th>
                    <th>STATUS</th>
                    <th>ASSIGNED TO</th>
                    <th>ACTUAL APPROVER</th>
                    <th>COMMENTS</th>
                    <th style="width: 140px;" class="text-center">OVERALL STATUS</th>
                  </tr>
                </thead>
                <tbody>
                  @if($rf->approvals->count() === 0)
                    <tr>
                      <td colspan="7" class="text-muted text-center py-4 bg-light">
                        <i class="bx bx-info-circle me-1"></i> Approval flow belum di-submit. Klik tombol <strong>Submit for Approval</strong> di atas untuk memulai persetujuan.
                      </td>
                    </tr>
                  @else
                    <tr class="bg-light">
                      <td colspan="6" class="fw-bold py-2 px-3 text-primary">
                        <i class="bx bx-paper-plane me-2"></i>Approval Request Submitted
                      </td>
                      <td class="bg-success text-white fw-bold text-center py-2">
                        <i class="bx bx-check-circle me-1"></i> Approved
                      </td>
                    </tr>
                    <tr class="bg-white">
                      <td>-</td>
                      <td>{{ $rf->approvals->first()->created_at->format('Y-m-d H:i') }}</td>
                      <td><span class="badge bg-label-info">Submitted</span></td>
                      <td>{{ $rf->requestor }}</td>
                      <td>{{ $rf->requestor }}</td>
                      <td>RF Submitted for approval</td>
                      <td class="text-center"></td>
                    </tr>
                    @foreach($rf->approvals->sortBy('level') as $approval)
                      @php
                        $statusBg = 'bg-secondary';
                        $statusIcon = 'bx-minus';
                        if ($approval->status === 'Approved') {
                            $statusBg = 'bg-success';
                            $statusIcon = 'bx-check-circle';
                        } elseif ($approval->status === 'Pending') {
                            $statusBg = 'bg-warning';
                            $statusIcon = 'bx-time-five';
                        } elseif ($approval->status === 'Rejected') {
                            $statusBg = 'bg-danger';
                            $statusIcon = 'bx-x-circle';
                        }
                      @endphp
                      <tr class="bg-light">
                        <td colspan="6" class="fw-bold py-2 px-3 text-dark">
                          <i class="bx bx-badge-check me-2 text-primary"></i>Step {{ $approval->level }}: {{ $approval->assignedRole?->name ?: ($approval->assignedUser?->name ?: 'Level '.$approval->level) }}
                        </td>
                        <td class="{{ $statusBg }} text-white fw-bold text-center py-2">
                          <i class="bx {{ $statusIcon }} me-1"></i> {{ $approval->status }}
                        </td>
                      </tr>
                      <tr class="bg-white">
                        <td>
                          @php
                              $canApprove = false;
                              if ($approval->status === 'Pending') {
                                  if (auth()->user()->hasRole('superadmin')) {
                                      $canApprove = true;
                                  } elseif (auth()->id() == $approval->assigned_to_user_id) {
                                      $canApprove = true;
                                  } elseif ($approval->assigned_to_role_id) {
                                      $canApprove = \Illuminate\Support\Facades\DB::connection('tenant')
                                          ->table('role_user')
                                          ->where('user_id', auth()->id())
                                          ->where('role_id', $approval->assigned_to_role_id)
                                          ->exists();
                                  }
                              }
                          @endphp
                          @if($canApprove)
                            <button class="btn btn-xs btn-success shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#approveModal{{ $approval->id }}">Approve</button>
                            
                            <!-- Modal Approve -->
                            <div class="modal fade" id="approveModal{{ $approval->id }}" tabindex="-1" aria-hidden="true">
                              <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                  <form action="{{ route('erp.approvals.approve', $approval) }}" method="POST">
                                    @csrf
                                    <div class="modal-header border-bottom">
                                      <h5 class="modal-title fw-bold"><i class="bx bx-check-shield me-2 text-success"></i>Approve Step {{ $approval->level }}</h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                      <div class="mb-3">
                                        <label class="form-label fw-semibold">Catatan / Comments (Opsional)</label>
                                        <textarea name="comments" class="form-control" rows="3" placeholder="Tuliskan catatan persetujuan..."></textarea>
                                      </div>
                                    </div>
                                    <div class="modal-footer border-top">
                                      <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                      <button type="submit" class="btn btn-success btn-sm px-4">Approve Step</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
                          @else
                            -
                          @endif
                        </td>
                        <td>{{ $approval->approved_at ? $approval->approved_at->format('Y-m-d H:i') : '-' }}</td>
                        <td><span class="badge bg-label-info">{{ $approval->status }}</span></td>
                        <td>{{ $approval->assignedUser?->name ?: ($approval->assignedRole?->name ?: '-') }}</td>
                        <td>{{ $approval->actualApprover?->name ?: '-' }}</td>
                        <td>{{ $approval->comments ?: '-' }}</td>
                        <td class="text-center"></td>
                      </tr>
                    @endforeach
                  @endif
                </tbody>
              </table>
            </div>
          </div>

          {{-- 2. Purchase Requests (PR) Status (Bawah) --}}
          <div class="col-12 mt-4 pt-2">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="d-flex align-items-center gap-2">
                <div class="bg-danger bg-opacity-10 rounded p-1">
                  <i class="bx bx-book text-danger fs-5"></i>
                </div>
                <h6 class="fw-bold mb-0 text-danger">Purchase Requests (PR) Status</h6>
              </div>

              @if($rf->status === 'Approved' && auth()->user()->hasRole(['logistik', 'superadmin']))
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#createPrModal">
                  <i class="bx bx-plus me-1"></i>Create PR
                </button>
              @endif
            </div>

            <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Action</th>
                    <th>PR No</th>
                    <th>Requestor</th>
                    <th>Date</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($rf->purchaseRequests as $pr)
                    <tr>
                      <td>
                        <form action="{{ route('erp.purchase-requests.destroy', $pr) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus PR ini?')">
                          @csrf @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2"><i class="bx bx-trash me-1"></i>Del</button>
                        </form>
                      </td>
                      <td>
                        <a href="{{ route('erp.purchase-requests.show', $pr) }}" class="fw-bold text-primary text-decoration-none">{{ $pr->pr_no }}</a>
                      </td>
                      <td>{{ $pr->requestor }}</td>
                      <td>{{ $pr->pr_date?->format('Y-m-d') }}</td>
                      <td><span class="badge bg-label-success">{{ $pr->status }}</span></td>
                    </tr>
                  @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada Purchase Request (PR) yang dibuat.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          {{-- 3. Purchase Orders (PO) Status (Paling Bawah Tab 4) --}}
          <div class="col-12 mt-4 pt-2">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="d-flex align-items-center gap-2">
                <div class="bg-success bg-opacity-10 rounded p-1">
                  <i class="bx bx-cart text-success fs-5"></i>
                </div>
                <h6 class="fw-bold mb-0 text-success">Purchase Orders (PO) Status</h6>
              </div>

              @if($rf->status === 'Approved' && auth()->user()->hasRole(['procurement', 'superadmin']))
                <a href="{{ route('erp.purchase-orders.create', $rf) }}" class="btn btn-sm btn-success rounded-pill px-3">
                  <i class="bx bx-plus me-1"></i>Create PO Request
                </a>
              @endif
            </div>

            <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr class="small text-uppercase text-muted">
                    <th>Action</th>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>PO Date</th>
                    <th class="text-end">Total Amount (Inc Tax)</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($rf->purchaseOrders as $po)
                    <tr>
                      <td>
                        <a href="{{ route('erp.purchase-orders.show', $po) }}" class="btn btn-xs btn-label-primary">
                          <i class="bx bx-show me-1"></i>View PO
                        </a>
                      </td>
                      <td>
                        <a href="{{ route('erp.purchase-orders.show', $po) }}" class="fw-bold text-primary text-decoration-none">{{ $po->po_no }}</a>
                      </td>
                      <td><span class="fw-bold text-dark">{{ $po->supplier?->name ?: '-' }}</span></td>
                      <td>{{ $po->date ? \Carbon\Carbon::parse($po->date)->format('Y-m-d') : '-' }}</td>
                      <td class="text-end fw-bold text-success">IDR {{ number_format($po->total_po_amount_with_tax, 0, ',', '.') }}</td>
                      <td>
                        @php
                          $poStBadge = match($po->status) {
                            'Approved', 'Completed' => 'bg-success',
                            'Submitted' => 'bg-warning',
                            'Rejected' => 'bg-danger',
                            default => 'bg-secondary',
                          };
                        @endphp
                        <span class="badge {{ $poStBadge }} px-3 py-1 fw-bold">{{ $po->status }}</span>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada Purchase Order (PO) yang dibuat.</td></tr>
                  @endforelse
                </tbody>
              </table>
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
        <a href="{{ route('erp.request-form.index') }}" class="btn btn-outline-secondary">Back to List</a>
        <button type="button" class="btn btn-primary" id="btnNextTab">
          Next Tab <i class="bx bx-chevron-right ms-1"></i>
        </button>
      </div>
    </div>

  </div>
</div>

{{-- MODALS --}}

{{-- Modal New Note --}}
<div class="modal fade" id="newNoteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
      <form action="{{ route('erp.request-form.notes.store', $rf) }}" method="POST">
        @csrf
        <div class="modal-header bg-light py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 rounded p-1">
              <i class="bx bx-note text-primary fs-5"></i>
            </div>
            <h5 class="modal-title fw-bold text-dark mb-0">Add New Note</h5>
          </div>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4 bg-white">
          <div class="mb-3">
            <label class="form-label fw-semibold">Note Content</label>
            <textarea name="content" class="form-control" rows="3" placeholder="Tuliskan catatan..." required></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-3 px-4">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Save Note</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Attach File --}}
<div class="modal fade" id="attachFileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
      <form action="{{ route('erp.request-form.attachments.store', $rf) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header bg-light py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 rounded p-1">
              <i class="bx bx-upload text-primary fs-5"></i>
            </div>
            <h5 class="modal-title fw-bold text-dark mb-0">Attach File</h5>
          </div>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4 bg-white">
          <div class="mb-3">
            <label class="form-label fw-semibold">Select File (PDF, Images)</label>
            <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-3 px-4">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Upload File</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Create PR --}}
<div class="modal fade" id="createPrModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
      <form action="{{ route('erp.purchase-requests.store', $rf) }}" method="POST">
        @csrf
        <input type="hidden" name="request_form_id" value="{{ $rf->id }}">
        
        <div class="modal-header bg-light py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 rounded p-1">
              <i class="bx bx-book text-primary fs-5"></i>
            </div>
            <h5 class="modal-title fw-bold text-dark mb-0">Create Purchase Request (PR)</h5>
          </div>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 bg-white">
          {{-- Expense Categories --}}
          <div class="mb-4">
            <label class="form-label fw-bold text-dark mb-2">Jenis Pengeluaran (Expense Categories)</label>
            <div class="row g-2 p-3 bg-light rounded-3 border">
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
                    <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="pr_{{ $field }}" @checked($rf->{$field})>
                    <label class="form-check-label small fw-medium" for="pr_{{ $field }}">{{ $label }}</label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          {{-- PR Items Table --}}
          <div class="mb-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label fw-bold text-dark mb-0">Rincian Barang PR (Line Items)</label>
              <span class="text-muted small">Tentukan jumlah qty yang mau dipesan di PR ini.</span>
            </div>

            <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr class="small text-uppercase fw-bold text-muted">
                    <th style="width:45px;" class="text-center">
                      <input type="checkbox" class="form-check-input" id="selectAllPrItems" checked>
                    </th>
                    <th>Nama Produk</th>
                    <th class="text-end" style="width:110px;">Qty RF</th>
                    <th class="text-end" style="width:110px;">Terpenuhi</th>
                    <th class="text-end" style="width:140px;">Qty Order PR</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($rf->items as $index => $item)
                    @php
                      $remainingQty = max(0, (float) $item->qty - (float) $item->qty_fulfilled);
                      $isSelected = $remainingQty > 0 || $rf->items->count() === 1;
                    @endphp
                    <tr>
                      <td class="text-center">
                        <input type="checkbox" class="form-check-input pr-item-toggle" @checked($isSelected)>
                      </td>
                      <td>
                        <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                        @if($item->wid)
                          <div class="text-muted small">WID: {{ $item->wid }}</div>
                        @endif
                      </td>
                      <td class="text-end fw-semibold text-secondary">{{ number_format($item->qty, 2) }}</td>
                      <td class="text-end text-muted">{{ number_format($item->qty_fulfilled, 2) }}</td>
                      <td class="text-end">
                        <input type="hidden" name="items[{{ $index }}][request_form_item_id]" value="{{ $item->id }}" {{ $isSelected ? '' : 'disabled' }}>
                        <input type="hidden" name="items[{{ $index }}][required_qty]" value="{{ $item->qty }}" {{ $isSelected ? '' : 'disabled' }}>
                        <input type="number" step="0.01" min="0.01" 
                          name="items[{{ $index }}][pr_requested_qty]" 
                          value="{{ number_format($remainingQty > 0 ? $remainingQty : $item->qty, 2, '.', '') }}" 
                          class="form-control form-control-sm text-end fw-bold text-primary" 
                          {{ $isSelected ? '' : 'disabled' }}>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top py-3 px-4">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bx bx-check me-1"></i>Buat Purchase Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal View Attachment --}}
<div class="modal fade" id="attachmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom py-2">
        <h6 class="modal-title fw-bold" id="attachmentModalTitle">Preview File</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0 text-center bg-dark">
        <iframe id="attachmentIframe" src="" style="width:100%; height:500px; border:none;"></iframe>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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

  // PR Item Checkbox toggle logic
  document.querySelectorAll('.pr-item-toggle').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
      const row = this.closest('tr');
      const inputs = row.querySelectorAll('input[type="hidden"], input[type="number"]');
      inputs.forEach(input => {
        input.disabled = !this.checked;
      });
    });
  });

  const selectAllPr = document.getElementById('selectAllPrItems');
  if (selectAllPr) {
    selectAllPr.addEventListener('change', function() {
      document.querySelectorAll('.pr-item-toggle').forEach(cb => {
        cb.checked = this.checked;
        cb.dispatchEvent(new Event('change'));
      });
    });
  }

  // Preview Attachment Modal
  window.showAttachmentModal = function(url, filename) {
    document.getElementById('attachmentModalTitle').textContent = filename;
    document.getElementById('attachmentIframe').src = url;
    new bootstrap.Modal(document.getElementById('attachmentModal')).show();
  };
});
</script>
@endpush
@endsection
