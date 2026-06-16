<?php
/**
 * RESET TPA - Reset Nilai TPA Siswa
 * SMK Pasundan 2 Bandung
 */
session_start(); // FIX 1: Wajib ada untuk membaca dan menulis session
include '../../config.php';

// FIX 2: Definisikan daftar jurusan agar fungsi foreach tidak memicu Fatal Error (Blank Putih)
$jurusan_list = [
    'TPM' => 'Teknik Pemesinan',
    'TKR' => 'Teknik Kendaraan Ringan',
    'TSM' => 'Teknik Sepeda Motor',
    'TKJ' => 'Teknik Komputer & Jaringan',
    'TAV' => 'Teknik Audio Video'
];

// Proteksi session
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database', 'superuser'])) {
    header("Location: ../../login.php");
    exit();
}

// Handle Reset Single TPA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_single'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $id_siswa = (int)($_POST['id_siswa'] ?? 0);
        if ($id_siswa > 0) {
            $q = mysqli_query($conn, "SELECT nama_lengkap FROM siswa WHERE id_siswa = $id_siswa");
            $nama = mysqli_fetch_assoc($q)['nama_lengkap'] ?? 'Unknown';

            // Reset TPA siswa
            mysqli_query($conn, "UPDATE siswa SET
                tpa_selesai = 0, tpa_tanggal = NULL, tpa_nilai_total = NULL,
                tpa_benar_verbal = NULL, tpa_benar_numerik = NULL, tpa_benar_logika = NULL
                WHERE id_siswa = $id_siswa");

            // Hapus jawaban TPA
            mysqli_query($conn, "DELETE FROM tpa_jawaban WHERE id_siswa = $id_siswa");

            // Log aktivitas
            $admin_id = $_SESSION['id_user'] ?? 0;
            $log_exists = mysqli_query($conn, "SHOW TABLES LIKE 'audit_log'");
            if (mysqli_num_rows($log_exists) > 0) {
                mysqli_query($conn, "INSERT INTO audit_log (user_id, action, details, created_at)
                    VALUES ($admin_id, 'RESET_TPA', 'Reset TPA siswa: $nama (ID: $id_siswa)', NOW())");
            }

            $_SESSION['success_message'] = "TPA siswa <strong>$nama</strong> berhasil di-reset!";
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
}

// Handle Bulk Reset by Jurusan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_reset_jurusan'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan'] ?? '');

        $count = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE tpa_selesai = 1 AND jurusan = '$jurusan'"));

        mysqli_query($conn, "UPDATE siswa SET
            tpa_selesai = 0, tpa_tanggal = NULL, tpa_nilai_total = NULL,
            tpa_benar_verbal = NULL, tpa_benar_numerik = NULL, tpa_benar_logika = NULL
            WHERE tpa_selesai = 1 AND jurusan = '$jurusan'");

        mysqli_query($conn, "DELETE tj FROM tpa_jawaban tj
            INNER JOIN siswa s ON tj.id_siswa = s.id_siswa
            WHERE s.jurusan = '$jurusan'");

        $admin_id = $_SESSION['id_user'] ?? 0;
        $log_exists = mysqli_query($conn, "SHOW TABLES LIKE 'audit_log'");
        if (mysqli_num_rows($log_exists) > 0) {
            mysqli_query($conn, "INSERT INTO audit_log (user_id, action, details, created_at)
                VALUES ($admin_id, 'RESET_TPA_BULK', 'Reset TPA $count siswa jurusan $jurusan', NOW())");
        }

        $_SESSION['success_message'] = "<strong>$count siswa</strong> jurusan <strong>$jurusan</strong> berhasil di-reset!";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
}

// Handle Bulk Reset Semua
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_reset_all'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $count = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE tpa_selesai = 1"));

        mysqli_query($conn, "UPDATE siswa SET
            tpa_selesai = 0, tpa_tanggal = NULL, tpa_nilai_total = NULL,
            tpa_benar_verbal = NULL, tpa_benar_numerik = NULL, tpa_benar_logika = NULL
            WHERE tpa_selesai = 1");

        mysqli_query($conn, "DELETE FROM tpa_jawaban");

        $admin_id = $_SESSION['id_user'] ?? 0;
        $log_exists = mysqli_query($conn, "SHOW TABLES LIKE 'audit_log'");
        if (mysqli_num_rows($log_exists) > 0) {
            mysqli_query($conn, "INSERT INTO audit_log (user_id, action, details, created_at)
                VALUES ($admin_id, 'RESET_TPA_ALL', 'Reset semua TPA ($count siswa)', NOW())");
        }

        $_SESSION['success_message'] = "<strong>Semua TPA ($count siswa)</strong> berhasil di-reset!";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
}

// Tampilkan success message jika ada
$success_msg = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Ambil filter
$filter_jurusan = isset($_GET['jurusan']) ? mysqli_real_escape_string($conn, $_GET['jurusan']) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query
$where = ["id_pendaftaran IS NOT NULL AND id_pendaftaran != ''"];
if ($filter_jurusan) $where[] = "jurusan = '$filter_jurusan'";
if ($filter_status === 'sudah') $where[] = "tpa_selesai = 1";
if ($filter_status === 'belum') $where[] = "tpa_selesai != 1 OR tpa_selesai IS NULL";
if ($search) $where[] = "(nama_lengkap LIKE '%$search%' OR id_pendaftaran LIKE '%$search%')";
$where_clause = implode(' AND ', $where);

$query = "SELECT id_siswa, id_pendaftaran, nama_lengkap, jurusan, kelas, asal_sekolah,
           tpa_selesai, tpa_tanggal, tpa_nilai_total, tpa_benar_verbal, tpa_benar_numerik, tpa_benar_logika,
           tpa_jumlah_soal_verbal, tpa_jumlah_soal_numerik, tpa_jumlah_soal_logika
           FROM siswa
           WHERE $where_clause
           ORDER BY tpa_tanggal DESC, nama_lengkap ASC";
$results = mysqli_query($conn, $query);

// Statistik
$stats_query = mysqli_query($conn, "SELECT
    COUNT(CASE WHEN tpa_selesai = 1 THEN 1 END) as sudah_tpa,
    COUNT(CASE WHEN (tpa_selesai != 1 OR tpa_selesai IS NULL) THEN 1 END) as belum_tpa,
    AVG(CASE WHEN tpa_selesai = 1 THEN tpa_nilai_total END) as rata_nilai
    FROM siswa WHERE id_pendaftaran IS NOT NULL AND id_pendaftaran != ''");
$stats = mysqli_fetch_assoc($stats_query);

// Statistik per jurusan
$stats_by_jur = [];
foreach ($jurusan_list as $kode => $nama) {
    $q = mysqli_query($conn, "SELECT
        COUNT(CASE WHEN tpa_selesai = 1 THEN 1 END) as sudah,
        COUNT(CASE WHEN tpa_selesai != 1 OR tpa_selesai IS NULL THEN 1 END) as belum
        FROM siswa WHERE id_pendaftaran IS NOT NULL AND id_pendaftaran != '' AND jurusan = '$kode'");
    $stats_by_jur[$kode] = mysqli_fetch_assoc($q);
}

$csrf_token = generate_csrf_token();
?>
