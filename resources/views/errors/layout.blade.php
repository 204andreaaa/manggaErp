{{--
  Shared error page. Deliberately standalone: no app layout, no auth, no
  database, so it still renders when the error IS the database or the session.
  Each code page sets: $code, $title, $message, $icon (boxicons name), $tone.
--}}
@php
  $tone = $tone ?? 'primary';
  $home = url('/');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $code }} · {{ $title }} · MANDAU ERP</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('sneat/assets/img/favicon/favicon.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/fonts/boxicons.css') }}">
  <style>
    :root {
      --bg: #f5f5f9; --card: #ffffff; --text: #384551; --muted: #8592a3; --border: #e4e6e8;
      --primary: #696cff; --primary-soft: #e7e7ff;
      --warning: #ff9f43; --warning-soft: #fff2e5;
      --danger: #ff3e1d; --danger-soft: #ffe5e0;
      --info: #03c3ec; --info-soft: #d7f5fc;
    }
    @media (prefers-color-scheme: dark) {
      :root {
        --bg: #232333; --card: #2b2c40; --text: #dbdbeb; --muted: #a3a4cc; --border: #444564;
        --primary-soft: #35365f; --warning-soft: #4d3a2b; --danger-soft: #4d2b2b; --info-soft: #25445c;
      }
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; min-height: 100%; }
    body {
      font-family: 'Public Sans', -apple-system, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg); color: var(--text);
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; padding: 24px 16px;
    }
    .card {
      width: 100%; max-width: 520px; background: var(--card); border: 1px solid var(--border);
      border-radius: 14px; padding: 40px 32px 32px; text-align: center;
      box-shadow: 0 6px 24px rgba(67, 89, 113, .08);
    }
    .badge .bx { font-size: 44px; }
    .badge {
      width: 88px; height: 88px; margin: 0 auto 18px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center; font-size: 44px;
      background: var(--tone-soft); color: var(--tone);
    }
    .code { font-size: 64px; font-weight: 800; line-height: 1; letter-spacing: -2px; color: var(--tone); margin: 0 0 8px; }
    h1 { font-size: 22px; font-weight: 700; margin: 0 0 10px; }
    p.message { color: var(--muted); font-size: 15px; line-height: 1.6; margin: 0 auto 8px; max-width: 420px; overflow-wrap: anywhere; }
    .detail {
      margin: 16px auto 0; padding: 10px 14px; border-radius: 8px; font-size: 14px; line-height: 1.5;
      background: var(--tone-soft); color: var(--text); text-align: left; overflow-wrap: anywhere;
    }
    .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 26px; }
    .btn {
      display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 8px;
      font: 600 14px 'Public Sans', sans-serif; text-decoration: none; cursor: pointer; border: 1px solid transparent;
    }
    .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 2px 6px rgba(105, 108, 255, .4); }
    .btn-primary:hover { filter: brightness(1.05); }
    .btn-outline { background: transparent; color: var(--text); border-color: var(--border); }
    .btn-outline:hover { border-color: var(--muted); }
    .foot { margin-top: 24px; font-size: 12px; color: var(--muted); }
    .tone-primary { --tone: var(--primary); --tone-soft: var(--primary-soft); }
    .tone-warning { --tone: var(--warning); --tone-soft: var(--warning-soft); }
    .tone-danger  { --tone: var(--danger);  --tone-soft: var(--danger-soft); }
    .tone-info    { --tone: var(--info);    --tone-soft: var(--info-soft); }
    @media (max-width: 480px) { .card { padding: 32px 20px 24px; } .code { font-size: 52px; } }
  </style>
</head>
<body>
  <main class="card tone-{{ $tone }}">
    <div class="badge"><i class="bx {{ $icon }}"></i></div>
    <div class="code">{{ $code }}</div>
    <h1>{{ $title }}</h1>
    <p class="message">{{ $message }}</p>

    @if(!empty($detail))
      <div class="detail"><i class="bx bx-info-circle"></i> {{ $detail }}</div>
    @endif

    <div class="actions">
      <button type="button" class="btn btn-outline" onclick="if (history.length > 1) { history.back(); } else { location.href = {!! js_attr($home) !!}; }"><i class="bx bx-arrow-back"></i> Kembali</button>
      <a href="{{ $actionUrl ?? $home }}" class="btn btn-primary"><i class="bx {{ $actionIcon ?? 'bx-home-alt' }}"></i> {{ $actionLabel ?? 'Ke Dashboard' }}</a>
    </div>

    <div class="foot">MANDAU ERP · {{ now()->format('d/m/Y H:i') }}</div>
  </main>
</body>
</html>
