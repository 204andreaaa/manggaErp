{{--
  Salesforce-like layout pieces for the Budget Control pages (Budget Plans,
  Advance Request, Expense Declaration), so users moving over from Salesforce
  find fields in the same places. Set the object colour on a wrapper with
  style="--sf-accent: #..." (defaults to the ERP primary).
--}}
@once
@push('styles')
<style>
  .sf-wrap { --sf-accent: var(--bs-primary, #696cff); }
  .sf-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
  .sf-head .sf-object { font-size: .78rem; color: #8592a3; line-height: 1.1; }
  .sf-head .sf-name { font-size: 1.45rem; font-weight: 700; color: #384551; line-height: 1.2; }
  .sf-head .sf-icon { width: 40px; height: 40px; border-radius: 8px; background: var(--sf-accent); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem; margin-right: 10px; }

  .sf-card { background: #fff; border: 1px solid #e4e6e8; border-top: 3px solid var(--sf-accent); border-radius: 6px; margin-bottom: 18px; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
  .sf-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 10px 14px; border-bottom: 1px solid #eef0f2; }
  .sf-card-head h6 { margin: 0; font-weight: 700; font-size: .95rem; color: #384551; }
  .sf-card-head .sf-actions { display: flex; gap: 6px; flex-wrap: wrap; }
  .sf-card-foot { display: flex; justify-content: center; gap: 6px; padding: 10px 14px; border-top: 1px solid #eef0f2; }

  .sf-fields { width: 100%; border-collapse: collapse; font-size: .875rem; }
  .sf-fields td { padding: 6px 10px; border-bottom: 1px solid #f1f2f4; vertical-align: top; }
  .sf-fields td.sf-label { width: 18%; text-align: right; color: #697a8d; font-weight: 600; font-size: .8rem; white-space: nowrap; }
  .sf-fields td.sf-value { width: 32%; color: #384551; }
  @media (max-width: 767.98px) {
    .sf-fields, .sf-fields tbody, .sf-fields tr, .sf-fields td { display: block; width: 100% !important; }
    .sf-fields td.sf-label { text-align: left; border-bottom: 0; padding-bottom: 0; }
  }

  .sf-table { width: 100%; font-size: .85rem; margin: 0; }
  .sf-table thead th { background: #f5f6f7; color: #566a7f; font-weight: 700; font-size: .75rem; text-transform: none; letter-spacing: 0; padding: 7px 10px; border-bottom: 1px solid #e4e6e8; white-space: nowrap; }
  .sf-table td { padding: 7px 10px; border-bottom: 1px solid #f1f2f4; vertical-align: top; white-space: nowrap; }
  .sf-table td.small { white-space: normal; min-width: 220px; }
  .sf-table tbody tr:hover td { background: #fafbfc; }
  .sf-empty { color: #8592a3; padding: 10px 14px; font-size: .85rem; }
  .sf-link { font-weight: 600; text-decoration: none; }

  .sf-approval .sf-step td { background: var(--sf-accent); color: #fff; font-weight: 700; font-size: .8rem; padding: 6px 10px; }
  .sf-approval .sf-step td.sf-overall { width: 150px; }
  .sf-overall .badge { font-size: .75rem; }
</style>
@endpush
@endonce
