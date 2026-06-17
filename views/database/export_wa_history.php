<?php
/**
 * EXPORT WA BROADCAST HISTORY - Export to Excel
 */
session_start();
include '../../config.php';

// Check session
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database', 'superuser', 'superuser1'])) {
    header("Location: index.php");
    exit();
}

// Get filter parameters
$from_date = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to_date = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$template_filter = isset($_GET['template']) ? $_GET['template'] : '';

$where = "WHERE DATE(created_at) BETWEEN '$from_date' AND '$to_date'";
if ($status_filter) {
    $where .= " AND status = '$status_filter'";
}
if ($template_filter) {
    $where .= " AND template_kode = '$template_filter'";
}

$query = "SELECT * FROM wa_broadcast_history $where ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Set headers for Excel download
$filename = 'WA_Broadcast_History_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output
$output = fopen('php://output', 'w');

// BOM for Excel UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, [
    'No',
    'ID Log',
    'ID Pendaftaran',
    'Nama Siswa',
    'No. HP',
    'Template',
    'Pesan Text',
    'Status',
    'Error Message',
    'Dikirim Oleh',
    'Tanggal Kirim',
    'Created At'
]);

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $no++,
        $row['id'],
        $row['id_pendaftaran'] ?? '-',
        $row['nama_siswa'] ?? '-',
        $row['no_hp'] ?? '-',
        $row['template_nama'] ?? $row['template_kode'],
        substr($row['pesan_text'] ?? '', 0, 200) . (strlen($row['pesan_text'] ?? '') > 200 ? '...' : ''),
        strtoupper($row['status'] ?? '-'),
        $row['error_message'] ?? '-',
        $row['sent_by'] ?? '-',
        $row['sent_at'] ?? '-',
        $row['created_at'] ?? '-'
    ]);
}

fclose($output);
exit();
