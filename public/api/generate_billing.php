<?php
/**
 * API: Generate Billing Code
 * Endpoint untuk generate kode billing baru
 */

header('Content-Type: application/json');
include '../../config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Get data
$nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
$no_hp = mysqli_real_escape_string($conn, $_POST['no_hp'] ?? '');
$jurusan = mysqli_real_escape_string($conn, $_POST['jurusan'] ?? '');

// Validate
if (empty($nama) || empty($no_hp) || empty($jurusan)) {
    echo json_encode(['success' => false, 'error' => 'Data tidak lengkap']);
    exit;
}

// Get active gelombang
$gelombang = get_gelombang_aktif($conn);
$gelombang_id = $gelombang ? $gelombang['id'] : 1;
$biaya = $gelombang ? $gelombang['biaya'] : 150000;

// Generate kode billing
$id_siswa = 0;
$kode_billing = 'PPDB26-' . strtoupper(substr(md5(uniqid()), 0, 6));

// Insert to database
$sql = "INSERT INTO siswa (
            nama_lengkap, no_hp, jurusan, sumber_data, status_pendaftaran,
            gelombang, kode_billing, tgl_daftar, status_siswa, status_bayar
        ) VALUES (?, ?, ?, 'public', 'pending', ?, ?, NOW(), 'BELUM DAFTAR ULANG', 'BELUM')";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssi", $nama, $no_hp, $jurusan, $gelombang_id);

if (mysqli_stmt_execute($stmt)) {
    $id_siswa = mysqli_insert_id($conn);

    // Update kode_billing with ID
    $kode_billing = 'PPDB26-' . str_pad($id_siswa, 5, '0', STR_PAD_LEFT);
    mysqli_query($conn, "UPDATE siswa SET kode_billing='$kode_billing' WHERE id_siswa=$id_siswa");

    // Insert notification
    mysqli_query($conn, "INSERT INTO wa_notifications (kode_billing, no_hp, jenis, pesan, status)
                         VALUES ('$kode_billing', '$no_hp', 'billing', 'Pendaftaran baru: $nama - $kode_billing', 'pending')");

    echo json_encode([
        'success' => true,
        'kode_billing' => $kode_billing,
        'biaya' => $biaya,
        'id_siswa' => $id_siswa,
        'no_hp' => $no_hp
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal menyimpan data']);
}