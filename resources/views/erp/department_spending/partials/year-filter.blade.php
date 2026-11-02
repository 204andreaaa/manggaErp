{{-- Year picker for the department spending pages. Expects: $year, $years, $action. --}}
<form method="GET" action="{{ $action }}" class="d-flex align-items-center gap-2">
  <label class="small text-muted mb-0" for="spendingYear">Tahun</label>
  <select id="spendingYear" name="year" class="form-select form-select-sm" style="width: auto" onchange="this.form.submit()">
    @foreach($years as $y)
      <option value="{{ $y }}" @selected((string) $year === (string) $y)>{{ $y }}</option>
    @endforeach
    <option value="all" @selected($year === 'all')>Semua</option>
  </select>
</form>
