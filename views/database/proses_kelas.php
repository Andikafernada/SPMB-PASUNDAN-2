<?php
/**
 * PROSES KELAS — Simpan / Reset hasil pengkelasan ke DB
 */
session_start();
include '../../config.php';

if(!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'database') {
    show_error_page("Akses Ditolak", "Halaman ini hanya untuk Tim Database."); 
}

// Pastikan kolom ada
mysqli_query($conn, "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nilai_btq INT DEFAULT 0");
mysqli_query($conn, "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS kelas VARCHAR(20) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS request_kelas VARCHAR(150) DEFAULT NULL");

$aksi = $_GET['aksi'] ?? '';
$jur  = mysqli_real_escape_string($conn, $_GET['jur'] ?? '');
$kap  = max(10, intval($_GET['kap'] ?? 36));

// ============================================================
// RESET KELAS
// ============================================================
if ($aksi == 'reset') {
    mysqli_query($conn, "UPDATE siswa SET kelas=NULL WHERE jurusan='$jur'");
    header("Location: pengkelasan.php?jur=" . urlencode($jur) . "&status=reset_ok"); exit();
}

// ============================================================
// SIMPAN KELAS
// ============================================================
if ($aksi == 'simpan') {
    // Ambil data siswa jurusan terpilih
    $res = mysqli_query($conn, "SELECT id_siswa, nama_lengkap, jenis_kelamin, nilai_btq, request_kelas
        FROM siswa WHERE jurusan='$jur' ORDER BY nilai_btq DESC, nama_lengkap ASC");

    $siswa_all = [];
    while ($s = mysqli_fetch_assoc($res)) { $siswa_all[] = $s; }
    $total    = count($siswa_all);

    if ($total == 0) {
        header("Location: pengkelasan.php?jur=" . urlencode($jur) . "&status=no_data"); exit();
    }

    // ===== ALGORITMA (sama dengan di pengkelasan.php) =====
    $laki    = array_values(array_filter($siswa_all, fn($s) => strtoupper($s['jenis_kelamin']) == 'LAKI-LAKI'));
    $pr      = array_values(array_filter($siswa_all, fn($s) => strtoupper($s['jenis_kelamin']) == 'PEREMPUAN'));
    $lainnya = array_values(array_filter($siswa_all, fn($s) => !in_array(strtoupper($s['jenis_kelamin']), ['LAKI-LAKI','PEREMPUAN'])));

    usort($laki,    fn($a,$b) => $b['nilai_btq'] <=> $a['nilai_btq']);
    usort($pr,      fn($a,$b) => $b['nilai_btq'] <=> $a['nilai_btq']);
    usort($lainnya, fn($a,$b) => $b['nilai_btq'] <=> $a['nilai_btq']);

    $n_kelas  = max(1, (int)ceil($total / $kap));
    $huruf_list = [];
    for ($i=0; $i < $n_kelas; $i++) $huruf_list[] = chr(65 + $i);

    $kelas_data = [];
    foreach ($huruf_list as $h) $kelas_data[$h] = ['L'=>[], 'P'=>[], 'X'=>[]];

    foreach ($laki as $idx => $s) {
        $k = $huruf_list[$idx % $n_kelas];
        $kelas_data[$k]['L'][] = $s;
    }
    foreach ($pr as $idx => $s) {
        $k = $huruf_list[$idx % $n_kelas];
        $kelas_data[$k]['P'][] = $s;
    }
    foreach ($lainnya as $idx => $s) {
        $min_k = $huruf_list[0];
        foreach ($huruf_list as $k) {
            $tc = count($kelas_data[$k]['L']) + count($kelas_data[$k]['P']) + count($kelas_data[$k]['X']);
            $tm = count($kelas_data[$min_k]['L']) + count($kelas_data[$min_k]['P']) + count($kelas_data[$min_k]['X']);
            if ($tc < $tm) $min_k = $k;
        }
        $kelas_data[$min_k]['X'][] = $s;
    }

    // === TERAPKAN REQUEST KELAS ===
    $id_to_kelas = [];
    foreach ($kelas_data as $h => $grp) {
        foreach (array_merge($grp['L'], $grp['P'], $grp['X']) as $s) {
            $id_to_kelas[$s['id_siswa']] = $h;
        }
    }
    $nama_to_id = [];
    foreach ($siswa_all as $s) {
        $nama_to_id[strtoupper(trim($s['nama_lengkap']))] = $s['id_siswa'];
    }
    foreach ($siswa_all as $s) {
        if (empty($s['request_kelas'])) continue;
        $req_nama    = strtoupper(trim($s['request_kelas']));
        $req_id      = $nama_to_id[$req_nama] ?? null;
        if (!$req_id) continue;
        $kelas_saya  = $id_to_kelas[$s['id_siswa']] ?? null;
        $kelas_teman = $id_to_kelas[$req_id] ?? null;
        if ($kelas_saya && $kelas_teman && $kelas_saya !== $kelas_teman) {
            foreach (['L','P','X'] as $g) {
                foreach ($kelas_data[$kelas_saya][$g] as $idx2 => $s2) {
                    if ($s2['id_siswa'] == $s['id_siswa']) {
                        array_splice($kelas_data[$kelas_saya][$g], $idx2, 1);
                        $kelas_data[$kelas_teman][$g][] = $s2;
                        $id_to_kelas[$s['id_siswa']] = $kelas_teman;
                        break 2;
                    }
                }
            }
        }
    }

    // ===== SIMPAN KE DB =====
    $saved = 0;
    foreach ($kelas_data as $huruf => $grp) {
        $nama_kelas = "$jur $huruf";
        foreach (array_merge($grp['L'], $grp['P'], $grp['X']) as $s) {
            $sid = intval($s['id_siswa']);
            $nm  = mysqli_real_escape_string($conn, $nama_kelas);
            if (mysqli_query($conn, "UPDATE siswa SET kelas='$nm' WHERE id_siswa='$sid'")) {
                $saved++;
            }
        }
    }

    header("Location: pengkelasan.php?jur=" . urlencode($jur) . "&status=saved&n=$saved"); exit();
}

header("Location: pengkelasan.php"); exit();
