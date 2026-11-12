# Mangga ERP — catatan untuk Claude

## PRD wajib ikut kode
`docs/PRD.md` adalah PRD as-built sistem ini. Setiap perubahan perilaku (function, aturan bisnis, field, status, hak akses, halaman, perintah artisan) harus:
1. memperbarui bagian modul terkait di `docs/PRD.md`,
2. menambah satu baris di bagian **Changelog** PRD (tanggal + ringkasan),
3. masuk di commit yang sama dengan perubahan kodenya.

Refactor tanpa perubahan perilaku dan perubahan test saja tidak perlu mengubah PRD. Hook `.claude/hooks/prd-reminder.cjs` mengingatkan hal ini setelah file di `app/`, `routes/`, `database/`, `config/` atau `resources/views/` diedit.
