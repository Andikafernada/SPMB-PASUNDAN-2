<?php
/**
 * PROSES BROADCAST - Handler untuk WA mass sending
 * Individual message processing with JSON response
 * Updated: 2026-06-17 - Add history logging
 */
session_start();
include '../../config.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database', 'superuser', 'superuser1'])) {
    json_response(false, 'Akses ditolak');
}

// ============================================
// 2. CSRF PROTECTION
// ============================================
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    json_response(false, 'Token keamanan tidak valid');
}

// ============================================
// 3. VALIDATE INPUT
// ============================================
if (!isset($_POST['siswa_id']) || empty($_POST['siswa_id'])) {
    json_response(false, 'ID Siswa tidak ditemukan');
}

if (!isset($_POST['template_kode']) || empty($_POST['template_kode'])) {
    json_response(false, 'Template tidak dipilih');
}

$id_siswa = (int)$_POST['siswa_id'];
$nama = trim($_POST['siswa_nama'] ?? '');
$id_daftar = trim($_POST['siswa_id_daftar'] ?? '');
$no_hp = trim($_POST['siswa_hp'] ?? '');
$jurusan = trim($_POST['siswa_jur'] ?? '');

// Validate phone number
if (empty($no_hp) || $no_hp == '-' || strlen($no_hp) < 10) {
    json_response(false, 'Nomor HP tidak valid');
}

// ============================================
// 4. LOAD TEMPLATE
// ============================================
$template = load_wa_template($conn, $_POST['template_kode']);
if (!$template) {
    json_response(false, 'Template tidak ditemukan');
}

// ============================================
// 5. PREPARE PAYLOAD
// ============================================
$payload = [
    'NAMA' => $nama ?: '-',
    'ID_DAFTAR' => $id_daftar ?: '-',
    'JURUSAN' => $jurusan ?: '-',
    'SEKOLAH' => '-',
    'NO_HP' => '083817203455', // Admin phone for contact
    'ADMIN' => $_SESSION['nama'] ?? 'Admin',
    'TANGGAL' => date('d/m/Y'),
    'GELOMBANG' => 'Gelombang 1',
    'BIAYA' => '150.000',
    'JURUSAN_LAMA' => '-',
    'JURUSAN_BARU' => '-',
    'ALASAN' => '-',
];

// Render message
$pesan_text = render_wa_template($template['template_text'], $payload);

// ============================================
// 6. SEND MESSAGE
// ============================================
$result = kirim_wa_template($conn, $_POST['template_kode'], $payload, $no_hp);

// ============================================
// 7. LOG TO HISTORY
// ============================================
$status = $result ? 'success' : 'failed';
$error_msg = $result ? null : 'Evo API not configured or send failed';

$stmt = mysqli_prepare($conn, "
    INSERT INTO wa_broadcast_history
    (siswa_id, id_pendaftaran, nama_siswa, no_hp, template_kode, template_nama, pesan_text, status, error_message, sent_by, sent_at, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
mysqli_stmt_bind_param($stmt, "isssssssss",
    $id_siswa, $id_daftar, $nama, $no_hp,
    $_POST['template_kode'], $template['nama_template'],
    $pesan_text, $status, $error_msg, $_SESSION['nama']
);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// ============================================
// 8. RETURN RESPONSE
// ============================================
if ($result) {
    error_log("BROADCAST SUCCESS: siswa_id=$id_siswa ($nama) -> $no_hp");
    json_response(true, 'Pesan terkirim');
} else {
    error_log("BROADCAST FAILED: siswa_id=$id_siswa ($nama) -> $no_hp - Evo API not configured");
    json_response(false, 'Gagal mengirim - Cek konfigurasi Evo API');
}

// ============================================
// HELPER: JSON Response
// ============================================
function json_response($success, $message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}
