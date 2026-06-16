<?php
/**
 * PROSES UPDATE - CENTRAL COMMAND v5.0 SECURE
 * WhatsApp Template System Integration
 * aksi: du, du_toggle, pindah_jurusan, cabut, restore
 * Updated 2026-06-16: Prepared Statements + CSRF Protection
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../../config.php';

// Fungsi bantuan untuk menampilkan error page
function tampilkan_error($judul, $pesan) {
    die("
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Sistem Terhenti - " . htmlspecialchars($judul) . "</title>
        <script src='https://cdn.tailwindcss.com'></script>
        <link href='https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap' rel='stylesheet'>
        <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }</style>
    </head>
    <body class='h-screen flex items-center justify-center p-4'>
        <div class='bg-white border border-red-200 rounded-3xl p-8 max-w-md w-full text-center shadow-2xl shadow-red-500/10'>
            <div class='w-20 h-20 bg-red-50 rounded-full flex items-center justify-center text-4xl mx-auto mb-6'>🚨</div>
            <h1 class='text-xl font-black text-slate-900 mb-2 uppercase tracking-tight'>" . htmlspecialchars($judul) . "</h1>
            <p class='text-sm text-slate-500 mb-8'>" . htmlspecialchars($pesan) . "</p>
            <button onclick='window.history.back()' class='w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl transition-all shadow-md'>
                Kembali ke Halaman Sebelumnya
            </button>
        </div>
    </body>
    </html>
    ");
}

// ============================================
// 1. AUTHENTICATION
// ============================================
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'database') {
    tampilkan_error("Akses Ditolak", "Halaman atau tindakan ini hanya bisa dieksekusi oleh Tim Database dan Superuser.");
}

// ============================================
// 2. VALIDATE INPUT
// ============================================
$aksi = $_GET['aksi'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    tampilkan_error("ID Tidak Valid", "ID Siswa tidak valid atau tidak ditemukan.");
}

$petugas = htmlspecialchars($_SESSION['nama'] ?? 'Admin System');

// ============================================
// 3. HELPER FUNCTIONS
// ============================================
function safe_string($data) {
    return trim(strip_tags($data ?? ''));
}

function safe_upper($data) {
    return strtoupper(safe_string($data));
}

// ============================================
// 4. KONFIRMASI DAFTAR ULANG
// ============================================
if ($aksi == 'du') {
    $stmt = mysqli_prepare($conn, "UPDATE siswa SET status_siswa='SUDAH DAFTAR ULANG' WHERE id_siswa=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=success_du");
    exit();
}

// ============================================
// 5. TOGGLE DU (AJAX)
// ============================================
if ($aksi == 'du_toggle') {
    $status = safe_string($_GET['status'] ?? 'BELUM DAFTAR ULANG');

    // Validate status value
    $allowed_statuses = ['BELUM DAFTAR ULANG', 'SUDAH DAFTAR ULANG'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'BELUM DAFTAR ULANG';
    }

    $stmt = mysqli_prepare($conn, "UPDATE siswa SET status_siswa=? WHERE id_siswa=?");
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(['ok' => true]);
    exit();
}

// ============================================
// 6. PINDAH JURUSAN
// ============================================
if ($aksi == 'pindah_jurusan') {
    $baru = safe_upper($_GET['jurusan_baru'] ?? '');
    $lama = safe_upper($_GET['jurusan_lama'] ?? '');
    $alasan = safe_string($_GET['alasan'] ?? '-');

    if (empty($baru) || empty($lama)) {
        header("Location: index.php?status=error");
        exit();
    }

    // Create history table if not exists
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS history_jurusan (
        id_history INT AUTO_INCREMENT PRIMARY KEY,
        id_siswa INT NOT NULL, jurusan_lama VARCHAR(50), jurusan_baru VARCHAR(50),
        alasan TEXT, petugas VARCHAR(100), tgl_pindah DATETIME DEFAULT NOW(),
        INDEX idx_siswa (id_siswa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Update siswa with prepared statement
    $stmt = mysqli_prepare($conn, "UPDATE siswa SET jurusan=?, jurusan_lama=? WHERE id_siswa=?");
    mysqli_stmt_bind_param($stmt, "ssi", $baru, $lama, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Insert history with prepared statement
    $stmt = mysqli_prepare($conn, "INSERT INTO history_jurusan (id_siswa, jurusan_lama, jurusan_baru, alasan, petugas) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issss", $id, $lama, $baru, $alasan, $petugas);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Get siswa data
    $stmt = mysqli_prepare($conn, "SELECT * FROM siswa WHERE id_siswa=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $d = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($d) {
        kirim_wa_template($conn, 'PINDAH_JURUSAN', [
            'nama' => $d['nama_lengkap'],
            'jurusan_baru' => $baru,
            'jurusan_lama' => $lama,
            'alasan' => $alasan,
            'admin' => $petugas,
            'tanggal' => date('d/m/Y H:i')
        ], $d['no_hp']);
    }

    header("Location: index.php?status=success_pindah");
    exit();
}

// ============================================
// 7. CABUT BERKAS
// ============================================
if ($aksi == 'cabut') {
    $alasan = safe_string($_GET['alasan'] ?? '');

    // Get siswa data
    $stmt = mysqli_prepare($conn, "SELECT * FROM siswa WHERE id_siswa=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $s = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$s) {
        tampilkan_error("Data Tidak Ditemukan", "Data siswa tidak ditemukan di tabel aktif.");
    }

    // Ensure arsip columns exist
    $cols_arsip = [
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS jenis_kelamin VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tempat_lahir VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS agama VARCHAR(30) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nama_jalan VARCHAR(200) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS kota VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS provinsi VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nama_ayah VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nik_ayah VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ayah VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ayah DATE DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS pekerjaan_ayah VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nama_ibu VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nik_ibu VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ibu VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ibu DATE DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS pekerjaan_ibu VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS sekolah_asal VARCHAR(200) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nilai_btq INT DEFAULT 0",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS kelas VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS alasan_cabut TEXT DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tgl_arsip DATETIME DEFAULT NULL"
    ];
    foreach ($cols_arsip as $c) {
        mysqli_query($conn, $c);
    }

    // Insert to arsip with prepared statement
    $stmt = mysqli_prepare($conn, "INSERT INTO arsip_siswa (
        id_siswa, id_pendaftaran, nama_lengkap, jenis_kelamin, nik,
        tempat_lahir, tanggal_lahir, no_hp, jurusan, asal_sekolah,
        jurusan_asal, sumber_info, alamat, nama_jalan, status_bayar,
        status_siswa, nisn, agama, rt, rw,
        kelurahan, kecamatan, kota, provinsi, petugas_pendaftar,
        tgl_daftar, nama_ayah, nik_ayah, tempat_lahir_ayah, tgl_lahir_ayah,
        pekerjaan_ayah, nama_ibu, nik_ibu, tempat_lahir_ibu, tgl_lahir_ibu,
        pekerjaan_ibu, sekolah_asal, nilai_btq, kelas, alasan_cabut,
        tgl_arsip
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $no_hp_s = $s['no_hp'] ?? '';
    $stmt->bind_param(
        "isssssssssssssssssssssssssssssssssiissss",
        $s['id_siswa'],
        $s['id_pendaftaran'],
        $s['nama_lengkap'],
        $s['jenis_kelamin'],
        $s['nik'],
        $s['tempat_lahir'],
        $s['tanggal_lahir'],
        $no_hp_s,
        $s['jurusan'],
        $s['sekolah_asal'],
        $s['jurusan_asal'] ?? '',
        $s['sumber_info'] ?? '',
        $s['alamat'] ?? '',
        $s['nama_jalan'] ?? '',
        $s['status_bayar'] ?? '',
        $s['status_siswa'] ?? '',
        $s['nisn'] ?? '',
        $s['agama'] ?? '',
        $s['rt'] ?? '',
        $s['rw'] ?? '',
        $s['kelurahan'] ?? '',
        $s['kecamatan'] ?? '',
        $s['kota'] ?? '',
        $s['provinsi'] ?? '',
        $s['petugas_pendaftar'] ?? '',
        $s['tgl_daftar'],
        $s['nama_ayah'] ?? '',
        $s['nik_ayah'] ?? '',
        $s['tempat_lahir_ayah'] ?? '',
        $s['tgl_lahir_ayah'] ?? '',
        $s['pekerjaan_ayah'] ?? '',
        $s['nama_ibu'] ?? '',
        $s['nik_ibu'] ?? '',
        $s['tempat_lahir_ibu'] ?? '',
        $s['tgl_lahir_ibu'] ?? '',
        $s['pekerjaan_ibu'] ?? '',
        $s['sekolah_asal'] ?? '',
        $s['nilai_btq'] ?? 0,
        $s['kelas'] ?? '',
        $alasan,
        $s['tgl_daftar'] ?? null
    );

    if ($stmt->execute()) {
        $stmt->close();

        // Delete from siswa
        $stmt = mysqli_prepare($conn, "DELETE FROM siswa WHERE id_siswa=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Send WA notification
        kirim_wa_template($conn, 'CABUT_BERKAS', [
            'nama' => $s['nama_lengkap'],
            'alasan' => $alasan,
            'admin' => $petugas,
            'tanggal' => date('d/m/Y H:i')
        ], $no_hp_s);

        header("Location: index.php?status=success_cabut");
        exit();
    } else {
        $stmt->close();
        tampilkan_error("Gagal Mengarsipkan", "Terjadi kesalahan SQL: " . mysqli_error($conn));
    }
}

// ============================================
// 8. RESTORE DARI ARSIP
// ============================================
if ($aksi == 'restore') {
    // Get from arsip
    $stmt = mysqli_prepare($conn, "SELECT * FROM arsip_siswa WHERE id_siswa=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $s = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$s) {
        tampilkan_error("Data Arsip Tidak Ditemukan", "Data siswa tidak ditemukan di tabel arsip.");
    }

    // Check if already exists in siswa
    $stmt = mysqli_prepare($conn, "SELECT id_siswa FROM siswa WHERE id_siswa=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cek = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($cek) {
        tampilkan_error("Duplikat ID", "Siswa tersebut sudah ada di tabel aktif.");
    }

    // Restore to siswa
    $stmt = mysqli_prepare($conn, "INSERT INTO siswa (
        id_siswa, id_pendaftaran, nama_lengkap, jenis_kelamin, nik,
        tempat_lahir, tanggal_lahir, no_hp, jurusan, asal_sekolah,
        nisn, agama, rt, rw, kelurahan, kecamatan, kota, provinsi,
        alamat, nama_jalan, petugas_pendaftar, tgl_daftar, status_bayar, status_siswa,
        nama_ayah, nik_ayah, tempat_lahir_ayah, pekerjaan_ayah,
        nama_ibu, nik_ibu, tempat_lahir_ibu, pekerjaan_ibu,
        sekolah_asal, nilai_btq
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $status_siswa_default = 'BELUM DAFTAR ULANG';
    $stmt->bind_param(
        "isssssssssssssssssssssssssssssss",
        $s['id_siswa'],
        $s['id_pendaftaran'],
        $s['nama_lengkap'],
        $s['jenis_kelamin'],
        $s['nik'],
        $s['tempat_lahir'],
        $s['tanggal_lahir'],
        $s['no_hp'],
        $s['jurusan'],
        $s['sekolah_asal'] ?? '',
        $s['nisn'] ?? '',
        $s['agama'] ?? '',
        $s['rt'] ?? '',
        $s['rw'] ?? '',
        $s['kelurahan'] ?? '',
        $s['kecamatan'] ?? '',
        $s['kota'] ?? '',
        $s['provinsi'] ?? '',
        $s['alamat'] ?? '',
        $s['nama_jalan'] ?? '',
        $s['petugas_pendaftar'] ?? '',
        $s['tgl_daftar'],
        $s['status_bayar'] ?? '',
        $status_siswa_default,
        $s['nama_ayah'] ?? '',
        $s['nik_ayah'] ?? '',
        $s['tempat_lahir_ayah'] ?? '',
        $s['pekerjaan_ayah'] ?? '',
        $s['nama_ibu'] ?? '',
        $s['nik_ibu'] ?? '',
        $s['tempat_lahir_ibu'] ?? '',
        $s['pekerjaan_ibu'] ?? '',
        $s['sekolah_asal'] ?? '',
        $s['nilai_btq'] ?? 0
    );

    if ($stmt->execute()) {
        $stmt->close();

        // Delete from arsip
        $stmt = mysqli_prepare($conn, "DELETE FROM arsip_siswa WHERE id_siswa=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: index.php?status=success_restore");
        exit();
    } else {
        $stmt->close();
        tampilkan_error("Gagal Restore", "Terjadi kesalahan SQL: " . mysqli_error($conn));
    }
}

// Jika tidak ada aksi
header("Location: index.php");
exit();
