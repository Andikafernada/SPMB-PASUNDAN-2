-- migrations/003_add_reminder_daftar_ulang.sql

-- Description: Add REMINDER_DAFTAR_ULANG template and reminder_du enum value
-- Author: Claude Code (ECC Agent)
-- Date: 2026-06-17
-- @UNDO: DELETE FROM wa_templates WHERE kode_template = 'REMINDER_DAFTAR_ULANG';

BEGIN;

-- 1. Add new enum value for reminder_du type
ALTER TABLE wa_templates MODIFY COLUMN jenis ENUM('acc','daftar_ulang','pindah_jurusan','cabut','reminder','reminder_du') DEFAULT 'reminder';

-- 2. Insert the reminder daftar ulang template
INSERT INTO wa_templates (kode_template, nama_template, jenis, template_text, is_active, created_at, updated_at)
VALUES (
    'REMINDER_DAFTAR_ULANG',
    'Pengingat Daftar Ulang',
    'reminder_du',
    '📢 *PENGINGAT DAFTAR ULANG*

Yth. {NAMA}
ID: {ID_DAFTAR}

Sehubungan dengan proses pendaftaran di SMK Pasundan 2 Bandung, dengan ini kami mengingatkan Anda untuk segera:

🔹 *Melengkapi Berkas Pendaftaran:*
   • Fotokopi Ijazah/SKHUN 3 lembar
   • Fotokopi Akta Kelahiran 2 lembar
   • Fotokopi Kartu Keluarga 2 lembar
   • Pas Foto 3x4 (3 lembar)
   • Fotokopi NISN
   • Fotokopi Rapor (jika ada)

🔹 *Mengisi Formulir Daftar Ulang Online*
   Silakan akses link berikut:
   👉 https://ppdb.smkkpasundan2.sch.id/daftar-ulang

📅 *Batas Waktu:* {TANGGAL}

⚠️ *Penting:*
• Berkas asli dibawa saat pengambilan seragam
• Formulir online WAJIB diisi sebelum batas waktu
• Hubungi kami jika ada kendala: 083817203455

Terima kasih atas kerjasamanya.

_Hormat kami,_
*Panitia PPDB SMK Pasundan 2 Bandung*',
    1,
    NOW(),
    NOW()
);

COMMIT;
