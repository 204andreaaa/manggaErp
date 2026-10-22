{{--
  WID first, then Area -> Work Type -> Subtype limited to the combos that WID has
  an Annual Budget Plan for in the year of #reqDate (a single choice is picked
  automatically). The Account follows the WID. Expects #reqDate, #workId, #area,
  #workType, #workSubtype, #accountName and #planHint in the page, and
  $annualPlans / $workItems from the controller.
--}}
@php
  $cascadePlans = $annualPlans->map(fn ($p) => [
      'wid' => $p->work_item_id,
      'year' => (int) $p->year,
      'area' => $p->area,
      'type' => $p->work_type,
      'subtype' => (string) ($p->work_subtype ?? ''),
  ])->values();
  $cascadeWids = $workItems->map(fn ($w) => [
      'id' => $w->id,
      'label' => $w->wid_code.' - '.$w->name,
      'account' => $w->subProject?->budgetParent?->name ?? '-',
  ])->values();
@endphp

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const plans = {!! json_encode($cascadePlans) !!};
  const wids = {!! json_encode($cascadeWids) !!};
  const initial = {!! json_encode($initial ?? []) !!};

  const el = (id) => document.getElementById(id);
  const reqDate = el('reqDate'), widSel = el('workId'), areaSel = el('area'),
        typeSel = el('workType'), subSel = el('workSubtype'), account = el('accountName'), hint = el('planHint');

  const uniq = (arr) => [...new Set(arr)];

  // A single available value is selected straight away, so the user only has to
  // choose where the WID really has more than one plan.
  function fill(select, values, placeholder, wanted) {
    const keep = wanted !== undefined && wanted !== null ? String(wanted) : select.value;
    select.innerHTML = '';
    if (placeholder !== null) select.add(new Option(placeholder, ''));
    values.forEach(v => select.add(new Option(v.label ?? v, v.value ?? v)));
    const options = Array.from(select.options).map(o => o.value);
    if (options.includes(keep) && keep !== '') select.value = keep;
    else if (values.length === 1) select.value = values[0].value ?? values[0];
    else if (placeholder === null && select.options.length) select.selectedIndex = 0;
  }

  fill(widSel, wids.map(w => ({ value: String(w.id), label: w.label + ' (' + w.account + ')' })), '-- Pilih WID --', initial.workId);

  function refresh(useInitial) {
    const pick = (k) => useInitial ? initial[k] : undefined;
    const year = reqDate.value ? new Date(reqDate.value).getFullYear() : null;
    const s = plans.filter(p => String(p.wid) === widSel.value && p.year === year);
    const wid = wids.find(w => String(w.id) === widSel.value);
    if (account) account.textContent = wid ? wid.account : '-';

    fill(areaSel, uniq(s.map(p => p.area)), '-- Pilih Area --', pick('area'));
    const byArea = s.filter(p => p.area === areaSel.value);
    fill(typeSel, uniq(byArea.map(p => p.type)), '-- Pilih Work Type --', pick('workType'));
    const byType = byArea.filter(p => p.type === typeSel.value);
    const subs = uniq(byType.map(p => p.subtype)).map(v => ({ value: v, label: v === '' ? '(tanpa subtype)' : v }));
    fill(subSel, subs, subs.length ? null : '-', pick('workSubtype'));

    if (widSel.value && !s.length) {
      hint.textContent = 'WID ini belum punya Annual Budget Plan tahun ' + (year ?? '') + '. Buat dulu di menu Budget Plans.';
      hint.classList.remove('d-none');
    } else {
      hint.classList.add('d-none');
    }
  }

  [reqDate, widSel].forEach(x => x && x.addEventListener('change', () => refresh(false)));
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
