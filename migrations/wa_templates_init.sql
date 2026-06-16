-- ============================================================
-- WhatsApp Template System Migration
-- Database: db_sekolah
-- Created: 2026-06-16
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
    'Assalamualaikum {NAMA}! 🎉

Pendaftaran Anda di *SMK Pasundan 2* telah DISETUJUI.

📋 *Detail Pendaftaran:*
• ID Pendaftaran: *{ID_DAFTAR}*
• Jurusan: *{JURUSAN}*
• Asal Sekolah: {SEKOLAH}

Silakan lakukan daftar ulang sesuai jadwal yang telah ditentukan.

Terdaftar oleh: {ADMIN}
Tanggal: {TANGGAL}'
),
(
    'DAFTAR_ULANG',
    'Konfirmasi Daftar Ulang',
    'daftar_ulang',
    'Assalamualaikum {NAMA}! ✅

Pendaftaran ulang Anda telah TERKONFIRMASI.

📋 *Detail:*
• ID Pendaftaran: *{ID_DAFTAR}*

Selamat bergabung di keluarga besar *SMK Pasundan 2*! 🎓

Ditangani oleh: {ADMIN}'
),
(
    'PINDAH_JURUSAN',
    'Notifikasi Pindah Jurusan',
    'pindah_jurusan',
    'Assalamualaikum {NAMA},

Terdapat perubahan data pada pendaftaran Anda:

🔄 *Perubahan Jurusan:*
• Jurusan Lama: *{JURUSAN_LAMA}*
• Jurusan Baru: *{JURUSAN_BARU}*
• Alasan: {ALASAN}

Jika ada pertanyaan silakan hubungi bagian administrasi.

Hormat kami,
{ADMIN}'
),
(
    'CABUT_BERKAS',
    'Notifikasi Cabut Berkas',
    'cabut',
    'Assalamualaikum {NAMA},

Berdasarkan permintaan, data pendaftaran Anda telah ditarik/dicabut dari sistem.

📋 *Alasan:* {ALASAN}

Hubungi admin jika perlu bantuan atau informasi lebih lanjut.

Hormat kami,
{ADMIN}
{TANGGAL}'
),
(
    'REMINDER_BAYAR',
    'Pengingat Pembayaran',
    'reminder',
    'Assalamualaikum {NAMA},

📢 *PENGINGAT PEMBAYARAN*

Pendaftaran {GELOMBANG} Anda:
• Nominal: *Rp{BIAYA}*
• Status: BELUM LUNAS ⚠️

Segera lunasi agar pendaftaran tidak hangus.

Terima kasih.
SMK Pasundan 2'
);

