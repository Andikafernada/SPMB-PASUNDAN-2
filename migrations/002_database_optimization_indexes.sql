-- ============================================================
-- Database Optimization Migration
-- SPMB SMK Pasundan 2 Bandung
-- Created: 2026-06-16
-- Purpose: Add indexes for better query performance
-- ============================================================

-- ============================================================
-- 1. SISWA TABLE INDEXES
-- ============================================================

-- Primary lookup indexes
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_id_pendaftaran (id_pendaftaran);
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_kode_billing (kode_billing);

-- Status filtering indexes (frequently used in dashboards)
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_status_siswa (status_siswa);
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_status_bayar (status_bayar);
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_status_pendaftaran (status_pendaftaran);

-- TPA indexes (for TPA system)
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_tpa_selesai (tpa_selesai);

-- Jurusan filtering (dashboard & reports)
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_jurusan (jurusan);
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_gelombang (gelombang);

-- Date-based queries (registration date)
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_tgl_daftar (tgl_daftar);

-- Composite indexes for common query patterns
-- Dashboard stats: jurusan + status
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_jurusan_status (jurusan, status_siswa);
-- TPA reports: tpa_selesai + tpa_tanggal
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_tpa_composite (tpa_selesai, tpa_tanggal);
-- Payment reports: status_bayar + tgl_daftar
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_bayar_tanggal (status_bayar, tgl_daftar);
-- Public portal: status_pendaftaran + sumber_data
ALTER TABLE siswa ADD INDEX IF NOT EXISTS idx_public_filter (status_pendaftaran, sumber_data);

-- ============================================================
-- 2. TPA_JAWABAN TABLE INDEXES
-- ============================================================

-- Composite primary key for faster lookups
ALTER TABLE TPA_JAWABAN ADD INDEX IF NOT EXISTS idx_siswa (id_siswa);
ALTER TABLE TPA_JAWABAN ADD INDEX IF NOT EXISTS idx_soal (id_soal);
ALTER TABLE TPA_JAWABAN ADD INDEX IF NOT EXISTS idx_benar (benar);

-- Composite for answer checking
ALTER TABLE TPA_JAWABAN ADD INDEX IF NOT EXISTS idx_siswa_soal (id_siswa, id_soal);

-- ============================================================
-- 3. TPA_SOAL TABLE INDEXES
-- ============================================================

-- For loading questions by category
ALTER TABLE TPA_SOAL ADD INDEX IF NOT EXISTS idx_kategori (kategori);
ALTER TABLE TPA_JAWABAN ADD INDEX IF NOT EXISTS idx_kategori_aktif (aktif);

-- ============================================================
-- 4. HISTORY_JURUSAN TABLE INDEXES
-- ============================================================

-- Already has idx_siswa, but add composite for reports
ALTER TABLE HISTORY_JURUSAN ADD INDEX IF NOT EXISTS idx_tgl_pindah (tgl_pindah);

-- ============================================================
-- 5. ARSIP_SISWA TABLE INDEXES
-- ============================================================

-- For restore operations
ALTER TABLE ARSIP_SISWA ADD INDEX IF NOT EXISTS idx_arsip_id (id_siswa);
ALTER TABLE ARSIP_SISWA ADD INDEX IF NOT EXISTS idx_arsip_jurusan (jurusan);

-- ============================================================
-- 6. LOGIN_ATTEMPTS TABLE INDEXES
-- ============================================================

-- For rate limiting queries
ALTER TABLE LOGIN_ATTEMPTS ADD INDEX IF NOT EXISTS idx_ip_status_time (ip_address, status, attempt_time);
ALTER TABLE LOGIN_ATTEMPTS ADD INDEX IF NOT EXISTS idx_username_time (username, attempt_time);

-- ============================================================
-- 7. AUDIT_LOG TABLE INDEXES
-- ============================================================

-- For audit queries
ALTER TABLE AUDIT_LOG ADD INDEX IF NOT EXISTS idx_user_time (user_id, created_at);
ALTER TABLE AUDIT_LOG ADD INDEX IF NOT EXISTS idx_action_time (action, created_at);

-- ============================================================
-- 8. WA_NOTIFICATIONS TABLE INDEXES
-- ============================================================

-- For WA queue processing
ALTER TABLE WA_NOTIFICATIONS ADD INDEX IF NOT EXISTS idx_wa_status (status);
ALTER TABLE WA_NOTIFICATIONS ADD INDEX IF NOT EXISTS idx_wa_kode (kode_billing);

-- ============================================================
-- 9. WA_TEMPLATES TABLE INDEXES
-- ============================================================

-- Already indexed in migration, verify exists
-- idx_jenis, idx_active

-- ============================================================
-- 10. GELOMBANG TABLE INDEXES
-- ============================================================

ALTER TABLE GELOMBANG ADD INDEX IF NOT EXISTS idx_aktif (aktif);

-- ============================================================
-- OPTIMIZE TABLES (defragment after adding indexes)
-- ============================================================
OPTIMIZE TABLE siswa;
OPTIMIZE TABLE tpa_jawaban;
OPTIMIZE TABLE tpa_soal;
OPTIMIZE TABLE history_jurusan;
OPTIMIZE TABLE arsip_siswa;
OPTIMIZE TABLE login_attempts;
OPTIMIZE TABLE audit_log;
OPTIMIZE TABLE wa_notifications;
OPTIMIZE TABLE gelombang;

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================

-- Show all indexes on siswa table
-- SHOW INDEX FROM siswa;

-- Show table sizes before/after optimization
-- SELECT TABLE_NAME, ROUND(DATA_LENGTH/1024/1024, 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'db_sekolah';
