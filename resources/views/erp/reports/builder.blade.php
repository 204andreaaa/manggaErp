@extends('layouts.home')

@section('title', 'Report Builder - ' . ($activeTypeConfig['name'] ?? 'Report'))

@push('styles')
<style>
    .field-item {
        cursor: grab;
        transition: all 0.2s ease;
        user-select: none;
    }
    .field-item:hover {
        background-color: #f2f4f8;
        transform: translateX(3px);
    }
    .field-item.active {
        background-color: #e7e7ff;
        border-color: #696cff !important;
        font-weight: 600;
    }
    .column-chip {
        display: inline-flex;
        align-items: center;
        background: #f1f2f6;
        border: 1px solid #d9dee3;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #566a7f;
        cursor: move;
        user-select: none;
        transition: all 0.2s ease;
    }
    .column-chip:hover {
        border-color: #696cff;
        background: #e7e7ff;
        color: #696cff;
    }
    .table-report thead th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background-color: #f8f9fa;
        white-space: nowrap;
    }
    .table-report tbody td {
        font-size: 0.85rem;
        vertical-align: middle;
        white-space: nowrap;
    }
    .builder-sidebar {
        max-height: calc(100vh - 210px);
        overflow-y: auto;
    }
    .builder-content {
        max-height: calc(100vh - 210px);
        overflow-y: auto;
    }
</style>
@endpush

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    {{-- Top Action Bar --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('erp.reports.index') }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Kembali">
                        <i class="bx bx-arrow-back"></i>
                    </a>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-label-primary px-2 py-1"><i class="bx {{ $activeTypeConfig['icon'] }} me-1"></i>{{ $activeTypeConfig['badge'] }}</span>
                            <h5 class="fw-bold mb-0 text-dark" id="reportTitleDisplay">{{ $savedReport->title ?? $activeTypeConfig['name'] }}</h5>
                        </div>
                        <small class="text-muted">Dynamic Ad-Hoc Report Generator</small>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex flex-wrap align-items-center gap-2">
                    {{-- Switch Dataset --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bx bx-cylinder me-1"></i> Sumber Data: <strong>{{ $activeTypeConfig['name'] }}</strong>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            @foreach($reportTypes as $tKey => $t)
                                <li>
                                    <a class="dropdown-item {{ $tKey === $reportType ? 'active' : '' }}" href="{{ route('erp.reports.builder', ['type' => $tKey]) }}">
                                        <i class="bx {{ $t['icon'] }} me-2"></i>{{ $t['name'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btnRunReport">
                        <i class="bx bx-play me-1"></i> Run Report
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" id="btnExportExcel">
                        <i class="bx bx-spreadsheet me-1"></i> Export Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mdlSaveReport">
                        <i class="bx bx-save me-1"></i> Simpan Template
                    </button>
                </div>
            </div>

            {{-- Filter Bar --}}
            <div class="row g-2 mt-2 pt-2 border-top align-items-center">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-bold text-muted mb-1">Tanggal Acuan</label>
                    <select class="form-select form-select-sm" id="filterDateField">
                        @foreach($activeTypeConfig['date_fields'] as $dfKey => $dfLabel)
                            <option value="{{ $dfKey }}" {{ ($savedReport->date_field ?? $activeTypeConfig['default_date_field']) === $dfKey ? 'selected' : '' }}>
                                {{ $dfLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-bold text-muted mb-1">Preset Periode</label>
                    <select class="form-select form-select-sm" id="filterDatePreset">
                        <option value="all_time" selected>Semua Waktu</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="this_year">Tahun Ini</option>
                        <option value="today">Hari Ini</option>
                        <option value="custom">Custom Tanggal</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6" id="wrapperDateFrom" style="display:none;">
                    <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="filterDateFrom">
                </div>

                <div class="col-md-2 col-sm-6" id="wrapperDateTo" style="display:none;">
                    <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="filterDateTo">
                </div>

                <div class="col-md-3 col-sm-6 ms-auto text-end pt-3">
                    <span class="badge bg-label-info" id="badgeRowCount">0 Baris Data</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Workspace Grid --}}
    <div class="row g-3">
        {{-- Left: Available Fields Sidebar --}}
        <div class="col-md-3 col-xl-3">
            <div class="card shadow-sm border-0 builder-sidebar">
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark mb-0"><i class="bx bx-list-ul text-primary me-1"></i>Fields Katalog</h6>
                        <small class="text-muted">{{ count($allFields) }} Fields</small>
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control bg-light border-start-0" id="searchFieldInput" placeholder="Cari nama field...">
                    </div>
                </div>

                <div class="card-body p-2" id="availableFieldsList">
                    @foreach($allFields as $f)
                        @php
                            $typeIcon = match($f['type']) {
                                'currency' => 'bx-dollar text-success',
                                'number'   => 'bx-hash text-warning',
                                'date'     => 'bx-calendar text-info',
                                'badge'    => 'bx-tag text-danger',
                                default    => 'bx-font text-primary'
                            };
                        @endphp
                        <div class="field-item p-2 mb-1 rounded border d-flex justify-content-between align-items-center" 
                             data-key="{{ $f['key'] }}" 
                             data-label="{{ $f['label'] }}" 
                             data-type="{{ $f['type'] }}"
                             draggable="true">
                            <div class="d-flex align-items-center text-truncate">
                                <i class="bx bx-grid-vertical text-muted me-1 fs-6"></i>
                                <i class="bx {{ $typeIcon }} me-2"></i>
                                <span class="small text-dark field-label">{{ $f['label'] }}</span>
                            </div>
                            <button type="button" class="btn btn-xs btn-icon btn-outline-primary js-add-field" title="Tambah ke Kolom">
                                <i class="bx bx-plus"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right: Columns & Live Preview Table --}}
        <div class="col-md-9 col-xl-9">
            <div class="card shadow-sm border-0 builder-content">
                {{-- Selected Columns Bar --}}
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                        <span class="fw-bold text-dark small"><i class="bx bx-columns text-primary me-1"></i>Kolom Aktif (Seret untuk ubah urutan):</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-xs btn-outline-secondary" id="btnAddAllFields">Pilih Semua</button>
                            <button type="button" class="btn btn-xs btn-outline-danger" id="btnRemoveAllFields">Hapus Semua</button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 p-2 bg-light rounded border min-height-40" id="selectedColumnsContainer">
                        {{-- Injected dynamically --}}
                    </div>
                </div>

                {{-- Table Preview Area --}}
                <div class="table-responsive text-nowrap" style="min-height: 380px;">
                    <table class="table table-hover table-report table-striped mb-0" id="reportPreviewTable">
                        <thead id="reportTableHead">
                            {{-- Injected dynamically --}}
                        </thead>
                        <tbody id="reportTableBody">
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                                    <div>Memuat data laporan...</div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot id="reportTableFoot" class="bg-light fw-bold">
                            {{-- Injected dynamically --}}
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Simpan Template --}}
<div class="modal fade" id="mdlSaveReport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white"><i class="bx bx-save me-1"></i> Simpan Template Laporan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSaveReport">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul Laporan <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="saveTitleInput" class="form-control" placeholder="contoh: Rekap PO Approved Bulanan" value="{{ $savedReport->title ?? '' }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Keterangan / Deskripsi</label>
                        <textarea name="description" id="saveDescInput" class="form-control" rows="2" placeholder="Catatan kegunaan laporan...">{{ $savedReport->description ?? '' }}</textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Simpan Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reportType = @json($reportType);
    const allFieldsMeta = @json($allFields);
    const initialSavedColumns = @json($savedReport->selected_columns ?? []);
    
    // State Active Columns (array of column objects)
    let activeColumns = [];

    // Initialize Default Columns
    if (initialSavedColumns && initialSavedColumns.length > 0) {
        initialSavedColumns.forEach(key => {
            const found = allFieldsMeta.find(f => f.key === key);
            if (found) activeColumns.push(found);
        });
    } else {
        allFieldsMeta.filter(f => f.default).forEach(f => activeColumns.push(f));
    }

    const container = document.getElementById('selectedColumnsContainer');
    const tableHead = document.getElementById('reportTableHead');
    const tableBody = document.getElementById('reportTableBody');
    const tableFoot = document.getElementById('reportTableFoot');
    const badgeRowCount = document.getElementById('badgeRowCount');

    // Render Column Chips
    function renderColumnChips() {
        container.innerHTML = '';
        activeColumns.forEach((col, idx) => {
            const chip = document.createElement('div');
            chip.className = 'column-chip';
            chip.setAttribute('draggable', 'true');
            chip.dataset.index = idx;
            chip.innerHTML = `
                <i class="bx bx-grid-vertical me-1 text-muted"></i>
                <span>${col.label}</span>
                <i class="bx bx-x ms-2 text-danger fs-6 js-remove-col" data-key="${col.key}" style="cursor:pointer;"></i>
            `;
            container.appendChild(chip);
        });

        // Update active class on left sidebar
        document.querySelectorAll('#availableFieldsList .field-item').forEach(el => {
            const k = el.getAttribute('data-key');
            if (activeColumns.some(c => c.key === k)) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });
    }

    // Add Field
    function addField(key) {
        if (!activeColumns.some(c => c.key === key)) {
            const found = allFieldsMeta.find(f => f.key === key);
            if (found) {
                activeColumns.push(found);
                renderColumnChips();
                fetchPreviewData();
            }
        }
    }

    // Remove Field
    function removeField(key) {
        activeColumns = activeColumns.filter(c => c.key !== key);
        renderColumnChips();
        fetchPreviewData();
    }

    // Quick Add on click left
    document.querySelectorAll('.js-add-field').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const parent = this.closest('.field-item');
            const key = parent.getAttribute('data-key');
            if (activeColumns.some(c => c.key === key)) {
                removeField(key);
            } else {
                addField(key);
            }
        });
    });

    document.querySelectorAll('#availableFieldsList .field-item').forEach(item => {
        item.addEventListener('click', function() {
            const key = this.getAttribute('data-key');
            if (activeColumns.some(c => c.key === key)) {
                removeField(key);
            } else {
                addField(key);
            }
        });
    });

    // Remove on chip click
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('js-remove-col')) {
            const key = e.target.getAttribute('data-key');
            removeField(key);
        }
    });

    // Add All & Remove All
    document.getElementById('btnAddAllFields').addEventListener('click', () => {
        activeColumns = [...allFieldsMeta];
        renderColumnChips();
        fetchPreviewData();
    });

    document.getElementById('btnRemoveAllFields').addEventListener('click', () => {
        activeColumns = [];
        renderColumnChips();
        fetchPreviewData();
    });

    // Search Field Filter
    document.getElementById('searchFieldInput').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('#availableFieldsList .field-item').forEach(el => {
            const label = el.querySelector('.field-label').textContent.toLowerCase();
            el.style.display = label.includes(query) ? 'flex' : 'none';
        });
    });

    // Preset Date Filter Handler
    const presetSelect = document.getElementById('filterDatePreset');
    const wrapFrom = document.getElementById('wrapperDateFrom');
    const wrapTo = document.getElementById('wrapperDateTo');
    const dateFromInput = document.getElementById('filterDateFrom');
    const dateToInput = document.getElementById('filterDateTo');

    presetSelect.addEventListener('change', function() {
        const val = this.value;
        const today = new Date();
        
        if (val === 'custom') {
            wrapFrom.style.display = 'block';
            wrapTo.style.display = 'block';
        } else {
            wrapFrom.style.display = 'none';
            wrapTo.style.display = 'none';
            
            if (val === 'today') {
                const yyyyMmDd = today.toISOString().split('T')[0];
                dateFromInput.value = yyyyMmDd;
                dateToInput.value = yyyyMmDd;
            } else if (val === 'this_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
                dateFromInput.value = firstDay;
                dateToInput.value = lastDay;
            } else if (val === 'this_year') {
                dateFromInput.value = `${today.getFullYear()}-01-01`;
                dateToInput.value = `${today.getFullYear()}-12-31`;
            } else {
                dateFromInput.value = '';
                dateToInput.value = '';
            }
            fetchPreviewData();
        }
    });

    // Fetch Preview Data via AJAX
    function fetchPreviewData() {
        if (activeColumns.length === 0) {
            tableHead.innerHTML = '<tr><th class="text-center py-3">Pilih setidaknya 1 kolom</th></tr>';
            tableBody.innerHTML = '<tr><td class="text-center text-muted py-5">Silakan pilih kolom dari katalog di sebelah kiri untuk melihat data.</td></tr>';
            tableFoot.innerHTML = '';
            badgeRowCount.textContent = '0 Baris Data';
            return;
        }

        tableBody.innerHTML = '<tr><td colspan="' + activeColumns.length + '" class="text-center py-5"><div class="spinner-border text-primary spinner-border-sm mb-2"></div><div>Mengambil data...</div></td></tr>';

        const payload = {
            report_type: reportType,
            columns: activeColumns.map(c => c.key),
            date_field: document.getElementById('filterDateField').value,
            date_from: dateFromInput.value || null,
            date_to: dateToInput.value || null,
        };

        fetch("{{ route('erp.reports.preview') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTable(data.columns, data.rows, data.summaries);
                badgeRowCount.textContent = `${data.total_rows} Baris Data`;
            }
        })
        .catch(err => {
            tableBody.innerHTML = '<tr><td colspan="' + activeColumns.length + '" class="text-center text-danger py-4">Gagal memuat data laporan.</td></tr>';
        });
    }

    // Render Table Output
    function renderTable(columns, rows, summaries) {
        // Headers
        let thHtml = '<tr><th style="width:40px" class="text-center">#</th>';
        columns.forEach(c => {
            const align = (c.type === 'currency' || c.type === 'number') ? 'text-end' : (c.type === 'badge' ? 'text-center' : 'text-start');
            thHtml += `<th class="${align}">${c.label}</th>`;
        });
        thHtml += '</tr>';
        tableHead.innerHTML = thHtml;

        // Rows
        if (!rows || rows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bx bx-info-circle fs-3 d-block mb-1"></i>Tidak ada data pada periode / filter yang dipilih.</td></tr>`;
            tableFoot.innerHTML = '';
            return;
        }

        let bodyHtml = '';
        rows.forEach((r, idx) => {
            bodyHtml += `<tr><td class="text-center text-muted small">${idx + 1}</td>`;
            columns.forEach(c => {
                let rawVal = r[c.key] ?? '-';
                let cellHtml = rawVal;
                let align = 'text-start';

                if (c.type === 'currency') {
                    align = 'text-end';
                    if (rawVal !== '-' && !isNaN(rawVal)) {
                        cellHtml = 'Rp ' + Number(rawVal).toLocaleString('id-ID');
                    }
                } else if (c.type === 'number') {
                    align = 'text-end';
                    if (rawVal !== '-' && !isNaN(rawVal)) {
                        cellHtml = Number(rawVal).toLocaleString('id-ID');
                    }
                } else if (c.type === 'date') {
                    if (rawVal && rawVal !== '-') {
                        const d = new Date(rawVal);
                        cellHtml = isNaN(d.getTime()) ? rawVal : d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    }
                } else if (c.type === 'badge') {
                    align = 'text-center';
                    const s = String(rawVal).toLowerCase();
                    let badgeClass = 'bg-label-secondary';
                    if (s.includes('approved') || s.includes('active') || s.includes('verified') || s.includes('paid')) {
                        badgeClass = 'bg-label-success';
                    } else if (s.includes('pending') || s.includes('draft') || s.includes('process')) {
                        badgeClass = 'bg-label-warning';
                    } else if (s.includes('reject')) {
                        badgeClass = 'bg-label-danger';
                    }
                    cellHtml = `<span class="badge ${badgeClass} text-uppercase font-monospace" style="font-size:0.75rem;">${rawVal}</span>`;
                }

                bodyHtml += `<td class="${align}">${cellHtml}</td>`;
            });
            bodyHtml += '</tr>';
        });
        tableBody.innerHTML = bodyHtml;

        // Footer Summary Row
        if (summaries && Object.keys(summaries).length > 0) {
            let footHtml = '<tr><td class="text-center">TOTAL</td>';
            columns.forEach(c => {
                let align = (c.type === 'currency' || c.type === 'number') ? 'text-end' : 'text-start';
                if (summaries[c.key] !== undefined) {
                    let sumVal = summaries[c.key];
                    let formattedSum = (c.type === 'currency') ? 'Rp ' + Number(sumVal).toLocaleString('id-ID') : Number(sumVal).toLocaleString('id-ID');
                    footHtml += `<td class="${align} text-primary">${formattedSum}</td>`;
                } else {
                    footHtml += `<td></td>`;
                }
            });
            footHtml += '</tr>';
            tableFoot.innerHTML = footHtml;
        } else {
            tableFoot.innerHTML = '';
        }
    }

    // Run Report Button
    document.getElementById('btnRunReport').addEventListener('click', fetchPreviewData);
    document.getElementById('filterDateField').addEventListener('change', fetchPreviewData);
    dateFromInput.addEventListener('change', fetchPreviewData);
    dateToInput.addEventListener('change', fetchPreviewData);

    // Export Excel Button
    document.getElementById('btnExportExcel').addEventListener('click', function() {
        if (activeColumns.length === 0) {
            Swal.fire('Perhatian', 'Pilih minimal 1 kolom sebelum melakukan export.', 'warning');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('erp.reports.export') }}";
        form.style.display = 'none';

        const addField = (name, val) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = val;
            form.appendChild(input);
        };

        addField('_token', '{{ csrf_token() }}');
        addField('report_type', reportType);
        addField('date_field', document.getElementById('filterDateField').value);
        if (dateFromInput.value) addField('date_from', dateFromInput.value);
        if (dateToInput.value) addField('date_to', dateToInput.value);

        activeColumns.forEach(c => addField('columns[]', c.key));

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    // Save Template Form Submit
    document.getElementById('formSaveReport').addEventListener('submit', function(e) {
        e.preventDefault();
        const title = document.getElementById('saveTitleInput').value;
        const description = document.getElementById('saveDescInput').value;

        const payload = {
            title: title,
            description: description,
            report_type: reportType,
            selected_columns: activeColumns.map(c => c.key),
            date_field: document.getElementById('filterDateField').value,
            date_range_preset: presetSelect.value,
            date_from: dateFromInput.value || null,
            date_to: dateToInput.value || null,
        };

        fetch("{{ route('erp.reports.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('mdlSaveReport'));
                if (modal) modal.hide();
                Swal.fire('Berhasil!', data.message, 'success');
                document.getElementById('reportTitleDisplay').textContent = title;
            }
        });
    });

    // Initial Execution
    renderColumnChips();
    fetchPreviewData();
});
</script>
@endpush
@endsection
