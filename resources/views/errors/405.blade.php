{{-- Usually a page refreshed after submitting a form (GET on a POST-only URL). --}}
@include('errors.layout', [
  'code' => 405,
  'title' => 'Halaman Tidak Bisa Dibuka Langsung',
  'message' => 'Alamat ini hanya untuk mengirim formulir, jadi tidak bisa dibuka atau di-refresh langsung. Kembali ke halaman sebelumnya lalu ulangi dari tombolnya.',
  'icon' => 'bx-block',
  'tone' => 'warning',
])
