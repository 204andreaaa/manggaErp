# Tes manual — perubahan PRD v1.1

Untuk dicek di lokal (http://127.0.0.1:8000) sebelum di-merge ke master. Centang kalau sudah sesuai.
Logika semuanya sudah lolos test otomatis; yang dicek di sini adalah tampilan dan klik di browser.

## Kasbon
- [ ] Buka ADVREQ-00006 → klik **Settle / Tutup Kasbon** → isi tanggal → Settle. Hasil: label **Closed**, outstanding 0, tombol "New Expense Declaration" hilang.
- [ ] Buat kasbon baru lalu Submit. Hasil: sisa budget di Monthly Plan langsung berkurang (booking).
- [ ] Tolak kasbon tadi. Hasil: sisa budget kembali.

## Approval
- [ ] Buka **Approval & Verif Configs** → Edit satu langkah → simpan. Hasil: hanya ada pilihan User (tidak ada Role), tersimpan normal.
- [ ] Tambah langkah kasbon dengan nominal minimum (mis. 5.000.000). Kasbon kecil cukup lewat langkah level 1, kasbon besar dapat langkah tambahan.

## User & Role
- [ ] Edit satu user → centang/hapus centang **Akses Project** → simpan → buka lagi, centangannya sesuai.
- [ ] Buka **Roles & Permissions** → edit role → menu Brand/UOM dkk. tidak punya kotak izin sendiri; ada kotak `department_spending.view` dan `users.export`.

## Request Form
- [ ] Login sebagai user biasa → bisa buat RF.
- [ ] Buka RF milik orang lain → tombol Edit dan Submit tidak muncul.

## PO & GR (butuh 1 RF → PO contoh)
- [ ] PO Draft yang belum diajukan → tombol Delete ada. PO yang sudah Approved → tombol Delete tidak ada, hanya Cancel.
- [ ] GR yang sudah Received → tombol hapus tidak ada.
- [ ] Bayar satu termin → di PO, "Sudah dibayar" dan "Sisa tagihan" berubah.

## Lain-lain
- [ ] Lonceng notifikasi → **Tandai semua dibaca** → notifikasi biasa jadi terbaca.
- [ ] Custom Report → simpan template tanpa centang "Bagikan" → login user lain, template tidak terlihat.
