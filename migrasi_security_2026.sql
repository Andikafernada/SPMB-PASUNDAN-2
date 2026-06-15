-- =========================================
-- SECURITY MIGRATION - SPMB SMK Pasundan 2
-- Date: 2026-06-09
-- Description: Tambah keamanan untuk login
-- =========================================

-- 1. Tabel untuk tracking login attempts (rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(100),
    status ENUM('success', 'failed') DEFAULT 'failed',
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel untuk session management (optional, untuk advanced security)
CREATE TABLE IF NOT EXISTS active_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Audit log untuk aktivitas penting
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(100),
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tambah kolom tracking untuk tabel siswa
ALTER TABLE siswa
ADD COLUMN IF NOT EXISTS sumber_data ENUM('pendaftaran', 'public') DEFAULT 'pendaftaran',
ADD COLUMN IF NOT EXISTS status_pendaftaran ENUM('pending', 'lunas', 'ditolak') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS gelombang INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS kode_billing VARCHAR(50),
ADD COLUMN IF NOT EXISTS bukti_bayar VARCHAR(255),
ADD COLUMN IF NOT EXISTS tgl_bayar DATETIME;

-- 5. Tabel gelombang/biaya pendaftaran
CREATE TABLE IF NOT EXISTS gelombang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL,
    biaya DECIMAL(10,2) NOT NULL,
    tgl_mulai DATE NOT NULL,
    tgl_selesai DATE NOT NULL,
    aktif BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert data gelombang jika belum ada
INSERT IGNORE INTO gelombang (id, nama, biaya, tgl_mulai, tgl_selesai, aktif) VALUES
(1, 'Gelombang 1', 150000.00, '2026-03-01', '2026-03-31', TRUE),
(2, 'Gelombang 2', 175000.00, '2026-04-01', '2026-04-30', TRUE),
(3, 'Gelombang 3', 200000.00, '2026-05-01', '2026-05-31', TRUE);

-- 6. Tabel upload bukti bayar
CREATE TABLE IF NOT EXISTS bukti_bayar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_billing VARCHAR(50) NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    ukuran_file INT,
    tipe_file VARCHAR(50),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at DATETIME,
    INDEX idx_kode (kode_billing)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabel notifikasi WhatsApp
CREATE TABLE IF NOT EXISTS wa_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_billing VARCHAR(50),
    no_hp VARCHAR(20) NOT NULL,
    jenis ENUM('billing', 'konfirmasi', 'info') DEFAULT 'info',
    pesan TEXT,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kode (kode_billing),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Tabel konfigurasi sistem
CREATE TABLE IF NOT EXISTS sistem_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default configs
INSERT IGNORE INTO sistem_config (config_key, config_value) VALUES
('app_name', 'SPMB SMK Pasundan 2 Bandung'),
('tahun_ajar', '2026/2027'),
('wa_auto_enabled', 'true'),
('max_upload_size', '2097152'),
('allowed_file_types', 'jpg,jpeg,png,pdf');

-- 9. Index untuk performa query
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_sumber (sumber_data);
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_status_pendaftaran (status_pendaftaran);
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_kode_billing (kode_billing);

-- 10. Password upgrade - flag untuk user yang perlu upgrade
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_needs_upgrade BOOLEAN DEFAULT FALSE;

-- Update flag untuk user dengan plain text password
UPDATE users SET password_needs_upgrade = TRUE WHERE password NOT LIKE '$2y$%';

SELECT 'Security Migration Complete!' AS status;