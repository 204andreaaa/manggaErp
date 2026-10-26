@include('errors.layout', [
  'code' => 419,
  'title' => 'Sesi Sudah Kedaluwarsa',
  'message' => 'Halaman ini sudah terbuka terlalu lama sehingga sesinya habis. Muat ulang halaman lalu kirim formulirnya lagi.',
  'icon' => 'bx-time-five',
  'tone' => 'info',
  'actionUrl' => url()->previous(),
  'actionLabel' => 'Muat Ulang Halaman',
  'actionIcon' => 'bx-refresh',
])
