-- ============================================================
-- WhatsApp Template System Migration
-- Database: db_sekolah
-- Created: 2026-06-16
-- Updated: 2026-06-17
-- ============================================================

-- Create wa_templates table
CREATE TABLE IF NOT EXISTS wa_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_template VARCHAR(50) NOT NULL UNIQUE,
    nama_template VARCHAR(100) NOT NULL,
    jenis ENUM('acc', 'daftar_ulang', 'pindah_jurusan', 'cabut', 'reminder') NOT NULL DEFAULT 'acc',
    template_text TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jenis (jenis),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default templates (ignore if exists)
INSERT IGNORE INTO wa_templates (kode_template, nama_template, jenis, template_text) VALUES
(
    'ACC_PENDAFTARAN',
    'Konfirmasi ACC Pendaftaran',
    'acc',
    'Assalamualaikum {NAMA},

Selamat! Pendaftaran Anda di SMK Pasundan 2 Bandung telah diverifikasi.

DETAIL PENDAFTARAN:
ID Daftar: {ID_DAFTAR}
Nama: {NAMA}
Asal Sekolah: {SEKOLAH}
Pilihan Jurusan: {JURUSAN}

LENGKAPI BIODATA (WAJIB)
Mohon lengkapi biodata, data alamat, dan orang tua Anda secara mandiri melalui tautan berikut:
https://spmb-pasundan2.my.id/lengkapi_data.php?id_reg={ID_DAFTAR}

INFORMASI DAFTAR ULANG:
Segera lakukan daftar ulang dengan membawa berkas-berkas berikut:

1. Ijazah/Paket B/STL/SKHUN SMP/MTs (Legalisir)
2. Surat Keterangan Kelakuan Baik (SKKB)
3. Fotocopy Akta Kelahiran
4. Fotocopy Kartu Keluarga & KTP Orang Tua
5. Fotocopy Ijazah SD
6. Surat Tes Kesehatan (Asli)

--
Panitia SPMB SMK Pasundan 2 Bandung'
),
(
    'DAFTAR_ULANG',
    'Konfirmasi Daftar Ulang',
    'daftar_ulang',
    'Assalamualaikum {NAMA},

Pendaftaran ulang Anda telah terkonfirmasi.

Detail:
- ID Pendaftaran: {ID_DAFTAR}

Selamat bergabung di keluarga besar SMK Pasundan 2 Bandung.

Ditangani oleh: {ADMIN}

--
Panitia SPMB SMK Pasundan 2 Bandung'
),
(
    'PINDAH_JURUSAN',
    'Notifikasi Pindah Jurusan',
    'pindah_jurusan',
    'Assalamualaikum {NAMA},

Dengan ini kami menginformasikan terjadi perubahan data pada pendaftaran Anda:

Data Perubahan Jurusan:
- Jurusan Lama: {JURUSAN_LAMA}
- Jurusan Baru: {JURUSAN_BARU}
- Alasan: {ALASAN}

Jika ada pertanyaan, silakan hubungi bagian administrasi.

Hormat Kami,
Team Data SMK Pasundan 2 Bandung'
),
(
    'CABUT_BERKAS',
    'Notifikasi Cabut Berkas',
    'cabut',
    'Assalamualaikum {NAMA},

Berdasarkan permintaan, data pendaftaran Anda telah ditarik/dicabut dari sistem.

Alasan: {ALASAN}

Hubungi admin jika perlu bantuan atau informasi lebih lanjut.

Hormat kami,
{ADMIN}
{TANGGAL}

--
Panitia SPMB SMK Pasundan 2 Bandung'
),
(
    'REMINDER_BAYAR',
    'Pengingat Pembayaran',
    'reminder',
    'Assalamualaikum {NAMA},

PENGINGAT PEMBAYARAN

Pendaftaran {GELOMBANG} Anda:
- Nominal: Rp{BIAYA}
- Status: BELUM LUNAS

Segera lunasi agar pendaftaran tidak hangus.

Terima kasih.
SMK Pasundan 2 Bandung'
);
