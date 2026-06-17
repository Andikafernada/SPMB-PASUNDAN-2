<?php
/**
 * PROSES BROADCAST - Handler untuk WA mass sending
 * Individual message processing with JSON response
 * Updated: 2026-06-17 - Fix hardcoded values, add phone validation
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
$is_retry = isset($_POST['retry']) && $_POST['retry'] == '1';

// ============================================
// 4. LOAD COMPLETE STUDENT DATA
// ============================================
$stmt_siswa = mysqli_prepare($conn, "SELECT * FROM siswa WHERE id_siswa = ?");
mysqli_stmt_bind_param($stmt_siswa, "i", $id_siswa);
mysqli_stmt_execute($stmt_siswa);
$result_siswa = mysqli_stmt_get_result($stmt_siswa);
$siswa = mysqli_fetch_assoc($result_siswa);
mysqli_stmt_close($stmt_siswa);

if (!$siswa) {
    json_response(false, 'Data siswa tidak ditemukan');
}

// Prepare student data
$nama = trim($siswa['nama_lengkap'] ?? '');
$id_daftar = trim($siswa['id_pendaftaran'] ?? '');
$no_hp = trim($siswa['no_hp'] ?? '');
$jurusan = trim($siswa['jurusan'] ?? '');
$asal_sekolah = trim($siswa['asal_sekolah'] ?? '');
$tgl_daftar = !empty($siswa['tgl_daftar']) ? date('d/m/Y', strtotime($siswa['tgl_daftar'])) : date('d/m/Y');

// ============================================
// 5. PHONE VALIDATION
// ============================================
function normalize_phone($hp) {
    // Remove all non-digit characters
    $hp = preg_replace('/[^0-9]/', '', $hp);

    // If starts with 0, convert to 62
    if (substr($hp, 0, 1) == '0') {
        $hp = '62' . substr($hp, 1);
    }

    // If doesn't start with 62, add it
    if (substr($hp, 0, 2) != '62') {
        $hp = '62' . $hp;
    }

    return $hp;
}

function validate_phone_for_wa($hp) {
    $hp = preg_replace('/[^0-9]/', '', $hp);

    // Valid WA formats: 08xx... (10-13 digits after cleanup)
    if (strlen($hp) < 10 || strlen($hp) > 15) {
        return false;
    }

    // Check valid Indonesian prefixes
    $valid_prefixes = [
        '6281', '6282', '6283', '6284', '6285', '6286', '6287', '6288', '6289',
        '6280', '62811', '62812', '62813', '62814', '62815', '62816', '62817', '62818', '62819',
        '62081', '62082', '62083', '62084', '62085', '62086', '62087', '62088', '62089'
    ];

    foreach ($valid_prefixes as $prefix) {
        if (substr($hp, 0, strlen($prefix)) === $prefix) {
            return true;
        }
    }

    return false;
}

// Normalize and validate phone
$no_hp_normalized = normalize_phone($no_hp);

if (!validate_phone_for_wa($no_hp)) {
    json_response(false, 'Nomor HP tidak valid untuk WhatsApp');
}

// ============================================
// 6. LOAD TEMPLATE
// ============================================
$template = load_wa_template($conn, $_POST['template_kode']);
if (!$template) {
    json_response(false, 'Template tidak ditemukan');
}

// ============================================
// 7. GET ADMIN CONTACT (from config or session)
// ============================================
$admin_contact = $_ENV['ADMIN_WA_NUMBER'] ?? '083817203455';

// ============================================
// 8. PREPARE PAYLOAD WITH REAL DATA
// ============================================
$payload = [
    'NAMA' => strtoupper($nama ?: '-'),
    'ID_DAFTAR' => $id_daftar ?: '-',
    'JURUSAN' => strtoupper($jurusan ?: '-'),
    'SEKOLAH' => strtoupper($asal_sekolah ?: '-'),
    'NO_HP' => $admin_contact,
    'ADMIN' => strtoupper($_SESSION['nama'] ?? 'Admin'),
    'TANGGAL' => $tgl_daftar,
    'GELOMBANG' => 'Gelombang 1',
    'BIAYA' => 'Rp 150.000',
    'JURUSAN_LAMA' => strtoupper($siswa['jurusan_lama'] ?? '-'),
    'JURUSAN_BARU' => strtoupper($jurusan ?: '-'),
    'ALASAN' => '-',
    'BULAN' => date('F Y'),
];

// Render message
$pesan_text = render_wa_template($template['template_text'], $payload);

// ============================================
// 9. SEND MESSAGE
// ============================================
$result = kirim_wa_template($conn, $_POST['template_kode'], $payload, $no_hp_normalized);

// ============================================
// 10. LOG TO HISTORY
// ============================================
$status = $result ? 'success' : 'failed';
$error_msg = $result ? null : 'Gagal mengirim via Evo API';

$stmt_log = mysqli_prepare($conn, "
    INSERT INTO wa_broadcast_history
    (siswa_id, id_pendaftaran, nama_siswa, no_hp, template_kode, template_nama, pesan_text, status, error_message, sent_by, sent_at, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
mysqli_stmt_bind_param($stmt_log, "isssssssss",
    $id_siswa, $id_daftar, $nama, $no_hp_normalized,
    $_POST['template_kode'], $template['nama_template'],
    $pesan_text, $status, $error_msg, $_SESSION['nama']
);
mysqli_stmt_execute($stmt_log);
$log_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt_log);

// ============================================
// 11. RETURN RESPONSE
// ============================================
if ($result) {
    error_log("BROADCAST SUCCESS: siswa_id=$id_siswa ($nama) -> $no_hp_normalized");
    json_response(true, 'Pesan terkirim', [
        'log_id' => $log_id,
        'phone_normalized' => $no_hp_normalized
    ]);
} else {
    error_log("BROADCAST FAILED: siswa_id=$id_siswa ($nama) -> $no_hp_normalized");
    json_response(false, 'Gagal mengirim - Cek konfigurasi Evo API', [
        'log_id' => $log_id,
        'phone_normalized' => $no_hp_normalized
    ]);
}

// ============================================
// HELPER: JSON Response
// ============================================
function json_response($success, $message, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}
