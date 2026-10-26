@include('errors.layout', [
  'code' => 429,
  'title' => 'Terlalu Banyak Percobaan',
  'message' => 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu sekitar satu menit, lalu coba lagi.',
  'icon' => 'bx-stopwatch',
  'tone' => 'warning',
])
