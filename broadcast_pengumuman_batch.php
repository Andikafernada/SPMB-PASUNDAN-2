<?php
/**
 * BROADCAST PENGUMUMAN_PENTING - Batch Sender
 * Untuk siswa SPMB26-001 s/d SPMB26-150 yang belum dapat broadcast
 * Run via CLI: php broadcast_batch.php
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

echo "==========================================\n";
echo "BROADCAST PENGUMUMAN_PENTING\n";
echo "Target: SPMB26-001 s/d SPMB26-150 (yang belum)\n";
echo "==========================================\n\n";

// Konfigurasi
$template_kode = 'PENGUMUMAN_PENTING';
$batch_id = 'BATCH_PENGUMUMAN_' . date('Ymd_His');

// Load Template
$template = load_wa_template($conn, $template_kode);
if (!$template) {
    die("ERROR: Template $template_kode tidak ditemukan!\n");
}
echo "Template loaded: " . $template['nama_template'] . "\n\n";

// Query siswa yang belum broadcast PENGUMUMAN_PENTING
// dengan no HP valid (bukan 0, panjang > 9)
$sql = "SELECT
            s.id_siswa,
            s.id_pendaftaran,
            s.nama_lengkap,
            s.no_hp,
            s.jurusan,
            s.asal_sekolah,
            s.tgl_daftar
        FROM siswa s
        WHERE s.id_pendaftaran BETWEEN 'SPMB26-001' AND 'SPMB26-150'
          AND s.no_hp != '0'
          AND s.no_hp IS NOT NULL
          AND s.no_hp != ''
          AND LENGTH(REPLACE(REPLACE(REPLACE(s.no_hp, '-', ''), ' ', ''), '+', '')) > 9
        ORDER BY s.id_pendaftaran";

$stmt = mysqli_query($conn, $sql);
if (!$stmt) {
    die("Query error: " . mysqli_error($conn) . "\n");
}

$siswa_list = [];
while ($row = mysqli_fetch_assoc($stmt)) {
    $siswa_list[] = $row;
}
mysqli_free_result($stmt);

$total = count($siswa_list);
echo "Jumlah siswa yang akan di-broadcast: $total\n\n";

if ($total === 0) {
    echo "Tidak ada siswa yang perlu di-broadcast.\n";
    exit;
}

// Fonnte Config
$fonnte_api_key = $_ENV['FONNTE_API_KEY'] ?? '';
$fonnte_base_url = $_ENV['FONNTE_BASE_URL'] ?? 'https://api.fonnte.com';

if (empty($fonnte_api_key)) {
    die("ERROR: FONNTE_API_KEY tidak dikonfigurasi di .env\n");
}

// Payload default
$admin_contact = getenv('ADMIN_WA_NUMBER') ?: '083817203455';

// Stats
$success_count = 0;
$failed_count = 0;
$skipped_count = 0;
$failed_list = [];

// Process each student
foreach ($siswa_list as $index => $siswa) {
    $id_daftar = $siswa['id_pendaftaran'];

    // Check if already broadcasted with PENGUMUMAN_PENTING
    $check_sql = "SELECT COUNT(*) as cnt FROM wa_broadcast_history
                  WHERE id_pendaftaran = '$id_daftar' AND template_kode = 'PENGUMUMAN_PENTING' LIMIT 1";
    $check_result = mysqli_query($conn, $check_sql);
    $check_row = mysqli_fetch_assoc($check_result);
    if ($check_row['cnt'] > 0) {
        $skipped_count++;
        continue; // Skip this student
    }

    $no = $index + 1 - $skipped_count;
    $id_siswa = $siswa['id_siswa'];
    $nama = trim($siswa['nama_lengkap'] ?? '');
    $no_hp_raw = trim($siswa['no_hp'] ?? '');
    $jurusan = trim($siswa['jurusan'] ?? '');
    $asal_sekolah = trim($siswa['asal_sekolah'] ?? '');
    $tgl_daftar = !empty($siswa['tgl_daftar']) ? date('d/m/Y', strtotime($siswa['tgl_daftar'])) : date('d/m/Y');

    echo "[$no/$total] $id_daftar - $nama\n";
    echo "    HP: $no_hp_raw -> ";

    // Format phone
    $no_hp_formatted = preg_replace('/[^0-9]/', '', $no_hp_raw);
    if (substr($no_hp_formatted, 0, 1) === '0') {
        $no_hp_formatted = '62' . substr($no_hp_formatted, 1);
    } elseif (substr($no_hp_formatted, 0, 2) !== '62') {
        $no_hp_formatted = '62' . $no_hp_formatted;
    }
    echo "$no_hp_formatted\n";

    // Render pesan
    $payload = [
        'NAMA'         => strtoupper($nama ?: '-'),
        'ID_DAFTAR'    => $id_daftar ?: '-',
        'JURUSAN'      => strtoupper($jurusan ?: '-'),
        'SEKOLAH'      => strtoupper($asal_sekolah ?: '-'),
        'NO_HP'        => $admin_contact,
        'ADMIN'        => 'ADMIN',
        'TANGGAL'      => $tgl_daftar,
        'GELOMBANG'    => 'Gelombang 1',
        'BIAYA'        => 'Rp 150.000',
        'JURUSAN_LAMA' => '-',
        'JURUSAN_BARU' => strtoupper($jurusan ?: '-'),
        'ALASAN'       => '-',
        'BULAN'        => date('F Y'),
    ];

    $pesan_text = render_wa_template($template['template_text'], $payload);
    $pesan_text = mb_convert_encoding($pesan_text, 'UTF-8', 'UTF-8');

    // Kirim via Fonnte
    $fonnte_url = rtrim($fonnte_base_url, '/') . '/send';
    $fonnte_data = [
        'target' => $no_hp_formatted,
        'message' => $pesan_text,
        'countryCode' => '62'
    ];

    $ch = curl_init($fonnte_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fonnte_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $fonnte_api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

    $fonnte_response = curl_exec($ch);
    $fonnte_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $fonnte_curl_err = curl_error($ch);
    curl_close($ch);

    $fonnte_result = json_decode($fonnte_response, true);

    // Cek sukses
    $send_success = ($fonnte_http_code === 200)
        && (isset($fonnte_result['status']) ? $fonnte_result['status'] !== false : true);

    $status = $send_success ? 'success' : 'failed';
    $error_msg = $send_success ? null : "HTTP $fonnte_http_code | " . substr($fonnte_response ?: $fonnte_curl_err, 0, 255);

    // Log ke database
    $stmt_log = mysqli_prepare($conn, "
        INSERT INTO wa_broadcast_history
        (siswa_id, id_pendaftaran, nama_siswa, no_hp, template_kode, template_nama, pesan_text, status, error_message, sent_by, batch_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if ($stmt_log) {
        $sent_by = 'Batch System';
        mysqli_stmt_bind_param($stmt_log, "issssssssss",
            $id_siswa, $id_daftar, $nama, $no_hp_formatted,
            $template_kode, $template['nama_template'],
            $pesan_text, $status, $error_msg, $sent_by, $batch_id
        );
        mysqli_stmt_execute($stmt_log);
        mysqli_stmt_close($stmt_log);
    }

    if ($send_success) {
        echo "    ✓ SUCCESS\n";
        $success_count++;
    } else {
        echo "    ✗ FAILED: $error_msg\n";
        $failed_count++;
        $failed_list[] = [
            'id' => $id_daftar,
            'nama' => $nama,
            'hp' => $no_hp_formatted,
            'error' => $error_msg
        ];
    }

    // Delay untuk avoid rate limit (1 detik)
    sleep(1);
    echo "\n";
}

// Summary
echo "==========================================\n";
echo "BROADCAST SELESAI\n";
echo "==========================================\n";
$actual_total = $total - $skipped_count;
echo "Total dicek: $total\n";
echo "Sudah pernah broadcast: $skipped_count\n";
echo "Di-broadcast sekarang: $actual_total\n";
echo "Berhasil: $success_count\n";
echo "Gagal: $failed_count\n";
echo "Batch ID: $batch_id\n";

if (count($failed_list) > 0) {
    echo "\n=== DAFTAR GAGAL ===\n";
    foreach ($failed_list as $f) {
        echo "- {$f['id']} | {$f['nama']} | {$f['hp']}\n";
        echo "  Error: {$f['error']}\n";
    }
}

echo "\nSelesai: " . date('Y-m-d H:i:s') . "\n";
