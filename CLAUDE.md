# Rencana: Sistem Template Pesan WhatsApp

## Tujuan
Membuat sistem template pesan WhatsApp yang **dikelola dari database** sehingga admin bisa edit template pesan tanpa perlu ubah kode PHP.

## Masalah Saat Ini
- Template pesan WA di-hardcoded di kode PHP
- Semua notifikasi pakai template yang sama (di N8N)
- Admin tidak bisa ubah isi pesan tanpa edit kode

## Solusi: Database Template System

### 1. Buat Tabel `wa_templates` di Database

```sql
CREATE TABLE IF NOT EXISTS wa_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_template VARCHAR(50) NOT NULL UNIQUE,
    nama_template VARCHAR(100) NOT NULL,
    jenis ENUM('acc', 'daftar_ulang', 'pindah_jurusan', 'cabut', 'reminder'),
    template_text TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME,
    updated_at DATETIME
);
```

### 2. Default Template (5 Jenis)

| Kode | Jenis | Contoh Template |
|------|-------|-----------------|
| `ACC_PENDAFTARAN` | acc | "Assalamualaikum {NAMA}! Pendaftaran {ID_DAFTAR} telah disetujui untuk jurusan {JURUSAN} di gelombang {GELOMBANG}." |
| `DAFTAR_ULANG` | daftar_ulang | "Assalamualaikum {NAMA}! Selamat, Anda telah daftar ulang di jurusan {JURUSAN}. Segera selesaikan pembayaran." |
| `PINDAH_JURUSAN` | pindah_jurusan | "Assalamualaikum {NAMA}! Perubahan jurusan dari {JURUSAN_LAMA} ke {JURUSAN_BARU} telah diproses." |
| `CABUT_BERKAS` | cabut | "Assalamualaikum {NAMA}! Berkas Anda telah dicabut dari sistem. Alasan: {ALASAN}." |
| `REMINDER_BAYAR` | reminder | "Assalamualaikum {NAMA}! Reminder: Pembayaran {ID_DAFTAR} belum lunas. Segera selesaikan." |

### 3. Placeholder yang Tersedia

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

### 4. File yang Dibuat/Dimodifikasi

#### File Baru:
- `/var/www/html/migrations/wa_templates_init.sql` - SQL migration + default data
- `/var/www/html/views/database/wa_templates.php` - Halaman CRUD template WA

#### File Dimodifikasi:
- `/var/www/html/config.php` - Tambah helper functions
- `/var/www/html/views/database/proses_update.php` - Update trigger DU, Pindah Jurusan, Cabut
- `/var/www/html/views/tu/proses_acc.php` - Update trigger ACC Pendaftaran

### 5. Helper Functions (di config.php)

```php
// Render template dengan placeholder replacement
render_wa_template($template, $data);

// Load template dari database
load_wa_template($conn, $kode_template);

// Kirim WA via N8N dengan template
kirim_wa_template($conn, 'KODE_TEMPLATE', $data, $no_hp);
```

### 6. Payload N8N yang Baru

```json
{
  "wa": "6281234567890",
  "type": "acc",
  "kode": "ACC_PENDAFTARAN",
  "message": "Assalamualaikum BUDI SANTOSO! 🎉\n\nPendaftaran SPMB26-001...",
  "raw_data": { "nama": "BUDI SANTOSO", ... },
  "timestamp": "2026-06-16 14:30:00"
}
```

**Perubahan:** Payload sekarang menyertakan field `message` yang sudah di-render dari template.

### 7. Halaman Template Manager

Fitur:
- List semua template dengan filter berdasarkan jenis
- Add/Edit/Delete template
- Toggle active/inactive
- Live preview dengan sample data
- Tombol quick insert placeholder
- WhatsApp-style preview modal

---

## Langkah Implementasi

### Fase 1: Database & Helper
1. Buat SQL migration `wa_templates_init.sql`
2. Insert 5 default templates
3. Tambah helper functions ke `config.php`

### Fase 2: CRUD Page
4. Buat halaman `wa_templates.php` untuk manajemen template

### Fase 3: Integrasi
5. Modifikasi `proses_update.php` - trigger DU, Pindah Jurusan, Cabut
6. Modifikasi `proses_acc.php` - trigger ACC Pendaftaran
7. Test semua flow

### Fase 4: N8N Update
8. Update N8N workflow untuk baca field `message` dari payload

---

## Estimasi Effort
- SQL + Helper functions: 30 menit
- CRUD page: 1-2 jam
- Integrasi trigger: 30 menit
- Testing: 30 menit
- **Total: ~3-4 jam**

---

## Catatan
- Template lama di N8N perlu diupdate untuk baca field `message`
- Support backward compatibility dengan cek field `message` ada atau tidak
- Admin harus punya akses untuk edit template
