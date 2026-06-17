-- migrations/004_wa_broadcast_history.sql

-- Description: Create wa_broadcast_history table for tracking sent messages
-- Author: Claude Code (ECC Agent)
-- Date: 2026-06-17
-- @UNDO: DROP TABLE IF EXISTS wa_broadcast_history;

BEGIN;

CREATE TABLE IF NOT EXISTS wa_broadcast_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT DEFAULT NULL COMMENT 'ID siswa dari tabel siswa',
    id_pendaftaran VARCHAR(50) DEFAULT NULL COMMENT 'ID pendaftaran SPMB',
    nama_siswa VARCHAR(255) DEFAULT NULL COMMENT 'Nama lengkap siswa',
    no_hp VARCHAR(20) DEFAULT NULL COMMENT 'Nomor HP tujuan',
    template_kode VARCHAR(50) DEFAULT NULL COMMENT 'Kode template WA',
    template_nama VARCHAR(100) DEFAULT NULL COMMENT 'Nama template WA',
    pesan_text TEXT COMMENT 'Isi pesan yang dikirim',
    status ENUM('pending','success','failed') DEFAULT 'pending' COMMENT 'Status pengiriman',
    error_message TEXT DEFAULT NULL COMMENT 'Pesan error jika gagal',
    sent_by VARCHAR(100) DEFAULT NULL COMMENT 'Admin yang mengirim',
    sent_at DATETIME DEFAULT NULL COMMENT 'Waktu terkirim',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu record dibuat',

    INDEX idx_siswa (siswa_id),
    INDEX idx_template (template_kode),
    INDEX idx_status (status),
    INDEX idx_tanggal (created_at),
    INDEX idx_no_hp (no_hp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='History semua pesan broadcast WA yang pernah dikirim';

COMMIT;
