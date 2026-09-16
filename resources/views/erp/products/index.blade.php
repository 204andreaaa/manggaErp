@extends('layouts.home')

@section('title', 'ERP Products')

@section('content')
<style>
  #dtProducts {
    border-collapse: separate;
    border-spacing: 0;
  }
  #dtProducts thead th {
    background-color: #f8fafc !important;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.6px;
    padding: 14px 16px;
    border-bottom: 1px solid #e2e8f0;
  }
  #dtProducts tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
  }
  #dtProducts tbody tr:hover {
    background-color: #f8fafc !important;
  }
  .prod-img-box {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    flex-shrink: 0;
  }
  .prod-img-box.clickable-img {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .prod-img-box.clickable-img:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
  }
  .prod-img-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: #f1f5f9;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
  }
  .prod-title {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
    margin-bottom: 2px;
  }
  .prod-desc {
    font-size: 12px;
    color: #64748b;
    line-height: 1.2;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .category-badge-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .category-icon-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
  }
  .badge-status-active {
    background-color: #dcfce7 !important;
    color: #166534 !important;
    font-weight: 700;
    font-size: 11px;
    padding: 5px 12px;
    border-radius: 20px;
    letter-spacing: 0.5px;
    display: inline-block;
  }
  .badge-status-inactive {
    background-color: #fee2e2 !important;
    color: #991b1b !important;
    font-weight: 700;
    font-size: 11px;
    padding: 5px 12px;
    border-radius: 20px;
    letter-spacing: 0.5px;
    display: inline-block;
  }
  .action-btn-custom {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    transition: all 0.2s ease;
    cursor: pointer;
    font-size: 16px;
  }
  .action-btn-edit:hover {
    background: #fef3c7;
    color: #d97706;
    border-color: #fde68a;
  }
  .action-btn-delete:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- TOP BAR --}}
  <div class="card mb-3">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 small text-uppercase fw-bold text-muted me-1">SHOW</label>
        <select id="pageLength" class="form-select form-select-sm" style="width:80px;">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="text-muted small ms-2">entries</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" id="dtSearch" class="form-control form-control-sm" placeholder="Search product...">
        </div>
        @if(auth()->user()->hasPermission('products.export'))
          <a href="{{ route('erp.products.export') }}" class="btn btn-outline-success btn-sm px-3">
            <i class="bx bx-download me-1"></i> Export Excel
          </a>
        @endif
        @if(auth()->user()->hasPermission('products.create'))
          <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalCreate">
            <i class="bx bx-plus me-1"></i> Add Product
          </button>
        @endif
      </div>
    </div>
  </div>

  {{-- TABLE CARD --}}
  <div class="card">
    <div class="table-responsive">
      <table id="dtProducts" class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th class="text-uppercase fw-bold" style="width:4%;">NO</th>
            <th class="text-uppercase fw-bold" style="width:30%;">PRODUCT</th>
            <th class="text-uppercase fw-bold" style="width:16%;">CATEGORY</th>
            <th class="text-uppercase fw-bold" style="width:12%;">CODE / SKU</th>
            <th class="text-uppercase fw-bold" style="width:10%;">ITEM TYPE</th>
            <th class="text-uppercase fw-bold" style="width:8%;">UOM</th>
            <th class="text-uppercase fw-bold" style="width:12%;">PRICE</th>
            <th class="text-uppercase fw-bold text-center" style="width:8%;">STATUS</th>
            <th class="text-uppercase fw-bold text-center" style="width:8%;">ACTIONS</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

{{-- ======================== CREATE MODAL ======================== --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <form id="formCreate" class="modal-content" method="POST" action="{{ route('erp.products.store') }}" enctype="multipart/form-data">
      @csrf

      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">
          <i class="bx bx-package me-2 text-primary"></i>Add ERP Product
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4">
        <div class="row g-4">

          {{-- ===== LEFT: Product Info ===== --}}
          <div class="col-md-6">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="bg-primary bg-opacity-10 rounded p-1">
                <i class="bx bx-info-circle text-primary fs-5"></i>
              </div>
              <h6 class="fw-bold mb-0 text-primary">Product Information</h6>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Code</label>
              <input name="product_code" class="form-control" value="{{ $nextProductCode }}" placeholder="Auto-generated e.g. EPRD-001">
              <div class="form-text">Leave empty to auto-generate</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Part Number</label>
              <input name="part_number" class="form-control" placeholder="e.g. PN-9988">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Name <span class="text-danger">*</span></label>
              <input name="name" class="form-control" required placeholder="e.g. Samsung Galaxy Book4">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Image (Gambar Produk)</label>
              <div class="paste-dropzone" data-target="#create_product_image_input">
                <input type="file" name="image" id="create_product_image_input" class="d-none paste-file-input" accept="image/*">
                <div class="dropzone-idle py-2">
                  <i class="bx bx-image-add dropzone-icon text-primary"></i>
                  <div class="fw-semibold text-dark fs-7">Klik untuk Browse File atau <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-0.5"><i class="bx bx-paste me-1"></i>Ctrl + V</span> Paste Screenshot</div>
                  <div class="text-muted small" style="font-size: 0.72rem;">JPG, JPEG, PNG, WEBP (Maks 5MB)</div>
                </div>
                <div class="dropzone-preview d-none text-start"></div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Item Type (Fisik vs Non-Fisik) <span class="text-danger">*</span></label>
              <select name="is_physical" class="form-select" required>
                <option value="1" selected>📦 Fisik (Track Inventory Stock)</option>
                <option value="0">🔧 Non-Fisik / Jasa / Biaya (No Inventory)</option>
              </select>
              <div class="form-text">Barang fisik akan dihitung stoknya di gudang saat DO diverifikasi.</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">UOM (Unit of Measure) <span class="text-danger">*</span></label>
              <select name="uom_id" class="form-select" required>
                <option value="">-- Select UOM --</option>
                @foreach($uoms as $uom)
                  <option value="{{ $uom->id }}">{{ $uom->uom_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Buying / Leasing Price <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bx bx-money"></i></span>
                <input type="number" name="buying_price" class="form-control" required min="0" value="0" step="any">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Currency</label>
              <select name="currency_id" class="form-select">
                <option value="">-- Select Currency --</option>
                @foreach($currencies as $curr)
                  <option value="{{ $curr->id }}">{{ $curr->code }} – {{ $curr->name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- ===== RIGHT: Classifications ===== --}}
          <div class="col-md-6">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="bg-warning bg-opacity-10 rounded p-1">
                <i class="bx bx-category text-warning fs-5"></i>
              </div>
              <h6 class="fw-bold mb-0 text-warning">ERP Classifications</h6>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Family</label>
              <select name="product_family_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($families as $fam)
                  <option value="{{ $fam->id }}">{{ $fam->family_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Type</label>
              <select name="product_type_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($types as $typ)
                  <option value="{{ $typ->id }}">{{ $typ->type_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Brand</label>
              <select name="brand_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($brands as $brnd)
                  <option value="{{ $brnd->id }}">{{ $brnd->brand_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Model</label>
              <select name="product_model_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($models as $mdl)
                  <option value="{{ $mdl->id }}">{{ $mdl->model_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Description</label>
              <textarea name="description" rows="3" class="form-control" placeholder="Specifications, notes, etc..."></textarea>
            </div>

            <div class="form-check form-switch mt-3">
              <input name="is_active" type="checkbox" class="form-check-input" id="isActiveCreate" value="1" checked>
              <label class="form-check-label fw-semibold" for="isActiveCreate">Active Status</label>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer border-top">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary px-4" type="submit" id="btnCreateProduct">
          <i class="bx bx-save me-1"></i>Save Product
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ======================== EDIT MODAL ======================== --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <form id="formEdit" class="modal-content" method="POST" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="_method" value="PUT">
      <input type="hidden" name="edit_id" id="edit_id">

      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">
          <i class="bx bx-edit-alt me-2 text-warning"></i>Edit ERP Product
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4">
        <div class="row g-4">

          {{-- ===== LEFT: Product Info ===== --}}
          <div class="col-md-6">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="bg-primary bg-opacity-10 rounded p-1">
                <i class="bx bx-info-circle text-primary fs-5"></i>
              </div>
              <h6 class="fw-bold mb-0 text-primary">Product Information</h6>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Code</label>
              <input name="product_code" id="edit_product_code" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Part Number</label>
              <input name="part_number" id="edit_part_number" class="form-control">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Name <span class="text-danger">*</span></label>
              <input name="name" id="edit_name" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Image (Gambar Produk)</label>
              <div class="d-flex align-items-center gap-3 mb-2">
                <div id="edit_image_preview_container" class="rounded border p-1 bg-light d-flex align-items-center justify-content-center" style="width:50px; height:50px; flex-shrink:0;">
                  <img id="edit_image_preview" src="" class="rounded" style="max-width:100%; max-height:100%; object-fit:cover; display:none;">
                  <i id="edit_image_icon" class="bx bx-package fs-3 text-muted"></i>
                </div>
                <div class="text-muted small">Gambar saat ini. Untuk mengganti, gunakan dropzone atau paste screenshot di bawah.</div>
              </div>
              <div class="paste-dropzone" data-target="#edit_product_image_input">
                <input type="file" name="image" id="edit_product_image_input" class="d-none paste-file-input" accept="image/*">
                <div class="dropzone-idle py-2">
                  <i class="bx bx-image-add dropzone-icon text-primary"></i>
                  <div class="fw-semibold text-dark fs-7">Klik untuk Browse File atau <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-0.5"><i class="bx bx-paste me-1"></i>Ctrl + V</span> Paste Screenshot</div>
                  <div class="text-muted small" style="font-size: 0.72rem;">JPG, PNG, WEBP (Biarkan kosong jika tidak ingin ganti)</div>
                </div>
                <div class="dropzone-preview d-none text-start"></div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Item Type (Fisik vs Non-Fisik) <span class="text-danger">*</span></label>
              <select name="is_physical" id="edit_is_physical" class="form-select" required>
                <option value="1">📦 Fisik (Track Inventory Stock)</option>
                <option value="0">🔧 Non-Fisik / Jasa / Biaya (No Inventory)</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">UOM (Unit of Measure) <span class="text-danger">*</span></label>
              <select name="uom_id" id="edit_uom_id" class="form-select" required>
                <option value="">-- Select UOM --</option>
                @foreach($uoms as $uom)
                  <option value="{{ $uom->id }}">{{ $uom->uom_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Buying / Leasing Price <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bx bx-money"></i></span>
                <input type="number" name="buying_price" id="edit_buying_price" class="form-control" required min="0" step="any">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Currency</label>
              <select name="currency_id" id="edit_currency_id" class="form-select">
                <option value="">-- Select Currency --</option>
                @foreach($currencies as $curr)
                  <option value="{{ $curr->id }}">{{ $curr->code }} – {{ $curr->name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- ===== RIGHT: Classifications ===== --}}
          <div class="col-md-6">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="bg-warning bg-opacity-10 rounded p-1">
                <i class="bx bx-category text-warning fs-5"></i>
              </div>
              <h6 class="fw-bold mb-0 text-warning">ERP Classifications</h6>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Family</label>
              <select name="product_family_id" id="edit_product_family_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($families as $fam)
                  <option value="{{ $fam->id }}">{{ $fam->family_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Type</label>
              <select name="product_type_id" id="edit_product_type_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($types as $typ)
                  <option value="{{ $typ->id }}">{{ $typ->type_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Brand</label>
              <select name="brand_id" id="edit_brand_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($brands as $brnd)
                  <option value="{{ $brnd->id }}">{{ $brnd->brand_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Model</label>
              <select name="product_model_id" id="edit_product_model_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($models as $mdl)
                  <option value="{{ $mdl->id }}">{{ $mdl->model_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small text-uppercase text-muted">Product Description</label>
              <textarea name="description" id="edit_description" rows="3" class="form-control"></textarea>
            </div>

            <div class="form-check form-switch mt-3">
              <input name="is_active" type="checkbox" class="form-check-input" id="edit_is_active" value="1">
              <label class="form-check-label fw-semibold" for="edit_is_active">Active Status</label>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer border-top">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-warning text-white px-4" type="submit" id="btnEditProduct">
          <i class="bx bx-save me-1"></i>Update Product
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ======================== IMAGE PREVIEW POPUP MODAL ======================== --}}
<div class="modal fade" id="modalImagePreview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 16px;">
      <div class="modal-header py-2 px-3 bg-light border-bottom d-flex align-items-center justify-content-between">
        <span id="previewModalTitle" class="fw-bold text-dark small mb-0 text-truncate me-2" style="max-width: 300px;"></span>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3 text-center bg-white">
        <img id="previewModalImage" src="" class="img-fluid rounded-3 border" style="max-height: 320px; width: 100%; object-fit: contain;">
      </div>
    </div>
  </div>
</div>

{{-- ======================== PRICE HISTORY MODAL ======================== --}}
<div class="modal fade" id="modalPriceHistory" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header py-3 px-4 bg-primary bg-opacity-10 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <div class="p-2 bg-primary text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
            <i class="bx bx-history fs-4"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark mb-0" id="histProdName">Riwayat Perubahan Harga Produk</h5>
            <div class="text-muted small" id="histProdCode">SKU / Kode: -</div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4 bg-light bg-opacity-50">
        {{-- Top Summary Card --}}
        <div class="card shadow-none border rounded-3 p-3 bg-white mb-4">
          <div class="row align-items-center g-3">
            <div class="col-auto">
              <img id="histProdImg" src="" class="prod-img-box rounded-3" style="width: 56px; height: 56px; object-fit: cover;">
            </div>
            <div class="col">
              <span class="badge bg-label-info mb-1" id="histProdCat">Category</span>
              <h6 class="fw-bold text-dark mb-1" id="histProdTitle">-</h6>
              <div class="text-muted small">Satuan / UOM: <span class="fw-semibold text-dark" id="histProdUom">-</span></div>
            </div>
            <div class="col-auto text-end">
              <div class="text-muted small fw-semibold mb-1">HARGA TERAKHIR DARI PO</div>
              <h4 class="fw-extrabold text-primary mb-0" id="histProdActivePrice">Rp 0</h4>
              <span class="badge bg-label-success small mt-1"><i class="bx bx-check-circle me-1"></i>Harga Otomatis Ter-update</span>
            </div>
          </div>
        </div>

        {{-- History Table Container --}}
        <div class="card shadow-none border rounded-3 bg-white overflow-hidden">
          <div class="card-header bg-white border-bottom py-2.5 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold text-dark small"><i class="bx bx-receipt text-primary me-1.5"></i>Log PO Terverifikasi & Approved</h6>
            <span class="badge bg-label-primary" id="histPoCountBadge">0 Transaksi PO</span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="histTable">
              <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                  <th class="ps-3" style="width: 24%;">No. PO & Tanggal</th>
                  <th style="width: 26%;">Vendor / Supplier</th>
                  <th class="text-center" style="width: 14%;">Qty Dibeli</th>
                  <th class="text-end" style="width: 18%;">Harga Satuan</th>
                  <th class="text-center" style="width: 18%;">Perubahan</th>
                </tr>
              </thead>
              <tbody id="histTableBody" class="small">
                {{-- Dynamic Rows --}}
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-white border-top py-2.5">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    window.openImagePreview = function(src, title) {
        $('#previewModalImage').attr('src', src);
        $('#previewModalTitle').text(title || 'Gambar Produk');
        new bootstrap.Modal($('#modalImagePreview')[0]).show();
    };

    window.openPriceHistory = async function(productId) {
        const modalEl = $('#modalPriceHistory');
        const modal = new bootstrap.Modal(modalEl[0]);
        
        $('#histProdTitle').text('Memuat data...');
        $('#histProdCode').text('SKU: -');
        $('#histProdActivePrice').text('...');
        $('#histTableBody').html('<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat riwayat perubahan harga...</td></tr>');
        modal.show();

        try {
            const res = await fetch(`{{ url('erp/products') }}/${productId}/price-history`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                }
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal mengambil riwayat harga');

            const p = data.product;
            $('#histProdName').text(p.name);
            $('#histProdTitle').text(p.name);
            $('#histProdCode').text('Kode: ' + p.product_code);
            $('#histProdCat').text(p.category);
            $('#histProdUom').text(p.uom);
            $('#histProdActivePrice').text(p.buying_price_formatted);
            $('#histPoCountBadge').text(data.total_po_count + ' Transaksi PO Approved');
            
            if (p.image_url) {
                $('#histProdImg').attr('src', p.image_url).show();
            } else {
                $('#histProdImg').attr('src', '{{ asset("assets/img/illustrations/page-pricing-standard.png") }}').show();
            }

            const tbody = $('#histTableBody');
            tbody.empty();

            if (data.history.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bx bx-info-circle fs-3 text-secondary d-block mb-1"></i>
                            <div class="fw-semibold text-dark">Belum Ada Riwayat PO Approved</div>
                            <small>Harga saat ini merupakan harga master awal dan belum pernah diubah oleh transaksi PO.</small>
                        </td>
                    </tr>
                `);
            } else {
                data.history.forEach((h, idx) => {
                    let trendBadge = '';
                    if (h.trend === 'up') {
                        trendBadge = `<span class="badge bg-label-danger"><i class="bx bx-trending-up me-1"></i>+${h.diff_percent}% (${h.diff_amount_formatted})</span>`;
                    } else if (h.trend === 'down') {
                        trendBadge = `<span class="badge bg-label-success"><i class="bx bx-trending-down me-1"></i>${h.diff_percent}% (${h.diff_amount_formatted})</span>`;
                    } else if (h.trend === 'same') {
                        trendBadge = `<span class="badge bg-label-secondary"><i class="bx bx-minus me-1"></i>Tetap</span>`;
                    } else {
                        trendBadge = `<span class="badge bg-label-primary"><i class="bx bx-star me-1"></i>Harga Awal PO</span>`;
                    }

                    const isLatest = idx === 0;
                    const rowHighlight = isLatest ? 'class="table-primary bg-opacity-25 fw-semibold"' : '';

                    tbody.append(`
                        <tr ${rowHighlight}>
                            <td class="ps-3">
                                <a href="${h.po_url}" target="_blank" class="fw-bold text-primary text-decoration-none">
                                    <i class="bx bx-file me-1"></i>${h.po_no}
                                </a>
                                ${isLatest ? '<span class="badge bg-primary ms-1" style="font-size:0.65rem;">Terbaru</span>' : ''}
                                <div class="text-muted" style="font-size:0.75rem;">Approved: ${h.date}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">${h.supplier_name}</div>
                                <small class="text-muted" style="font-size:0.75rem;">Approver: ${h.approver}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-dark">${h.qty}</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold text-dark">${h.unit_cost_formatted}</div>
                                <div class="text-muted" style="font-size:0.72rem;">Total: ${h.total_cost_formatted}</div>
                            </td>
                            <td class="text-center">
                                ${trendBadge}
                            </td>
                        </tr>
                    `);
                });
            }

        } catch (err) {
            $('#histTableBody').html(`<tr><td colspan="5" class="text-center py-3 text-danger"><i class="bx bx-error me-1"></i>${err.message}</td></tr>`);
        }
    };

    const DT = $('#dtProducts').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "{{ route('erp.products.datatable') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': CSRF }
        },
        columns: [
            { data: 'rownum',       name: 'id',                className: 'align-middle fw-semibold text-muted', orderable: false },
            { data: 'product',      name: 'name',              className: 'align-middle' },
            { data: 'category',     name: 'product_family_id', className: 'align-middle' },
            { data: 'product_code', name: 'product_code',      className: 'align-middle' },
            { data: 'item_type',    name: 'is_physical',       className: 'align-middle' },
            { data: 'uom',          name: 'uom_id',            className: 'align-middle' },
            { data: 'buying_price', name: 'buying_price',      className: 'align-middle' },
            { data: 'is_active',    name: 'is_active',         className: 'text-center align-middle', orderable: false },
            { data: 'actions',      orderable: false,          searchable: false, className: 'text-center align-middle' },
        ],
        dom: 'tip',
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' },
        order: [[1, 'asc']]
    });

    $('#pageLength').on('change', function () { DT.page.len(+this.value).draw(); });
    $('#dtSearch').on('keyup change', function () { DT.search(this.value).draw(); });

    const toast = (msg, icon = 'success') =>
        Swal.fire({ icon, title: msg, timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });

    // ---- CREATE ----
    $('#formCreate').on('submit', async function (e) {
        e.preventDefault();
        const btn = $('#btnCreateProduct');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        try {
            const res = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                }
            });
            let json = {};
            try { json = await res.json(); } catch(err) { throw new Error('Server error (' + res.status + ').'); }
            if (!res.ok) throw new Error(json.message || Object.values(json.errors || {}).flat().join('\n'));
            bootstrap.Modal.getInstance($('#modalCreate')[0]).hide();
            this.reset();

            // Refresh next code
            fetch("{{ route('erp.products.next_code') }}", { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => { if (d.next_code) $('[name="product_code"]', this).val(d.next_code); });

            DT.ajax.reload(null, false);
            toast('Product created successfully!');
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Product');
        }
    });

    // ---- PREVIEW IMAGE ----
    window.previewImage = function(input, previewImgSelector) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(previewImgSelector).attr('src', e.target.result).show();
                $('#edit_image_icon').hide();
                $('#create_image_preview_box').removeClass('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    };

    // ---- EDIT open ----
    window.openEdit = function (id, code, partNum, name, desc, uomId, buyingPrice, familyId, typeId, brandId, modelId, currencyId, isActive, isPhysical, imageUrl) {
        $('#edit_id').val(id);
        $('#edit_product_code').val(code);
        $('#edit_part_number').val(partNum);
        $('#edit_name').val(name);
        $('#edit_description').val(desc || '');
        $('#edit_uom_id').val(uomId || '');
        $('#edit_buying_price').val(buyingPrice);
        $('#edit_product_family_id').val(familyId || '');
        $('#edit_product_type_id').val(typeId || '');
        $('#edit_brand_id').val(brandId || '');
        $('#edit_product_model_id').val(modelId || '');
        $('#edit_currency_id').val(currencyId || '');
        $('#edit_is_active').prop('checked', parseInt(isActive, 10) === 1);
        $('#edit_is_physical').val(isPhysical !== undefined ? isPhysical : 1);
        
        // Reset file input & dropzone
        $('#formEdit input[type="file"]').val('');
        if (window.resetDropzone) {
            window.resetDropzone('#modalEdit .paste-dropzone');
        }
        if (imageUrl) {
            $('#edit_image_preview').attr('src', imageUrl).show();
            $('#edit_image_icon').hide();
        } else {
            $('#edit_image_preview').attr('src', '').hide();
            $('#edit_image_icon').show();
        }

        $('#formEdit').attr('action', "{{ route('erp.products.update', ':id') }}".replace(':id', id));
        new bootstrap.Modal($('#modalEdit')[0]).show();
    };

    // ---- EDIT submit ----
    $('#formEdit').on('submit', async function (e) {
        e.preventDefault();
        const btn = $('#btnEditProduct');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        try {
            const res = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                }
            });
            let json = {};
            try { json = await res.json(); } catch(err) { throw new Error('Server error (' + res.status + ').'); }
            if (!res.ok) throw new Error(json.message || Object.values(json.errors || {}).flat().join('\n'));
            bootstrap.Modal.getInstance($('#modalEdit')[0]).hide();
            DT.ajax.reload(null, false);
            toast('Product updated successfully!');
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        } finally {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Update Product');
        }
    });

    // ---- DELETE ----
    window.deleteItem = async function (id) {
        const ask = await Swal.fire({
            icon: 'warning', title: 'Delete Product?',
            text: 'This action cannot be undone.',
            showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel'
        });
        if (!ask.isConfirmed) return;
        try {
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('_method', 'DELETE');
            const res = await fetch("{{ route('erp.products.destroy', ':id') }}".replace(':id', id), {
                method: 'POST',
                body: fd,
                headers: { 
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                }
            });
            let json = {};
            try { json = await res.json(); } catch(err) { throw new Error('Server error (' + res.status + ').'); }
            if (!res.ok) throw new Error(json.message || 'Delete failed');
            DT.ajax.reload(null, false);
            toast('Product deleted', 'success');
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    };
});
</script>
@endpush
@endsection
