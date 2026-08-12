@extends('layouts.home')

@section('title', 'Create PO Request: ' . $requestForm->rf_no)

@section('content')
<style>
  .po-nav-tabs .nav-link {
    color: #64748b;
    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;
    font-weight: 500;
    transition: all 0.2s ease;
  }
  .po-nav-tabs .nav-link:hover {
    color: #4f46e5;
    background: #f8fafc;
  }
  .po-nav-tabs .nav-link.active {
    color: #4f46e5 !important;
    background: #ffffff !important;
    border-bottom-color: #4f46e5 !important;
    font-weight: 700 !important;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
  
  {{-- Header & Breadcrumb --}}
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1 fw-bold text-dark"><i class="bx bx-cart text-primary me-2"></i>Create Purchase Order</h4>
      <div class="text-muted small">Reference RF No: <span class="badge bg-label-primary fs-7">{{ $requestForm->rf_no }}</span></div>
    </div>
    <a href="{{ route('erp.procurement.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
      <i class="bx bx-arrow-back me-1"></i>Back to Dashboard
    </a>
  </div>

  @if(session('error'))
    <div class="alert alert-danger shadow-sm border-0 alert-dismissible mb-4" role="alert">
      <div class="d-flex align-items-center">
        <i class="bx bx-error-circle me-2 fs-4"></i>
        <div>{{ session('error') }}</div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger shadow-sm border-0 mb-4 rounded-3">
      <div class="fw-bold mb-1"><i class="bx bx-error-circle me-1"></i>Please check the required fields:</div>
      <ul class="mb-0 small ps-3">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('erp.purchase-orders.store') }}" method="POST" enctype="multipart/form-data" class="card shadow-sm border-0 rounded-4 overflow-hidden" id="poCreateForm">
    @csrf
    <input type="hidden" name="request_form_id" value="{{ $requestForm->id }}">

    {{-- Top Header Widget --}}
    <div class="card-header bg-primary bg-opacity-10 py-3 border-bottom">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <span class="text-uppercase fw-bold text-primary small">Purchase Order No (Auto Generated)</span>
          <h4 class="mb-0 fw-extrabold text-primary">{{ $poNo }}</h4>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-label-secondary px-3 py-2 fs-7">Draft</span>
          <span class="badge bg-primary px-3 py-2 fs-7">RF: {{ $requestForm->rf_no }}</span>
        </div>
      </div>
    </div>

    {{-- Tab Navigation Bar --}}
    <div class="bg-white border-bottom">
      <ul class="nav nav-tabs nav-fill po-nav-tabs border-0" id="poTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active py-3" id="tab-supplier-btn" data-bs-toggle="tab" data-bs-target="#tab-supplier" type="button" role="tab">
            <i class="bx bx-buildings me-2 fs-5"></i>1. Supplier & Header Info
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-delivery-btn" data-bs-toggle="tab" data-bs-target="#tab-delivery" type="button" role="tab">
            <i class="bx bx-printer me-2 fs-5"></i>2. Delivery & Print Related
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
            <i class="bx bx-package me-2 fs-5"></i>3. PO Items & Pricing <span class="badge bg-primary rounded-pill ms-1">{{ count($requestForm->items) }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link py-3" id="tab-attachments-btn" data-bs-toggle="tab" data-bs-target="#tab-attachments" type="button" role="tab">
            <i class="bx bx-paperclip me-2 fs-5"></i>4. Attachments & Instructions
          </button>
        </li>
      </ul>
    </div>

    {{-- Tab Content Panes --}}
    <div class="card-body p-4 tab-content" id="poTabContent">

      {{-- Tab 1: Supplier & Header Info --}}
      <div class="tab-pane fade show active" id="tab-supplier" role="tabpanel">
        <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-buildings me-1"></i>Supplier & Basic Details</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">PO Number</label>
            <input type="text" class="form-control bg-light rounded-3" value="{{ $poNo }}" readonly>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">PO Date <span class="text-danger">*</span></label>
            <input type="date" name="date" class="form-control rounded-3 @error('date') is-invalid @enderror" value="{{ old('date', now()->format('Y-m-d')) }}" required>
            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
            <select name="supplier_id" class="form-select rounded-3 @error('supplier_id') is-invalid @enderror" required>
              <option value="">-- Select Supplier --</option>
              @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" 
                  data-address="{{ $supplier->address }}"
                  data-bank="{{ $supplier->bank_name ? ($supplier->bank_name . ' ' . $supplier->bank_account) : $supplier->bank_account }}"
                  data-payment-terms="{{ $supplier->paymentTerm?->name ?: 'Payment In Advance' }}"
                  data-contacts="{{ json_encode($supplier->contacts) }}"
                  {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                  {{ $supplier->name }}
                </option>
              @endforeach
            </select>
            @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Contact Person</label>
            <select name="contact_person" id="contact_person" class="form-select rounded-3 @error('contact_person') is-invalid @enderror">
              <option value="">-- Select Contact Person --</option>
            </select>
            @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Destination Warehouse</label>
            <select name="erp_warehouse_id" class="form-select rounded-3">
              <option value="">-- Select Destination --</option>
              @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ old('erp_warehouse_id') == $wh->id ? 'selected' : '' }}>
                  {{ $wh->name }} ({{ $wh->warehouse_code }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Estimated Time of Arrival (ETA)</label>
            <input type="date" name="eta" class="form-control rounded-3" value="{{ old('eta') }}">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Supplier Address</label>
            <textarea name="address" class="form-control bg-light rounded-3" rows="3" readonly placeholder="Auto filled upon selecting supplier">{{ old('address') }}</textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Supplier Bank Account</label>
            <input type="text" name="bank_account" class="form-control bg-light rounded-3 mb-2" value="{{ old('bank_account') }}" placeholder="Auto filled upon selecting supplier" readonly>
            <label class="form-label fw-semibold mt-2">PO Description / Header Note</label>
            <textarea name="description" class="form-control rounded-3" rows="2" placeholder="General description or note for this PO...">{{ old('description') }}</textarea>
          </div>
        </div>
      </div>

      {{-- Tab 2: Delivery & Print Related --}}
      <div class="tab-pane fade" id="tab-delivery" role="tabpanel">
        <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-printer me-1"></i>Print & Billing Specifications</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Project Code / Tag</label>
            @if($requestForm->project_code)
              <input type="text" name="project" class="form-control bg-light rounded-3" value="{{ $requestForm->project_code }}" readonly>
            @else
              <div class="input-group">
                <span class="input-group-text bg-light fw-bold text-muted">INTERNAL -</span>
                <input type="hidden" name="project" id="hidden_project" value="{{ old('project', 'INTERNAL') }}">
                <input type="text" class="form-control rounded-end-3" placeholder="e.g. IT, ATK, Dapur..." 
                  value="{{ str_replace('INTERNAL - ', '', old('project', '')) }}"
                  oninput="document.getElementById('hidden_project').value = this.value ? 'INTERNAL - ' + this.value : 'INTERNAL'">
              </div>
            @endif
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Payment Terms</label>
            <select name="payment_terms" class="form-select rounded-3">
              <option value="">-- Select Payment Term --</option>
              @foreach($paymentTerms as $term)
                <option value="{{ $term->name }}" {{ old('payment_terms') == $term->name ? 'selected' : '' }}>
                  {{ $term->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Payment Method</label>
            <select name="payment_method" class="form-select rounded-3">
              <option value="Bank Transfer" {{ old('payment_method') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
              <option value="Cash" {{ old('payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
              <option value="COD" {{ old('payment_method') == 'COD' ? 'selected' : '' }}>COD</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Authorized Signature User</label>
            <select name="signature" class="form-select rounded-3 @error('signature') is-invalid @enderror">
              <option value="">-- Select Authorized Approver --</option>
              @foreach($users as $user)
                <option value="{{ $user->name }}" {{ old('signature') == $user->name ? 'selected' : '' }}>
                  {{ $user->name }}
                </option>
              @endforeach
            </select>
            @error('signature')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Invoice To</label>
            <input type="text" name="invoice_to" class="form-control rounded-3" value="{{ old('invoice_to') }}" placeholder="e.g. PT Mandiri Daya Utama Nusantara">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Attention To</label>
            <input type="text" name="attention_to" class="form-control rounded-3" value="{{ old('attention_to') }}" placeholder="e.g. Finance Department">
          </div>

          <div class="col-md-12">
            <label class="form-label fw-semibold">Transfer To Account</label>
            <input type="text" name="transfer_to" class="form-control bg-light rounded-3" value="{{ old('transfer_to') }}" placeholder="Auto filled from Supplier Bank Account" readonly>
          </div>
        </div>
      </div>

      {{-- Tab 3: PO Items & Pricing --}}
      <div class="tab-pane fade" id="tab-items" role="tabpanel">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="fw-bold mb-0 text-primary"><i class="bx bx-package me-1"></i>Items to Include in Purchase Order</h6>
          <span class="badge bg-label-info"><i class="bx bx-info-circle me-1"></i>Excluding an item: Click X or set Qty PO to 0</span>
        </div>

        <div class="table-responsive rounded-3 border">
          <table class="table table-hover align-middle mb-0" id="itemsTable">
            <thead class="bg-light">
              <tr class="text-uppercase small fw-bold text-muted">
                <th>RF Detail</th>
                <th>Product Name</th>
                <th>UOM</th>
                <th style="width: 110px;">Qty Req</th>
                <th style="width: 120px;">Qty PO</th>
                <th style="width: 150px;">Unit Cost (Rp)</th>
                <th style="width: 120px;">Tax (Rp)</th>
                <th style="width: 160px;" class="text-end">Total Cost</th>
                <th>Remarks</th>
                <th style="width: 40px;" class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($requestForm->items as $idx => $item)
                @php
                  $prItem = null;
                  foreach($requestForm->purchaseRequests as $pr) {
                    $found = $pr->items->where('request_form_item_id', $item->id)->first();
                    if ($found) {
                      $prItem = $found;
                      break;
                    }
                  }
                  $qty = $prItem ? $prItem->pr_requested_qty : $item->qty;
                @endphp
                <tr data-row="{{ $idx }}">
                  <td>
                    <input type="hidden" name="items[{{ $idx }}][request_form_item_id]" value="{{ $item->id }}">
                    <span class="fw-semibold text-dark">{{ $item->rf_detail_no }}</span>
                  </td>
                  <td>
                    <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                  </td>
                  <td>
                    <span class="badge bg-label-secondary">{{ $item->erpProduct?->uom?->uom_name ?: '-' }}</span>
                  </td>
                  <td>
                    <span class="fw-semibold">{{ number_format($qty, 2, ',', '.') }}</span>
                  </td>
                  <td>
                    <input type="number" step="0.01" name="items[{{ $idx }}][qty]" class="form-control form-control-sm rounded-2 item-qty fw-bold" value="{{ old("items.$idx.qty", $qty) }}" oninput="calculateRow({{ $idx }})">
                  </td>
                  <td>
                    @php
                      $effectiveCost = $item->actual_cost > 0 ? $item->actual_cost : $item->unit_cost;
                    @endphp
                    <input type="number" name="items[{{ $idx }}][unit_cost]" class="form-control form-control-sm rounded-2 item-cost fw-bold text-success" value="{{ old("items.$idx.unit_cost", $effectiveCost) }}" oninput="calculateRow({{ $idx }})">
                  </td>
                  <td>
                    <input type="number" name="items[{{ $idx }}][tax]" class="form-control form-control-sm rounded-2 item-tax" value="{{ old("items.$idx.tax", 0) }}" oninput="calculateRow({{ $idx }})">
                  </td>
                  <td class="text-end fw-bold text-primary">
                    <span class="row-total">Rp 0</span>
                  </td>
                  <td>
                    <input type="text" name="items[{{ $idx }}][remarks]" class="form-control form-control-sm rounded-2" value="{{ old("items.$idx.remarks", $item->remark) }}" placeholder="Item note...">
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger border-0 rounded-circle" onclick="removeRow({{ $idx }})" title="Remove item">
                      <i class="bx bx-trash"></i>
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- Tab 4: Attachments & Instructions --}}
      <div class="tab-pane fade" id="tab-attachments" role="tabpanel">
        <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-paperclip me-1"></i>Attachments & Special Instructions</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Upload Attachments</label>
            <input type="file" name="attachments[]" class="form-control rounded-3" accept=".jpg,.jpeg,.png,.pdf" multiple>
            <div class="form-text mt-1"><i class="bx bx-info-circle me-1"></i>Allowed formats: JPG, PNG, PDF. You can select multiple files.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Other Instructions for Vendor</label>
            <textarea name="other_instructions" class="form-control rounded-3" rows="4" placeholder="Special delivery instructions, packaging requirements, or additional terms...">{{ old('other_instructions') }}</textarea>
          </div>
        </div>
      </div>

    </div>

    {{-- Form Footer Actions --}}
    <div class="card-footer bg-light border-top py-3 px-4 d-flex align-items-center justify-content-between">
      <a href="{{ route('erp.procurement.dashboard') }}" class="btn btn-label-secondary">Cancel</a>
      <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary px-4 shadow-sm">
          <i class="bx bx-check-circle me-1"></i>Create PO Request
        </button>
      </div>
    </div>
  </form>
</div>

<script>
  function removeRow(idx) {
    const row = document.querySelector(`tr[data-row="${idx}"]`);
    if (row) {
      row.remove();
    }
  }

  function calculateRow(idx) {
    const row = document.querySelector(`tr[data-row="${idx}"]`);
    if (!row) return;

    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const cost = parseFloat(row.querySelector('.item-cost').value) || 0;
    const tax = parseFloat(row.querySelector('.item-tax').value) || 0;

    const total = (qty * cost) + tax;
    row.querySelector('.row-total').textContent = new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(total);
  }

  // Calculate all rows on load
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('tr[data-row]').forEach((row) => {
      const idx = row.getAttribute('data-row');
      calculateRow(idx);
    });

    // Autofill supplier details
    const supplierSelect = document.querySelector('select[name="supplier_id"]');
    const contactSelect = document.getElementById('contact_person');
    if (supplierSelect) {
      supplierSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        
        // Reset and populate contacts
        if (contactSelect) {
          contactSelect.innerHTML = '<option value="">-- Select Contact Person --</option>';
          if (option && option.value) {
            const contactsStr = option.getAttribute('data-contacts') || '[]';
            const contacts = JSON.parse(contactsStr);
            contacts.forEach(c => {
              const opt = document.createElement('option');
              opt.value = c.contact_name;
              opt.textContent = c.contact_name + (c.title ? ' (' + c.title + ')' : '');
              contactSelect.appendChild(opt);
            });
            if (contacts.length > 0) {
              contactSelect.value = contacts[0].contact_name;
            }
          }
        }

        if (option && option.value) {
          const updateField = (selector, val) => {
            const el = document.querySelector(selector);
            if (el) el.value = val || '';
          };

          updateField('textarea[name="address"]', option.dataset.address);
          updateField('input[name="bank_account"]', option.dataset.bank);
          updateField('input[name="transfer_to"]', option.dataset.bank);
          const ptSelect = document.querySelector('select[name="payment_terms"]');
          if (ptSelect && option.dataset.paymentTerms) {
            const match = Array.from(ptSelect.options).find(o => o.value === option.dataset.paymentTerms);
            if (match) ptSelect.value = match.value;
          }
        } else {
          ['textarea[name="address"]', 'input[name="bank_account"]', 'input[name="transfer_to"]', 'select[name="payment_terms"]'].forEach(sel => {
            const el = document.querySelector(sel);
            if (el) el.value = '';
          });
        }
      });
    }
  });
</script>
@endsection
