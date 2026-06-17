<?php
/**
 * PROSES TUTUP SPMB & ARSIP TAHUNAN
 * Hanya bisa dijalankan role: superuser / kepala sekolah
 * Alur: snapshot siswa aktif → spmb_arsip_tahunan → kosongkan tabel siswa
 */
session_start();
include '../../config.php';

// Hanya superuser yang boleh
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['superuser','database','superuser1'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak.']);
    exit();
}

header('Content-Type: application/json');

$aksi    = $_POST['aksi'] ?? $_GET['aksi'] ?? '';
$petugas = $_SESSION['nama'];

// ============================================================
// PREVIEW — berapa yang akan diarsipkan
// ============================================================
if ($aksi == 'preview') {
    $tahun   = intval($_POST['tahun'] ?? date('Y'));
    $total   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM siswa"))['n'];
    $du      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM siswa WHERE status_siswa='SUDAH DAFTAR ULANG'"))['n'];
    $per_jur = [];
    $res     = mysqli_query($conn, "SELECT jurusan, COUNT(*) as n FROM siswa GROUP BY jurusan ORDER BY n DESC");
    while ($r = mysqli_fetch_assoc($res)) { $per_jur[] = $r; }
    echo json_encode(['ok'=>true,'tahun'=>$tahun,'total'=>$total,'du'=>$du,'tidak_du'=>$total-$du,'per_jurusan'=>$per_jur]);
    exit();
}

// ============================================================
// EKSEKUSI TUTUP SPMB
// ============================================================
if ($aksi == 'tutup') {
    $tahun   = intval($_POST['tahun']   ?? date('Y'));
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');

    // Cek apakah tahun ini sudah pernah diarsipkan
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as n FROM spmb_arsip_tahunan WHERE tahun_spmb='$tahun'"));
    if ($cek['n'] > 0) {
        echo json_encode(['ok'=>false,'msg'=>"Data SPMB $tahun sudah pernah diarsipkan sebelumnya."]);
        exit();
    }

    // Ambil semua siswa aktif
    $res_siswa = mysqli_query($conn, "SELECT * FROM siswa");
    if (!$res_siswa) {
        echo json_encode(['ok'=>false,'msg'=>'Gagal membaca data siswa: '.mysqli_error($conn)]);
        exit();
    }

    $total_arsip = 0;
    $total_du    = 0;

    mysqli_begin_transaction($conn);
    try {
        while ($s = mysqli_fetch_assoc($res_siswa)) {
            $tgl_lhr    = !empty($s['tanggal_lahir']) ? "'" . $s['tanggal_lahir'] . "'" : "NULL";
            $tgl_daftar = !empty($s['tgl_daftar'])    ? "'" . $s['tgl_daftar']    . "'" : "NULL";

            function sa($conn, $v) { return mysqli_real_escape_string($conn, $v ?? ''); }

            $sql = "INSERT INTO spmb_arsip_tahunan (
                tahun_spmb, id_siswa_asli, id_pendaftaran, nama_lengkap, jenis_kelamin,
                tempat_lahir, tanggal_lahir, agama, nisn, nik,
                no_hp, jurusan, jurusan_lama, kelas, asal_sekolah,
                sekolah_asal, nama_jalan, rt, rw, kelurahan,
                kecamatan, kota, provinsi, nama_ayah, pekerjaan_ayah,
                nama_ibu, pekerjaan_ibu, nilai_btq, status_siswa, status_bayar,
                petugas_pendaftar, tgl_daftar
            ) VALUES (
                '$tahun', '{$s['id_siswa']}', '" . sa($conn,$s['id_pendaftaran']) . "', '" . sa($conn,$s['nama_lengkap']) . "', '" . sa($conn,$s['jenis_kelamin']) . "',
                '" . sa($conn,$s['tempat_lahir']) . "', $tgl_lhr, '" . sa($conn,$s['agama']) . "', '" . sa($conn,$s['nisn']) . "', '" . sa($conn,$s['nik']) . "',
                '" . sa($conn,$s['no_hp']) . "', '" . sa($conn,$s['jurusan']) . "', '" . sa($conn,$s['jurusan_lama']) . "', '" . sa($conn,$s['kelas']) . "', '" . sa($conn,$s['asal_sekolah']) . "',
                '" . sa($conn,$s['sekolah_asal']) . "', '" . sa($conn,$s['nama_jalan']) . "', '" . sa($conn,$s['rt']) . "', '" . sa($conn,$s['rw']) . "', '" . sa($conn,$s['kelurahan']) . "',
                '" . sa($conn,$s['kecamatan']) . "', '" . sa($conn,$s['kota']) . "', '" . sa($conn,$s['provinsi']) . "', '" . sa($conn,$s['nama_ayah']) . "', '" . sa($conn,$s['pekerjaan_ayah']) . "',
                '" . sa($conn,$s['nama_ibu']) . "', '" . sa($conn,$s['pekerjaan_ibu']) . "', '" . intval($s['nilai_btq']) . "', '" . sa($conn,$s['status_siswa']) . "', '" . sa($conn,$s['status_bayar']) . "',
                '" . sa($conn,$s['petugas_pendaftar']) . "', $tgl_daftar
            )";

            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Gagal arsip siswa ID {$s['id_siswa']}: " . mysqli_error($conn));
            }
            $total_arsip++;
            if ($s['status_siswa'] == 'SUDAH DAFTAR ULANG') $total_du++;
        }

        // Simpan log tutup
        $sql_log = "INSERT INTO spmb_log_tutup
            (tahun_spmb, total_diarsipkan, total_du, total_tidak_du, petugas, catatan)
            VALUES ('$tahun', $total_arsip, $total_du, " . ($total_arsip - $total_du) . ", '$petugas', '$catatan')";
        mysqli_query($conn, $sql_log);

        // Kosongkan tabel aktif
        // (arsip_siswa tetap, history_jurusan tetap, hanya tabel siswa yang dikosongkan)
        mysqli_query($conn, "DELETE FROM siswa");
        // Reset AUTO_INCREMENT agar ID mulai dari 1 lagi
        mysqli_query($conn, "ALTER TABLE siswa AUTO_INCREMENT = 1");

        mysqli_commit($conn);

        echo json_encode([
            'ok'      => true,
            'msg'     => "SPMB $tahun berhasil diarsipkan.",
            'total'   => $total_arsip,
            'du'      => $total_du
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['ok' => false, 'msg' => 'Aksi tidak dikenali.']);
