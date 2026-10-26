@php
  // abort_unless(..., 403, 'pesan') in the controllers carries a readable reason;
  // the framework's own defaults don't, so those are not shown.
  $reason = trim((string) ($exception?->getMessage() ?? ''));
  $generic = ['', 'Forbidden', 'This action is unauthorized.', 'User does not have the right permissions.'];
@endphp
@include('errors.layout', [
  'code' => 403,
  'title' => 'Akses Ditolak',
  'message' => 'Akun Anda tidak punya izin untuk membuka halaman atau menjalankan aksi ini. Kalau menurut Anda seharusnya bisa, minta admin menambahkan izin ke role Anda.',
  'detail' => in_array($reason, $generic, true) ? null : $reason,
  'icon' => 'bx-lock-alt',
  'tone' => 'warning',
])
