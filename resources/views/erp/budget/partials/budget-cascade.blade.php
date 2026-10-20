{{--
  Cascading Account -> Area -> Work Type -> Subtype selects, filtered to combos that
  have an Annual Budget Plan in the year of #reqDate, plus WIDs filtered to the
  chosen Account. Expects #reqDate, #budgetParent, #area, #workType, #workSubtype,
  #workId and #planHint in the page, and $annualPlans / $workItems from the controller.
--}}
@php
  $cascadePlans = $annualPlans->map(fn ($p) => [
      'bp' => $p->budget_parent_id,
      'year' => (int) $p->year,
      'area' => $p->area,
      'type' => $p->work_type,
      'subtype' => (string) ($p->work_subtype ?? ''),
  ])->values();
  $cascadeWids = $workItems->map(fn ($w) => [
      'id' => $w->id,
      'label' => $w->wid_code.' - '.$w->name,
      'bp' => $w->subProject?->budget_parent_id,
  ])->values();
@endphp

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const plans = {!! json_encode($cascadePlans) !!};
  const wids = {!! json_encode($cascadeWids) !!};
  const initial = {!! json_encode($initial ?? []) !!};

  const el = (id) => document.getElementById(id);
  const reqDate = el('reqDate'), bpSel = el('budgetParent'), areaSel = el('area'),
        typeSel = el('workType'), subSel = el('workSubtype'), widSel = el('workId'), hint = el('planHint');

  const uniq = (arr) => [...new Set(arr)];

  function fill(select, values, placeholder, wanted) {
    const keep = wanted !== undefined && wanted !== null ? String(wanted) : select.value;
    select.innerHTML = '';
    if (placeholder !== null) select.add(new Option(placeholder, ''));
    values.forEach(v => select.add(new Option(v.label ?? v, v.value ?? v)));
    const values_ = Array.from(select.options).map(o => o.value);
    if (values_.includes(keep)) select.value = keep;
    else if (placeholder === null && select.options.length) select.selectedIndex = 0;
  }

  function scoped() {
    const year = reqDate.value ? new Date(reqDate.value).getFullYear() : null;
    return plans.filter(p => String(p.bp) === bpSel.value && p.year === year);
  }

  function refresh(useInitial) {
    const pick = (k) => useInitial ? initial[k] : undefined;
    const s = scoped();

    fill(areaSel, uniq(s.map(p => p.area)), '-- Pilih Area --', pick('area'));
    const byArea = s.filter(p => p.area === areaSel.value);
    fill(typeSel, uniq(byArea.map(p => p.type)), '-- Pilih Work Type --', pick('workType'));
    const byType = byArea.filter(p => p.type === typeSel.value);
    const subs = uniq(byType.map(p => p.subtype)).map(v => ({ value: v, label: v === '' ? '(tanpa subtype)' : v }));
    fill(subSel, subs, subs.length ? null : '-', pick('workSubtype'));

    fill(widSel, wids.filter(w => String(w.bp) === bpSel.value).map(w => ({ value: String(w.id), label: w.label })), '-- Pilih WID --', pick('workId'));

    if (bpSel.value && !s.length) {
      const year = reqDate.value ? new Date(reqDate.value).getFullYear() : '';
      hint.textContent = 'Belum ada Annual Budget Plan tahun ' + year + ' untuk Account ini. Buat dulu di menu Budget Plans.';
      hint.classList.remove('d-none');
    } else {
      hint.classList.add('d-none');
    }
  }

  [reqDate, bpSel].forEach(x => x && x.addEventListener('change', () => refresh(false)));
  areaSel.addEventListener('change', () => refresh(false));
  typeSel.addEventListener('change', () => refresh(false));

  // Typing an amount ticks that row's checkbox, like ticking it by hand.
  document.querySelectorAll('.line-amount').forEach(input => {
    input.addEventListener('input', function () {
      const box = this.closest('tr').querySelector('.line-check');
      if (box && parseFloat(this.value) > 0) box.checked = true;
    });
  });

  refresh(true);
});
</script>
@endpush
