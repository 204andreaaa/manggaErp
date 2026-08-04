# Multi-Tenant API Documentation

## Overview

Multi-tenant system for managing multiple projects (Mandau, Imprima, etc) dalam 1 aplikasi.

---

## Architecture

```
Master Database (mp3_master)
├── users (login credentials)
├── projects (project list)
├── user_projects (permissions)
└── user_sessions (track current project)

Project Databases
├── mandau_db (project 1 data)
├── imprima_db (project 2 data)
└── ... (more projects)
```

---

## API Endpoints

### 1. Get All Projects for User
**GET** `/api/projects/`

```bash
curl -X GET http://localhost:8000/api/projects \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "current_project": 1,
  "current_project_name": "Mandau",
  "projects": [
    {
      "id": 1,
      "name": "Mandau",
      "db_name": "mandau_db",
      "db_host": "127.0.0.1",
      "db_port": 3306,
      "is_active": true,
      "role": "admin",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

### 2. Switch to Project
**POST** `/api/projects/switch`

```bash
curl -X POST http://localhost:8000/api/projects/switch \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": 1
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Berhasil switch ke project: Mandau",
  "project": {
    "id": 1,
    "name": "Mandau",
    "db_name": "mandau_db"
  }
}
```

---

### 3. Get Current Project
**GET** `/api/projects/current`

```bash
curl -X GET http://localhost:8000/api/projects/current \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "project": {
    "id": 1,
    "name": "Mandau",
    "db_name": "mandau_db",
    "db_host": "127.0.0.1",
    "db_port": 3306
  },
  "role": "admin"
}
```

---

### 4. Create New Project (Superadmin Only)
**POST** `/api/projects/create`

```bash
curl -X POST http://localhost:8000/api/projects/create \
  -H "Authorization: Bearer YOUR_SUPERADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Imprima",
    "db_name": "imprima_db",
    "db_host": "127.0.0.1",
    "db_port": 3306,
    "db_username": "root",
    "db_password": ""
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Project berhasil dibuat",
  "project_id": 2
}
```

---

## How It Works

### 1. User Login
```
1. POST /login dengan email & password
2. User login ke mp3_master database
3. Credentials verified against users table di master
```

### 2. After Login - Auto Project Switch
```
1. SetTenantConnection middleware detect user authenticated
2. Cek session untuk current_project
3. Jika tidak ada, ambil project pertama dari user_projects
4. Switch database connection ke project database
5. Set current_project & current_project_name di session
```

### 3. User Request
```
1. User melakukan query (ambil products, sales, etc)
2. Middleware sudah switch DB ke project yang benar
3. Query dijalankan di project database (mandau_db, imprima_db, etc)
4. Data hasil query hanya dari project tersebut
```

### 4. User Switch Project
```
1. POST /api/projects/switch dengan project_id baru
2. Verify user punya akses ke project itu
3. Switch database connection
4. Update session current_project
5. Response success dengan project info
```

---

## Usage in Code

### Get Current Project
```php
use App\Services\TenantManager;

// Get current project ID
$projectId = TenantManager::getCurrentProject();

// Get current project name
$projectName = TenantManager::getCurrentProjectName();

// Using helper
$projectId = currentProject();
$projectName = currentProjectName();
```

### Switch Project
```php
use App\Services\TenantManager;

TenantManager::switchToProject($projectId);

// Using helper
switchProject($projectId);
```

### Get User Projects
```php
use App\Services\TenantManager;

$projects = TenantManager::getUserProjects($userId);

// Using helper
$projects = userProjects($userId);
```

### Query Current Project Database
```php
use DB;

// Queries automatically use switched connection
$products = DB::connection('tenant')->table('products')->get();

// Or using helper
$products = tenantDB()->table('products')->get();

// Or just use DB:: normally (if switched)
$products = DB::table('products')->get();
```

---

## Testing Checklist

- [ ] User dapat login (mp3_master)
- [ ] After login, auto switch ke first project
- [ ] User dapat lihat projects mereka
- [ ] User dapat switch project
- [ ] Query setelah switch return data dari project yang benar
- [ ] User tanpa akses ke project tidak bisa switch
- [ ] Superadmin dapat create project baru
- [ ] New project punya database sendiri
- [ ] New users dapat assign ke project
- [ ] Multiple users dapat access same project
- [ ] User data isolated per project

---

## Troubleshooting

### User tidak bisa login
- Check mp3_master.users table
- Verify password hashing

### After login, error "User tidak punya akses ke project"
- Check mp3_master.user_projects table
- Verify user_id dan project_id mapping

### Query return wrong data
- Check current_project di session
- Verify database connection switched correctly
- Use `tenantDB()` explicitly untuk confirm

### Database connection error
- Check project database exists
- Verify credentials (db_username, db_password)
- Check db_host & db_port accessible

---

## Example: Create New Project (Step by Step)

### 1. Login as Superadmin
```bash
POST /login
{
  "email": "superadmin@example.com",
  "password": "password"
}
```

### 2. Create Project
```bash
POST /api/projects/create
{
  "name": "Imprima",
  "db_name": "imprima_db"
}
```

### 3. Assign Users to Project
```php
// Di database, insert ke user_projects
INSERT INTO mp3_master.user_projects (user_id, project_id, role)
VALUES (1, 2, 'admin');
```

### 4. User Login & Switch
```bash
# User login
POST /login
{
  "email": "user@example.com",
  "password": "password"
}

# Switch ke new project
POST /api/projects/switch
{
  "project_id": 2
}
```

---

## Notes

- SetTenantConnection middleware runs untuk setiap request
- Database connection di-switch di middleware, bukan di controller
- Semua queries default ke switched connection
- Session menyimpan current_project untuk persistence
- Logout akan reset session (user back to master DB)

---

## Next Steps

1. Update User model untuk accept username, position, phone, dll dari old DB
2. Create admin panel untuk manage projects & users
3. Add role-based access control per project
4. Add project-specific configurations
5. Add audit logs per project
