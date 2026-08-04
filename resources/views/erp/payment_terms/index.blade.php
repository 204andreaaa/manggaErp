@extends('layouts.home')

@section('title', 'Payment Terms (TOP) Picklist')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <div class="text-muted small">Add Picklist Values</div>
      <h4 class="mb-0 fw-bold">Payment Terms</h4>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <p class="text-muted small mb-4">
        Add one or more picklist values below. Each value should be on its own line and it is used for both a value's label and API name.
      </p>

      <form action="{{ route('erp.payment-terms.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <textarea name="values" class="form-control font-monospace" rows="12" style="border: 2px solid #697a8d; border-radius: 4px; padding: 10px;" placeholder="e.g.&#10;Payment In Advance&#10;COD&#10;Net 15&#10;Net 30&#10;Net 60">{{ $activeTerms }}</textarea>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary">Save</button>
          <a href="{{ route('erp.suppliers.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
