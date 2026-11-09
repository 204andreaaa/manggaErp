{{-- Shared look for the employee detail and ADVEXNPJ pages (theme accent, dark-mode aware). --}}
@once
@push('styles')
<style>
  .min-w-0 { min-width: 0; }
  .hr-hero { border: 0; overflow: hidden; }
  .hr-hero-banner {
    height: 96px;
    background: linear-gradient(120deg, var(--primary-accent, #696cff) 0%, color-mix(in srgb, var(--primary-accent, #696cff) 55%, #03c3ec) 100%);
    position: relative;
  }
  .hr-hero-banner::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(circle at 85% 20%, rgba(255,255,255,.25) 0, transparent 40%),
                radial-gradient(circle at 15% 120%, rgba(255,255,255,.18) 0, transparent 45%);
  }
  .hr-hero-body { padding: .9rem 1.5rem 1.25rem; position: relative; }
  .hr-hero-body > .hr-avatar { margin-top: -58px; }
  .hr-avatar {
    width: 88px; height: 88px; border-radius: 22px; font-size: 2.1rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    background: #fff; color: var(--primary-accent, #696cff);
    border: 4px solid #fff; box-shadow: 0 6px 18px rgba(67, 89, 113, .18);
  }
  .hr-avatar.is-sm { width: 44px; height: 44px; border-radius: 12px; font-size: 1.1rem; border-width: 2px; }
  .hr-meta { display: flex; flex-wrap: wrap; gap: .4rem 1.1rem; color: #697a8d; font-size: .85rem; }
  .hr-meta i { color: var(--primary-accent, #696cff); margin-right: .25rem; }

  .hr-info-list { list-style: none; padding: 0; margin: 0; }
  .hr-info-list li { display: flex; gap: .75rem; padding: .7rem 0; border-bottom: 1px dashed #e9ecef; }
  .hr-info-list li:last-child { border-bottom: 0; }
  .hr-info-list .hr-info-icon { flex: 0 0 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; }
  .hr-info-list .hr-info-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #a1acb8; font-weight: 600; }
  .hr-info-list .hr-info-value { font-weight: 600; color: #566a7f; word-break: break-word; }

  .hr-stat { border: 0; height: 100%; }
  .hr-stat .hr-stat-value { font-size: 1.25rem; font-weight: 700; margin: 0; white-space: nowrap; }
  .hr-stat .hr-stat-label { font-size: .78rem; color: #8592a3; font-weight: 600; margin: 0; }

  .hr-clamp { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; min-width: 190px; max-width: 280px; }
  .hr-table td { vertical-align: middle; }
  .hr-code { font-family: var(--bs-font-monospace); font-weight: 700; font-size: .82rem; }
  .hr-section-title { display: flex; align-items: center; gap: .6rem; margin: 0; font-weight: 700; }

  .hr-amount-card { border-radius: 14px; padding: 1rem 1.15rem; height: 100%; }
  .hr-amount-card .hr-amount { font-size: 1.35rem; font-weight: 700; margin: .15rem 0 0; }

  .hr-tabs { border-top: 1px solid #eceef1; }
  .hr-tabs .nav-tabs { background: transparent !important; box-shadow: none !important; }
  .hr-tabs .nav-link { border: 0 !important; border-bottom: 3px solid transparent !important; border-radius: 0; padding: .85rem 1rem; color: #697a8d; font-weight: 600; background: transparent !important; box-shadow: none !important; }
  .hr-tabs .nav-link:hover { color: var(--primary-accent, #696cff); }
  .hr-tabs .nav-link.active { color: var(--primary-accent, #696cff); border-bottom-color: var(--primary-accent, #696cff) !important; }

  .hr-activity { padding: .65rem .5rem; border-radius: 10px; border-bottom: 1px dashed #eceef1; }
  .hr-activity:last-child { border-bottom: 0; }
  .hr-activity:hover { background: rgba(105, 108, 255, .06); }

  html[data-theme="dark"] .hr-tabs { border-top-color: #444564; }
  html[data-theme="dark"] .hr-tabs .nav-link { color: #a3a4cc; }
  html[data-theme="dark"] .hr-activity { border-bottom-color: #444564; }
  html[data-theme="dark"] .hr-avatar { background: #2b2c40; border-color: #2b2c40; }
  html[data-theme="dark"] .hr-info-list li { border-bottom-color: #444564; }
  html[data-theme="dark"] .hr-info-list .hr-info-value { color: #cbcbe2; }
  html[data-theme="dark"] .hr-meta { color: #a3a4cc; }
</style>
@endpush
@endonce
