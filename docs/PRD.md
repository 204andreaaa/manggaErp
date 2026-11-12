# PRD — Mangga ERP (Mandau)

| | |
|---|---|
| **Dokumen** | Product Requirements Document (as-built) |
| **Sistem** | Mangga ERP — ERP multi-project untuk procurement, budget project, kasbon, pembayaran supplier, gudang dan HR |
| **Repo** | `204andreaaa/manggaErp` (branch utama `master`) |
| **Versi kode** | Laravel 13, PHP 8.2+ (server: PHP 8.4) |
| **Terakhir diperbarui** | 2026-11-12 |
| **Status** | Belum production (masih tahap uji internal) |

> **Cara dokumen ini dirawat.** PRD ini ditulis dari kode yang berjalan, bukan dari rencana. Setiap perubahan perilaku sistem (function, aturan, field, status, hak akses, halaman) **wajib** memperbarui bagian modul terkait di dokumen ini dan menambah satu baris di [Changelog](#14-changelog), di commit yang sama dengan perubahan kodenya. Di repo ini sudah ada hook Claude Code (`.claude/settings.json` → `.claude/hooks/prd-reminder.cjs`) yang mengingatkan hal ini setiap kali file di `app/`, `routes/`, `database/`, `config/` atau `resources/views/` diubah.

## Daftar isi

1. [Ringkasan produk](#1-ringkasan-produk)
2. [Pengguna dan peran](#2-pengguna-dan-peran)
3. [Arsitektur dan teknologi](#3-arsitektur-dan-teknologi)
4. [Alur bisnis utama](#4-alur-bisnis-utama)
5. [Modul Procurement](#5-modul-procurement) — RF, PR, PO, GR, Stok, Pengeluaran Departemen
6. [Modul Keuangan](#6-modul-keuangan) — Payment Advice, Payment Terms
7. [Modul Budget Project dan Kasbon](#7-modul-budget-project-dan-kasbon)
8. [Mesin Approval](#8-mesin-approval)
9. [Modul Orang dan Organisasi](#9-modul-orang-dan-organisasi) — User, Role, Departemen, Karyawan, ADVEXNPJ
10. [Master Data](#10-master-data)
11. [Fitur pendukung](#11-fitur-pendukung) — Login, Project, Dashboard, Notifikasi, Laporan, Tampilan
12. [Hak akses](#12-hak-akses)
13. [Data, penomoran, operasional dan pengujian](#13-data-penomoran-operasional-dan-pengujian)
14. [Changelog](#14-changelog)
15. [Lampiran: celah dan pekerjaan terbuka](#15-lampiran-celah-dan-pekerjaan-terbuka)

---

## 1. Ringkasan produk

### 1.1 Masalah yang diselesaikan
Perusahaan menjalankan beberapa project (mis. Mandau, Imprima). Tiap project butuh:
- pengajuan barang/jasa yang tercatat dan disetujui berjenjang,
- pembelian ke supplier yang terkontrol terhadap budget project,
- penerimaan barang ke gudang dan stok,
- pembayaran supplier per termin,
- kasbon karyawan untuk kebutuhan lapangan beserta pertanggungjawabannya,
- catatan pengeluaran per departemen untuk kebutuhan non-project.

Sebelumnya proses ini mengikuti pola Salesforce. Mangga ERP menggantikannya dengan satu aplikasi web.

### 1.2 Tujuan
- Satu alur dokumen yang saling terhubung: **RF → PR → PO → GR → Payment Advice**.
- Budget project terkendali dari atas ke bawah: **Budget Parent → Sub Project → WID → Annual Plan → Monthly Plan → Detail per jenis biaya**.
- Approval berjenjang yang bisa dikonfigurasi (per user/role, per nominal, project/non-project), dengan riwayat per putaran.
- Data tiap project terpisah (database sendiri), user dan HR terpusat.

### 1.3 Di luar cakupan saat ini
- Absensi, cuti dan payroll (halaman "coming soon").
- Rental mobil GA (hanya pesan "dalam pengembangan").
- Akuntansi/jurnal umum.
- Retur barang, penyesuaian stok manual.
- Pengembalian sisa kasbon (close/return).

---

## 2. Pengguna dan peran

Role disimpan di database master (`roles`), satu user bisa punya beberapa role. `superadmin` dan `admin` selalu lolos semua cek permission.

| Role (slug) | Siapa | Pekerjaan utama di sistem |
|---|---|---|
| `superadmin` | Pengelola sistem | Semua akses; satu-satunya yang bisa hapus RF/PO/GR/PA, unlock dokumen, kelola project dan konfigurasi approval |
| `admin` | Admin pusat | Semua permission (kecuali aksi khusus superadmin) |
| `admin_project` | Admin project | Budget (Budget Parent, Sub Project, WID), membuat RF |
| `procurement` | Tim purchasing | Membuat PO dari RF, supplier, payment terms, katalog produk |
| `logistik` | Logistik & gudang | Membuat PR dari RF, GR, stok, gudang |
| `general_affair` | GA | Membuat RF, mencatat dan memverifikasi GR |
| `finance` | Finance | Payment Advice dan termin, membayar kasbon |
| `ceo` | CEO | Approval RF/PO/termin, melihat semua |
| `hrd` | HR | Departemen, data karyawan |

Hak akses default per role ada di [§12.3](#123-hak-akses-default-per-role).

---

## 3. Arsitektur dan teknologi

### 3.1 Multi-project (multi-tenant)
- **Database master** (`master`): users, roles, role_user, projects, project_user, employees, departments, notifications, custom_reports, activity_logs, companies.
- **Database tenant** (`tenant`, satu per project): seluruh data operasional (RF, PR, PO, GR, stok, PA, budget, kasbon, master produk/supplier/gudang, ADVEXNPJ).
- Setelah login user memilih project. `project_id` disimpan di session. Middleware `SetTenantConnection` mengarahkan koneksi `tenant` ke database project itu di setiap request.
- User non-superadmin hanya bisa masuk ke project yang terhubung lewat `project_user`.
- Akibatnya: data karyawan dan user sama di semua project, tetapi RF, PO, budget, master produk, supplier, gudang dan ADVEXNPJ **berbeda per project**.

### 3.2 Teknologi
| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13, PHP ^8.2 |
| Database | MySQL 8 |
| Realtime | Laravel Reverb (websocket), laravel-echo + pusher-js |
| UI | Blade + template admin Sneat (Bootstrap 5), jQuery DataTables, Select2, SweetAlert2, Boxicons |
| Ekspor | maatwebsite/excel (export user), CSV (produk, custom report), dompdf terpasang (cetak PO/GR pakai view print) |
| Build aset | Vite 5 (`npm run build` → `public/build`) |
| Tes | PHPUnit 12, feature test di database `mangga_test_*` |

### 3.3 Lingkungan
- **Lokal:** Laragon (PHP 8.3, MySQL 8), `php artisan serve`.
- **Server:** Mac Mini, Docker Compose (container `mangga-app`, `mangga-reverb`, `mysql`), Cloudflare Tunnel ke `mangga.ndrexit.my.id`. Langkah deploy di [§13.5](#135-deploy).

### 3.4 Penyimpanan file
| Isi | Lokasi |
|---|---|
| Gambar produk | `public/uploads/products/` |
| Invoice termin | `public/uploads/invoices/` |
| Bukti bayar termin | `public/uploads/receipts/` |
| Lampiran RF, PO, supplier | disk `public` → `storage/app/public/erp/attachments` |
| Tanda tangan user | path di `users.signature_path` |

---

## 4. Alur bisnis utama

### 4.1 Pembelian (project dan non-project)
```
RF (Draft) ──submit──► approval berjenjang ──► RF Approved
   │                                              │
   │                                    PR (oleh Logistik)
   │                                              │
   │                              PO Draft (oleh Procurement)
   │                       [verifikasi PO bila dikonfigurasi]
   │                         submit ──► approval berjenjang
   │                                              │
   │                       PO Approved ──► potong budget WID (RF project)
   │                                   ──► Payment Advice + termin dibuat otomatis
   │                                   ──► harga beli produk diperbarui
   │                                              │
   │                   GR Recorded ──verifikasi──► GR Received ──► stok bertambah
   │                                              │
   │             Termin: submit ──► approval ──► Paid ──► PA closed
   │                                              │
   └──────────── PO Completed bila barang diterima penuh DAN pembayaran lunas
```

- **RF Project**: dibebankan ke WID (budget project). Potongan budget terjadi saat **PO approved**, bukan saat RF.
- **RF Non Project**: dibebankan ke **departemen**. Tidak memotong budget apa pun, hanya tercatat di halaman Pengeluaran Departemen.

### 4.2 Kasbon project
```
Annual Plan → Monthly Plan → Detail (jenis biaya)
        Kasbon (Advance Request) Draft ──submit──► approval ──► Approved
                 └─► Finance tandai Paid
                        └─► Expense Declaration "with advance" (langsung Approved)
                               → sisa kasbon (outstanding) berkurang
Expense Declaration "without advance": Draft ──submit──► approval ──► Approved
```

### 4.3 Non project per karyawan (ADVEXNPJ)
Pinjaman/klaim karyawan di luar project dicatat di halaman karyawan, tanpa approval.

---

## 5. Modul Procurement

### 5.1 Request Form (RF)
**Tujuan:** user mengajukan kebutuhan barang/jasa.

**Halaman**
- **Daftar RF**: kolom no RF, jenis, kode project, requestor, tanggal, status, total, remark, jumlah item. Tombol buat RF menampilkan pilihan *Project Based* / *Non Project Based*.
- **Buat/Ubah RF**: tab General, Line Items, Approval.
- **Detail RF**: header, item, catatan/lampiran, riwayat approval per putaran, daftar PR, daftar PO, modal buat PR, modal hapus.
- **Clone RF**: membuka form baru berisi salinan RF lain (status item kembali *Requested*, tanggal hari ini).

**Jenis RF**
| | Project | Non Project |
|---|---|---|
| Kode project | Wajib (kode Sub Project) | Kosong |
| WID per item | **Wajib** di setiap item | Tidak dipakai |
| Departemen | Kosong | **Wajib** (lihat aturan di bawah) |
| Potong budget | Ya, saat PO approved | Tidak |

**Aturan departemen (RF Non Project)**
- Admin/superadmin **memilih** departemen (harus departemen aktif). Default: departemen mereka sendiri.
- User lain otomatis memakai departemen dari data karyawannya. Saat mengedit RF lama, departemen yang sudah tersimpan dipertahankan.
- Bila data departemen pembuat belum lengkap, RF **ditolak** dengan pesan: *"Data departemen pembuat RF belum lengkap… Minta admin melengkapi departemen di data user/karyawan sebelum membuat RF Non Project."*

**Field header**
| Field | Aturan |
|---|---|
| Jenis RF | Wajib: project / non_project |
| Kode project | Wajib bila project, maks 80 |
| Requestor, Owner | Opsional, default nama user login |
| Prioritas | Wajib (Normal, High, Urgent, Low) |
| Tanggal RF | Wajib |
| Remark, Long remark, Rekomendasi supplier | Opsional |
| Kategori biaya | Centang: Material-Equipment, Material-Subcon, Transportation & Telecommunication, Personnel, Office, Other Expense, Utilities |
| Catatan | Tiap baris catatan jadi satu note |
| Lampiran | jpg/png/pdf maks 10 MB |
| Departemen | Lihat aturan departemen |

**Field item**
| Field | Aturan |
|---|---|
| Nama produk | Wajib, dipilih dari katalog |
| Kode produk | Opsional |
| WID | Wajib untuk RF project |
| Mata uang | Wajib (IDR, USD, SGD) |
| Harga satuan | Wajib ≥ 0 (default harga beli produk) |
| Harga aktual | Opsional; bila > 0 dipakai sebagai harga efektif |
| Qty | Wajib ≥ 0,01 |
| Total | qty × harga efektif |
| Tanggal dibutuhkan, PIC, Within budget, Remark | Opsional |
| Status item | Requested / Ordered / Completed / Cancelled |

**Status RF**
| Dari → Ke | Aksi | Siapa | Syarat dan efek |
|---|---|---|---|
| — → Draft | Simpan | User login | — |
| Draft/Rejected → Submitted | Submit / Ajukan Ulang | User login | Minimal 1 item; harus ada konfigurasi approval `request_form` yang cocok (jenis project dan nominal), bila tidak: *"Approval flow is not configured"*. Membuka putaran approval baru. |
| Submitted → Approved | Approve level terakhir | Approver yang ditunjuk / anggota role / superadmin | Pembuat RF tidak boleh approve RF sendiri (kecuali superadmin) |
| Submitted → Rejected | Reject | Sama dengan approve | Alasan wajib. Bisa diedit dan diajukan ulang |
| Selain Draft → Draft | Unlock | Superadmin | **Seluruh riwayat approval dihapus** |
| → dihapus | Hapus RF | Superadmin | **Ditolak bila masih ada PO yang belum Cancelled** (tombol abu-abu dengan alasan). PO yang sudah Cancelled, PR, item dan approval ikut dihapus. |

- RF hanya bisa diedit saat Draft atau Rejected.
- Status item berubah otomatis: PR dibuat → Ordered/Completed sesuai qty terpenuhi; PO dibuat → Ordered; PO cancel/hapus → Requested; GR diterima penuh → Completed.

### 5.2 Purchase Request (PR)
- **Dibuat oleh:** logistik, superadmin, atau permission `request_forms.create_pr`, dari RF yang **Approved**.
- **Isi:** pilih item RF dan qty yang diminta (default sisa qty yang belum terpenuhi).
- **Efek:** `qty_fulfilled` item RF bertambah, status item jadi Ordered/Completed. Status PR selalu *Submitted*.
- **Hapus PR:** logistik/superadmin/`request_forms.delete_pr`. Qty terpenuhi dikembalikan.
- RF baru muncul di dashboard Procurement ("PO Request") setelah punya PR.

### 5.3 Purchase Order (PO)
**Halaman**
- **PO Request (dashboard procurement):** RF Approved yang punya PR, dengan tombol Buat PO.
- **Daftar PO:** no PO, tanggal, supplier, gudang tujuan, total + pajak, status, GR, pembayaran lunas. Filter Draft/Approved/Completed.
- **Detail PO**, **Edit**, **Cetak**.

**Membuat PO**
- Oleh procurement, superadmin, atau `purchase_orders.create`.
- Hanya item RF yang belum ada di PO aktif (non-Cancelled) yang bisa dipilih.
- Field: supplier (wajib; alamat, rekening, transfer to, payment terms terisi otomatis), gudang tujuan, contact person, tanggal (wajib), ETA, metode bayar (Bank Transfer/Cash/COD), payment terms, invoice to / attention to (default PT Mandiri Daya Utama Nusantara, atau PT Imprima Prima Sejahtera untuk project Imprima), tanda tangan (user role CEO), deskripsi, lampiran.
- Item: qty, harga satuan, pajak (nominal per baris), remark. Baris qty 0 diabaikan.
- Total: `total_po_amount` = Σ qty × harga; `tax` = Σ pajak; `total_po_amount_with_tax` = jumlah keduanya.

**Status PO:** Draft → Submitted → Approved → Completed, serta Rejected dan Cancelled.

| Aksi | Siapa | Syarat dan efek |
|---|---|---|
| Edit | Procurement/superadmin/`purchase_orders.update` | Hanya Draft/Rejected. Status kembali Draft, verifikasi direset |
| Verifikasi | User/role di konfigurasi `po_verification`, atau superadmin | Bila konfigurasi ini ada, **PO wajib diverifikasi sebelum submit** |
| Submit | Procurement/admin/superadmin | Draft/Rejected. Membuat putaran approval dari konfigurasi `purchase_order` (cocok jenis RF dan total + pajak). Bila tidak ada konfigurasi: ≤ Rp1.000.000 di-approve procurement, di atasnya CEO |
| Approve | Approver yang ditunjuk / role / superadmin | Pembuat PO tidak boleh approve sendiri. Approve terakhir → **Approved**: potong budget WID, buat Payment Advice otomatis, perbarui harga beli produk |
| Reject | Sama | Alasan wajib. Bisa diedit dan diajukan ulang |
| Cancel | Superadmin | Ditolak bila sudah ada GR atau ada termin yang sudah diproses/dibayar. Efek: PA dan termin → Cancelled, **budget WID dikembalikan**, item RF → Requested |
| Unlock | Superadmin | Ditolak bila ada GR Received atau pembayaran. Efek: budget dikembalikan, approval dan PA dihapus, status → Draft |
| Hapus | Superadmin | Hapus permanen PO, GR (stok dikurangi bila sudah Received), item RF → Requested |

**Potong budget WID (saat PO Approved)**
- Per baris PO: biaya = total baris (qty × harga + pajak).
- WID dicari dari: WID di item RF → WID di header RF → kode project RF.
- `remaining_budget` WID dikurangi (minimal 0). Dikembalikan saat Cancel/Unlock (maksimal sebesar alokasi).
- RF Non Project tidak punya WID, jadi tidak memotong apa pun.

**Aturan Completed:** PO Approved otomatis menjadi **Completed** saat semua item sudah diterima penuh (GR) **dan** Payment Advice sudah lunas, urutan mana pun yang terjadi duluan.

**Harga beli produk:** setiap PO approved/cancel/unlock/hapus, harga beli produk disinkronkan ke harga satuan PO Approved terbaru.

### 5.4 Goods Receipt (GR / DO)
- **Dibuat oleh:** logistik, GA, superadmin, atau `goods_receipts.create`, dari PO **Approved/Completed** yang belum diterima penuh.
- **Field:** tanggal (wajib), no DO supplier, pengirim, penerima, remark, dan per item qty dikirim / qty diterima (tidak boleh melebihi sisa qty PO).
- **Penerimaan sebagian** didukung; satu PO bisa punya banyak GR.
- **Status:** Recorded → Received.
- **Verifikasi (Receive):** user/role di konfigurasi `gr_verification`; bila tidak ada konfigurasi, role logistik/GA/superadmin atau `goods_receipts.verify`.
- **Efek Received:** stok bertambah (hanya produk fisik), item RF yang sudah penuh → Completed, flag GR di PO diperbarui, cek aturan Completed PO.
- **Hapus GR:** superadmin. Stok dikurangi bila sudah Received, PO dikembalikan ke Approved.
- **Cetak GR** tersedia.

### 5.5 Stok
- Stok disimpan per **produk + gudang + supplier**.
- Halaman stok: filter gudang dan jenis (Fisik / Jasa / semua), kolom kode, nama, part no, jenis, gudang, supplier, qty + UOM, status stok (*In Stock* > 10, *Low Stock* > 0, *Out of Stock*).
- Stok hanya berubah dari GR Received, hapus GR, hapus PO, dan perintah `erp:sync-stocks`. **Belum ada penyesuaian manual.**

### 5.6 Pengeluaran Departemen
**Tujuan:** mencatat (bukan membatasi) pengeluaran RF/PO Non Project per departemen.
- **Akses:** permission `budgets.view` (admin/superadmin selalu bisa).
- **Filter tahun:** default tahun berjalan, atau "semua".
- **Ringkasan per departemen:** jumlah dan nilai RF Approved, jumlah PO dan nilai terpakai (PO Approved/Completed, total + pajak), baris total.
- **Peringatan:** jumlah RF Non Project tanpa departemen (tidak ikut dihitung).
- **Detail departemen:** semua RF Non Project departemen itu (semua status) dan PO-nya.

---

## 6. Modul Keuangan

### 6.1 Payment Terms (TOP)
- Field: nama (unik), aktif, jadwal termin (daftar nama + persentase, **total harus 100%**).
- Contoh: "DP 50% - Pelunasan 50%".
- Dipakai PO untuk membuat termin Payment Advice.

### 6.2 Payment Advice (PA) dan termin
**Pembuatan otomatis (saat PO Approved, bila PO belum punya PA)**
- Header: total = outstanding = total PO + pajak; jatuh tempo = tanggal PO + N hari (diambil dari nama payment terms, default 30); status Draft.
- Satu termin per baris jadwal payment terms (default satu termin "100% Payment").

**Pembuatan manual:** finance/superadmin/`payment_advices.update`, dari PO (dan GR opsional), dengan no invoice, total invoice, pembayaran awal, metode, jenis.

**Operasi termin**
| Aksi | Siapa | Aturan |
|---|---|---|
| Tambah termin | Finance/superadmin | Nominal tidak boleh melebihi sisa yang belum dialokasikan |
| Hapus termin | Finance/superadmin | Tidak boleh bila sudah Approved atau dibayar |
| Update invoice | Finance/superadmin | No invoice wajib; lampiran pdf/jpg/png maks 10 MB |
| Submit termin | Finance/superadmin | No invoice wajib (bukan "-"); **termin sebelumnya harus sudah Approved**; approval dari konfigurasi `payment_advice` sesuai nominal; bila tidak ada konfigurasi, Finance atau CEO yang approve |
| Approve / Reject | Approver yang ditunjuk, role, atau superadmin | Pemilik PA tidak boleh approve sendiri |
| Tandai dibayar | Finance/superadmin | Termin harus Approved; bukti bayar opsional |

**Status termin:** Draft → Submitted → Approved → (dibayar), atau Rejected (bisa diajukan ulang) / Cancelled.

**Penutupan:** PA **closed/Completed** saat outstanding = 0 dan semua termin aktif sudah dibayar. Saat itu `payment_closed` PO = true dan aturan Completed PO dicek.

**Hapus PA:** superadmin.

---

## 7. Modul Budget Project dan Kasbon

### 7.1 Hirarki budget
```
Budget Parent (Account)          total_budget
 └─ Sub Project (Project)
     └─ Work Item / WID          allocated_budget, remaining_budget
         └─ Annual Budget Plan   per tahun + area + jenis pekerjaan
             └─ Monthly Plan     per bulan (qty, revenue plan)
                 └─ Budget Plan Detail   per jenis biaya (direct/indirect)
```
- **PO** memotong **WID**.
- **Kasbon dan Expense Declaration** memotong **Budget Plan Detail**.

### 7.2 Budget Parent
- Field: kode (unik, manual), nama, KAM (user), total budget, status.
- Sisa = total − Σ alokasi WID di bawahnya. Total tidak boleh lebih kecil dari yang sudah dialokasikan.
- Halaman detail bergaya Salesforce: Sub Project, WID, Annual Plan, RF, PO, Kasbon, Expense Declaration terkait.

### 7.3 Sub Project
- Field: Budget Parent, kode (unik), nama.
- Pindah parent → alokasi WID ikut pindah.

### 7.4 Work Item (WID)
- Field: Sub Project, kode WID (otomatis bila kosong: `WID-{kode sub project tanpa SP-}-001`), nama, alokasi (> 0).
- **Batas pagu:** Σ alokasi WID di satu Budget Parent ≤ total budget parent (pesan *"Alokasi Anggaran Melebihi Batas Pagu!"*).
- Alokasi tidak boleh diturunkan di bawah yang sudah terpakai.
- **Hapus WID ditolak** bila sudah dipakai Annual Plan, Kasbon, Expense Declaration atau RF.
- **Yang mengubah sisa WID:** dibuat (= alokasi), alokasi diubah, Annual Plan dibuat/diubah (dikurangi nominal plan), PO approved (dikurangi), PO cancel/unlock (dikembalikan).

### 7.5 Annual Budget Plan
- Field: WID, tahun, area, jenis pekerjaan, sub jenis, mata uang, nominal budget.
- Kode: `{tahun}-{NNNN}`.
- Kombinasi WID + tahun + area + jenis + sub jenis harus unik.
- Nominal ≤ sisa WID; tidak boleh diturunkan di bawah Σ Monthly Plan.
- Kolom "Belum Dibagi ke Bulan" = nominal − Σ budget bulanan.
- Tidak ada hapus.

### 7.6 Monthly Budget Plan
- Field: bulan (1–12, satu per annual plan), unit, qty, revenue plan per unit, nominal budget, deskripsi.
- Kode: `BP-{YYYY-MM}-{NNNNN}`.
- Nominal ≤ sisa annual plan.
- Total expense plan = (Σ detail direct + indirect) × qty; total revenue plan = revenue × qty.
- Ditandai merah "Over Budget" bila aktual > rencana.

### 7.7 Budget Plan Detail
- Jenis biaya: Personnel, Materials, Materials-Subcon, Transportation & Telecommunication, Office, Utilities, Other Expense; masing-masing direct atau indirect (unik per bulan).
- Kode: `BPD-{YYYY-MM}-{NNNNN}`.
- Total expense plan = nominal × qty bulan; harus muat di budget bulan.
- **Sisa detail** = total expense plan − Σ Expense Declaration Approved − Σ outstanding Kasbon Approved.
- Kolom aktual: total kasbon approved, deklarasi dengan kasbon, deklarasi tanpa kasbon, total aktual.

### 7.8 Kasbon (Advance Request)
- **Form:** tanggal, WID, area, jenis pekerjaan, sub jenis (hanya kombinasi yang punya Annual Plan di tahun itu), pemohon, jumlah site (opsional), dan baris per jenis biaya (nominal + deskripsi).
- **Satu kasbon dibuat per jenis biaya** yang diisi. Total = nominal × jumlah site.
- Nominal harus muat di sisa Budget Plan Detail.
- Kode: `ADVREQ-{NNNNN}`.
- **Status:** Draft → Submitted → Approved / Rejected (bisa diajukan ulang).
- **Approval:** konfigurasi `advance_request` (semua level, tanpa filter nominal); pemohon tidak boleh approve sendiri; di level terakhir sisa budget dicek ulang.
- **Approved:** budget detail terpotong lewat outstanding, Finance dapat notifikasi "Kasbon Siap Dibayar".
- **Bayar:** Finance/superadmin menandai **Paid** (tanggal dan pembayar tercatat).
- **Outstanding** = nominal approved − Σ Expense Declaration with advance yang Approved.
- Detail kasbon menampilkan daftar Expense Declaration-nya dan tombol buat deklarasi (bila Approved + Paid).

### 7.9 Expense Declaration
| | With advance | Without advance |
|---|---|---|
| Sumber | Kasbon Approved + Paid | Langsung ke budget |
| Budget | Mengikuti kasbon | Dicari dari WID + area + jenis + tanggal |
| Jenis biaya | Sama dengan kasbon (kasbon "Other Expense" boleh dipecah ke jenis lain) | Bebas |
| Batas | Σ deklarasi ≤ nominal kasbon | Sisa Budget Plan Detail |
| Status awal | **Langsung Approved** (tanpa approval) | Draft → approval `expense_declaration` |
| Efek | Outstanding kasbon berkurang, aktual naik, sisa budget tetap | Sisa budget berkurang saat Approved |

- Field: tanggal, pemohon, info kendaraan, nama site, no PO (tanpa kasbon), baris per jenis biaya.
- Kode: `EXP{yymm}-{NNNNNNN}`.

---

## 8. Mesin Approval

### 8.1 Konfigurasi (superadmin)
| Jenis | Tipe |
|---|---|
| `request_form`, `purchase_order`, `payment_advice`, `advance_request`, `expense_declaration` | Bertingkat (level 1, 2, 3…) |
| `po_verification`, `gr_verification` | Satu langkah, satu konfigurasi per jenis |

- Field: jenis, level, nama langkah, ditugaskan ke **user** atau **role**, nominal min/maks (opsional), project/non-project/semua.
- Konfigurasi di level yang sama tidak boleh tumpang tindih (rentang nominal dan jenis project).
- Halaman konfigurasi menampilkan rentang nominal yang belum tertutup level 1.

**Filter yang benar-benar dipakai saat submit**
| Dokumen | Filter |
|---|---|
| RF | Jenis project + nominal total RF |
| PO | Jenis project RF + total PO dengan pajak |
| Termin PA | Nominal termin |
| Kasbon, Expense Declaration | Tidak ada filter (semua level) |

### 8.2 Aturan putaran (round)
- Submit membuat satu set langkah baru: level 1 *Pending*, sisanya *Waiting*.
- Approve: langkah → Approved, langkah berikutnya → Pending; langkah terakhir menyetujui dokumen.
- Reject: langkah → Rejected, sisa langkah putaran itu → Cancelled, dokumen → Rejected.
- Ajukan ulang membuka **putaran baru dari level 1**. Riwayat putaran lama disimpan dan ditampilkan ("Approval Request Submitted (Round N)").
- **Pemisahan tugas:** pembuat/pemohon/pemilik dokumen tidak boleh approve dokumennya sendiri, kecuali superadmin.
- **Override:** superadmin boleh approve langkah siapa pun; langkah ditandai "Override".
- Notifikasi dikirim ke user yang ditunjuk di langkah berikutnya.

---

## 9. Modul Orang dan Organisasi

### 9.1 User
- **Field:** nama, username (unik), email (unik), telepon (unik), jabatan, departemen (harus departemen aktif), tanda tangan (gambar maks 2 MB), password (min 6), role (minimal 1), status aktif/nonaktif.
- **Efek simpan:** user otomatis terhubung ke project yang sedang aktif; data karyawan dibuat/diperbarui (NIK `EMP-0001` dst., departemen = pilihan atau tebakan dari role).
- **Aturan:** hanya superadmin yang boleh memberi/mencabut role superadmin dan mengubah akun superadmin; user tidak bisa menghapus dirinya sendiri.
- **Fitur:** export Excel, export CSV seeder, hapus massal.
- Daftar user menandai merah bila departemen kosong atau tidak ada di master.
- User nonaktif otomatis di-logout ("Akun kamu nonaktif").

### 9.2 Role dan permission
- Field: slug, nama, halaman awal, menu yang terlihat, daftar permission.
- Permission dipilih lewat checkbox per grup menu (sumber: `config/menu.php`).
- Daftar lengkap di [§12.1](#121-daftar-permission).

### 9.3 Departemen
- Field: kode (unik, huruf besar), nama, deskripsi, status.
- Rename departemen ikut mengubah nama departemen di data karyawan.
- Tidak bisa dihapus selama masih dipakai karyawan.
- **Daftar master:** Project Management (PROJ), Procurement & Purchasing (PROC), General Affair (GA), Logistik & Gudang (LOG), Finance & Accounting (FIN), Human Resource (HRD), Information Technology (IT), Health, Safety & Environment (HSE), Executive & Management (EXEC).
- **Tebakan departemen dari role:** admin_project → PROJ, procurement → PROC, general_affair → GA, logistik → LOG, finance → FIN, hrd → HRD, ceo/superadmin/admin → EXEC.

### 9.4 Data karyawan
- **Daftar karyawan:** NIK, nama (klik ke detail), departemen & jabatan, kontak, akun login, status.
- Karyawan dibuat lewat menu User (belum ada form tambah/ubah karyawan langsung).
- **Halaman detail karyawan** berisi tab:

| Tab | Isi | Terlihat oleh |
|---|---|---|
| Ringkasan | Info kontak, masa kerja, rekening; kartu statistik RF tahun ini, kasbon, ADVEXNPJ, sisa cuti (soon); 8 aktivitas terbaru | Semua yang boleh membuka halaman |
| Request Form | RF yang **dibuat** karyawan ini | Lihat semua*, atau `request_forms.view` |
| Kasbon Project | Satu baris per kasbon (jumlah deklarasi di bawah nominal), plus deklarasi tanpa kasbon | Lihat semua*, atau `advance_requests.view` / `expense_declarations.view` |
| ADVEXNPJ Non Project | Daftar ADVEXNPJ karyawan | Lihat semua*, atau `employees.view` |
| Cuti | Segera hadir | Semua |

\* **Lihat semua** = karyawan itu sendiri, HRD, CEO, admin, superadmin.

- **Siapa yang boleh membuka halaman:** "lihat semua" atau permission `employees.view`.
- Tab terakhir yang dibuka diingat lewat URL (`#kasbon` dst.).
- Menu user di navbar punya link **Profil Saya** ke halaman karyawan sendiri.

### 9.5 ADVEXNPJ — Advance-Expense Declaration Non Project
**Tujuan:** mencatat uang yang diberikan ke karyawan di luar project (pinjaman, tunjangan, klaim, denda, acara kantor) beserta pertanggungjawabannya. **Tanpa approval.**

| Field | Aturan |
|---|---|
| Kode | Otomatis `ADVEXNPJ-00001` (nomor terhapus tidak dipakai ulang) |
| Karyawan | Wajib |
| Departemen | Otomatis dari karyawan |
| Status | Draft / Completed / Cancelled (bebas diubah) |
| Mata uang | IDR / USD |
| Pengajuan | Tanggal, nominal, deskripsi, jenis biaya |
| Jenis biaya | Pinjaman Karyawan, Beasiswa / Tunjangan, Klaim Asuransi / Reimburse, Own Risk / Denda, Acara Kantor, Lainnya |
| Pembayaran | Unpaid / Paid, tanggal bayar |
| Deklarasi | Tanggal, nominal, deskripsi |
| Selisih | Nominal pengajuan − nominal deklarasi |

- Tambah/ubah/hapus: permission `employees.update`. Lihat: yang boleh melihat karyawan tersebut.
- Tersimpan per project (database tenant).

---

## 10. Master Data
Semua master data di bawah ini **per project**.

| Master | Field utama | Aturan |
|---|---|---|
| Produk | Kode (otomatis `EPRD-001` bila kosong), part number, nama, gambar (maks 3 MB), deskripsi, UOM, family, type, brand, model, mata uang, harga beli, fisik/jasa, aktif | Kode unik di antara produk aktif; hapus = soft delete. Ada riwayat harga dari PO Approved (grafik, min/max, tren) dan export CSV |
| Product Family / Type / Brand / Model | Nama (unik), deskripsi | Hapus permanen; produk terkait menjadi kosong |
| UOM | Nama (unik), deskripsi | Sama |
| Mata uang | Kode (unik), nama, simbol | Sama |
| Payment Terms | Lihat [§6.1](#61-payment-terms-top) | |
| Supplier | Kode (otomatis `SUP-001`), nama, klasifikasi, industri, produk/jasa, payment terms, alamat, telepon, fax, website, bank, rekening, catatan; kontak (nama, jabatan, email, telepon); lampiran (jpg/png/pdf maks 10 MB) | Halaman detail menampilkan jumlah PO |
| Gudang | Kode (otomatis `WH001`), nama, tipe, alamat, telepon, tanggal stock take, koordinat, kapasitas, nilai, aktif | Hanya gudang aktif yang muncul di PO dan filter stok |

- Produk **fisik** menambah stok saat GR; produk **jasa** tidak.

---

## 11. Fitur pendukung

### 11.1 Login
- Login dengan **username atau email** + password, opsi "ingat saya".
- Belum ada registrasi atau reset password mandiri.

### 11.2 Pilih project
- Setelah login user memilih project (kartu project dengan logo, nama database, status). User dengan satu project langsung masuk.
- Superadmin bisa menambah project (database baru dibuat, dimigrasi dan diisi data awal otomatis), mengubah nama/status, dan menghapus (database tidak ikut dihapus).

### 11.3 Dashboard
- Filter tahun, bulan, Budget Parent.
- Angka: PO (total, approved, draft, submitted, rejected, nilai belanja), Payment Advice (total, outstanding, dibayar), GR, RF, budget WID (alokasi, sisa, terpakai, utilisasi %).
- Action center: PO dan termin yang menunggu approval user.
- Grafik tren bulanan, top 5 supplier, PO/GR/PA terbaru, jumlah produk dan stok.

### 11.4 Notifikasi dan pembaruan langsung
- **Notifikasi tersimpan** (lonceng di navbar): submit/approve/reject RF, PO, kasbon, deklarasi, termin; kasbon siap dibayar; dll.
- **Daftar tugas otomatis** di lonceng: PO/RF menunggu approval, PO perlu verifikasi, RF approved belum PR/PO, termin perlu diproses, PO approved belum GR, GR perlu verifikasi (sesuai role).
- **Live update:** setiap perubahan data disiarkan lewat Reverb; halaman yang terbuka memuat ulang datanya sendiri.
- Notifikasi yang sudah dibaca dihapus otomatis setelah 7 hari.

### 11.5 Custom Reports
- Jenis laporan: alur procurement (PO + RF + WID + supplier + GR + PA), PO, RF, GR, Payment Advice, WID, stok, karyawan.
- Pilih kolom, filter tanggal dan status, preview (300 baris) dengan total, export CSV (5.000 baris).
- Template laporan bisa disimpan.

### 11.6 Tampilan
- Menu samping atau menu atas (bisa dipilih user).
- Mode terang / gelap / otomatis, warna aksen bisa diganti; tersimpan di browser.
- Halaman error ramah dalam Bahasa Indonesia untuk 403, 404, 405, 419, 429, 500, 503.

---

## 12. Hak akses

### 12.1 Daftar permission
Permission disimpan sebagai daftar di setiap role. `superadmin` dan `admin` selalu lolos.

| Grup | Permission |
|---|---|
| Budget | `budget_parents.*`, `sub_projects.*`, `work_items.*` (view/create/update/delete); `annual_budget_plans.view/create`; legacy `budgets.view/create/update/delete` (berlaku untuk ketiganya; `budgets.view` juga membuka Pengeluaran Departemen) |
| Kasbon | `advance_requests.view/create/approve/pay`; `expense_declarations.view/create/approve` |
| Request Form | `request_forms.view/create/update/delete/submit/approve/reject/create_pr/delete_pr` |
| Purchase Order | `purchase_orders.view/create/update/delete/verify/submit/approve/reject/print` |
| Goods Receipt | `goods_receipts.view/create/update/delete/verify/print` |
| Payment | `payment_advices.view/update/delete`; `payment_advice_details.view/update_invoice/submit/approve/reject/mark_paid`; `payment_terms.*` |
| Gudang | `stocks.view/adjust`; `warehouses.*` |
| Master | `products.view/create/update/delete/export`; `suppliers.*`, `product_families.*`, `product_types.*`, `brands.*`, `product_models.*`, `currencies.*`, `uoms.*` |
| HR | `departments.*`, `employees.*`, `hr_attendances.view/manage`, `hr_payroll.view/manage` |
| Sistem | `users.*`, `roles.*`, `projects.*`, `approval_configs.view/create/delete`, `custom_reports.view/create/export/delete` |

### 12.2 Aksi khusus role
| Aksi | Hanya untuk |
|---|---|
| Hapus/unlock RF, cancel/unlock/hapus PO, hapus GR, hapus PA | superadmin |
| Kelola project dan konfigurasi approval | superadmin |
| Tandai kasbon dibayar, kelola termin | finance, superadmin |
| Buat PR | logistik, superadmin (atau `request_forms.create_pr`) |
| Buat PO | procurement, superadmin (atau `purchase_orders.create`) |
| Melihat semua tab karyawan | karyawan itu sendiri, hrd, ceo, admin, superadmin |

### 12.3 Hak akses default per role
| Role | Permission bawaan |
|---|---|
| superadmin, admin | Semua |
| admin_project | Budget Parent / Sub Project / WID penuh; RF view/create/update/submit; products.view |
| general_affair | RF view/create/update/submit; GR view/create/update/verify/print; products.view |
| procurement | RF view/update/approve/reject; PO penuh; GR view/print; supplier dan payment terms penuh; produk view/create/update/export; atribut master view |
| logistik | RF view/create/submit/create_pr/delete_pr; GR view/create/update/verify/print; stok view/adjust; gudang penuh; products.view; uoms.view |
| finance | PO view/print; GR view/print; payment advice view/update; semua permission termin; suppliers.view; payment_terms.view |
| hrd | Departemen, karyawan, absensi, payroll penuh |
| ceo | View budget; RF view/approve/reject; PO view/approve/reject/print; GR view/print; PA view; termin view/approve/reject/mark_paid; stocks.view; products.view; approval_configs.view |

---

## 13. Data, penomoran, operasional dan pengujian

### 13.1 Format nomor dokumen
| Dokumen | Format | Reset |
|---|---|---|
| Request Form | `RF-YYYY-MM-NNNNN` | Bulanan |
| Item RF | `RFIN-YYYY-MM-NNNNN-SSS` | — |
| Purchase Request | `PR-YYYY-MM-NNNNN` | Bulanan |
| Purchase Order | `PO-YYYY-NNNNN` | Tahunan |
| Goods Receipt | `GR-YYYY-MM-NNNNN` (mulai 35150) | Bulanan |
| Payment Advice otomatis | `PA-YYYY-MM-DD-{acak 5 digit}`; termin `SID-YYYY-MM-DD-{acak}` | — |
| Payment Advice manual | `SI{YYYY-MM}-NNNNN`; termin `SID-{YYYY-MM}-NNNNN` | Bulanan |
| WID | `WID-{kode sub project}-NNN` | Per sub project |
| Annual Plan | `{tahun}-NNNN` | Tahunan |
| Monthly Plan / Detail | `BP-YYYY-MM-NNNNN` / `BPD-YYYY-MM-NNNNN` | — |
| Kasbon | `ADVREQ-NNNNN` | Tidak reset |
| Expense Declaration | `EXP{yymm}-NNNNNNN` | Bulanan |
| ADVEXNPJ | `ADVEXNPJ-NNNNN` | Tidak reset |
| Produk / Supplier / Gudang | `EPRD-NNN` / `SUP-NNN` / `WHNNN` | — |
| Karyawan | `EMP-NNNN` (id user) | — |

Nomor dokumen yang sudah dihapus tidak dipakai ulang.

### 13.2 Tabel utama
- **Master:** users, roles, role_user, projects, project_user, companies, departments, employees, notifications, custom_reports, activity_logs.
- **Tenant:** request_forms, request_form_items, erp_notes_attachments, erp_purchase_requests (+items), erp_purchase_orders (+items), erp_goods_receipts (+items), erp_stocks, erp_payment_advices, erp_payment_advice_details, erp_payment_terms, erp_approvals, erp_approval_configs, erp_budget_parents, erp_sub_projects, erp_work_items, erp_annual_budget_plans, erp_monthly_budget_plans, erp_budget_plan_details, erp_advance_requests, erp_expense_declarations, erp_employee_advances, erp_products, product_families, product_types, brands, product_models, uoms, currencies, erp_suppliers (+contacts, attachments), erp_warehouses.

### 13.3 Perintah operasional
| Perintah | Fungsi |
|---|---|
| `php artisan master:migrate` | Pratinjau migrasi master; `--execute` (+ `--force` di production) untuk menjalankan |
| `php artisan tenants:migrate-all --execute` | Migrasi semua database project aktif |
| `php artisan tenant:migrate {project_id} --execute` | Migrasi satu project |
| `php artisan erp:sync-stocks` | Bangun ulang stok dari GR Received (**mengosongkan stok dulu**) |
| `php artisan app:fresh --seed` | Reset total (hanya lokal, **jangan di production**) |

### 13.4 Pengujian
- Feature test di `tests/Feature` (Budget, Kasbon, MasterData, Payment, PurchaseOrder, RequestForm, dll.).
- Jalan di database khusus `mangga_test_master` / `mangga_test_tenant`; test menolak jalan di database lain.
- Perintah: `php artisan test`. Kondisi per 2026-11-10: **357 lolos, 1 dilewati** (ProjectLogoTest, perlu ditulis ulang).

### 13.5 Deploy
Di server (`/Users/mac/apps/mangga`), satu per satu:
1. Backup database.
2. `cd src` lalu `git pull origin master`.
3. `docker exec mangga-app composer install --no-dev --optimize-autoloader`.
4. `docker exec mangga-app php artisan tenants:migrate-all --execute`.
5. `docker exec mangga-app php artisan master:migrate` (pratinjau), lalu `... --execute --force`.
6. `docker exec mangga-app php artisan view:clear` dan `config:clear`.
7. `docker compose restart app reverb`.
8. `npm run build` hanya bila file JS/CSS atau `VITE_*` berubah.

---

## 14. Changelog
| Tanggal | Perubahan |
|---|---|
| 2026-11-12 | PRD pertama ditulis dari kode yang berjalan (as-built). |
| 2026-11-10 | RF yang masih punya PO non-Cancelled tidak bisa dihapus; harus cancel PO dulu (refund budget terjadi saat cancel). |
| 2026-11-10 | Upgrade Laravel 10 → 13 digabung ke `master`. |
| 2026-11-09 | Halaman karyawan dengan tab (Ringkasan, RF, Kasbon Project, ADVEXNPJ, Cuti); tab Kasbon satu baris per kasbon. |
| 2026-11-09 | Modul ADVEXNPJ (Advance-Expense Declaration Non Project). |
| 2026-11-09 | Departemen karyawan wajib dari daftar master; Procurement dan GA dipisah. |
| 2026-11-02 | RF Non Project dibebankan ke departemen; halaman Pengeluaran Departemen. |
| 2026-10-27 | Aturan approval bergaya Salesforce: reject menyimpan riwayat, ajukan ulang = putaran baru. PO Completed saat diterima penuh dan lunas. |

---

## 15. Lampiran: celah dan pekerjaan terbuka
Temuan dari pembacaan kode saat menyusun PRD ini. Belum semuanya diverifikasi dengan pengujian; perlu keputusan bisnis atau perbaikan.

**Aturan bisnis yang perlu dikonfirmasi**
- Sisa budget detail hanya menghitung kasbon/deklarasi berstatus Approved (komentar di kode menyebut belum dikonfirmasi bisnis).
- Kasbon dan Expense Declaration tidak memakai filter nominal/jenis project di konfigurasi approval.
- Approver yang ditunjuk lewat **role** tidak mendapat tombol Approve di riwayat approval kasbon/deklarasi dan tidak mendapat notifikasi (backend menerima).
- Belum ada alur pengembalian sisa kasbon (outstanding negatif/positif tidak ditindaklanjuti).

**Hak akses**
- Banyak aksi hanya dicek di tampilan atau tidak dicek sama sekali: membuat/submit RF, submit PO, halaman cetak, dashboard, daftar WID per project.
- Tombol Edit RF di tampilan memakai permission `products.update`.
- Enam sub-master produk memakai permission `products.*`, bukan permission masing-masing.
- Beberapa permission dicek di kode tapi tidak bisa diberikan lewat UI role: `users.export`, `users.bulk_delete`, `users.export_seeder`, `po.verify`, `budgets.*`.
- Permission `projects.*`, `hr_attendances.*`, `hr_payroll.*`, `custom_reports.create/export/delete` tidak dicek di kode.

**Data dan perilaku**
- Hapus PO tidak mengembalikan budget WID dan tidak menghapus Payment Advice.
- Hapus GR memaksa PO kembali ke Approved walaupun sebelumnya Completed.
- `erp:sync-stocks` memaksa PO yang punya GR Received menjadi Completed tanpa melihat pembayaran.
- Edit PO tidak mengecek ulang item RF yang sudah ada di PO aktif lain.
- `amount_paid` dan `balance_amount` PO tidak diperbarui oleh pembayaran termin.
- Field RF `rf_type`, pilihan status superadmin dan tab Approval di form RF dikirim tapi diabaikan; `remarks_print` PO divalidasi tapi tidak tersimpan.
- Nomor Payment Advice otomatis memakai angka acak.
- Tabel `activity_logs` dan `erp_budget_ledgers` tidak dipakai.
- Perintah `master:sync-users` rusak (memanggil class yang tidak ada).
- Belum ada UI untuk menghubungkan user ke project selain otomatis saat user dibuat.
- Template custom report global dan bisa dihapus siapa saja yang punya akses laporan.
- Notifikasi tugas otomatis tidak bisa ditandai dibaca; tombol "tandai semua dibaca" belum berfungsi.

**Operasional**
- Upload invoice/bukti bayar ada di folder publik; perlu dipindah ke penyimpanan privat.
- Production perlu `SESSION_SECURE_COOKIE=true` dan `APP_DEBUG=false`.
- PHP server 8.4.26 perlu diperbarui ke ≥ 8.4.27 (tambalan keamanan).
- ProjectLogoTest perlu ditulis ulang.
