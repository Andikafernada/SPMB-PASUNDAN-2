-- ============================================================
-- MIGRASI FINAL - SPMB SMK PASUNDAN 2 BANDUNG
-- Jalankan SEKALI di phpMyAdmin → db_sekolah → tab SQL
-- Urutan: kolom baru → arsip tahunan → superuser
-- ============================================================

-- ===== BAGIAN 1: KOLOM BARU TABEL SISWA =====
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS jenis_kelamin VARCHAR(20) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tempat_lahir VARCHAR(100) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tanggal_lahir DATE DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS agama VARCHAR(30) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nisn VARCHAR(20) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nik VARCHAR(20) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS no_telepon VARCHAR(20) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nama_jalan VARCHAR(200) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS kota VARCHAR(100) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS provinsi VARCHAR(100) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nama_ayah VARCHAR(100) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ayah VARCHAR(100) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ayah DATE DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS pekerjaan_ayah VARCHAR(100) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nik_ayah VARCHAR(20) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nama_ibu VARCHAR(100) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ibu VARCHAR(100) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ibu DATE DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS pekerjaan_ibu VARCHAR(100) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nik_ibu VARCHAR(20) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS sekolah_asal VARCHAR(200) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nilai_btq INT DEFAULT 0;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS kelas VARCHAR(20) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS request_kelas VARCHAR(150) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS jurusan_lama VARCHAR(50) DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS alamat TEXT DEFAULT NULL;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tahun_spmb YEAR DEFAULT 2026;
ALTER TABLE siswa MODIFY COLUMN status_siswa VARCHAR(50) DEFAULT 'BELUM DAFTAR ULANG';
UPDATE siswa SET status_siswa = 'BELUM DAFTAR ULANG' WHERE status_siswa IS NULL OR status_siswa = '';
UPDATE siswa SET tahun_spmb = YEAR(tgl_daftar) WHERE tahun_spmb IS NULL AND tgl_daftar IS NOT NULL;
UPDATE siswa SET tahun_spmb = 2026 WHERE tahun_spmb IS NULL;

-- ===== BAGIAN 2: KOLOM BARU ARSIP_SISWA =====
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS jenis_kelamin VARCHAR(20) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tempat_lahir VARCHAR(100) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tanggal_lahir DATE DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS agama VARCHAR(30) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nama_jalan VARCHAR(200) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS kota VARCHAR(100) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS provinsi VARCHAR(100) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nama_ayah VARCHAR(100) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ayah VARCHAR(100) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ayah DATE DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS pekerjaan_ayah VARCHAR(100) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nik_ayah VARCHAR(20) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nama_ibu VARCHAR(100) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ibu VARCHAR(100) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ibu DATE DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS pekerjaan_ibu VARCHAR(100) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nik_ibu VARCHAR(20) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS sekolah_asal VARCHAR(200) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nilai_btq INT DEFAULT 0;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS kelas VARCHAR(20) DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS alasan_cabut TEXT DEFAULT NULL;
ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tgl_arsip DATETIME DEFAULT NULL;

-- ===== BAGIAN 3: TABEL HISTORY JURUSAN =====
CREATE TABLE IF NOT EXISTS history_jurusan (
    id_history INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    jurusan_lama VARCHAR(50) NOT NULL,
    jurusan_baru VARCHAR(50) NOT NULL,
    alasan TEXT,
    petugas VARCHAR(100),
    tgl_pindah DATETIME DEFAULT NOW(),
    INDEX idx_siswa (id_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== BAGIAN 4: ARSIP TAHUNAN =====
CREATE TABLE IF NOT EXISTS spmb_arsip_tahunan (
    id_arsip INT AUTO_INCREMENT PRIMARY KEY,
    tahun_spmb YEAR NOT NULL,
    id_siswa_asli INT,
    id_pendaftaran VARCHAR(30),
    nama_lengkap VARCHAR(150),
    jenis_kelamin VARCHAR(20),
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    agama VARCHAR(30),
    nisn VARCHAR(20),
    nik VARCHAR(20),
    no_hp VARCHAR(20),
    jurusan VARCHAR(50),
    jurusan_lama VARCHAR(50),
    kelas VARCHAR(20),
    asal_sekolah VARCHAR(200),
    sekolah_asal VARCHAR(200),
    nama_jalan VARCHAR(200),
    rt VARCHAR(10),
    rw VARCHAR(10),
    kelurahan VARCHAR(100),
    kecamatan VARCHAR(100),
    kota VARCHAR(100),
    provinsi VARCHAR(100),
    nama_ayah VARCHAR(100),
    pekerjaan_ayah VARCHAR(100),
    nama_ibu VARCHAR(100),
    pekerjaan_ibu VARCHAR(100),
    nilai_btq INT DEFAULT 0,
    status_siswa VARCHAR(50),
    status_bayar VARCHAR(30),
    petugas_pendaftar VARCHAR(100),
    tgl_daftar DATETIME,
    tgl_diarsipkan DATETIME DEFAULT NOW(),
    INDEX idx_tahun (tahun_spmb),
    INDEX idx_jurusan (tahun_spmb, jurusan),
    INDEX idx_sekolah (tahun_spmb, asal_sekolah),
    INDEX idx_kecamatan (tahun_spmb, kecamatan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS spmb_log_tutup (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    tahun_spmb YEAR NOT NULL,
    total_diarsipkan INT DEFAULT 0,
    total_du INT DEFAULT 0,
    total_tidak_du INT DEFAULT 0,
    petugas VARCHAR(100),
    tgl_tutup DATETIME DEFAULT NOW(),
    catatan TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== BAGIAN 5: USER ROLE =====
ALTER TABLE users MODIFY COLUMN role VARCHAR(30) NOT NULL DEFAULT 'pendaftaran';
DELETE FROM users WHERE username = 'superuser';
-- NOTE (2026-06-10): Superuser account sekarang dibuat via PHP bcrypt()
-- di database secara terprogram. Jangan INSERT dengan plaintext/MD5 password.
DELETE FROM users WHERE role = 'kepsek';

-- Contoh cara membuat superuser dengan bcrypt (jalankan via PHP):
-- $hash = password_hash('YourPasswordHere', PASSWORD_DEFAULT);
-- mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('superuser', '$hash', 'Administrator', 'superuser')");

-- ===== CATATAN KEAMANAN (2026-06-10) =====
-- AKUN SUPERUSER SEKARANG Sdh DICIPTA OTOMATIS via .env & config.php
-- Password superuser default ada di .env — GANTI setelah login pertama!
-- Password semua akun SUDAH di-hash bcrypt via bcrypt()
-- ==========================================

-- Hapus catatan lama
-- CATATAN: File migrasi ini TIDAK lagi membuat user dengan password plaintext/MD5
-- Semua akun dibuat via PHP bcrypt() di database secara terprogram
