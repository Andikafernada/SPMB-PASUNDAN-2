<?php
/**
 * PROSES CRUD - v3.0 FINAL
 * Handle semua kolom: identitas, alamat lengkap, data ortu, BTQ
 */
session_start();
include '../../config.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'database') {
    show_error_page("Akses Ditolak", "Halaman ini hanya untuk Tim Database.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_siswa'])) {

    $id = mysqli_real_escape_string($conn, $_POST['id_siswa']);

    // Helper: escape + uppercase
    function esc($conn, $val, $upper = false) {
        $val = trim($val ?? '');
        if ($upper) $val = strtoupper($val);
        return mysqli_real_escape_string($conn, $val);
    }
    function escDate($conn, $val) {
        $val = trim($val ?? '');
        return (!empty($val)) ? "'" . mysqli_real_escape_string($conn, $val) . "'" : "NULL";
    }

    // ===== IDENTITAS =====
    $nama        = esc($conn, $_POST['nama_lengkap'] ?? '', true);
    $jk          = esc($conn, $_POST['jenis_kelamin'] ?? '');
    $tempat_lhr  = esc($conn, $_POST['tempat_lahir'] ?? '', true);
    $tgl_lhr     = escDate($conn, $_POST['tanggal_lahir'] ?? '');
    $agama       = esc($conn, $_POST['agama'] ?? '');
    $no_hp       = esc($conn, $_POST['no_hp'] ?? '');
    $nisn        = esc($conn, $_POST['nisn'] ?? '');
    $nik         = esc($conn, $_POST['nik'] ?? '');
    $sekolah_asal = esc($conn, $_POST['sekolah_asal'] ?? '', true);

    // ===== ALAMAT =====
    $nama_jalan  = esc($conn, $_POST['nama_jalan'] ?? '', true);
    $rt          = esc($conn, $_POST['rt'] ?? '');
    $rw          = esc($conn, $_POST['rw'] ?? '');
    $kelurahan   = esc($conn, $_POST['kelurahan'] ?? '', true);
    $kecamatan   = esc($conn, $_POST['kecamatan'] ?? '', true);
    $kota        = esc($conn, $_POST['kota'] ?? '', true);
    $provinsi    = esc($conn, $_POST['provinsi'] ?? '', true);

    // ===== DATA AYAH =====
    $nama_ayah         = esc($conn, $_POST['nama_ayah'] ?? '', true);
    $nik_ayah          = esc($conn, $_POST['nik_ayah'] ?? '');
    $tempat_lahir_ayah = esc($conn, $_POST['tempat_lahir_ayah'] ?? '', true);
    $tgl_lahir_ayah    = escDate($conn, $_POST['tgl_lahir_ayah'] ?? '');
    $pekerjaan_ayah    = esc($conn, $_POST['pekerjaan_ayah'] ?? '');

    // ===== DATA IBU =====
    $nama_ibu         = esc($conn, $_POST['nama_ibu'] ?? '', true);
    $nik_ibu          = esc($conn, $_POST['nik_ibu'] ?? '');
    $tempat_lahir_ibu = esc($conn, $_POST['tempat_lahir_ibu'] ?? '', true);
    $tgl_lahir_ibu    = escDate($conn, $_POST['tgl_lahir_ibu'] ?? '');
    $pekerjaan_ibu    = esc($conn, $_POST['pekerjaan_ibu'] ?? '');

    // ===== BTQ & KELAS =====
    $btq         = intval($_POST['nilai_btq'] ?? 0);
    $req_kelas   = esc($conn, $_POST['request_kelas'] ?? '', true);

    // Pastikan semua kolom ada (safe guard)
    $alters = [
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS jenis_kelamin VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tempat_lahir VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tanggal_lahir DATE DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS agama VARCHAR(30) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nisn VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nik VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS sekolah_asal VARCHAR(200) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nama_jalan VARCHAR(200) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS kota VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS provinsi VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nama_ayah VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nik_ayah VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ayah VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ayah DATE DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS pekerjaan_ayah VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nama_ibu VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nik_ibu VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ibu VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ibu DATE DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS pekerjaan_ibu VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nilai_btq INT DEFAULT 0",
        "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS request_kelas VARCHAR(150) DEFAULT NULL",
    ];
    foreach ($alters as $alt) { mysqli_query($conn, $alt); }

    $sql = "UPDATE siswa SET
        nama_lengkap       = '$nama',
        jenis_kelamin      = '$jk',
        tempat_lahir       = '$tempat_lhr',
        tanggal_lahir      = $tgl_lhr,
        agama              = '$agama',
        no_hp              = '$no_hp',
        nisn               = '$nisn',
        nik                = '$nik',
        sekolah_asal       = '$sekolah_asal',
        nama_jalan         = '$nama_jalan',
        alamat             = '$nama_jalan',
        rt                 = '$rt',
        rw                 = '$rw',
        kelurahan          = '$kelurahan',
        kecamatan          = '$kecamatan',
        kota               = '$kota',
        provinsi           = '$provinsi',
        nama_ayah          = '$nama_ayah',
        nik_ayah           = '$nik_ayah',
        tempat_lahir_ayah  = '$tempat_lahir_ayah',
        tgl_lahir_ayah     = $tgl_lahir_ayah,
        pekerjaan_ayah     = '$pekerjaan_ayah',
        nama_ibu           = '$nama_ibu',
        nik_ibu            = '$nik_ibu',
        tempat_lahir_ibu   = '$tempat_lahir_ibu',
        tgl_lahir_ibu      = $tgl_lahir_ibu,
        pekerjaan_ibu      = '$pekerjaan_ibu',
        nilai_btq          = $btq,
        request_kelas      = '$req_kelas'
        WHERE id_siswa     = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: index.php?status=success_edit");
        exit();
    } else {
        show_error_page("Error Database", mysqli_error($conn));
    }

} else {
    header("Location: index.php");
    exit();
}
