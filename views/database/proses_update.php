<?php
/**
 * PROSES UPDATE - CENTRAL COMMAND v4.0
 * WhatsApp Template System Integration
 * Aksi: du, du_toggle, pindah_jurusan, cabut, restore
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../../config.php';

// Fungsi bantuan untuk menampilkan error page bergaya Light SaaS
function tampilkan_error($judul, $pesan) {
    die("
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Sistem Terhenti - $judul</title>
        <script src='https://cdn.tailwindcss.com'></script>
        <link href='https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap' rel='stylesheet'>
        <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }</style>
    </head>
    <body class='h-screen flex items-center justify-center p-4'>
        <div class='bg-white border border-red-200 rounded-3xl p-8 max-w-md w-full text-center shadow-2xl shadow-red-500/10'>
            <div class='w-20 h-20 bg-red-50 rounded-full flex items-center justify-center text-4xl mx-auto mb-6'>🚨</div>
            <h1 class='text-xl font-black text-slate-900 mb-2 uppercase tracking-tight'>$judul</h1>
            <p class='text-sm text-slate-500 mb-8'>$pesan</p>
            <button onclick='window.history.back()' class='w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl transition-all shadow-md'>
                Kembali ke Halaman Sebelumnya
            </button>
        </div>
    </body>
    </html>
    ");
}

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'database') {
    tampilkan_error("Akses Ditolak", "Halaman atau tindakan ini hanya bisa dieksekusi oleh Tim Database dan Superuser.");
}

$aksi    = $_GET['aksi'] ?? '';
$id      = mysqli_real_escape_string($conn, $_GET['id'] ?? '');
$petugas = $_SESSION['nama'] ?? 'Admin System';

// ============================================================
// 1. KONFIRMASI DAFTAR ULANG (TANPA WA)
// ============================================================
if ($aksi == 'du') {
    mysqli_query($conn, "UPDATE siswa SET status_siswa='SUDAH DAFTAR ULANG' WHERE id_siswa='$id'");
    // CATATAN: DAftar ulang TIDAK kirim WA ke siswa (sesuai kebijakan)
    header("Location: index.php?status=success_du"); exit();
}

// ============================================================
// 2. TOGGLE DU (AJAX dari edit.php)
// ============================================================
if ($aksi == 'du_toggle') {
    $status = mysqli_real_escape_string($conn, $_GET['status'] ?? 'BELUM DAFTAR ULANG');
    mysqli_query($conn, "UPDATE siswa SET status_siswa='$status' WHERE id_siswa='$id'");
    echo json_encode(['ok' => true]);
    exit();
}

// ============================================================
// 3. PINDAH JURUSAN + HISTORY + WA NOTIFIKASI
// ============================================================
if ($aksi == 'pindah_jurusan') {
    $baru   = mysqli_real_escape_string($conn, $_GET['jurusan_baru'] ?? '');
    $lama   = mysqli_real_escape_string($conn, $_GET['jurusan_lama'] ?? '');
    $alasan = mysqli_real_escape_string($conn, $_GET['alasan'] ?? '-');

    if (!$baru || !$lama) { header("Location: index.php?status=error"); exit(); }

    // Create history table if not exists
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS history_jurusan (
        id_history INT AUTO_INCREMENT PRIMARY KEY,
        id_siswa INT NOT NULL, jurusan_lama VARCHAR(50), jurusan_baru VARCHAR(50),
        alasan TEXT, petugas VARCHAR(100), tgl_pindah DATETIME DEFAULT NOW(),
        INDEX idx_siswa (id_siswa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "UPDATE siswa SET jurusan='$baru', jurusan_lama='$lama' WHERE id_siswa='$id'");
    mysqli_query($conn, "INSERT INTO history_jurusan (id_siswa,jurusan_lama,jurusan_baru,alasan,petugas)
                         VALUES ('$id','$lama','$baru','$alasan','$petugas')");

    $d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa='$id'"));
    
    // Kirim WA dengan template system
    kirim_wa_template($conn, 'PINDAH_JURUSAN', [
        'nama'         => strtoupper($d['nama_lengkap']),
        'jurusan_baru' => $baru,
        'jurusan_lama' => $lama,
        'alasan'       => $alasan,
        'admin'        => $petugas,
        'tanggal'      => date('d/m/Y H:i')
    ], $d['no_hp']);
    
    header("Location: index.php?status=success_pindah"); exit();
}

// ============================================================
// 4. CABUT BERKAS → ARSIP + WA NOTIFIKASI
// ============================================================
if ($aksi == 'cabut') {
    $alasan = mysqli_real_escape_string($conn, $_GET['alasan'] ?? '');
    $s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa='$id'"));
    
    if (!$s) tampilkan_error("Data Tidak Ditemukan", "Data siswa tidak ditemukan di tabel aktif. Kemungkinan sudah dicabut.");

    // Helper safe date
    $tgl_lhr    = !empty($s['tanggal_lahir'])  ? "'{$s['tanggal_lahir']}'"  : "NULL";
    $tgl_daftar = !empty($s['tgl_daftar'])     ? "'{$s['tgl_daftar']}'"     : "NULL";
    $tgl_ayah   = !empty($s['tgl_lahir_ayah']) ? "'{$s['tgl_lahir_ayah']}'" : "NULL";
    $tgl_ibu    = !empty($s['tgl_lahir_ibu'])  ? "'{$s['tgl_lahir_ibu']}'"  : "NULL";

    function se($conn, $v) { return mysqli_real_escape_string($conn, $v ?? ''); }

    // Ensure arsip columns exist
    $cols_arsip = [
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS jenis_kelamin VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tempat_lahir VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS agama VARCHAR(30) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nama_jalan VARCHAR(200) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS kota VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS provinsi VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nama_ayah VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nik_ayah VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ayah VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ayah DATE DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS pekerjaan_ayah VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nama_ibu VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nik_ibu VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tempat_lahir_ibu VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tgl_lahir_ibu DATE DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS pekerjaan_ibu VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS sekolah_asal VARCHAR(200) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS nilai_btq INT DEFAULT 0",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS kelas VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS alasan_cabut TEXT DEFAULT NULL",
        "ALTER TABLE arsip_siswa ADD COLUMN IF NOT EXISTS tgl_arsip DATETIME DEFAULT NULL"
    ];
    foreach ($cols_arsip as $c) { mysqli_query($conn, $c); }

    $sql_arsip = "INSERT INTO arsip_siswa (
        id_siswa, id_pendaftaran, nama_lengkap, jenis_kelamin, nik,
        tempat_lahir, tanggal_lahir, no_hp, jurusan, asal_sekolah,
        jurusan_asal, sumber_info, alamat, nama_jalan, status_bayar,
        status_siswa, nisn, agama, rt, rw,
        kelurahan, kecamatan, kota, provinsi, petugas_pendaftar,
        tgl_daftar, nama_ayah, nik_ayah, tempat_lahir_ayah, tgl_lahir_ayah,
        pekerjaan_ayah, nama_ibu, nik_ibu, tempat_lahir_ibu, tgl_lahir_ibu,
        pekerjaan_ibu, sekolah_asal, nilai_btq, kelas, alasan_cabut,
        tgl_arsip
    ) VALUES (
        '{$s['id_siswa']}', '" . se($conn,$s['id_pendaftaran']) . "', '" . se($conn,$s['nama_lengkap']) . "', '" . se($conn,$s['jenis_kelamin']) . "', '" . se($conn,$s['nik']) . "',
        '" . se($conn,$s['tempat_lahir']) . "', $tgl_lhr, '" . se($conn,$s['no_hp']) . "', '" . se($conn,$s['jurusan']) . "', '" . se($conn,$s['asal_sekolah']) . "',
        '" . se($conn,$s['jurusan_asal']) . "', '" . se($conn,$s['sumber_info']) . "', '" . se($conn,$s['alamat']) . "', '" . se($conn,$s['nama_jalan']) . "', '" . se($conn,$s['status_bayar']) . "',
        '" . se($conn,$s['status_siswa']) . "', '" . se($conn,$s['nisn']) . "', '" . se($conn,$s['agama']) . "', '" . se($conn,$s['rt']) . "', '" . se($conn,$s['rw']) . "',
        '" . se($conn,$s['kelurahan']) . "', '" . se($conn,$s['kecamatan']) . "', '" . se($conn,$s['kota']) . "', '" . se($conn,$s['provinsi']) . "', '" . se($conn,$s['petugas_pendaftar']) . "',
        $tgl_daftar, '" . se($conn,$s['nama_ayah']) . "', '" . se($conn,$s['nik_ayah']) . "', '" . se($conn,$s['tempat_lahir_ayah']) . "', $tgl_ayah,
        '" . se($conn,$s['pekerjaan_ayah']) . "', '" . se($conn,$s['nama_ibu']) . "', '" . se($conn,$s['nik_ibu']) . "', '" . se($conn,$s['tempat_lahir_ibu']) . "', $tgl_ibu,
        '" . se($conn,$s['pekerjaan_ibu']) . "', '" . se($conn,$s['sekolah_asal']) . "', '" . intval($s['nilai_btq']) . "', '" . se($conn,$s['kelas']) . "', '$alasan',
        NOW()
    )";

    if (mysqli_query($conn, $sql_arsip)) {
        mysqli_query($conn, "DELETE FROM siswa WHERE id_siswa='$id'");
        
        // Kirim WA dengan template system
        kirim_wa_template($conn, 'CABUT_BERKAS', [
            'nama'    => strtoupper($s['nama_lengkap']),
            'alasan'  => $alasan,
            'admin'   => $petugas,
            'tanggal' => date('d/m/Y H:i')
        ], $s['no_hp']);
        
        header("Location: index.php?status=success_cabut"); exit();
    } else {
        tampilkan_error("Gagal Mengarsipkan", "Terjadi kesalahan SQL: " . mysqli_error($conn));
    }
}

// ============================================================
// 5. RESTORE DARI ARSIP
// ============================================================
if ($aksi == 'restore') {
    $s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM arsip_siswa WHERE id_siswa='$id'"));
    if (!$s) tampilkan_error("Data Arsip Tidak Ditemukan", "Data siswa tidak ditemukan di tabel arsip.");

    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE id_siswa='{$s['id_siswa']}'"));
    if ($cek) tampilkan_error("Duplikat ID", "Siswa tersebut sudah ada di tabel aktif (mungkin di-double input). Harap hubungi superuser.");

    $tgl_lhr    = !empty($s['tanggal_lahir'])  ? "'{$s['tanggal_lahir']}'"  : "NULL";
    $tgl_daftar = !empty($s['tgl_daftar'])     ? "'{$s['tgl_daftar']}"     : "NULL";

    function se2($conn, $v) { return mysqli_real_escape_string($conn, $v ?? ''); }

    $sql_restore = "INSERT INTO siswa (
        id_siswa, id_pendaftaran, nama_lengkap, jenis_kelamin, nik,
        tempat_lahir, tanggal_lahir, no_hp, jurusan, asal_sekolah,
        nisn, agama, rt, rw, kelurahan, kecamatan, kota, provinsi,
        alamat, nama_jalan, petugas_pendaftar, tgl_daftar, status_bayar, status_siswa,
        nama_ayah, nik_ayah, tempat_lahir_ayah, pekerjaan_ayah,
        nama_ibu, nik_ibu, tempat_lahir_ibu, pekerjaan_ibu,
        sekolah_asal, nilai_btq
    ) VALUES (
        '{$s['id_siswa']}', '" . se2($conn,$s['id_pendaftaran']) . "', '" . se2($conn,$s['nama_lengkap']) . "', '" . se2($conn,$s['jenis_kelamin']) . "', '" . se2($conn,$s['nik']) . "',
        '" . se2($conn,$s['tempat_lahir']) . "', $tgl_lhr, '" . se2($conn,$s['no_hp']) . "', '" . se2($conn,$s['jurusan']) . "', '" . se2($conn,$s['asal_sekolah']) . "',
        '" . se2($conn,$s['nisn']) . "', '" . se2($conn,$s['agama']) . "', '" . se2($conn,$s['rt']) . "', '" . se2($conn,$s['rw']) . "', '" . se2($conn,$s['kelurahan']) . "', '" . se2($conn,$s['kecamatan']) . "', '" . se2($conn,$s['kota']) . "', '" . se2($conn,$s['provinsi']) . "',
        '" . se2($conn,$s['alamat']) . "', '" . se2($conn,$s['nama_jalan']) . "', '" . se2($conn,$s['petugas_pendaftar']) . "', $tgl_daftar, '" . se2($conn,$s['status_bayar']) . "', 'BELUM DAFTAR ULANG',
        '" . se2($conn,$s['nama_ayah']) . "', '" . se2($conn,$s['nik_ayah']) . "', '" . se2($conn,$s['tempat_lahir_ayah']) . "', '" . se2($conn,$s['pekerjaan_ayah']) . "',
        '" . se2($conn,$s['nama_ibu']) . "', '" . se2($conn,$s['nik_ibu']) . "', '" . se2($conn,$s['tempat_lahir_ibu']) . "', '" . se2($conn,$s['pekerjaan_ibu']) . "',
        '" . se2($conn,$s['sekolah_asal']) . "', '" . intval($s['nilai_btq']) . "'
    )";

    if (mysqli_query($conn, $sql_restore)) {
        mysqli_query($conn, "DELETE FROM arsip_siswa WHERE id_siswa='$id'");
        header("Location: index.php?status=success_restore"); exit();
    } else {
        tampilkan_error("Gagal Restore", "Terjadi kesalahan SQL: " . mysqli_error($conn));
    }
}

// Jika tidak ada aksi, kembalikan ke index
header("Location: index.php"); exit();
?>
