<?php
/**
 * PROSES SIMPAN PENDAFTARAN - UPGRADE v3.2
 * Tim Pendaftaran hanya simpan 12 field dasar
 * ID Pendaftaran akan di-generate oleh TU saat ACC
 */
session_start();
include '../../config.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pendaftaran','superuser'])) {
    header("Location: ../../panitia/index.php"); exit();
}

function bersihkan($data) {
    $data = str_replace(["\r","\n","\t"], ' ', $data);
    return strtoupper(trim($data));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Validasi input required
    $required = ['nama', 'hp', 'jurusan', 'asal_sekolah', 'alamat_lengkap', 'rt', 'rw', 'kelurahan', 'kecamatan'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            header("Location: index.php?menu=input&status=error&msg=" . urlencode("Field $field tidak boleh kosong"));
            exit();
        }
    }

    $nama      = bersihkan($_POST['nama']);
    $hp        = mysqli_real_escape_string($conn, $_POST['hp']);
    $jurusan   = mysqli_real_escape_string($conn, $_POST['jurusan']);
    $sekolah   = bersihkan($_POST['asal_sekolah']);
    $alamat    = bersihkan($_POST['alamat_lengkap']);
    $rt        = mysqli_real_escape_string($conn, $_POST['rt']);
    $rw        = mysqli_real_escape_string($conn, $_POST['rw']);
    $kelurahan = bersihkan($_POST['kelurahan']);
    $kecamatan = bersihkan($_POST['kecamatan']);
    $petugas   = $_SESSION['nama'];
    $tanggal   = date('Y-m-d H:i:s');

    // Validasi HP (harus angka dan minimal 10 digit)
    if (!is_numeric($hp) || strlen($hp) < 10) {
        header("Location: index.php?menu=input&status=error&msg=" . urlencode("Nomor HP tidak valid"));
        exit();
    }

    // Auto-learning sekolah
    $sekolah_esc = mysqli_real_escape_string($conn, $sekolah);
    $kec_esc     = mysqli_real_escape_string($conn, $kecamatan);
    $cek = mysqli_query($conn, "SELECT id FROM ref_sekolah WHERE nama_sekolah='$sekolah_esc'");
    if (mysqli_num_rows($cek) == 0) {
        mysqli_query($conn, "INSERT INTO ref_sekolah (nama_sekolah, kecamatan) VALUES ('$sekolah_esc','$kec_esc')");
    }

    // INSERT: id_pendaftaran dikosongkan, akan di-generate oleh TU saat ACC
    $sql = "INSERT INTO siswa (
            nama_lengkap, no_hp, jurusan, asal_sekolah,
            alamat, rt, rw, kelurahan, kecamatan,
            petugas_pendaftar, tgl_daftar, status_siswa, status_bayar
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'BELUM DAFTAR ULANG', 'BELUM')";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        header("Location: index.php?menu=input&status=error&msg=" . urlencode("Gagal prepare statement"));
        exit();
    }

    mysqli_stmt_bind_param($stmt, "sssssssssss",
        $nama, $hp, $jurusan, $sekolah,
        $alamat, $rt, $rw, $kelurahan, $kecamatan,
        $petugas, $tanggal
    );

    if (mysqli_stmt_execute($stmt)) {
        error_log("SUCCESS: Data siswa disimpan - Nama: $nama, Petugas: $petugas");
        header("Location: index.php?menu=input&status=success");
    } else {
        error_log("ERROR: Gagal simpan - " . mysqli_error($conn));
        header("Location: index.php?menu=input&status=error&msg=" . urlencode(mysqli_error($conn)));
    }
    mysqli_stmt_close($stmt);
    exit();
}
header("Location: index.php?menu=input");
