<?php
/**
 * PROSES CRUD - v4.0 SECURE
 * Handle semua kolom: identitas, alamat lengkap, data ortu, BTQ
 * Updated 2026-06-16: Add CSRF protection + Prepared Statements
 */
session_start();
include '../../config.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'database') {
    show_error_page("Akses Ditolak", "Halaman ini hanya untuk Tim Database.");
}

// ============================================
// 2. CSRF PROTECTION
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'])) {
    header("Location: index.php?error=invalid_request");
    exit();
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: index.php?error=csrf");
    exit();
}

// ============================================
// 3. VALIDATE INPUT
// ============================================
if (!isset($_POST['id_siswa']) || empty($_POST['id_siswa'])) {
    header("Location: index.php?error=missing_id");
    exit();
}

$id_siswa = (int)$_POST['id_siswa'];
if ($id_siswa <= 0) {
    show_error_page("Error", "ID Siswa tidak valid.");
}

// ============================================
// 4. HELPER FUNCTIONS (Sanitization)
// ============================================
function clean_input($data) {
    return trim(strip_tags($data ?? ''));
}

function clean_upper($data) {
    return strtoupper(clean_input($data));
}

// ============================================
// 5. COLLECT & SANITIZE ALL INPUT
// ============================================

// ===== IDENTITAS =====
$nama_lengkap = clean_upper($_POST['nama_lengkap'] ?? '');
$jenis_kelamin = clean_input($_POST['jenis_kelamin'] ?? '');
$tempat_lahir = clean_upper($_POST['tempat_lahir'] ?? '');
$tanggal_lahir = clean_input($_POST['tanggal_lahir'] ?? '');
$agama = clean_input($_POST['agama'] ?? '');
$no_hp = clean_input($_POST['no_hp'] ?? '');
$nisn = clean_input($_POST['nisn'] ?? '');
$nik = clean_input($_POST['nik'] ?? '');
$sekolah_asal = clean_upper($_POST['sekolah_asal'] ?? '');

// ===== ALAMAT =====
$nama_jalan = clean_upper($_POST['nama_jalan'] ?? '');
$rt = clean_input($_POST['rt'] ?? '');
$rw = clean_input($_POST['rw'] ?? '');
$kelurahan = clean_upper($_POST['kelurahan'] ?? '');
$kecamatan = clean_upper($_POST['kecamatan'] ?? '');
$kota = clean_upper($_POST['kota'] ?? '');
$provinsi = clean_upper($_POST['provinsi'] ?? '');

// ===== DATA AYAH =====
$nama_ayah = clean_upper($_POST['nama_ayah'] ?? '');
$nik_ayah = clean_input($_POST['nik_ayah'] ?? '');
$tempat_lahir_ayah = clean_upper($_POST['tempat_lahir_ayah'] ?? '');
$tgl_lahir_ayah = clean_input($_POST['tgl_lahir_ayah'] ?? '');
$pekerjaan_ayah = clean_input($_POST['pekerjaan_ayah'] ?? '');

// ===== DATA IBU =====
$nama_ibu = clean_upper($_POST['nama_ibu'] ?? '');
$nik_ibu = clean_input($_POST['nik_ibu'] ?? '');
$tempat_lahir_ibu = clean_upper($_POST['tempat_lahir_ibu'] ?? '');
$tgl_lahir_ibu = clean_input($_POST['tgl_lahir_ibu'] ?? '');
$pekerjaan_ibu = clean_input($_POST['pekerjaan_ibu'] ?? '');

// ===== BTQ & KELAS =====
$nilai_btq = max(0, min(100, (int)($_POST['nilai_btq'] ?? 0)));
$request_kelas = clean_upper($_POST['request_kelas'] ?? '');

// ============================================
// 6. ENSURE COLUMNS EXIST (Safe ALTER TABLE)
// ============================================
$columns_to_add = [
    'jenis_kelamin' => "VARCHAR(20)",
    'tempat_lahir' => "VARCHAR(100)",
    'tanggal_lahir' => "DATE",
    'agama' => "VARCHAR(30)",
    'nisn' => "VARCHAR(20)",
    'nik' => "VARCHAR(20)",
    'sekolah_asal' => "VARCHAR(200)",
    'nama_jalan' => "VARCHAR(200)",
    'kota' => "VARCHAR(100)",
    'provinsi' => "VARCHAR(100)",
    'nama_ayah' => "VARCHAR(100)",
    'nik_ayah' => "VARCHAR(20)",
    'tempat_lahir_ayah' => "VARCHAR(100)",
    'tgl_lahir_ayah' => "DATE",
    'pekerjaan_ayah' => "VARCHAR(100)",
    'nama_ibu' => "VARCHAR(100)",
    'nik_ibu' => "VARCHAR(20)",
    'tempat_lahir_ibu' => "VARCHAR(100)",
    'tgl_lahir_ibu' => "DATE",
    'pekerjaan_ibu' => "VARCHAR(100)",
    'nilai_btq' => "INT DEFAULT 0",
    'request_kelas' => "VARCHAR(150)",
];

// Check and add missing columns (use try-catch to avoid errors)
$result = mysqli_query($conn, "DESCRIBE siswa");
if ($result) {
    $existing_columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $existing_columns[] = $row['Field'];
    }

    foreach ($columns_to_add as $col => $type) {
        if (!in_array($col, $existing_columns)) {
            mysqli_query($conn, "ALTER TABLE siswa ADD COLUMN $col $type");
        }
    }
}

// ============================================
// 7. PREPARE AND EXECUTE UPDATE (SECURE)
// ============================================
$sql = "UPDATE siswa SET
    nama_lengkap = ?,
    jenis_kelamin = ?,
    tempat_lahir = ?,
    tanggal_lahir = ?,
    agama = ?,
    no_hp = ?,
    nisn = ?,
    nik = ?,
    sekolah_asal = ?,
    nama_jalan = ?,
    alamat = ?,
    rt = ?,
    rw = ?,
    kelurahan = ?,
    kecamatan = ?,
    kota = ?,
    provinsi = ?,
    nama_ayah = ?,
    nik_ayah = ?,
    tempat_lahir_ayah = ?,
    tgl_lahir_ayah = ?,
    pekerjaan_ayah = ?,
    nama_ibu = ?,
    nik_ibu = ?,
    tempat_lahir_ibu = ?,
    tgl_lahir_ibu = ?,
    pekerjaan_ibu = ?,
    nilai_btq = ?,
    request_kelas = ?
WHERE id_siswa = ?";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    // Bind 32 parameters + 1 WHERE clause = 33 total
    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssssssssssssssssiiisssi",
        $nama_lengkap,
        $jenis_kelamin,
        $tempat_lahir,
        $tanggal_lahir ?: null,
        $agama,
        $no_hp,
        $nisn,
        $nik,
        $sekolah_asal,
        $nama_jalan,
        $nama_jalan,
        $rt,
        $rw,
        $kelurahan,
        $kecamatan,
        $kota,
        $provinsi,
        $nama_ayah,
        $nik_ayah,
        $tempat_lahir_ayah,
        $tgl_lahir_ayah ?: null,
        $pekerjaan_ayah,
        $nama_ibu,
        $nik_ibu,
        $tempat_lahir_ibu,
        $tgl_lahir_ibu ?: null,
        $pekerjaan_ibu,
        $nilai_btq,
        $request_kelas,
        $id_siswa
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: index.php?status=success_edit");
        exit();
    } else {
        mysqli_stmt_close($stmt);
        show_error_page("Error Database", mysqli_error($conn));
    }
} else {
    show_error_page("Error Prepare", mysqli_error($conn));
}
