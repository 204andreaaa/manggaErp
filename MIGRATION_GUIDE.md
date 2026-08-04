# Multi-Tenant Migration Guide - SAFE & STEP BY STEP

> **Update implementasi 22 Juni 2026:** aplikasi sekarang memakai connection
> `master` dan `tenant` yang terpisah. Jangan gunakan `php artisan migrate`
> langsung untuk proses pemisahan ini. Gunakan command aman di bawah; semuanya
> preview secara default dan baru menulis database jika diberi `--execute`.

## Alur Aman yang Dipakai Sekarang

```bash
# Preview perubahan schema master (read-only)
php artisan master:migrate

# Jalankan setelah backup diverifikasi
php artisan master:migrate --execute

# Preview lalu sinkronkan akun Mandau ke master
php artisan master:sync-users 1
php artisan master:sync-users 1 --execute

# Preview migration operasional khusus mandau_db
php artisan tenant:migrate 1

# Jalankan hanya setelah daftar pending diaudit
# php artisan tenant:migrate 1 --execute
```

Aturan keselamatan:

- `mp3_master`: identitas akun, daftar project, dan mapping akses.
- `mandau_db`: role, warehouse, stok, penjualan, dan transaksi lama.
- Sinkronisasi user hanya update/insert; tidak menghapus user tenant.
- Jangan jalankan `migrate:fresh`, `migrate:refresh`, atau `db:wipe`.
- Pending migration tenant wajib diaudit sebelum memakai `--execute`.

## ⚠️ CRITICAL: BACKUP DULU!
```bash
# 1. Backup database sekarang
mysqldump -u root -p mp3 > backup_mp3_$(date +%Y%m%d_%H%M%S).sql

# Simpan file ini di lokasi aman!
```

---

## Phase 1: Setup Master Database

### Step 1: Buat Master Database
```bash
# Login ke MySQL
mysql -u root -p

# Create master database
CREATE DATABASE mp3_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Exit MySQL
exit
```

### Step 2: Configure .env untuk Master DB (TEMPORARY)
Di project sekarang, ubah `.env` untuk point ke master database:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mp3_master
DB_USERNAME=root
DB_PASSWORD=
```

### Step 3: Run Migration untuk Master Tables
```bash
php artisan migrate
```

Ini akan create tables: projects, users, user_projects, user_sessions

---

## Phase 2: Copy Data dari Old DB ke Master DB

### Step 1: Restore backup ke database temp (untuk reference)
```bash
mysql -u root -p mp3_old < backup_mp3_*.sql
```

### Step 2: Copy users dari mp3_old ke mp3_master
Login ke MySQL:
```bash
mysql -u root -p
```

Jalankan query ini:
```sql
-- Copy all users dari old ke master
INSERT INTO mp3_master.users (id, name, email, password, email_verified_at, remember_token, created_at, updated_at)
SELECT id, name, email, password, email_verified_at, remember_token, created_at, updated_at 
FROM mp3_old.users;

-- Set auto_increment
SELECT MAX(id) FROM mp3_master.users;
-- Misal max id = 50, jalankan:
-- ALTER TABLE mp3_master.users AUTO_INCREMENT = 51;
```

### Step 3: Create Projects Entry
```sql
INSERT INTO mp3_master.projects (id, name, db_name, db_host, db_port, db_username, db_password, is_active, created_at, updated_at) VALUES
(1, 'Mandau', 'mandau_db', '127.0.0.1', 3306, 'root', '', true, NOW(), NOW());
```

### Step 4: Map Users to Project
```sql
-- Assign semua users ke project Mandau
INSERT INTO mp3_master.user_projects (user_id, project_id, role, created_at, updated_at)
SELECT id, 1, 'admin', NOW(), NOW() 
FROM mp3_master.users;
```

---

## Phase 3: Create Project Database (mandau_db)

### Step 1: Create Database
```bash
mysql -u root -p

CREATE DATABASE mandau_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
```

### Step 2: Copy Schema & Data dari Old Database
```bash
# Backup structure dari old db
mysqldump -u root -p mp3_old --no-data > schema_backup.sql

# Restore ke mandau_db
mysql -u root -p mandau_db < schema_backup.sql

# Copy data
mysqldump -u root -p mp3_old > data_backup.sql
mysql -u root -p mandau_db < data_backup.sql
```

### Verify Data
```bash
mysql -u root -p mandau_db -e "SELECT COUNT(*) as total_tables FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'mandau_db';"
```

---

## Phase 4: Update Application Code

### Step 1: Create Multi-Tenant Service
File: `app/Services/TenantManager.php` 
- Ini untuk manage database connections

### Step 2: Create Middleware untuk Switch Database
File: `app/Http/Middleware/SetTenantConnection.php`
- Middleware ini detect project dari request

### Step 3: Update Config Database
File: `config/database.php`
- Add dynamic database configuration

### Step 4: Update Models
- Add `project_id` ke relevant models
- Override connection based on project

---

## Phase 5: Testing

### Test 1: Login ke Master
- User bisa login dengan credentials lama

### Test 2: Switch Project
- Setelah login, check projects yang available
- Bisa switch antar project

### Test 3: Data Isolation
- Data dari mandau_db tidak mixed dengan project lain

### Test 4: Create New Project
- Superadmin bisa create project baru
- New project otomatis create new database

---

## Phase 6: Go Live

### Pre-Flight Checklist:
- [ ] Backup EXISTING database (3x untuk security!)
- [ ] All tests passed
- [ ] No data loss
- [ ] All users dapat login
- [ ] Project switching works
- [ ] Superadmin dapat create new project
- [ ] Performance acceptable

### Go Live Steps:
1. Stop aplikasi (maintenance mode)
2. Final backup
3. Update .env ke production
4. Restart aplikasi
5. Monitor untuk 1 jam pertama

---

## Troubleshooting

### Jika terjadi error saat migration:
1. **Restore dari backup** (sudah siap kan?)
2. **Check logs**: `storage/logs/`
3. **Verify connections**: `php artisan tinker` → `DB::connections()`

### Data tidak match:
- Re-run Step 3 (copy data)
- Verify foreign keys intact

### Performance issue:
- Check indexes: `EXPLAIN SELECT ...`
- Monitor query: `SHOW PROCESSLIST;`

---

## Next Steps:

Setelah semua siap, saya akan buatkan:
1. TenantManager service
2. SetTenantConnection middleware
3. Updated models
4. Project selection controller

Siap jalan? 🚀
