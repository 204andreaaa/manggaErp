# PRD — Mangga ERP (Mandau)

| | |
|---|---|
| **Dokumen** | Product Requirements Document (as-built) |
| **Sistem** | Mangga ERP — ERP multi-project untuk procurement, budget project, kasbon, pembayaran supplier, gudang dan HR |
| **Repo** | `204andreaaa/manggaErp` (branch utama `master`) |
| **Versi kode** | Laravel 13, PHP 8.2+ (server: PHP 8.4) |
| **Versi PRD** | 1.9 |
| **Terakhir diperbarui** | 2026-11-26 |
| **Status** | Belum production (masih tahap uji internal) |

> **Cara dokumen ini dirawat.** PRD ini ditulis dari kode yang berjalan, bukan dari rencana. Setiap perubahan perilaku sistem (function, aturan, field, status, hak akses, halaman) menghasilkan **PRD versi baru**: versi yang lama disalin dulu ke `docs/prd-history/PRD-v<versi>.md`, lalu dokumen ini diperbarui, nomor versinya dinaikkan, dan satu baris ditambahkan di [Changelog](#14-changelog) — semuanya di commit yang sama dengan perubahan kodenya. Di repo ini ada hook Claude Code (`.claude/settings.json` → `.claude/hooks/prd-reminder.cjs`) yang mengingatkan hal ini setiap kali file di `app/`, `routes/`, `database/`, `config/` atau `resources/views/` diubah.

## Daftar isi

1. [Ringkasan produk](#1-ringkasan-produk)
2. [Pengguna dan peran](#2-pengguna-dan-peran)
3. [Arsitektur dan teknologi](#3-arsitektur-dan-teknologi)
4. [Alur bisnis utama](#4-alur-bisnis-utama)
5. [Modul Procurement](#5-modul-procurement) — RF, PR, PO, GR, Stok, Pengeluaran Departemen, Kendaraan
6. [Modul Keuangan](#6-modul-keuangan) — Payment Advice, Payment Terms, Invoice (tagihan ke customer)
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
Grup menjalankan beberapa PT (mis. PT Mandiri Daya Utama Nusantara / Mandau dan PT Intertel Media Prima / Imprima). Tiap PT punya datanya sendiri dan butuh:
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

---

## 2. Pengguna dan peran

Role disimpan di database master (`roles`), satu user bisa punya beberapa role. `superadmin` dan `admin` selalu lolos semua cek permission.

| Role (slug) | Siapa | Pekerjaan utama di sistem |
|---|---|---|
| `superadmin` | Pengelola sistem | Semua akses; satu-satunya yang bisa hapus RF/PO/GR/PA, unlock dokumen, kelola project dan konfigurasi approval |
| `admin` | Admin pusat | Semua permission (kecuali aksi khusus superadmin) |
| `admin_project` | Admin project | Budget (Budget Parent, Sub Project, WID, Budget Plan), kasbon dan expense, membuat RF |
| `procurement` | Tim purchasing | Membuat PO dari RF, supplier, payment terms, katalog produk |
| `logistik` | Logistik & gudang | Membuat PR dari RF, GR, stok, gudang |
| `general_affair` | GA | Membuat RF, mencatat dan memverifikasi GR |
| `finance` | Finance | Invoice, Payment Advice dan termin, membayar dan menutup kasbon |
| `ceo` | CEO | Approval RF/PO/termin, melihat semua |
| `hrd` | HR | Departemen, data karyawan |

**Role jabatan** (ditambahkan `UserRosterSeeder`). Setiap orang cukup punya **satu role**. Role jabatan dihitung sebagai anggota divisinya (`Role::DIVISION_MEMBERS`): semua cek role divisi di kode (`hasRole('procurement')`, middleware `role:`, notifikasi ke Finance) juga meloloskan role jabatannya. Yang membedakan head dan staff adalah **permission** masing-masing role, diatur oleh `RolePermissionSeeder` (lihat [§12.3](#123-hak-akses-default-per-role)): staff mendapat semua permission head divisinya **kecuali** approve, reject, delete, verify dan delete_pr. Project Manager dan Project Coordinator mengikuti aturan staff.

| Role (slug) | Anggota divisi | Dipakai untuk |
|---|---|---|
| `head_project` | `admin_project` | Head of Project |
| `project_manager` | `admin_project` | Project Manager |
| `project_coordinator` | `admin_project` | Project Coordinator (belum ada orangnya) |
| `staff_project` | `admin_project` | Staff Project |
| `head_procurement` | `procurement` | Head of Procurement (staff memakai `procurement`) |
| `head_logistic` | `logistik` | Head of Logistic (staff memakai `logistik`) |
| `head_finance` | `finance` | Head of Finance (staff memakai `finance`) |
| `head_hr` | `hrd` | Head of HR (staff memakai `hrd`) |

Tim IT (Head dan Staff IT) memakai `superadmin`. GA belum punya head.

Hak akses default per role ada di [§12.3](#123-hak-akses-default-per-role).

---

## 3. Arsitektur dan teknologi

### 3.1 Multi-perusahaan (multi-tenant)
- **Satu tenant = satu PT** (Mandau, Imprima). Di kode dan database tenant disebut *project* (tabel `projects`); di layar disebut **Perusahaan** supaya tidak tertukar dengan Project (Sub Project) di modul budget.
- **Database master** (`master`): users, roles, role_user, projects, project_user, employees, departments, notifications, custom_reports, activity_logs, companies (tidak dipakai lagi).
- **Database tenant** (`tenant`, satu per project): seluruh data operasional (RF, PR, PO, GR, stok, PA, budget, kasbon, master produk/supplier/gudang, ADVEXNPJ).
- Setelah login user memilih perusahaan (halaman "MANGGA ERP"). `project_id` disimpan di session. Middleware `SetTenantConnection` mengarahkan koneksi `tenant` ke database project itu di setiap request.
- User non-superadmin hanya bisa masuk ke project yang terhubung lewat `project_user`.
- Akibatnya: data karyawan dan user sama di semua perusahaan, tetapi RF, PO, budget, master produk, supplier, gudang, invoice, kendaraan dan ADVEXNPJ **berbeda per perusahaan**.
- **Identitas PT** disimpan di tenant itu sendiri: nama legal, logo, alamat, telepon, fax, email, website, NPWP. Identitas ini menjadi **kop surat** cetakan PO dan GR, default *Invoice To / Attention* di PO, dan PT penagih invoice. Diubah superadmin lewat tombol Edit di halaman pilih perusahaan atau menu System & Security → Perusahaan (Tenant). Logo PNG/JPG/WEBP maks 2 MB disimpan di `public/uploads/logos`. Saat fitur ini dipasang, perusahaan yang sudah ada diisi identitas Mandau (yang sebelumnya tertulis langsung di cetakan), kecuali yang namanya mengandung "imprima" (diisi nama legal PT Intertel Media Prima saja).

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
        Kasbon (Advance Request) Draft ──submit (budget di-booking)──► approval ──► Approved
                 └─► Finance tandai Paid
                        └─► Expense Declaration "with advance" (langsung Approved)
                               → sisa kasbon (outstanding) berkurang
                        └─► Finance Settle: sisa dicatat dikembalikan, kasbon Closed, budget dilepas
Expense Declaration "without advance": Draft ──submit (budget di-booking)──► approval ──► Approved
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
| Kendaraan (plat) | Opsional, dipilih dari kendaraan aktif (lihat [5.7](#57-kendaraan-ga)). Kosongkan untuk item yang bukan urusan kendaraan |
| Jenis biaya kendaraan | **Wajib bila plat diisi**: Sewa / Servis / Lainnya. **Sewa hanya untuk kendaraan Rent** |
| Periode sewa (mulai, selesai) | **Wajib untuk Sewa**, selesai tidak boleh sebelum mulai. Untuk jenis lain dikosongkan |

**Status RF**
| Dari → Ke | Aksi | Siapa | Syarat dan efek |
|---|---|---|---|
| — → Draft | Simpan / Clone | Permission `request_forms.create` (diberikan ke **semua role** secara default) | — |
| Draft/Rejected → Submitted | Submit / Ajukan Ulang | Pembuat RF atau admin/superadmin | Minimal 1 item; harus ada konfigurasi approval `request_form` yang cocok (jenis project dan nominal), bila tidak: *"Approval flow is not configured"*. Membuka putaran approval baru. |
| Submitted → Approved | Approve level terakhir | Approver yang ditunjuk / anggota role / superadmin | Pembuat RF tidak boleh approve RF sendiri (kecuali superadmin) |
| Submitted → Rejected | Reject | Sama dengan approve | Alasan wajib. Bisa diedit dan diajukan ulang |
| Selain Draft → Draft | Unlock | Superadmin | **Seluruh riwayat approval dihapus** |
| → dihapus | Hapus RF | Superadmin | **Ditolak bila masih ada PO yang belum Cancelled** (tombol abu-abu dengan alasan). PO yang sudah Cancelled, PR, item dan approval ikut dihapus. |

- RF hanya bisa diedit saat Draft atau Rejected, dan **hanya oleh pembuatnya atau admin/superadmin**. Tombol Edit dan Submit tidak muncul untuk orang lain.
- Semua role mendapat `request_forms.view/create/submit` secara default (RoleSeeder + migrasi master `2026_11_12_000001`). Akibatnya tab Request Form di halaman karyawan juga terlihat oleh siapa pun yang boleh membuka halaman karyawan itu.
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
- **Detail PO**, **Edit**, **Cetak**. Kop cetakan (logo, nama PT), alamat pengiriman bila gudang tidak punya alamat, serta alamat dan NPWP di Invoice Details diambil dari identitas PT perusahaan aktif.

**Membuat PO**
- Oleh procurement, superadmin, atau `purchase_orders.create`.
- Hanya item RF yang belum ada di PO aktif (non-Cancelled) yang bisa dipilih.
- Field: supplier (wajib; alamat, rekening, transfer to, payment terms terisi otomatis), gudang tujuan, contact person, tanggal (wajib), ETA, metode bayar (Bank Transfer/Cash/COD), payment terms, invoice to / attention to (default nama legal PT perusahaan tempat PO dibuat, dan "Finance <nama PT>"), tanda tangan (user role CEO), deskripsi, lampiran.
- Item: qty, harga satuan, pajak (nominal per baris), remark. Baris qty 0 diabaikan.
- Total: `total_po_amount` = Σ qty × harga; `tax` = Σ pajak; `total_po_amount_with_tax` = jumlah keduanya.

**Status PO:** Draft → Submitted → Approved → Completed, serta Rejected dan Cancelled.

| Aksi | Siapa | Syarat dan efek |
|---|---|---|
| Edit | Procurement/superadmin/`purchase_orders.update` | Hanya Draft/Rejected. Status kembali Draft, verifikasi direset. Baris harus dari RF PO itu sendiri dan tidak boleh sudah ada di PO aktif lain. Baris qty 0 tidak disimpan. Item RF yang dibuang dari PO kembali *Requested*, yang ada di PO jadi *Ordered* |
| Verifikasi | User di konfigurasi `po_verification`, atau superadmin. Tanpa konfigurasi: role procurement atau permission `purchase_orders.verify` | Bila konfigurasi ini ada, **PO wajib diverifikasi sebelum submit** |
| Submit | Procurement/admin/superadmin | Draft/Rejected. Membuat putaran approval dari konfigurasi `purchase_order` (cocok jenis RF dan total + pajak). Bila tidak ada konfigurasi: ≤ Rp1.000.000 di-approve procurement, di atasnya CEO |
| Approve | Approver yang ditunjuk / role / superadmin | Pembuat PO tidak boleh approve sendiri. Approve terakhir → **Approved**: potong budget WID, buat Payment Advice otomatis, perbarui harga beli produk |
| Reject | Sama | Alasan wajib. Bisa diedit dan diajukan ulang |
| Cancel | Superadmin | Ditolak bila sudah ada GR atau ada termin yang sudah diproses/dibayar. Efek: PA dan termin → Cancelled, **budget WID dikembalikan**, item RF → Requested |
| Unlock | Superadmin | Ditolak bila ada GR Received atau pembayaran. Efek: budget dikembalikan, approval dan PA dihapus, status → Draft |
| Hapus | Superadmin | **Hanya PO Draft yang belum pernah diajukan** (belum ada approval, GR, atau Payment Advice). PO lain harus di-Cancel; tombol Delete tidak muncul. Efek: PO dan itemnya dihapus, item RF → Requested |

**Potong budget WID (saat PO Approved)**
- Per baris PO: biaya = total baris (qty × harga + pajak).
- WID dicari dari: WID di item RF → WID di header RF → kode project RF.
- `remaining_budget` WID dikurangi (minimal 0). Dikembalikan saat Cancel/Unlock (maksimal sebesar alokasi).
- RF Non Project tidak punya WID, jadi tidak memotong apa pun.

**Aturan Completed:** PO Approved otomatis menjadi **Completed** saat semua item sudah diterima penuh (GR) **dan** Payment Advice sudah lunas, urutan mana pun yang terjadi duluan. Perintah `erp:sync-stocks` memakai aturan yang sama.

**Sudah dibayar / sisa tagihan:** setiap kali termin ditandai dibayar, `amount_paid` PO = Σ termin yang sudah dibayar (semua Payment Advice PO itu) dan `balance_amount` = total PO dengan pajak − yang sudah dibayar.

**Harga beli produk:** setiap PO approved/cancel/unlock/hapus, harga beli produk disinkronkan ke harga satuan PO Approved terbaru.

### 5.4 Goods Receipt (GR / DO)
- **Dibuat oleh:** logistik, GA, superadmin, atau `goods_receipts.create`, dari PO **Approved/Completed** yang belum diterima penuh.
- **Field:** tanggal (wajib), no DO supplier, pengirim, penerima, remark, dan per item qty dikirim / qty diterima (tidak boleh melebihi sisa qty PO).
- **Penerimaan sebagian** didukung; satu PO bisa punya banyak GR.
- **Status:** Recorded → Received.
- **Verifikasi (Receive):** user di konfigurasi `gr_verification`; bila tidak ada konfigurasi, role logistik/GA/superadmin atau `goods_receipts.verify`.
- **Efek Received:** stok bertambah (hanya produk fisik), item RF yang sudah penuh → Completed, flag GR di PO diperbarui, cek aturan Completed PO.
- **Hapus GR:** superadmin, **hanya GR yang masih Recorded** (belum diverifikasi). GR Received tetap tercatat dan tidak bisa dihapus (tombol tidak muncul). Setelah GR Recorded dihapus: flag GR PO dihitung ulang; PO Completed hanya turun ke Approved bila tidak lagi diterima penuh dan lunas.
- **Cetak GR** tersedia; kopnya dari identitas PT perusahaan aktif.

### 5.5 Stok
- Stok disimpan per **produk + gudang + supplier**.
- Halaman stok: filter gudang dan jenis (Fisik / Jasa / semua), kolom kode, nama, part no, jenis, gudang, supplier, qty + UOM, status stok (*In Stock* > 10, *Low Stock* > 0, *Out of Stock*).
- Stok hanya bertambah dari GR Received dan dibangun ulang oleh perintah `erp:sync-stocks`. **Belum ada penyesuaian manual atau retur.**

### 5.6 Pengeluaran Departemen
**Tujuan:** mencatat (bukan membatasi) pengeluaran RF/PO Non Project per departemen.
- **Akses:** permission `department_spending.view` (bisa dicentang di menu Role; `budgets.view` lama tetap berlaku). Admin/superadmin selalu bisa.
- **Filter tahun:** default tahun berjalan, atau "semua".
- **Ringkasan per departemen:** jumlah dan nilai RF Approved, jumlah PO dan nilai terpakai (PO Approved/Completed, total + pajak), baris total.
- **Peringatan:** jumlah RF Non Project tanpa departemen (tidak ikut dihitung).
- **Detail departemen:** semua RF Non Project departemen itu (semua status) dan PO-nya.

### 5.7 Kendaraan (GA)
**Tujuan:** buku induk mobil/motor kantor, milik sendiri (**Own**) atau sewa (**Rent**), beserta biayanya. Mengikuti objek "Vehicle" di Salesforce.

**Halaman**
- **Daftar kendaraan:** kotak ringkasan (kendaraan aktif Own/Rent, sewa habis, STNK/pajak yang lewat atau habis ≤ 30 hari; kotak bisa diklik sebagai filter), cari plat/merk/lokasi/PIC, filter Own/Rent dan aktif. Kolom: plat, jenis, merk/model, lokasi, status sewa + akhir sewa, tanggal STNK dan pajak (merah = lewat, kuning = ≤ 30 hari).
- **Kendaraan baru:** pilih dulu Own atau Rent (tidak bisa diubah setelah disimpan), lalu isi form. Ada **Simpan & Tambah Lagi**.
- **Detail kendaraan:** kotak ringkasan (Rent: total sewa, total servis, masa sewa; Own: harga beli, total servis, total biaya), **Riwayat Biaya**, **Pengajuan Dalam Proses**, dan data kendaraan.

**Field**
| Field | Own | Rent |
|---|---|---|
| Plat nomor | Wajib, unik, disimpan huruf besar | Wajib, unik |
| Merk, kondisi, region, lokasi, area sekarang, STNK habis | Opsional | **Wajib** |
| Jenis (Mobil/Motor), tahun produksi | Wajib | Wajib |
| Harga beli, tanggal beli | Harga beli **wajib** | Tidak dipakai |
| Vendor rental (dari master Supplier), harga sewa per **Bulan / Tahun** (harga kontrak, biaya tetap dihitung dari PO), mulai dan akhir sewa | Tidak dipakai | **Wajib**; akhir ≥ mulai |
| Model, tipe, warna, kilometer, status pemakaian, project (Sub Project), PIC, nama/alamat STNK, nomor rangka/mesin, pajak habis, plat yang digantikan, keterangan | Opsional | Opsional |
| Aktif | Centang; dihilangkan bila mobil dijual / sewa dikembalikan | Sama |

Merk, model, tipe, warna, kondisi, status pemakaian, region, lokasi dan area menawarkan isian yang pernah dipakai (teks bebas).

**Biaya kendaraan lewat RF (tanpa form terpisah)**
- Sewa, servis dan biaya lain diajukan lewat **RF biasa**: di item RF pilih plat, jenis biaya dan (untuk Sewa) periode sewa. Item lain di RF yang sama boleh tanpa plat. Plat ikut tampil di detail RF, detail PO dan cetakan PO.
- Approval-nya **sama dengan RF dan PO lain** (Approval Config yang sudah ada), tidak ada jalur khusus kendaraan.
- **Riwayat Biaya** = baris PO yang item RF-nya menunjuk kendaraan itu dan PO-nya **Approved/Completed**; nilainya total baris PO termasuk pajak. RF yang belum punya PO approved tampil di **Pengajuan Dalam Proses**. PO yang di-cancel otomatis tidak dihitung lagi.
- **Akhir sewa yang berlaku** = yang lebih akhir antara akhir sewa yang diisi di kendaraan dan akhir periode Sewa dari PO yang approved. **Status sewa** *Expired* bila tanggal itu sudah lewat, selain itu *Not Yet* (dihitung saat dibuka, tidak disimpan).

**Hapus:** permission `vehicles.delete`, **ditolak bila kendaraan sudah dipakai di RF** (nonaktifkan saja). Penghapusan bersifat soft delete.

**Hak akses:** `vehicles.view/create/update/delete`. General Affair mendapat semuanya dan menu GA → Kendaraan, CEO `vehicles.view` (migrasi master `2026_11_26_000001`). Memilih plat di RF tidak butuh permission kendaraan.

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

### 6.3 Invoice (tagihan ke customer)
**Tujuan:** mencatat tagihan perusahaan (PT tenant yang sedang dibuka — tidak dipilih per invoice) ke customer (piutang, uang masuk) beserta pembayarannya, dengan rincian per WID. Meniru objek "Invoice" di Salesforce. Tanpa approval.

> **Asumsi tahap 1 (dibuat tanpa konfirmasi tim Finance, 2026-11-16).** Form rincian sudah disamakan dengan form "New Invoice Detail" Salesforce. Pajak (PPN/PPh) ditambahkan di versi 1.7 sesuai keputusan user. Belum dibuat: master Customer (customer masih teks bebas), upload bukti bayar, pelunasan invoice USD, tautan ke Revenue Plan WID. Lihat [Lampiran](#15-lampiran-celah-dan-pekerjaan-terbuka).

**Invoice**
| Field | Aturan |
|---|---|
| Invoice Id | Otomatis `INV-00001` |
| Invoice Number | Wajib, maks 120, unik (mis. `MDU04871/IJE-MANDAU/XI/2026`) |
| Invoice Date | Wajib |
| Invoice Due Date | Opsional, tidak boleh sebelum Invoice Date. Lewat jatuh tempo dan belum lunas ditandai merah |
| Customer | Teks bebas (opsional) |
| TOP | Teks bebas (mis. "DP 30%", "TERMIN 1 - 95%", "FP 100%") |
| Remarks | Opsional |
| Invoice Amount (Nilai Invoice) | **Otomatis** = Σ rincian bermata uang IDR; nilai **sebelum pajak** (DPP) |
| PPN % | Wajib, 0–100. Invoice baru terisi tarif default dari admin, bisa diubah per invoice |
| PPh % | Wajib, 0–100. Sama seperti PPN |
| PPN | **Otomatis** = Invoice Amount × PPN % |
| Nilai Invoice + PPN | **Otomatis** = Invoice Amount + PPN (total yang ditagih) |
| PPh | **Otomatis** = Invoice Amount × PPh %; dipotong customer (bukti potong) dan **langsung dianggap terbayar**. Invoice yang tidak kena PPh (mis. material saja) diisi PPh 0% |
| Harus Dibayar Customer | **Otomatis** = Nilai Invoice + PPN − PPh. Contoh: 100 jt, PPN 11%, PPh 2% → 111 jt − 2 jt = 109 jt |
| Invoice Amount USD | **Otomatis** = Σ rincian bermata uang USD (ditampilkan terpisah, tidak ikut hitungan pembayaran) |
| Amount Payment Detail | **Otomatis** = Σ pembayaran |
| Belum Dibayar | **Otomatis** = (Nilai Invoice + PPN − PPh) − Amount Payment Detail |
| Invoice Status | **Otomatis**: New (belum ada bayar) → Partial Paid → Paid (lunas = pembayaran mencapai Harus Dibayar Customer) |
| Partial Payment Date | **Otomatis** = tanggal pembayaran pertama |
| Final Payment Date | **Otomatis** = tanggal pembayaran terakhir saat lunas |

**Invoice Detail** (rincian per WID; satu invoice bisa menagih beberapa WID sekaligus). Field mengikuti form Salesforce:
| Field | Aturan |
|---|---|
| Invoice Detail Id | Otomatis `INVDET-00001` |
| Account | **Wajib** (Budget Parent) |
| Project Code | **Wajib** (Sub Project) |
| Work Id | **Wajib**. Pilihan Account → Project → Work Id saling menyaring; memilih Work Id langsung mengisi Account dan Project-nya. Server selalu mengambil Account/Project dari WID dan menolak pasangan yang tidak cocok |
| Terms | **Wajib**, teks dengan saran (DP I, DP II, Termin I, Termin II, Final Payment) sampai daftar pilihan Salesforce yang sebenarnya diketahui |
| Currency | **Wajib**: IDR atau USD |
| Amount | Wajib, > 0 |
| Received Doc | Tanggal, opsional |

Tombol **Simpan & Tambah Lagi** (seperti "Save & New") langsung membuka form rincian berikutnya. Rincian bisa dihapus; penghapusan rincian IDR ditolak bila nilai invoice jadi lebih kecil dari yang sudah dibayar.

**Payment Detail** (pembayaran masuk): tanggal bayar, amount, metode (Bank Transfer / Cash / Giro / Cek / Lainnya), referensi, catatan. Tidak bisa dicatat sebelum ada rincian, dan tidak boleh melebihi sisa tagihan (setelah pajak). Menghapus pembayaran menghitung ulang status.

**Pajak.** Mengubah PPN/PPh di Edit Invoice menghitung ulang semua nilai; ditolak bila hasilnya lebih kecil dari yang sudah dibayar customer. Menghapus rincian juga ditolak bila nilai setelah pajak jadi lebih kecil dari yang sudah dibayar. Invoice yang dibuat sebelum versi 1.7 mulai dengan PPN dan PPh 0% (nilainya tidak berubah) sampai Finance mengisinya.

**Tarif default** (halaman Invoice → *Tarif Pajak Default*, hanya superadmin/admin): PPN % dan PPh % yang otomatis terisi di invoice baru (awal: PPN 11%, PPh 2%). Disimpan di tabel master `app_settings`. Mengubahnya tidak mengubah invoice yang sudah ada.

**Halaman:** daftar invoice (ringkasan total ditagih termasuk PPN / sudah dibayar / belum dibayar, filter status, cari nomor/customer/TOP), form invoice (dengan bagian Pajak), detail invoice dengan lima kotak: **Nilai Invoice**, **Nilai Invoice + PPN**, **Harus Dibayar Customer** (Nilai Invoice + PPN − PPh), **Sudah Dibayar**, **Belum Dibayar**; tabel rincian menampilkan total sebelum pajak, PPN, PPh dan yang harus dibayar customer.

**Hapus invoice:** permission `invoices.delete`, hanya bila belum ada pembayaran.

**Hak akses:** `invoices.view/create/update/delete`. Finance mendapat semuanya, CEO `invoices.view` (migrasi master `2026_11_16_000001`). Menu: Finance → Invoice (Tagihan Customer).

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
- **Sisa detail** = total expense plan − Σ Expense Declaration Approved − Σ outstanding Kasbon Approved − **booking** (Σ kasbon yang sedang Submitted + Σ deklarasi tanpa kasbon yang sedang Submitted). Kasbon yang sudah di-settle outstanding-nya 0.
- **Booking:** budget dipesan begitu kasbon/deklarasi tanpa kasbon di-submit, jadi dua pengajuan yang menunggu approval tidak bisa bersama-sama melewati budget. Booking lepas sendiri bila pengajuan Rejected. Pengecekan dilakukan saat simpan, saat submit, dan sekali lagi di approval terakhir (kalau budget line-nya diturunkan sementara itu).
- Kolom aktual: total kasbon approved, deklarasi dengan kasbon, deklarasi tanpa kasbon, total aktual.

### 7.8 Kasbon (Advance Request)
- **Form:** tanggal, WID, area, jenis pekerjaan, sub jenis (hanya kombinasi yang punya Annual Plan di tahun itu), pemohon, jumlah site (opsional), dan baris per jenis biaya (nominal + deskripsi).
- **Satu kasbon dibuat per jenis biaya** yang diisi. Total = nominal × jumlah site.
- Nominal harus muat di sisa Budget Plan Detail (dicek saat simpan dan saat submit).
- Kode: `ADVREQ-{NNNNN}`.
- **Status:** Draft → Submitted (budget di-booking) → Approved / Rejected (booking dilepas, bisa diajukan ulang).
- **Approval:** konfigurasi `advance_request` yang rentang nominalnya mencakup total kasbon (min/maks opsional); yang boleh approve/reject hanya orang yang ditunjuk di langkah itu (atau superadmin), tanpa perlu permission `advance_requests.approve`; pemohon tidak boleh approve sendiri; di level terakhir sisa budget dicek ulang.
- **Melihat:** halaman detail bisa dibuka oleh pemegang `advance_requests.view`, pemohonnya, dan orang yang ditunjuk di salah satu langkah approval-nya.
- **Approved:** booking berubah jadi outstanding (nominal sama), Finance dapat notifikasi "Kasbon Siap Dibayar".
- **Bayar:** pemegang `advance_requests.pay` (Finance) menandai **Paid** (tanggal dan pembayar tercatat).
- **Outstanding** = nominal approved − Σ Expense Declaration with advance yang Approved (0 setelah di-settle).
- **Settle / Tutup Kasbon:** pemegang `advance_requests.pay` menutup kasbon Approved + Paid. Sisa yang belum dideklarasikan dicatat **dikembalikan** karyawan (nominal otomatis = outstanding), dengan tanggal pengembalian dan catatan. Setelah itu outstanding = 0, budget-nya dilepas, kasbon berlabel **Closed**, dan tidak bisa dibuat Expense Declaration lagi. Karyawan pemohon dapat notifikasi. (Karyawan tidak bisa "nombok" lewat kasbon karena deklarasi dibatasi sebesar kasbon; kelebihan biaya dibuat sebagai deklarasi tanpa kasbon.)
- Detail kasbon menampilkan daftar Expense Declaration-nya dan tombol buat deklarasi (bila Approved + Paid dan belum di-settle).

### 7.9 Expense Declaration
| | With advance | Without advance |
|---|---|---|
| Sumber | Kasbon Approved + Paid yang belum di-settle | Langsung ke budget |
| Budget | Mengikuti kasbon | Dicari dari WID + area + jenis + tanggal |
| Jenis biaya | Sama dengan kasbon (kasbon "Other Expense" boleh dipecah ke jenis lain) | Bebas |
| Batas | Σ deklarasi ≤ nominal kasbon | Sisa Budget Plan Detail |
| Status awal | **Langsung Approved** (tanpa approval) | Draft → approval `expense_declaration` (sesuai rentang nominal) |
| Efek | Outstanding kasbon berkurang, aktual naik, sisa budget tetap | Budget di-booking saat submit, terpakai saat Approved, dilepas bila Rejected |

- Field: tanggal, pemohon, info kendaraan, nama site, no PO (tanpa kasbon), baris per jenis biaya.
- Kode: `EXP{yymm}-{NNNNNNN}`.
- Approve/reject dan melihat detail mengikuti aturan kasbon (§7.8): orang yang ditunjuk di approval, pemohon, atau pemegang `expense_declarations.view`.

---

## 8. Mesin Approval

### 8.1 Konfigurasi (superadmin)
| Jenis | Tipe |
|---|---|
| `request_form`, `purchase_order`, `payment_advice`, `advance_request`, `expense_declaration` | Bertingkat (level 1, 2, 3…) |
| `po_verification`, `gr_verification` | Satu langkah, satu konfigurasi per jenis |

- Field: jenis, level, nama langkah, **user approver (wajib, satu orang)**, nominal min/maks (opsional), project/non-project/semua.
- Penugasan ke role sudah tidak bisa dipilih. Konfigurasi lama yang masih ke role ditandai merah "Ganti ke user"; begitu disimpan ulang dengan user, role-nya dihapus.
- Konfigurasi di level yang sama tidak boleh tumpang tindih (rentang nominal dan jenis project).
- Halaman konfigurasi menampilkan rentang nominal yang belum tertutup level 1.

**Filter yang benar-benar dipakai saat submit**
| Dokumen | Filter |
|---|---|
| RF | Jenis project + nominal total RF |
| PO | Jenis project RF + total PO dengan pajak |
| Termin PA | Nominal termin |
| Kasbon | Nominal total kasbon |
| Expense Declaration | Nominal deklarasi |

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
- **Field:** nama, username (unik), email (unik), telepon (unik), jabatan, departemen (harus departemen aktif), tanda tangan (gambar maks 2 MB), password (min 6), role (minimal 1), status aktif/nonaktif, **Akses Project** (centang project yang boleh dibuka).
- **Akses Project:** superadmin bisa memberi akses ke semua project; admin lain hanya ke project yang dia sendiri punya, dan akses user ke project lain tidak diubah. Saat membuat user tanpa centang, user otomatis dapat akses ke project yang sedang aktif.
- **Efek simpan:** data karyawan dibuat/diperbarui (NIK `EMP-0001` dst., departemen = pilihan atau tebakan dari role).
- **Aturan:** hanya superadmin yang boleh memberi/mencabut role superadmin dan mengubah akun superadmin; user tidak bisa menghapus dirinya sendiri.
- **Fitur:** export Excel, export CSV seeder, hapus massal.
- Daftar user menandai merah bila departemen kosong atau tidak ada di master.
- User nonaktif otomatis di-logout ("Akun kamu nonaktif").

### 9.2 Role dan permission
- Field: slug, nama, halaman awal, menu yang terlihat, daftar permission.
- Permission dipilih lewat checkbox per grup menu (sumber: `config/menu.php`).
- Sub-master produk (Product Family/Type/Model, Brand, UOM, Currency) tidak punya permission sendiri: menunya tetap bisa dicentang, tapi izin ubah datanya mengikuti `products.*` (tertulis di bawah menunya).
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
| Product Family / Type / Brand / Model | Nama (unik), deskripsi | Hapus permanen; produk terkait menjadi kosong. Izin ikut `products.*` |
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
- Tombol **Tandai semua dibaca** menandai semua notifikasi tersimpan sebagai dibaca. Daftar tugas otomatis tetap tampil sampai tugasnya selesai.
- Notifikasi yang sudah dibaca dihapus otomatis setelah 7 hari.

### 11.5 Custom Reports
- Jenis laporan: alur procurement (PO + RF + WID + supplier + GR + PA), PO, RF, GR, Payment Advice, WID, stok, karyawan.
- Pilih kolom, filter tanggal dan status, preview (300 baris) dengan total, export CSV (5.000 baris).
- Template laporan bisa disimpan. Defaultnya **pribadi** (hanya terlihat oleh pembuatnya); bisa dicentang **Bagikan ke semua user**. Template hanya bisa dihapus oleh pembuatnya atau superadmin. Template yang dibuat sebelum 2026-11-12 dijadikan "dibagikan" supaya tidak hilang dari user lain.

### 11.6 Tampilan
- Menu samping atau menu atas (bisa dipilih user).
- **Isi menu ikut divisi.** Grup divisi (Admin Project, GA, Procurement, Finance, Logistik, HR) hanya muncul untuk orang yang memegang pekerjaan divisi itu (menu modul utamanya dicentang di role). Modul divisi lain yang hanya perlu dilihat tidak punya menu; dokumennya dibuka dari tautan (mis. supplier dari PO/PA). Kasbon dan Expense Declaration ada di **Pengajuan Umum**. Budget Plans dan Pengeluaran Departemen ada di grup Admin Project, atau di grup Finance untuk orang Finance. Penerimaan Barang ada di grup GA untuk GA, dan di grup Logistik untuk orang Logistik.
- **Tombol ikut permission.** Tombol tambah/ubah/hapus/ajukan/bayar hanya tampil bila user memegang permission aksinya; pemegang `.view` saja hanya melihat data.
- Mode terang / gelap / otomatis, warna aksen bisa diganti; tersimpan di browser.
- Halaman error ramah dalam Bahasa Indonesia untuk 403, 404, 405, 419, 429, 500, 503.

---

## 12. Hak akses

### 12.1 Daftar permission
Permission disimpan sebagai daftar di setiap role. `superadmin` dan `admin` selalu lolos. Aksi tulis (tambah, ubah, hapus, ajukan, verifikasi, bayar, buat PR/PO, buat GR) dicek **hanya dari permission**, tidak lagi dari nama role divisi; jadi role yang hanya punya `.view` tidak bisa mengubah data walaupun membuka halamannya. Approve/reject dokumen yang memakai Approval Config ditentukan oleh orang yang ditunjuk di langkahnya.

| Grup | Permission |
|---|---|
| Budget | `budget_parents.*`, `sub_projects.*`, `work_items.*` (view/create/update/delete); `annual_budget_plans.view/create`; `department_spending.view`; legacy `budgets.view/create/update/delete` (masih berlaku untuk ketiga layar budget dan Pengeluaran Departemen) |
| Kasbon | `advance_requests.view/create/approve/pay`; `expense_declarations.view/create/approve` |
| Request Form | `request_forms.view/create/update/delete/submit/approve/reject/create_pr/delete_pr` |
| Purchase Order | `purchase_orders.view/create/update/delete/verify/submit/approve/reject/print` (`po.verify` lama tetap berlaku sebagai `purchase_orders.verify`) |
| Goods Receipt | `goods_receipts.view/create/update/delete/verify/print` |
| Payment | `payment_advices.view/update/delete`; `payment_advice_details.view/update_invoice/submit/approve/reject/mark_paid`; `payment_terms.*`; `invoices.view/create/update/delete` (tagihan ke customer) |
| Gudang | `stocks.view/adjust`; `warehouses.*` |
| Kendaraan | `vehicles.view/create/update/delete` |
| Master | `products.view/create/update/delete/export` (juga berlaku untuk Family, Type, Brand, Model, UOM, Currency); `suppliers.*` |
| HR | `departments.*`, `employees.*`, `hr_attendances.view/manage`, `hr_payroll.view/manage` |
| Sistem | `users.view/create/update/delete/bulk_delete/export/export_seeder`, `roles.*`, `projects.*`, `approval_configs.view/create/delete`, `custom_reports.view/create/export/delete` |

### 12.2 Aksi khusus role
| Aksi | Hanya untuk |
|---|---|
| Hapus/unlock RF, cancel/unlock PO, hapus PO Draft, hapus GR Recorded, hapus PA | superadmin |
| Edit dan submit RF | pembuat RF, admin, superadmin |
| Kelola perusahaan (tenant) beserta identitas PT/kop surat, dan konfigurasi approval | superadmin |
| Atur tarif pajak default invoice customer | superadmin, admin |
| Tandai kasbon dibayar, settle kasbon | `advance_requests.pay` |
| Kelola termin PA (tambah/hapus termin, input invoice, ajukan, tandai lunas) | `payment_advices.update`, `payment_advice_details.update_invoice` / `.submit` / `.mark_paid` |
| Buat PR / hapus PR | `request_forms.create_pr` / `request_forms.delete_pr` (pembuat PR boleh menghapus PR-nya sendiri) |
| Buat PO | `purchase_orders.create` |
| Verifikasi PO / GR tanpa Approval Config | `purchase_orders.verify` / `goods_receipts.verify` |
| Melihat semua tab karyawan | karyawan itu sendiri, hrd, ceo, admin, superadmin |

### 12.3 Hak akses default per role
Sumbernya satu: `Database\Seeders\Launch\RolePermissionSeeder` (dipakai `LaunchSeeder` dan `RoleSeeder`). Aturannya: pekerjaan divisi sendiri penuh; modul divisi lain yang perlu dipantau hanya `.view` **tanpa menu**; sisanya tidak ada. Staff = head tanpa approve/reject/delete/verify/delete_pr. Semua role juga mendapat `request_forms.view/create/submit` dan menu Request Form. Menjalankan seeder ini mengembalikan role di bawah ke standar (perubahan lewat menu Roles pada role ini tertimpa); superadmin dan admin tidak disentuh.

| Role | Permission (head) | Menu |
|---|---|---|
| superadmin, admin | Semua | Semua |
| head_project, admin_project (staff: project_manager, project_coordinator, staff_project) | Budget Parent / Sub Project / WID penuh; Budget Plan view/create; kasbon dan expense view/create; RF update; lihat PO, GR, invoice, produk | WID, Sub Project, Budget Parent, Budget Plans, Kasbon, Expense, PO, Produk |
| head_procurement (staff: procurement) | RF update/approve/reject; PO penuh; GR view/print; supplier dan payment terms penuh; produk penuh + export; stocks.view | PO, Supplier, Payment Terms, Produk dan atributnya |
| head_logistic (staff: logistik) | Buat/hapus PR; PO view/print; GR penuh; stok view/adjust; gudang penuh; lihat supplier dan produk | PO, GR, Stok, Gudang, Produk, UOM |
| general_affair | RF update; PO view/print; GR view/create/update/verify/print; products.view; kendaraan penuh | PO, GR, Produk, Kendaraan |
| head_finance (staff: finance) | PO view/print; PA view/update/delete; semua permission termin; invoice penuh; kasbon view/approve/pay; expense view/approve; Budget Plan view; Pengeluaran Departemen; lihat GR, supplier, payment terms, budget project | PO, Invoice, PA, Kasbon, Expense, Budget Plans, Pengeluaran Departemen |
| head_hr (staff: hrd) | Departemen, karyawan, absensi, payroll penuh | Departemen, Karyawan, Absensi, Payroll |
| ceo | Lihat semua modul bisnis; approve/reject RF, PO, termin, kasbon, expense; tandai termin lunas; approval_configs.view | Semua modul bisnis (tanpa absensi, payroll, sistem selain Approval Config) |

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
| Invoice customer / rinciannya | `INV-NNNNN` / `INVDET-NNNNN` | Tidak reset |
| Expense Declaration | `EXP{yymm}-NNNNNNN` | Bulanan |
| ADVEXNPJ | `ADVEXNPJ-NNNNN` | Tidak reset |
| Produk / Supplier / Gudang | `EPRD-NNN` / `SUP-NNN` / `WHNNN` | — |
| Karyawan | `EMP-NNNN` (id user) | — |

Nomor dokumen yang sudah dihapus tidak dipakai ulang.

### 13.2 Tabel utama
- **Master:** users, roles, role_user, projects, project_user, companies, departments, employees, notifications, custom_reports, activity_logs, app_settings.
- **Tenant:** request_forms, request_form_items, erp_notes_attachments, erp_purchase_requests (+items), erp_purchase_orders (+items), erp_goods_receipts (+items), erp_stocks, erp_payment_advices, erp_payment_advice_details, erp_payment_terms, erp_approvals, erp_approval_configs, erp_budget_parents, erp_sub_projects, erp_work_items, erp_annual_budget_plans, erp_monthly_budget_plans, erp_budget_plan_details, erp_advance_requests, erp_expense_declarations, erp_employee_advances, erp_invoices, erp_invoice_details, erp_invoice_payments, erp_vehicles, erp_products, product_families, product_types, brands, product_models, uoms, currencies, erp_suppliers (+contacts, attachments), erp_warehouses.

### 13.3 Perintah operasional
| Perintah | Fungsi |
|---|---|
| `php artisan master:migrate` | Pratinjau migrasi master; `--execute` (+ `--force` di production) untuk menjalankan |
| `php artisan tenants:migrate-all --execute` | Migrasi semua database project aktif |
| `php artisan tenant:migrate {project_id} --execute` | Migrasi satu project |
| `php artisan erp:sync-stocks` | Bangun ulang stok dari GR Received (**mengosongkan stok dulu**) |
| `php artisan db:seed --class="Database\Seeders\LaunchSeeder" --force` | Data acuan untuk server sungguhan, aman diulang: 9 departemen, role jabatan, dan daftar user tim Mandau (`UserRosterSeeder`). User baru diberi password awal `123456` (superadmin `password123`), email `<username>@mandau.id`, dan akses ke semua project aktif. User yang sudah ada tidak diganti password/nama/email-nya; role, jabatan dan departemennya disamakan dengan daftar. Akun demo lama (budi, siti, procurement, ceo, Chenthia_Budiharto, admin_project, ga_budi) dinonaktifkan, tidak dihapus; `admin` tetap aktif sebagai cadangan. Permission dan menu role divisi, role jabatan dan CEO **dikembalikan ke standar** §12.3 (`RolePermissionSeeder`) |
| `php artisan db:seed --class="Database\Seeders\Launch\RolePermissionSeeder" --force` | Hanya menyamakan permission dan menu role divisi, role jabatan dan CEO dengan standar §12.3 |
| `php artisan users:transfer {lama} {pengganti} --delete` | Memindahkan semua data milik satu user (dokumen yang dibuat, approval dan langkah approval, kasbon, notifikasi, data ADVEXNPJ, akses project) ke user lain di database master dan semua database project, lalu menghapus user lama. Tanpa `--execute` hanya pratinjau |
| `php artisan db:seed --class="Database\Seeders\ErpVehicleSeeder"` | 12 kendaraan contoh (7 Rent, 5 Own; sebagian STNK/pajak/sewa sudah lewat atau hampir habis) plus 2 supplier rental contoh (`SUP-RENT-DEMO1/2`), di database tenant dari `.env`. Aman diulang (dicocokkan lewat plat). Data contoh — untuk lokal/demo |
| `php artisan db:seed` (DatabaseSeeder) | Data demo: **mengosongkan** budget, produk dan approval config, menimpa permission role. Hanya lokal, **jangan di production** |
| `php artisan app:fresh --seed` | Reset total (hanya lokal, **jangan di production**) |

### 13.4 Pengujian
- Feature test di `tests/Feature` (Budget, Kasbon, MasterData, Payment, PurchaseOrder, RequestForm, dll.).
- Jalan di database khusus `mangga_test_master` / `mangga_test_tenant`; test menolak jalan di database lain.
- Perintah: `php artisan test`. Kondisi per 2026-11-19: **388 lolos, 0 dilewati**.

### 13.5 Deploy
Di server (`/Users/mac/apps/mangga`), satu per satu:
1. Backup database.
2. `cd src` lalu `git pull origin master`.
3. `docker exec mangga-app composer install --no-dev --optimize-autoloader`.
4. `docker exec mangga-app php artisan tenants:migrate-all --execute`.
5. `docker exec mangga-app php artisan master:migrate` (pratinjau), lalu `... --execute --force`.
6. `docker exec mangga-app php artisan view:clear` dan `config:clear`.
7. `docker exec mangga-app php artisan db:seed --class="Database\Seeders\LaunchSeeder" --force`.
8. `docker compose restart app reverb`.
9. `npm run build` hanya bila file JS/CSS atau `VITE_*` berubah.

---

## 14. Changelog
Riwayat versi lengkap ada di `docs/prd-history/`.

| Tanggal | Versi | Perubahan |
|---|---|---|
| 2026-11-26 | 1.9 | Modul baru **Kendaraan** (GA): kendaraan Own / Rent dengan field wajib mengikuti Salesforce, status sewa (Expired / Not Yet) dan peringatan STNK/pajak. Item RF bisa memilih plat, jenis biaya (Sewa / Servis / Lainnya) dan periode sewa; biaya kendaraan diambil dari PO yang approved, dan PO sewa yang approved memajukan akhir sewa. Harga sewa kontrak per Bulan atau per Tahun. Approval memakai jalur RF/PO yang ada. Permission `vehicles.*` (GA penuh, CEO lihat). Menu "Rental Mobil GA (Soon)" diganti halaman Kendaraan. **Satu tenant = satu PT:** tenant disebut "Perusahaan" di layar dan menyimpan identitas PT (nama legal, logo, alamat, telepon, fax, email, website, NPWP) yang menjadi kop cetakan PO/GR dan default Invoice To di PO (sebelumnya Mandau tertulis langsung di cetakan). Pilihan Perusahaan di Invoice dihapus (PT penagih = tenant; data lama tidak dihapus, hanya tidak ditampilkan). Seeder kendaraan contoh `ErpVehicleSeeder`. |
| 2026-11-20 | 1.8 | Centang "Update WID" di form rincian invoice dihapus karena artinya tidak diketahui dan tidak berpengaruh apa pun ke WID (sebelumnya hanya disimpan sebagai tanda). Kolom lamanya di database dibiarkan, tidak dipakai lagi. |
| 2026-11-19 | 1.7 | Pajak di invoice customer: PPN % dan PPh % per invoice (default diatur admin di halaman Tarif Pajak Default, awal 11% dan 2%), keduanya dihitung dari Nilai Invoice. Nilai Invoice tetap sebelum pajak; yang harus dibayar customer = Nilai Invoice + PPN − PPh, dan PPh langsung dianggap terbayar. Detail invoice menampilkan Nilai Invoice, Nilai Invoice + PPN, Harus Dibayar Customer (Nilai Invoice + PPN − PPh), Sudah Dibayar, Belum Dibayar. Tabel master baru `app_settings`. |
| 2026-11-19 | 1.6 | Hak akses per divisi disamakan (`RolePermissionSeeder`): pekerjaan sendiri penuh, modul divisi lain hanya lihat tanpa menu, staff tanpa approve/reject/hapus/verify. Aksi tulis dicek dari permission saja, bukan nama role (sebelumnya mis. Finance bisa ubah/hapus Payment Terms walau hanya punya izin lihat). Tombol aksi disembunyikan bila tidak punya izin. Sidebar per divisi: Kasbon dan Expense pindah ke Pengajuan Umum, Budget Plans ikut Finance untuk orang Finance, GR ikut Logistik untuk orang Logistik. Approve/reject kasbon dan expense cukup ditunjuk di Approval Config (sebelumnya macet karena tidak ada role selain superadmin yang punya `advance_requests.approve`); approver dan pemohon bisa membuka detailnya. Tim project dan Finance kini mendapat akses kasbon/expense. |
| 2026-11-19 | 1.5 | Satu role per orang: role jabatan (Head of …, Project Manager, dll.) dihitung sebagai anggota divisinya dan mulai dari salinan permission divisi, jadi head tidak perlu dua role. Perintah baru `users:transfer` untuk memindahkan data user lama ke penggantinya lalu menghapusnya. |
| 2026-11-19 | 1.4 | `LaunchSeeder` untuk server: departemen, role jabatan (Head of Project, Project Manager, Project Coordinator, Staff Project, Head of Procurement/Logistic/Finance/HR) di atas role divisi, dan daftar user tim Mandau (29 orang) beserta jabatan dan departemennya. Akun demo lama dinonaktifkan. Aman dijalankan ulang. |
| 2026-11-16 | 1.3 | Form rincian invoice disamakan dengan Salesforce: Account, Project Code dan Work Id wajib (saling menyaring, diambil dari WID), Terms dan Currency (IDR/USD) wajib, centang Update WID (tanda saja), tombol Simpan & Tambah Lagi. Invoice punya total USD terpisah. Tampilan halaman invoice diperbarui. |
| 2026-11-16 | 1.2 | Modul baru **Invoice (tagihan ke customer)**: invoice, rincian per WID, pembayaran masuk; nilai dan status (New / Partial Paid / Paid) dihitung otomatis. Permission `invoices.*` (Finance penuh, CEO lihat). Dibuat dari asumsi tanpa konfirmasi Finance. |
| 2026-11-12 | 1.1 | **Keputusan bisnis:** PO hanya boleh dihapus selagi Draft dan belum pernah diajukan, sisanya Cancel. GR Received tidak bisa dihapus. Kasbon dan deklarasi tanpa kasbon mem-booking budget sejak submit. Approval kasbon dan deklarasi mengikuti rentang nominal. Kasbon bisa di-Settle (Closed). Setiap langkah approval wajib ke satu user. RF bisa dibuat semua role, tapi hanya pembuat atau admin yang bisa edit/submit. Sub-master produk ikut izin Products. Akses project diatur di form User. Template laporan pribadi kecuali dibagikan. **Perbaikan:** `erp:sync-stocks` tidak lagi memaksa PO Completed. Edit PO menolak baris yang sudah ada di PO lain dan mengembalikan status item RF. `amount_paid`/`balance_amount` PO ikut pembayaran termin. Tombol "Tandai semua dibaca" berfungsi. Permission user export/bulk delete, `purchase_orders.verify` dan `department_spending.view` bisa diberikan lewat menu Role. Perintah rusak `master:sync-users` dan test usang ProjectLogoTest dihapus. |
| 2026-11-12 | 1.0 | PRD pertama ditulis dari kode yang berjalan (as-built). |
| 2026-11-10 | — | RF yang masih punya PO non-Cancelled tidak bisa dihapus; harus cancel PO dulu (refund budget terjadi saat cancel). |
| 2026-11-10 | — | Upgrade Laravel 10 → 13 digabung ke `master`. |
| 2026-11-09 | — | Halaman karyawan dengan tab (Ringkasan, RF, Kasbon Project, ADVEXNPJ, Cuti); tab Kasbon satu baris per kasbon. |
| 2026-11-09 | — | Modul ADVEXNPJ (Advance-Expense Declaration Non Project). |
| 2026-11-09 | — | Departemen karyawan wajib dari daftar master; Procurement dan GA dipisah. |
| 2026-11-02 | — | RF Non Project dibebankan ke departemen; halaman Pengeluaran Departemen. |
| 2026-10-27 | — | Aturan approval bergaya Salesforce: reject menyimpan riwayat, ajukan ulang = putaran baru. PO Completed saat diterima penuh dan lunas. |

---

## 15. Lampiran: celah dan pekerjaan terbuka
Temuan dari pembacaan kode saat menyusun PRD ini. Belum semuanya diverifikasi dengan pengujian; perlu keputusan bisnis atau perbaikan.

Celah dari versi 1.0 yang sudah diputuskan atau diperbaiki di versi 1.1 sudah dihapus dari daftar ini (lihat Changelog).

**Kop surat / identitas PT**
- Cetakan PO, bila supplier tidak punya rekening, masih menampilkan "VA BCA 00112112099300 PT Badan Mandiri Daya" untuk semua perusahaan. Perlu dicek ini rekening apa dan apakah Imprima punya sendiri.
- Tabel master `companies` dan export lama yang memakainya (tidak dipanggil dari mana pun) belum dihapus.

**Invoice customer — perlu dikonfirmasi ke Finance**
- Perlu master Customer, atau cukup teks bebas?
- Daftar pilihan Terms yang sebenarnya (baru diketahui "Final Payment" dan "DP I").
- Rincian USD: dibayar dalam USD atau dikonversi ke IDR? Saat ini total USD hanya ditampilkan, tidak ikut status pembayaran.
- Isi Payment Detail yang sebenarnya (bukti transfer, rekening penerima, potongan).
- Bukti potong PPh dari customer belum bisa di-upload (PPh langsung dianggap terbayar).
- Status selain New / Partial Paid / Paid (mis. Cancelled).
- Siapa yang membuat dan boleh melihat invoice selain Finance.

**Kendaraan — perlu dikonfirmasi ke GA**
- Form servis khusus (KM, jenis servis, checklist komponen, bengkel, biaya aktual) yang otomatis membuat RF belum dibuat; servis sementara diajukan lewat RF dengan plat + jenis Servis.
- Servis dibayar lewat PO ke bengkel, atau kadang lewat kasbon/expense? Servis mobil sewa ditanggung vendor atau perusahaan?
- Siapa yang approve servis dan sewa (sementara jalur RF/PO biasa).
- Biaya kendaraan dibebankan ke project (WID) bila mobilnya dipakai project, atau selalu Non Project?
- Vehicle Usage Request dan Vehicle Return belum dibuat; isinya belum diketahui.
- Impor 218 kendaraan dari Salesforce (menunggu file export). Field Work Type Salesforce belum dibuat karena pilihannya belum diketahui.
- Pengingat STNK/pajak/sewa baru berupa tanda di halaman, belum notifikasi.

**Hak akses**
- Masih ada halaman yang tidak mengecek permission di server: submit PO, halaman cetak PO/GR, dashboard, daftar WID per project.
- Permission `projects.*`, `hr_attendances.*`, `hr_payroll.*`, `custom_reports.create/export/delete` ada di menu Role tapi tidak dicek di kode.
- Approver level 1 kasbon dan expense ("KAM Approval") saat ini Sesa (GA), hasil pemindahan dari akun lama Budi. Perlu dipastikan siapa KAM yang benar.
- Notifikasi dan tombol Approve di riwayat approval hanya untuk user yang ditunjuk. Konfigurasi lama yang masih ditujukan ke role (ditandai "Ganti ke user") perlu diganti ke user.

**Data dan perilaku**
- Field RF `rf_type`, pilihan status superadmin dan tab Approval di form RF dikirim tapi diabaikan.
- Nomor Payment Advice otomatis memakai angka acak.
- Tabel `activity_logs` dan `erp_budget_ledgers` tidak dipakai.
- Belum ada retur barang atau penyesuaian stok manual (GR Received tidak bisa dihapus).

**Operasional**
- Upload invoice/bukti bayar ada di folder publik; perlu dipindah ke penyimpanan privat.
- Production perlu `SESSION_SECURE_COOKIE=true` dan `APP_DEBUG=false`.
- PHP server 8.4.26 perlu diperbarui ke ≥ 8.4.27 (tambalan keamanan).
