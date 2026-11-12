# Mangga ERP — catatan untuk Claude

## PRD wajib ikut kode
`docs/PRD.md` adalah PRD as-built sistem ini. Setiap perubahan perilaku (function, aturan bisnis, field, status, hak akses, halaman, perintah artisan) menghasilkan PRD **versi baru**:
1. salin `docs/PRD.md` yang sekarang ke `docs/prd-history/PRD-v<versi lama>.md`,
2. perbarui bagian modul terkait di `docs/PRD.md` dan naikkan **Versi PRD** di tabel atas (1.1 → 1.2, dst.),
3. tambah satu baris di **Changelog** (tanggal, versi, ringkasan) dan hapus celah di Lampiran yang sudah beres,
4. masukkan semuanya di commit yang sama dengan perubahan kodenya.

Refactor tanpa perubahan perilaku dan perubahan test saja tidak perlu mengubah PRD. Hook `.claude/hooks/prd-reminder.cjs` mengingatkan hal ini setelah file di `app/`, `routes/`, `database/`, `config/` atau `resources/views/` diedit.
