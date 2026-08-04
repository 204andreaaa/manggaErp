@extends('layouts.home')

@section('title', 'Create PO Request: ' . $requestForm->rf_no)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <div class="text-muted small">Create PO Request for</div>
      <h4 class="mb-0 fw-bold">{{ $requestForm->rf_no }}</h4>
    </div>
    <a href="{{ route('erp.procurement.dashboard') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bx bx-arrow-back me-1"></i>Back to Dashboard
    </a>
  </div>

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form action="{{ route('erp.purchase-orders.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="request_form_id" value="{{ $requestForm->id }}">

    <div class="card mb-4">
      <div class="card-header border-bottom py-3">
        <h6 class="mb-0 fw-bold">PO Details</h6>
      </div>
      <div class="card-body mt-3">
        <div class="row">
          <!-- Left Column -->
          <div class="col-md-6">
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">PO No</label>
              <div class="col-sm-9">
                <input type="text" class="form-control" value="{{ $poNo }}" readonly>
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Supplier</label>
              <div class="col-sm-9">
                <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
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
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Destination</label>
              <div class="col-sm-9">
                <select name="erp_warehouse_id" class="form-select">
                  <option value="">-- Select Destination --</option>
                  @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ old('erp_warehouse_id') == $wh->id ? 'selected' : '' }}>
                      {{ $wh->name }} ({{ $wh->warehouse_code }})
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Contact Person</label>
              <div class="col-sm-9">
                <select name="contact_person" id="contact_person" class="form-select @error('contact_person') is-invalid @enderror">
                  <option value="">-- Select Contact Person --</option>
                </select>
                @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Address</label>
              <div class="col-sm-9">
                <textarea name="address" class="form-control bg-light" rows="3" readonly>{{ old('address') }}</textarea>
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Bank Account</label>
              <div class="col-sm-9">
                <input type="text" name="bank_account" class="form-control bg-light" value="{{ old('bank_account') }}" placeholder="e.g. VA BCA 001..." readonly>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="col-md-6">
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Date</label>
              <div class="col-sm-9">
                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">ETA</label>
              <div class="col-sm-9">
                <input type="date" name="eta" class="form-control" value="{{ old('eta') }}">
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Payment Method</label>
              <div class="col-sm-9">
                <select name="payment_method" class="form-select">
                  <option value="Bank Transfer">Bank Transfer</option>
                  <option value="Cash">Cash</option>
                  <option value="COD">COD</option>
                </select>
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Description</label>
              <div class="col-sm-9">
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Attachments</label>
              <div class="col-sm-9">
                <input type="file" name="attachments[]" class="form-control" accept=".jpg,.jpeg,.png,.pdf" multiple>
                <small class="text-muted">You can select multiple files</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Print Related Section -->
    <div class="card mb-4">
      <div class="card-header border-bottom py-3">
        <h6 class="mb-0 fw-bold">Print Related</h6>
      </div>
      <div class="card-body mt-3">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Project</label>
              <div class="col-sm-9">
                @if($requestForm->project_code)
                  <input type="text" name="project" class="form-control bg-light" value="{{ $requestForm->project_code }}" readonly>
                @else
                  <div class="input-group">
                    <span class="input-group-text bg-light">INTERNAL -</span>
                    <input type="hidden" name="project" id="hidden_project" value="{{ old('project', 'INTERNAL') }}">
                    <input type="text" class="form-control" placeholder="e.g. IT, ATK, Dapur..." 
                      value="{{ str_replace('INTERNAL - ', '', old('project', '')) }}"
                      oninput="document.getElementById('hidden_project').value = this.value ? 'INTERNAL - ' + this.value : 'INTERNAL'">
                  </div>
                @endif
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Invoice To</label>
              <div class="col-sm-9">
                <input type="text" name="invoice_to" class="form-control" value="{{ old('invoice_to') }}">
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Attention To</label>
              <div class="col-sm-9">
                <input type="text" name="attention_to" class="form-control" value="{{ old('attention_to') }}">
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Transfer To</label>
              <div class="col-sm-9">
                <input type="text" name="transfer_to" class="form-control bg-light" value="{{ old('transfer_to') }}" readonly>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Payment Terms</label>
              <div class="col-sm-9">
                <select name="payment_terms" class="form-select">
                  <option value="">-- Select Payment Term --</option>
                  @foreach($paymentTerms as $term)
                    <option value="{{ $term->name }}" {{ old('payment_terms') == $term->name ? 'selected' : '' }}>
                      {{ $term->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Signature</label>
              <div class="col-sm-9">
                <select name="signature" class="form-select @error('signature') is-invalid @enderror">
                  <option value="">-- Select User --</option>
                  @foreach($users as $user)
                    <option value="{{ $user->name }}" {{ old('signature') == $user->name ? 'selected' : '' }}>
                      {{ $user->name }}
                    </option>
                  @endforeach
                </select>
                @error('signature')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="mb-3 row">
              <label class="col-sm-3 col-form-label fw-semibold">Other Instructions</label>
              <div class="col-sm-9">
                <textarea name="other_instructions" class="form-control" rows="2">{{ old('other_instructions') }}</textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Items Section -->
    <div class="card mb-4">
      <div class="card-header border-bottom py-3">
        <h6 class="mb-0 fw-bold">PO Items</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 small" id="itemsTable">
          <thead class="table-light">
            <tr>
              <th>RF Item Detail</th>
              <th>Product Name</th>
              <th>UOM</th>
              <th style="width: 120px;">Qty Request</th>
              <th style="width: 120px;">Qty PO</th>
              <th style="width: 150px;">Unit Cost</th>
              <th style="width: 120px;">Tax</th>
              <th style="width: 150px;" class="text-end">Total Cost</th>
              <th>Remarks</th>
              <th style="width: 50px;"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($requestForm->items as $idx => $item)
              @php
                // Get quantity from PR if exists
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
                  {{ $item->rf_detail_no }}
                </td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->erpProduct?->uom?->uom_name ?: '-' }}</td>
                <td>{{ number_format($qty, 2, ',', '.') }}</td>
                <td>
                  <input type="number" step="0.01" name="items[{{ $idx }}][qty]" class="form-control form-control-sm item-qty" value="{{ old("items.$idx.qty", $qty) }}" oninput="calculateRow({{ $idx }})">
                </td>
                <td>
                  <input type="number" name="items[{{ $idx }}][unit_cost]" class="form-control form-control-sm item-cost" value="{{ old("items.$idx.unit_cost", $item->unit_cost) }}" oninput="calculateRow({{ $idx }})">
                </td>
                <td>
                  <input type="number" name="items[{{ $idx }}][tax]" class="form-control form-control-sm item-tax" value="{{ old("items.$idx.tax", 0) }}" oninput="calculateRow({{ $idx }})">
                </td>
                <td class="text-end fw-semibold text-primary">
                  <span class="row-total">0</span>
                </td>
                <td>
                  <input type="text" name="items[{{ $idx }}][remarks]" class="form-control form-control-sm" value="{{ old("items.$idx.remarks", $item->remark) }}">
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="removeRow({{ $idx }})" title="Remove item">
                    <i class="bx bx-x"></i>
                  </button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="p-3 text-muted small bg-light border-top">
        <i class="bx bx-info-circle me-1"></i> * To exclude an item from this Purchase Order, you can click the <strong>X</strong> button to remove it, or change the <strong>Qty PO</strong> to 0.
      </div>
      <div class="card-footer border-top text-end py-3">
        <button type="submit" class="btn btn-primary">Create PO Request</button>
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
            // Auto-select first contact if exists
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
          // Only auto-select if a matching option exists in the dropdown
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
