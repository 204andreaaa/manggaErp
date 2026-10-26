@include('errors.layout', [
  'code' => 404,
  'title' => 'Halaman Tidak Ditemukan',
  'message' => 'Halaman atau data yang Anda cari tidak ada. Mungkin sudah dihapus, alamatnya salah ketik, atau tautannya sudah lama.',
  'icon' => 'bx-search-alt',
  'tone' => 'primary',
])
