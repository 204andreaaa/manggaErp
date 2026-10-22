{{--
  WID + Budget Amount fields of the Annual Budget Plan modal. The plan's
  budget is taken out of the WID's Remaining Budget, so each option shows it.
--}}
<div class="mb-3">
  <label class="form-label fw-semibold">Work ID (WID) <span class="text-danger">*</span></label>
  <select name="work_item_id" class="form-select" required>
    <option value="">-- Pilih WID --</option>
    @foreach($workItems as $w)
      <option value="{{ $w->id }}" @selected(old('work_item_id') == $w->id)>
        {{ $w->wid_code }} - {{ $w->name }} ({{ $w->subProject?->budgetParent?->name ?? '-' }}) &middot; sisa IDR {{ number_format($w->remaining_budget, 0, ',', '.') }}
      </option>
    @endforeach
  </select>
  <div class="form-text">Account otomatis mengikuti WID.</div>
</div>
<div class="mb-3">
  <label class="form-label fw-semibold">Budget Amount <span class="text-danger">*</span></label>
  <input type="number" step="0.01" min="0.01" name="budget_amount" class="form-control" required value="{{ old('budget_amount') }}">
  <div class="form-text">Jatah tahunan ini langsung memotong Remaining WID.</div>
</div>
