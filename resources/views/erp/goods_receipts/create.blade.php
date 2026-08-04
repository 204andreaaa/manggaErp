@extends('layouts.home')

@section('title', 'Create External DO')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-header border-bottom">
      <h5 class="mb-0 fw-bold">Create External DO</h5>
    </div>
    
    <div class="card-body mt-4">
      <form action="{{ route('erp.goods-receipts.store', $purchaseOrder) }}" method="POST">
        @csrf
        
        <!-- Header Info -->
        <div class="row mb-3">
          <div class="col-md-2 text-end fw-semibold text-muted align-self-center">P.I.C.</div>
          <div class="col-md-4">
            <input type="text" class="form-control form-control-sm bg-light" value="{{ auth()->user()->name }}" readonly>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-2 text-end fw-semibold text-muted align-self-center">Warehouse</div>
          <div class="col-md-4">
            <input type="text" class="form-control form-control-sm bg-light" value="{{ $purchaseOrder->warehouse?->name ?: '-' }}" readonly>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-2 text-end fw-semibold text-muted">PO Number</div>
          <div class="col-md-4 fw-bold">{{ $purchaseOrder->po_no }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-md-2 text-end fw-semibold text-muted">Status</div>
          <div class="col-md-4 fw-bold text-success">{{ $purchaseOrder->status }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-md-2 text-end fw-semibold text-muted">Supplier Name</div>
          <div class="col-md-4">{{ $purchaseOrder->supplier?->name ?: '-' }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-md-2 text-end fw-semibold text-muted">Supplier Address</div>
          <div class="col-md-6">{{ $purchaseOrder->address ?: '-' }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-md-2 text-end fw-semibold text-muted align-self-center">Supplier DO No</div>
          <div class="col-md-4">
            <input type="text" name="supplier_do_no" class="form-control form-control-sm border-danger" placeholder="Supplier DO No (Optional)">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-2 text-end fw-semibold text-muted">Remark</div>
          <div class="col-md-4">
            <textarea name="remarks" class="form-control form-control-sm border-danger" rows="3">{{ old('remarks') }}</textarea>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-2 text-end fw-semibold text-muted align-self-center">DO Date</div>
          <div class="col-md-4">
            <input type="date" name="date" class="form-control form-control-sm border-danger" value="{{ old('date', date('Y-m-d')) }}" required>
          </div>
        </div>

        <!-- DO Items Table -->
        <div class="table-responsive mt-4">
          <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Product</th>
                <th>Product Description</th>
                <th>UOM</th>
                <th>Remark</th>
                <th class="text-end">Required Qty</th>
                <th class="text-end">Delivered Qty</th>
                <th class="text-end">Received Qty</th>
              </tr>
            </thead>
            <tbody>
              @foreach($purchaseOrder->items as $index => $item)
                <tr>
                  <td>{{ $item->requestFormItem?->product_name ?: '-' }}</td>
                  <td>{{ $item->requestFormItem?->product_description ?: '-' }}</td>
                  <td>{{ $item->requestFormItem?->erpProduct?->uom?->name ?: '-' }}</td>
                  <td>
                    [{{ $purchaseOrder->remark_print ?? 'Biaya Penghemat Telepon Periode Juni 2026' }}]
                    <input type="hidden" name="items[{{ $index }}][po_item_id]" value="{{ $item->id }}">
                    <input type="hidden" name="items[{{ $index }}][remark]" value="[{{ $purchaseOrder->remark_print ?? 'Biaya Penghemat Telepon Periode Juni 2026' }}]">
                  </td>
                  <td class="text-end fw-bold">{{ floatval($item->qty) }}</td>
                  <td class="text-end">
                    <input type="number" step="0.01" name="items[{{ $index }}][delivered_qty]" class="form-control form-control-sm text-end border-danger" value="{{ old('items.'.$index.'.delivered_qty', 0) }}" required min="0">
                  </td>
                  <td class="text-end">
                    <input type="number" step="0.01" name="items[{{ $index }}][received_qty]" class="form-control form-control-sm text-end border-danger" value="{{ old('items.'.$index.'.received_qty', 0) }}" required min="0">
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="mt-4 text-center">
          <button type="submit" class="btn btn-sm btn-outline-secondary">Create</button>
          <a href="{{ route('erp.purchase-orders.show', $purchaseOrder) }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
