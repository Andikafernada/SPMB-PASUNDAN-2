<?php
/**
 * API: Upload Bukti Bayar
 * Endpoint untuk upload bukti pembayaran
 */

header('Content-Type: application/json');
include '../../config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get kode billing
$kode_billing = mysqli_real_escape_string($conn, $_POST['kode_billing'] ?? '');

if (empty($kode_billing)) {
    echo json_encode(['success' => false, 'error' => 'Kode billing diperlukan']);
    exit;
}

// Check if bukti_bayar directory exists
$upload_dir = '../../uploads/bukti_bayar/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Allowed types
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
$max_size = 2 * 1024 * 1024; // 2MB

// Check if file is uploaded
if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'File terlalu besar (max 2MB)',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar',
        UPLOAD_ERR_PARTIAL => 'File tidak lengkap terupload',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file diupload',
    ];
    $error = $error_messages[$_FILES['bukti']['error']] ?? 'Upload gagal';
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $_FILES['bukti']['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Tipe file tidak diizinkan. Gunakan JPG, PNG, atau PDF']);
    exit;
}

// Validate file size
if ($_FILES['bukti']['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'File terlalu besar (max 2MB)']);
    exit;
}

// Generate filename
$extension = pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION);
$new_filename = $kode_billing . '_' . time() . '.' . $extension;
$target_path = $upload_dir . $new_filename;

// Move file
if (move_uploaded_file($_FILES['bukti']['tmp_name'], $target_path)) {
    // Update database
    $sql = "UPDATE siswa SET bukti_bayar = ?, tgl_bayar = NOW() WHERE kode_billing = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $new_filename, $kode_billing);
    $stmt->execute();

    // Insert to bukti_bayar table
    mysqli_query($conn, "INSERT INTO bukti_bayar (kode_billing, nama_file, ukuran_file, tipe_file)
                         VALUES ('$kode_billing', '$new_filename', " . $_FILES['bukti']['size'] . ", '$mime_type')");

    echo json_encode([
        'success' => true,
        'message' => 'Bukti bayar berhasil diupload',
        'filename' => $new_filename
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal menyimpan file']);
}