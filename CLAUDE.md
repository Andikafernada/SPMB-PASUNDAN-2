# SPMB SMK Pasundan 2 Bandung

## Deskripsi Project
Sistem Penerimaan Murid Baru (SPMB) untuk SMK Pasundan 2 Bandung.

## Database Migrations

| File | Purpose | Status |
|------|---------|--------|
| `wa_templates_init.sql` | WhatsApp Template System | ✅ Done |
| `002_database_optimization_indexes.sql` | Database Performance Indexes | ✅ Done |

## Setup Database

```bash
# 1. Import schema (jika belum ada)
mysql -u root -p db_sekolah < migrations/wa_templates_init.sql

# 2. Tambahkan indexes untuk performa
mysql -u root -p db_sekolah < migrations/002_database_optimization_indexes.sql
```

## Struktur Database

### Tabel Utama

| Tabel | Purpose |
|-------|---------|
| `siswa` | Data calon siswa |
| `arsip_siswa` | Arsip siswa yang cabut |
| `history_jurusan` | Riwayat pindah jurusan |
| `users` | Akun admin/panitia |
| `login_attempts` | Rate limiting login |
| `audit_log` | Log aktivitas admin |
| `tpa_soal` | Bank soal TPA |
| `tpa_jawaban` | Jawaban siswa TPA |
| `wa_templates` | Template pesan WA |
| `wa_notifications` | Queue notifikasi WA |
| `gelombang` | Gelombang pendaftaran |

### Indexes (Performance)

Indexes yang ditambahkan untuk performa query:

```sql
-- siswa table
- idx_id_pendaftaran (lookup)
- idx_kode_billing (billing)
- idx_status_siswa (dashboard filter)
- idx_status_bayar (payment filter)
- idx_tpa_selesai (TPA status)
- idx_jurusan (filter by major)
- idx_tgl_daftar (date range)
- idx_jurusan_status (composite)
- idx_tpa_composite (composite)
```

---

## Security Status

### ✅ Sudah Diimplementasi

| Fitur | File | Status |
|-------|------|--------|
| SQL Injection Prevention | `proses_crud.php`, `proses_update.php` | ✅ Prepared Statements |
| XSS Prevention | `edit.php`, `reset_tpa.php` | ✅ htmlspecialchars() |
| CSRF Protection | `proses_crud.php` | ✅ Token Validation |
| Session Security | `config.php` | ✅ HttpOnly + Regenerate |
| Password Hashing | `login_proses.php` | ✅ bcrypt |
| Rate Limiting | `login_proses.php` | ✅ Max 5 attempts/15min |

### ⚠️ Environment Variables

Set environment variables untuk konfigurasi:

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

### Fitur
- Gamification dengan Hero Selection
- 40 soal (Verbal 15, Numerik 15, Logika 10)
- Timer 45 menit
- Anti-cheat (no back button, session lock)
- Achievement Card + Certificate PDF

### Akses TPA
- Semua siswa bisa akses (tanpa syarat pembayaran)
- Admin bisa force login untuk bantu siswa

### File TPA
- `views/tpa/login.php` - Login dengan ID Pendaftaran
- `views/tpa/hero_select.php` - Pilih Hero
- `views/tpa/index.php` - Halaman Soal
- `views/tpa/hasil.php` - Hasil & Nilai
- `views/tpa/card.php` - Achievement Card
- `views/tpa/sertifikat.php` - Certificate PDF
- `views/tpa/admin_hasil.php` - Admin Panel
- `views/tpa/force_login.php` - Committee Access

---

## WhatsApp Template System

### Placeholder Tersedia

| Placeholder | Keterangan |
|-------------|------------|
| `{NAMA}` | Nama lengkap siswa |
| `{ID_DAFTAR}` | ID Pendaftaran |
| `{JURUSAN}` | Jurusan yang dipilih |
| `{JURUSAN_BARU}` | Jurusan baru (untuk pindah) |
| `{JURUSAN_LAMA}` | Jurusan lama (untuk pindah) |
| `{ALASAN}` | Alasan perubahan |
| `{ADMIN}` | Nama admin/petugas |
| `{TANGGAL}` | Tanggal kejadian |
| `{SEKOLAH}` | Asal sekolah |
| `{NO_HP}` | Nomor HP |
| `{GELOMBANG}` | Gelombang |
| `{BIAYA}` | Jumlah biaya |

### Template Default

| Kode | Jenis | Trigger |
|------|-------|---------|
| `ACC_PENDAFTARAN` | acc | ACC dari TU |
| `DAFTAR_ULANG` | daftar_ulang | Konfirmasi DU |
| `PINDAH_JURUSAN` | pindah_jurusan | Pindah jurusan |
| `CABUT_BERKAS` | cabut | Cabut berkas |
| `REMINDER_BAYAR` | reminder | Reminder payment |

---

## Roles & Permissions

| Role | Akses |
|------|-------|
| `superuser` | Full access |
| `database` | Manage siswa, TPA admin |
| `tu` | ACC, payment, laporan |
| `pendaftaran` | Pendaftaran baru |
| `user` | View only |

---

## Commit History

| Date | Commit | Description |
|------|--------|-------------|
| 2026-06-16 | e7b6e8e | Disable IP restriction - PPDB accessible from anywhere |
| 2026-06-16 | 052c1c7 | SQL Injection Fix - Prepared Statements + CSRF |
| 2026-06-16 | eceae4c | Security fixes - XSS, credentials, IP restriction |
| 2026-06-16 | 0d5ac08 | Cleanup TPA files |
| 2026-06-16 | f2b1858 | FIX: session_start() in hero_select.php |
| 2026-06-16 | 750726c | Fix TPA access - remove payment requirement |

---

## Catatan Deployment

### Production Checklist

- [x] SQL Injection - Fixed with Prepared Statements
- [x] XSS Prevention - Fixed with htmlspecialchars()
- [x] CSRF Protection - Implemented
- [x] Credentials - Use environment variables
- [x] Session Security - HttpOnly cookies
- [x] Database Indexes - Added for performance
- [x] TPA System - Working without payment requirement
- [x] WhatsApp Templates - Database-driven
