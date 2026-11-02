{{--
  Department a non-project RF is charged to. Admins pick it; everyone else sees their own, locked.
  Expects: $canPickDepartment, $departments, $myDepartment, $selectedId, $lockedName.
--}}
<div class="mb-3">
  <label class="form-label fw-semibold small text-uppercase text-muted">Departemen <span class="text-danger">*</span></label>
  @if($canPickDepartment)
    <select name="department_id" class="form-select select2" required>
      <option value="">-- Pilih Departemen --</option>
      @foreach($departments as $dept)
        <option value="{{ $dept->id }}" @selected((string) old('department_id', $selectedId) === (string) $dept->id)>{{ $dept->name }}</option>
      @endforeach
    </select>
    <div class="form-text text-muted small">Pengeluaran RF ini dicatat ke departemen yang dipilih (default: departemen kamu).</div>
  @elseif($lockedName)
    <input class="form-control bg-light" value="{{ $lockedName }}" readonly>
    <div class="form-text text-muted small">Pengeluaran RF ini dicatat ke departemen pembuat RF.</div>
  @else
    <div class="alert alert-danger py-2 small mb-0">
      Data departemen kamu belum lengkap, jadi RF Non Project belum bisa disimpan. Minta admin melengkapi departemen di data user/karyawan.
    </div>
  @endif
</div>
