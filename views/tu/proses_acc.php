<?php
/**
 * PROSES KONFIRMASI TU - SMK PASUNDAN 2
 * Developer: Andika Fernanda
 * Versi: 2.3 - Anti-Duplicate ID & WA Status Tracker
 */
session_start();
include '../../config.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'tu' && $_SESSION['role'] != 'superuser')) {
    show_error_page("Akses Ditolak", "Halaman ini hanya untuk Tim TU.");
}

$id_siswa = mysqli_real_escape_string($conn, $_GET['id'] ?? '');
if (empty($id_siswa)) {
    header("Location: index.php?status=error&msg=id_kosong");
    exit();
}

// 1. GENERATE ID PENDAFTARAN (ANTI-DUPLICATE SYSTEM)
$next_num = 1;
// Cari angka paling besar yang ada saat ini
$query_max = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(id_pendaftaran, 8) AS UNSIGNED)) as max_num FROM siswa WHERE id_pendaftaran LIKE 'SPMB26-%'");
if ($row_max = mysqli_fetch_assoc($query_max)) {
    $next_num = $row_max['max_num'] + 1;
}

// Looping proteksi: Pastikan ID benar-benar belum dipakai di database
do {
    $id_pendaftaran = "SPMB26-" . str_pad($next_num, 3, "0", STR_PAD_LEFT);
    $cek_duplikat = mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE id_pendaftaran = '$id_pendaftaran'");
    
    if (mysqli_num_rows($cek_duplikat) > 0) {
        $next_num++; // Jika sudah ada, tambah 1 dan putar ulang
    } else {
        break; // Jika kosong/belum dipakai, keluar dari loop (ID Aman)
    }
} while (true);

// 2. AMBIL DATA SISWA
$res_siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = '$id_siswa'");
$d = mysqli_fetch_assoc($res_siswa);
if (!$d) show_error_page("Data Tidak Ditemukan", "Data calon siswa tidak ditemukan.");

// 3. UPDATE DATABASE DENGAN ID YANG SUDAH DIJAMIN UNIK
$update = mysqli_query($conn, "UPDATE siswa SET
    id_pendaftaran = '$id_pendaftaran',
    status_bayar   = 'LUNAS'
    WHERE id_siswa = '$id_siswa'");
if (!$update) show_error_page("Gagal Update Database", mysqli_error($conn));


// 4. FORMAT NOMOR HP KE STANDAR WA
$no_hp = preg_replace('/[^0-9]/', '', $d['no_hp']);
if (substr($no_hp, 0, 1) == '0') {
    $no_hp = '62' . substr($no_hp, 1);
}

// 5. TRIGGER WEBHOOK N8N (from config.php / .env)
$webhook_url = N8N_WEBHOOK_URL;

$payload = [
    'wa'      => $no_hp,                                              
    'nama'    => strtoupper($d['nama_lengkap']),                      
    'id_reg'  => $id_pendaftaran,                                     
    'sekolah' => strtoupper($d['asal_sekolah'] ?? $d['sekolah_asal'] ?? '-'), 
    'jurusan' => strtoupper($d['jurusan']),                           
    'petugas' => $_SESSION['nama'] ?? 'TU',
    'tgl_acc' => date('Y-m-d H:i:s'),
];

$ch = curl_init($webhook_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 8, // Maksimal nunggu N8N selama 8 detik
    CURLOPT_CONNECTTIMEOUT => 4,
]);
$n8n_response = curl_exec($ch);
$http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Ambil status kode HTTP dari N8N
curl_close($ch);

// 6. DETEKSI STATUS PENGIRIMAN WA
$wa_status = 'failed';
if ($http_code == 200) {
    // Jika n8n merespons dengan kode 200 (OK), berarti data sukses masuk webhook
    $wa_status = 'sent';
}

// 7. REDIRECT KEMBALI KE INDEX (Bawa parameter wa_status)
header("Location: index.php?id_reg=$id_pendaftaran&status=success&wa=$wa_status");
exit();
?>