{{-- Never show the exception text here: it can contain SQL or file paths. --}}
@include('errors.layout', [
  'code' => 500,
  'title' => 'Terjadi Kesalahan di Sistem',
  'message' => 'Maaf, ada yang tidak beres di sisi server dan permintaan Anda belum berhasil diproses. Coba lagi beberapa saat lagi. Kalau terus terjadi, laporkan ke tim IT beserta jam di bawah ini.',
  'icon' => 'bx-error',
  'tone' => 'danger',
])
