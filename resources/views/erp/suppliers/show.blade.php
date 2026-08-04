@extends('layouts.home')

@section('title', 'Supplier Detail: ' . $supplier->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Top Navigation & Title -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <div class="text-muted small">Account Detail</div>
      <h4 class="mb-0 fw-bold">{{ $supplier->name }}</h4>
    </div>
    <a href="{{ route('erp.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bx bx-arrow-back me-1"></i>Back to List
    </a>
  </div>

  <!-- Detail Card -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold">Account Detail</h6>
      <div>
        <button class="btn btn-xs btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSupplierModal">Edit</button>
        <form action="{{ route('erp.suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this supplier?')">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-xs btn-outline-danger">Delete</button>
        </form>
      </div>
    </div>
    
    <div class="card-body mt-3">
      <!-- Grid Fields -->
      <div class="row small mb-4">
        <!-- Left Column -->
        <div class="col-md-6 border-end">
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Parent Account</div>
            <div class="col-8">{{ $supplier->parent_account ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Account Owner</div>
            <div class="col-8">Team Procurement</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Account Name</div>
            <div class="col-8 fw-bold">{{ $supplier->name }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Industry</div>
            <div class="col-8">{{ $supplier->industry ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Category</div>
            <div class="col-8">{{ $supplier->category ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Products</div>
            <div class="col-8">{{ $supplier->products ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Billing Address</div>
            <div class="col-8">{{ $supplier->address ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Phone</div>
            <div class="col-8">{{ $supplier->phone ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Fax</div>
            <div class="col-8">{{ $supplier->fax ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Website</div>
            <div class="col-8">
              @if($supplier->website)
                <a href="{{ $supplier->website }}" target="_blank" class="text-primary text-decoration-none">{{ $supplier->website }}</a>
              @else
                -
              @endif
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Bank Account</div>
            <div class="col-8 fw-bold">
              @if($supplier->bank_name || $supplier->bank_account)
                {{ $supplier->bank_name }} {{ $supplier->bank_account }}
              @else
                -
              @endif
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Classification</div>
            <div class="col-8">{{ $supplier->classification ?: 'Unclassified' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Products Provided</div>
            <div class="col-8">{{ $supplier->products_provided ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Services Provided</div>
            <div class="col-8">{{ $supplier->services_provided ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">PO Issued YTD</div>
            <div class="col-8 fw-semibold text-primary">{{ $poCount }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Payment Terms</div>
            <div class="col-8">{{ $supplier->paymentTerm?->name ?: 'Payment In Advance' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Account Record Type</div>
            <div class="col-8">Supplier</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Create By</div>
            <div class="col-8">System Admin</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Remark</div>
            <div class="col-8">{{ $supplier->note ?: '-' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Related List: Contacts -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold"><i class="bx bx-group me-1 text-primary"></i>Contacts</h6>
      <div>
        <button class="btn btn-xs btn-primary" data-bs-toggle="modal" data-bs-target="#newContactModal">New Contact</button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th style="width: 100px;">Action</th>
            <th>Contact Name</th>
            <th>Title</th>
            <th>Email</th>
            <th>Phone</th>
          </tr>
        </thead>
        <tbody>
          @forelse($supplier->contacts as $contact)
            <tr>
              <td>
                <form action="{{ route('erp.suppliers.contacts.destroy', $contact) }}" method="POST" onsubmit="return confirm('Delete this contact?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-xs text-danger border-0 bg-transparent p-0">Del</button>
                </form>
              </td>
              <td>
                <span class="fw-semibold text-primary">{{ $contact->contact_name }}</span>
              </td>
              <td>{{ $contact->title ?: '-' }}</td>
              <td>{{ $contact->email ?: '-' }}</td>
              <td>{{ $contact->phone ?: '-' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No contacts recorded</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Related List: Notes & Attachments -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold"><i class="bx bx-file me-1 text-primary"></i>Notes & Attachments</h6>
      <div>
        <button class="btn btn-xs btn-primary" data-bs-toggle="modal" data-bs-target="#newAttachmentModal">Attach File</button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th style="width: 120px;">Action</th>
            <th>Type</th>
            <th>Title</th>
            <th>Last Modified</th>
            <th>Created By</th>
          </tr>
        </thead>
        <tbody>
          @forelse($supplier->attachments as $att)
            <tr>
              <td>
                <div class="d-flex gap-2">
                  <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="text-primary">View</a>
                  <span class="text-muted">|</span>
                  <form action="{{ route('erp.suppliers.attachments.destroy', $att) }}" method="POST" onsubmit="return confirm('Delete this attachment?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-xs text-danger border-0 bg-transparent p-0">Del</button>
                  </form>
                </div>
              </td>
              <td>{{ $att->type }}</td>
              <td>
                <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="fw-semibold text-primary">
                  {{ $att->title }}
                </a>
              </td>
              <td>{{ $att->updated_at->format('Y/m/d H:i') }}</td>
              <td>{{ $att->created_by ?: 'System' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No attachments recorded</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Edit Supplier -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form action="{{ route('erp.suppliers.update', $supplier) }}" method="POST" class="modal-content">
      @csrf
      @method('PUT')
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">Edit ERP Supplier Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Supplier Code</label>
            <input type="text" name="supplier_code" class="form-control" value="{{ $supplier->supplier_code }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Company Name</label>
            <input type="text" name="name" class="form-control" value="{{ $supplier->name }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Parent Account</label>
            <input type="text" name="parent_account" class="form-control" value="{{ $supplier->parent_account }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Classification</label>
            <select name="classification" class="form-select">
              <option value="Unclassified" {{ $supplier->classification === 'Unclassified' ? 'selected' : '' }}>Unclassified</option>
              <option value="VIP" {{ $supplier->classification === 'VIP' ? 'selected' : '' }}>VIP</option>
              <option value="Regular" {{ $supplier->classification === 'Regular' ? 'selected' : '' }}>Regular</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Industry</label>
            <input type="text" name="industry" class="form-control" value="{{ $supplier->industry }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Category</label>
            <input type="text" name="category" class="form-control" value="{{ $supplier->category }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Products Provided</label>
            <input type="text" name="products_provided" class="form-control" value="{{ $supplier->products_provided }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Services Provided</label>
            <input type="text" name="services_provided" class="form-control" value="{{ $supplier->services_provided }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Products</label>
            <input type="text" name="products" class="form-control" value="{{ $supplier->products }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Payment Terms</label>
            <select name="payment_terms_id" class="form-select">
              <option value="">-- None --</option>
              @foreach($paymentTerms as $term)
                <option value="{{ $term->id }}" {{ $supplier->payment_terms_id == $term->id ? 'selected' : '' }}>
                  {{ $term->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Bank Name</label>
            <input type="text" name="bank_name" class="form-control" value="{{ $supplier->bank_name }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Bank Account</label>
            <input type="text" name="bank_account" class="form-control" value="{{ $supplier->bank_account }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Billing Address</label>
            <input type="text" name="address" class="form-control" value="{{ $supplier->address }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Phone</label>
            <input type="text" name="phone" class="form-control" value="{{ $supplier->phone }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Fax</label>
            <input type="text" name="fax" class="form-control" value="{{ $supplier->fax }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Website</label>
            <input type="text" name="website" class="form-control" value="{{ $supplier->website }}">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Remark / Note</label>
            <textarea name="note" class="form-control" rows="2">{{ $supplier->note }}</textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Supplier</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal New Contact -->
<div class="modal fade" id="newContactModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="{{ route('erp.suppliers.contacts.store', $supplier) }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">Add New Contact</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Contact Name</label>
          <input type="text" name="contact_name" class="form-control" required placeholder="e.g. Susi">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Title</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Accounts Officer">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" name="email" class="form-control" placeholder="e.g. susi@amantelepower.com">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Phone</label>
          <input type="text" name="phone" class="form-control" placeholder="e.g. +6221-6680916">
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Contact</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal New Attachment -->
<div class="modal fade" id="newAttachmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="{{ route('erp.suppliers.attachments.store', $supplier) }}" method="POST" enctype="multipart/form-data" class="modal-content">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">Attach File</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Attachment Title</label>
          <input type="text" name="title" class="form-control" required placeholder="e.g. form seleksi supplier">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Choose File</label>
          <input type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Upload Attachment</button>
      </div>
    </form>
  </div>
</div>
@endsection
