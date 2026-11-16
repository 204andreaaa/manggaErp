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

## Invoice customer (PRD v1.2)
- [ ] Finance → **Invoice (Tagihan Customer)** → New Invoice → isi nomor, tanggal, perusahaan, customer, TOP → Save.
- [ ] Tambah 2 rincian per WID → Invoice Amount = jumlah keduanya, link WID bisa diklik.
- [ ] Di form rincian: pilih Account → daftar Project & Work Id menyempit; pilih Work Id langsung → Account & Project terisi sendiri.
- [ ] Klik **Simpan & Tambah Lagi** → form rincian terbuka lagi.
- [ ] Tambah rincian Currency **USD** → muncul "Total USD" terpisah, Invoice Amount (IDR) tidak berubah.
- [ ] Catat pembayaran sebagian → status **Partial Paid**, Partial Payment Date terisi.
- [ ] Catat sisa pembayaran → status **Paid**, Final Payment Date terisi; tombol Delete invoice hilang.
- [ ] Coba bayar lebih dari sisa tagihan → ditolak.
- [ ] Daftar invoice: filter status/perusahaan dan kotak total jalan.

## Lain-lain
- [ ] Lonceng notifikasi → **Tandai semua dibaca** → notifikasi biasa jadi terbaca.
- [ ] Custom Report → simpan template tanpa centang "Bagikan" → login user lain, template tidak terlihat.
