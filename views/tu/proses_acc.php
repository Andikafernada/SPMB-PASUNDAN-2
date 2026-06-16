<?php
/**
 * PROSES KONFIRMASI TU - SMK PASUNDAN 2
 * WhatsApp Template System Integration v3.0
 * FIX: Pencegahan Race Condition dengan Transaction & Row Locking
 */
session_start();
include '../../config.php';

// Proteksi Akses
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'tu' && $_SESSION['role'] != 'superuser')) {
    show_error_page("Akses Ditolak", "Halaman ini hanya untuk Tim TU.");
}

$id_siswa = (int)($_GET['id'] ?? 0);
if (empty($id_siswa)) {
    header("Location: index.php?status=error&msg=id_kosong");
    exit();
}

// 1. MULAI TRANSACTION DATABASE
// Transaksi ini akan mengunci tabel/baris terkait sampai proses update selesai (Anti-Duplicate / Anti-Race Condition)
mysqli_begin_transaction($conn);

try {
    // 2. CEK STATUS SISWA DAN LOCK BARIS INI
    $query_siswa = "SELECT * FROM siswa WHERE id_siswa = ? FOR UPDATE";
    $stmt_siswa = mysqli_prepare($conn, $query_siswa);
    mysqli_stmt_bind_param($stmt_siswa, "i", $id_siswa);
    mysqli_stmt_execute($stmt_siswa);
    $result_siswa = mysqli_stmt_get_result($stmt_siswa);
    $d = mysqli_fetch_assoc($result_siswa);
    mysqli_stmt_close($stmt_siswa);

    if (!$d) {
        throw new Exception("Data calon siswa tidak ditemukan.");
    }

    // Jika siswa sudah LUNAS dan punya ID, batalkan proses
    if ($d['status_bayar'] === 'LUNAS' && !empty($d['id_pendaftaran'])) {
        mysqli_rollback($conn);
        $id_pendaftaran = $d['id_pendaftaran'];
        header("Location: index.php?id_reg=$id_pendaftaran&status=success&wa=already_sent");
        exit();
    }

    // 3. GENERATE ID PENDAFTARAN (MENGUNCI NILAI MAX)
    // Query ini dilindungi di dalam lingkup Transaction
    $year = date('y'); // Misal: 26
    $prefix = "SPMB{$year}-";
    $next_num = 1;

    $query_max = "SELECT MAX(CAST(SUBSTRING(id_pendaftaran, 8) AS UNSIGNED)) as max_num FROM siswa WHERE id_pendaftaran LIKE '{$prefix}%' FOR UPDATE";
    $result_max = mysqli_query($conn, $query_max);
    
    if ($row_max = mysqli_fetch_assoc($result_max)) {
        $next_num = ($row_max['max_num'] ?? 0) + 1;
    }

    $id_pendaftaran = $prefix . str_pad($next_num, 3, "0", STR_PAD_LEFT);

    // 4. UPDATE STATUS & ID SISWA
    $query_update = "UPDATE siswa SET id_pendaftaran = ?, status_bayar = 'LUNAS' WHERE id_siswa = ?";
    $stmt_update = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt_update, "si", $id_pendaftaran, $id_siswa);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Gagal mengupdate database: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt_update);

    // 5. SIMPAN DATA KE DATABASE (COMMIT)
    mysqli_commit($conn);

} catch (Exception $e) {
    // Jika ada error di atas, batalkan SEMUA proses
    mysqli_rollback($conn);
    show_error_page("Gagal Memproses Data", $e->getMessage());
    exit();
}

// 6. FORMAT NOMOR HP DAN KIRIM WHATSAPP (Di luar transaksi database agar loading tidak menggantung)
$no_hp = $d['no_hp'];
$no_hp = preg_replace('/[^0-9]/', '', $no_hp);
if (substr($no_hp, 0, 1) == '0') {
    $no_hp = '62' . substr($no_hp, 1);
}

// 7. KIRIM WA DENGAN TEMPLATE SYSTEM (Mengambil data array asosiatif)
$kirim = kirim_wa_template($conn, 'ACC_PENDAFTARAN', [
    'nama'      => strtoupper($d['nama_lengkap']),
    'id_daftar' => $id_pendaftaran,
    'sekolah'   => strtoupper($d['asal_sekolah'] ?? $d['sekolah_asal'] ?? '-'),
    'jurusan'   => strtoupper($d['jurusan']),
    'admin'     => $_SESSION['nama'] ?? 'TU',
    'tanggal'   => date('d/m/Y H:i')
], $no_hp);

$wa_status = $kirim ? 'sent' : 'failed';

// 8. REDIRECT
header("Location: index.php?id_reg=$id_pendaftaran&status=success&wa=$wa_status");
exit();
?>
