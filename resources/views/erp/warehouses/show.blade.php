@extends('layouts.home')

@section('title', 'Warehouse Detail: ' . $warehouse->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Top Navigation & Title -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <div class="text-muted small">Warehouse Detail</div>
      <h4 class="mb-0 fw-bold">{{ $warehouse->name }}</h4>
    </div>
    <a href="{{ route('erp.warehouses.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bx bx-arrow-back me-1"></i>Back to List
    </a>
  </div>

  <!-- Detail Card -->
  <div class="card mb-4">
    <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold">Warehouse Detail</h6>
      <div>
        <button class="btn btn-xs btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editWarehouseModal">Edit</button>
        <form action="{{ route('erp.warehouses.destroy', $warehouse) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this warehouse?')">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-xs btn-outline-danger">Delete</button>
        </form>
      </div>
    </div>
    
    <div class="card-body mt-3">
      <div class="row small mb-4">
        <!-- Left Column -->
        <div class="col-md-6 border-end">
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Type</div>
            <div class="col-8">{{ $warehouse->type ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">ID</div>
            <div class="col-8 fw-bold">{{ $warehouse->warehouse_code }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Name</div>
            <div class="col-8 fw-bold text-primary">{{ $warehouse->name }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Address</div>
            <div class="col-8">{{ $warehouse->address ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Phone</div>
            <div class="col-8">{{ $warehouse->phone ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Fax</div>
            <div class="col-8">{{ $warehouse->fax ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Last Stock Take Date</div>
            <div class="col-8">{{ $warehouse->last_stock_take_date?->format('Y/m/d') ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Work</div>
            <div class="col-8">{{ $warehouse->work ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">IsActive</div>
            <div class="col-8">
              @if($warehouse->is_active)
                <i class="bx bx-check text-success fs-4"></i>
              @else
                <i class="bx bx-x text-danger fs-4"></i>
              @endif
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Latitude</div>
            <div class="col-8">{{ $warehouse->latitude ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Longitude</div>
            <div class="col-8">{{ $warehouse->longitude ?: '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Capacity</div>
            <div class="col-8">{{ $warehouse->capacity ? number_format($warehouse->capacity) : '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Total Value</div>
            <div class="col-8 fw-semibold text-success">IDR {{ number_format($warehouse->total_value, 0, ',', '.') }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-end fw-semibold text-muted">Remark</div>
            <div class="col-8">{{ $warehouse->remark ?: '-' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit Warehouse -->
<div class="modal fade" id="editWarehouseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form action="{{ route('erp.warehouses.update', $warehouse) }}" method="POST" class="modal-content">
      @csrf
      @method('PUT')
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">Edit ERP Warehouse Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Warehouse Code</label>
            <input type="text" name="warehouse_code" class="form-control" value="{{ $warehouse->warehouse_code }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Warehouse Name</label>
            <input type="text" name="name" class="form-control" value="{{ $warehouse->name }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Type</label>
            <input type="text" name="type" class="form-control" value="{{ $warehouse->type }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Phone Number</label>
            <input type="text" name="phone" class="form-control" value="{{ $warehouse->phone }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Fax</label>
            <input type="text" name="fax" class="form-control" value="{{ $warehouse->fax }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Last Stock Take Date</label>
            <input type="date" name="last_stock_take_date" class="form-control" value="{{ $warehouse->last_stock_take_date?->format('Y-m-d') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Work</label>
            <input type="text" name="work" class="form-control" value="{{ $warehouse->work }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Latitude</label>
            <input type="text" name="latitude" class="form-control" value="{{ $warehouse->latitude }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Longitude</label>
            <input type="text" name="longitude" class="form-control" value="{{ $warehouse->longitude }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Capacity</label>
            <input type="number" name="capacity" class="form-control" value="{{ $warehouse->capacity }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Total Value (IDR)</label>
            <input type="number" step="0.01" name="total_value" class="form-control" value="{{ $warehouse->total_value }}">
          </div>
          <div class="col-md-6 d-flex align-items-center">
            <div class="form-check mt-4">
              <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1" {{ $warehouse->is_active ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="edit_is_active">Active Warehouse</label>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Billing Address</label>
            <textarea name="address" class="form-control" rows="2">{{ $warehouse->address }}</textarea>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Remark</label>
            <textarea name="remark" class="form-control" rows="2">{{ $warehouse->remark }}</textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Warehouse</button>
      </div>
    </form>
  </div>
</div>
@endsection
