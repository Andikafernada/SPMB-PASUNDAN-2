<?php
/**
 * PROSES SIMPAN PENDAFTARAN - UPGRADE v3.0
 * Tim Pendaftaran hanya simpan 9 field dasar
 */
session_start();
include '../../config.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pendaftaran','superuser'])) {
    header("Location: ../../panitia/index.php"); exit();
}

function bersihkan($data) {
    $data = str_replace(["\r","\n","\t"], ' ', $data);
    return strtoupper(trim($data));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

    // Auto-learning sekolah
    $sekolah_esc = mysqli_real_escape_string($conn, $sekolah);
    $kec_esc     = mysqli_real_escape_string($conn, $kecamatan);
    $cek = mysqli_query($conn, "SELECT id FROM ref_sekolah WHERE nama_sekolah='$sekolah_esc'");
    if (mysqli_num_rows($cek) == 0) {
        mysqli_query($conn, "INSERT INTO ref_sekolah (nama_sekolah, kecamatan) VALUES ('$sekolah_esc','$kec_esc')");
    }

    $sql = "INSERT INTO siswa (
                nama_lengkap, no_hp, jurusan, asal_sekolah,
                alamat, rt, rw, kelurahan, kecamatan,
                petugas_pendaftar, tgl_daftar, status_siswa, status_bayar
            ) VALUES (
                '".mysqli_real_escape_string($conn,$nama)."',
                '$hp', '$jurusan', '$sekolah_esc',
                '".mysqli_real_escape_string($conn,$alamat)."',
                '$rt', '$rw',
                '".mysqli_real_escape_string($conn,$kelurahan)."',
                '$kec_esc',
                '$petugas', '$tanggal', 'BELUM DAFTAR ULANG', 'BELUM'
            )";

    if (mysqli_query($conn, $sql)) {
        header("Location: index.php?menu=input&status=success");
    } else {
        header("Location: index.php?menu=input&status=error&msg=" . urlencode(mysqli_error($conn)));
    }
    exit();
}
header("Location: index.php?menu=input");
