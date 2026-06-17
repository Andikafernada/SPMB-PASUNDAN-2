# SPMB SMK Pasundan 2 Bandung

## Project Identity

**Nama:** SPMB (Sistem Penerimaan Murid Baru)  
**Institusi:** SMK Pasundan 2 Bandung  
**Tech Stack:** PHP 8+, MySQL, JavaScript, WhatsApp Integration  
**Tipe:** Web Application - Sistem Informasi Sekolah

---

## Core Principles (ECC-Inspired)

### 1. Agent-First
Selalu gunakan agen khusus untuk tugas spesifik:
- **php-developer** → Untuk semua kode PHP
- **security-reviewer** → Untuk validasi input dan keamanan
- **database-admin** → Untuk query dan migrasi
- **frontend-dev** → Untuk UI/UX dan JavaScript
- **qa-reviewer** → Untuk testing dan quality assurance

### 2. Security-First
- **Always**: Prepared statements untuk semua query SQL
- **Always**: htmlspecialchars() untuk output user input
- **Always**: CSRF token validation untuk form submission
- **Always**: Environment variables untuk credentials
- **Never**: Hardcode passwords atau API keys
- **Never**: Trust user input tanpa validasi

### 3. Plan Before Execute
Untuk perubahan kompleks:
1. Analisis requirements
2. Identifikasi file yang affected
3. Buat implementation plan
4. Execute dengan langkah terkecil
5. Verify setiap perubahan

### 4. Immutability
- Gunakan `$newData` daripada `$data['key'] = $value` yang mutating
- Fungsi dengan return value yang jelas
- Hindari global state mutation

---

## Project Structure

```
/var/www/html/
├── config.php              # Main configuration (DB, sessions, security)
├── login.php               # Authentication pages
├── login_proses.php        # Login handler
├── index.php               # Landing page
├── lengkapi_data.php       # Student data completion
├── dashboard_pendaftaran.php
├── process_ai.php          # AI processing
├── process_crud.php        # CRUD operations
├── proses_update.php       # Update operations
├── migrations/             # Database migrations
│   ├── wa_templates_init.sql
│   └── 002_database_optimization_indexes.sql
├── views/
│   ├── database/           # Admin database management
│   │   ├── edit.php
│   │   ├── index.php
│   │   ├── wa_*.php        # WhatsApp management
│   │   └── reset_tpa.php
│   ├── pendaftaran/        # Registration flow
│   ├── analisis/           # Analytics
│   ├── tu/                # Tata Usaha module
│   └── tpa/               # TPA (Tes Potensi Akademik)
│       ├── login.php
│       ├── hero_select.php
│       ├── index.php
│       ├── hasil.php
│       ├── card.php
│       ├── sertifikat.php
│       └── admin_hasil.php
├── public/                 # Public-facing pages
│   ├── daftar.php
│   └── cek_status.php
├── assets/                 # CSS, JS, images
├── vendor/                 # Composer dependencies
└── .env                    # Environment variables
```

---

## Database Schema

### Core Tables

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `siswa` | Student data | id, id_pendaftaran, nama, jurusan, status_siswa, status_bayar |
| `arsip_siswa` | Archived students | id_siswa, alasan_cabut, tgl_arsip |
| `history_jurusan` | Major change history | id_siswa, jurusan_lama, jurusan_baru, admin, alasan |
| `users` | Admin/committee accounts | id, username, role, password_hash |
| `login_attempts` | Rate limiting | ip_address, attempts, last_attempt |
| `audit_log` | Activity logging | user_id, action, details, timestamp |
| `tpa_soal` | TPA question bank | id, kategori (verbal/numerik/logika), soal, jawaban |
| `tpa_jawaban` | Student TPA answers | id_siswa, id_soal, jawaban, benar, waktu |
| `wa_templates` | WhatsApp templates | id, kode, template, placeholder |
| `wa_notifications` | WA queue | id_siswa, template, status, created_at |
| `gelombang` | Registration waves | id, nama, tgl_mulai, tgl_selesai, status |

### Performance Indexes

```sql
-- siswa table
idx_id_pendaftaran, idx_kode_billing, idx_status_siswa, 
idx_status_bayar, idx_tpa_selesai, idx_jurusan, idx_tgl_daftar,
idx_jurusan_status, idx_tpa_composite
```

---

## Security Implementation

### ✅ Implemented

| Feature | Files | Implementation |
|---------|-------|----------------|
| SQL Injection Prevention | proses_crud.php, proses_update.php | Prepared Statements dengan `?= ?` placeholders |
| XSS Prevention | edit.php, reset_tpa.php | htmlspecialchars($var, ENT_QUOTES, 'UTF-8') |
| CSRF Protection | proses_crud.php | Token validation via $_SESSION['csrf_token'] |
| Session Security | config.php | session_regenerate_id(), HttpOnly cookies |
| Password Hashing | login_proses.php | password_hash() dengan bcrypt |
| Rate Limiting | login_proses.php | Max 5 attempts per 15 minutes |
| Input Validation | All user-facing files | filter_input(), preg_match() patterns |

### ⚠️ Environment Variables

```bash
# Evo API (WhatsApp Gateway)
EVO_INSTANCE=Pasundan2
EVO_API_KEY=your_api_key_here
EVO_BASE_URL=http://172.16.0.180:8080

# Database
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=db_sekolah
```

---

## TPA System

### Features
- Gamification dengan Hero Selection (5 heroes)
- 40 soal: Verbal (15), Numerik (15), Logika (10)
- Timer 45 menit
- Anti-cheat: No back button, session lock
- Achievement Card + Certificate PDF generation

### Access Control
- Semua siswa bisa akses TPA (tanpa syarat pembayaran)
- Admin bisa force login untuk membantu siswa

### File TPA Structure
```
views/tpa/
├── login.php           # ID Pendaftaran login
├── hero_select.php     # Hero selection screen
├── index.php           # Question page
├── hasil.php           # Results display
├── card.php            # Achievement card generator
├── sertifikat.php      # PDF certificate generator
├── admin_hasil.php     # Admin result panel
└── force_login.php     # Committee access bypass
```

---

## WhatsApp Template System

### Available Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{NAMA}` | Full student name |
| `{ID_DAFTAR}` | Registration ID |
| `{JURUSAN}` | Chosen major |
| `{JURUSAN_BARU}` | New major (for transfers) |
| `{JURUSAN_LAMA}` | Previous major |
| `{ALASAN}` | Reason for change |
| `{ADMIN}` | Admin/committee name |
| `{TANGGAL}` | Event date |
| `{SEKOLAH}` | Previous school |
| `{NO_HP}` | Phone number |
| `{GELOMBANG}` | Registration wave |
| `{BIAYA}` | Amount |

### Default Templates

| Code | Type | Trigger |
|------|------|---------|
| ACC_PENDAFTARAN | acc | TU approval |
| DAFTAR_ULANG | daftar_ulang | DU confirmation |
| PINDAH_JURUSAN | pindah_jurusan | Major change |
| CABUT_BERKAS | cabut | Withdraw |
| REMINDER_BAYAR | reminder | Payment reminder |
| REMINDER_DAFTAR_ULANG | reminder_du | Reminder daftar ulang dengan berkas |

---

## Roles & Permissions

| Role | Access Level |
|------|--------------|
| `superuser` | Full system access |
| `database` | Student management, TPA admin |
| `tu` | Approval, payments, reports |
| `pendaftaran` | New registrations |
| `user` | View only |

---

## Development Guidelines

### PHP Coding Standards

1. **Session Initialization**: Selalu gunakan `session_start()` di awal file
2. **Error Reporting**: Development use `E_ALL`, Production use `0`
3. **Prepared Statements**: WAJIB untuk semua query dengan user input
4. **File Organization**: Max 500 lines per file, split jika lebih
5. **Naming Convention**: snake_case untuk functions, camelCase untuk JS

### Security Checklist

- [ ] Prepared statements untuk semua INSERT, UPDATE, DELETE
- [ ] htmlspecialchars() untuk semua output
- [ ] CSRF token untuk form POST
- [ ] password_verify() untuk login
- [ ] filter_input() untuk input validation
- [ ] rate limiting untuk sensitive endpoints

### Git Workflow

```bash
# Feature branch
git checkout -b feat/feature-name

# Commit dengan conventional commits
git commit -m "feat(database): add new field for student origin"

# Push dan create PR
git push origin feat/feature-name
```

---

## Database Migrations

```bash
# Import schema
mysql -u root -p db_sekolah < migrations/wa_templates_init.sql

# Add indexes
mysql -u root -p db_sekolah < migrations/002_database_optimization_indexes.sql

# Add reminder daftar ulang template
mysql -u root -p db_sekolah < migrations/003_add_reminder_daftar_ulang.sql
```

---

## Deployment Checklist

- [x] SQL Injection Prevention
- [x] XSS Prevention
- [x] CSRF Protection
- [x] Session Security
- [x] Password Hashing
- [x] Rate Limiting
- [x] Database Indexes
- [x] TPA System Functional
- [x] WhatsApp Templates

---

## Commit History

| Date | Commit | Description |
|------|--------|-------------|
| 2026-06-17 | - | ECC Integration - Agent System |
| 2026-06-16 | e7b6e8e | Disable IP restriction |
| 2026-06-16 | 052c1c7 | SQL Injection Fix |
| 2026-06-16 | eceae4c | Security fixes - XSS, credentials |
| 2026-06-16 | 0d5ac08 | Cleanup TPA files |
| 2026-06-16 | f2b1858 | Fix session_start() in hero_select |
| 2026-06-16 | 750726c | Fix TPA access |

---

## Related Files

- **AGENTS.md** - Specialized agents configuration
- **RULES.md** - Coding rules and guidelines
- **SOUL.md** - Project identity and principles
