<?php
/**
 * GET HISTORY JURUSAN - AJAX ENDPOINT
 */
session_start();
include '../../config.php';

if(!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'database') {
    show_error_page("Akses Ditolak", "Halaman ini hanya untuk Tim Database.");
}

$id = mysqli_real_escape_string($conn, $_GET['id'] ?? '');

// Pastikan tabel ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS history_jurusan (
    id_history   INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa     INT NOT NULL,
    jurusan_lama VARCHAR(50),
    jurusan_baru VARCHAR(50),
    alasan       TEXT,
    petugas      VARCHAR(100),
    tgl_pindah   DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$hist = mysqli_query($conn, "SELECT * FROM history_jurusan WHERE id_siswa='$id' ORDER BY tgl_pindah DESC");

if (!$hist || mysqli_num_rows($hist) == 0) {
    echo '<p style="color:#64748b;text-align:center;padding:20px;font-size:0.85rem;">Belum ada history pindah jurusan.</p>';
    exit();
}

echo '<div style="text-align:left;">';
while ($h = mysqli_fetch_assoc($hist)) {
    $tgl = date('d M Y H:i', strtotime($h['tgl_pindah']));
    echo "
    <div style='padding:14px 18px; background:rgba(0,0,0,0.3); border-radius:12px;
        border-left:3px solid #f59e0b; margin-bottom:10px;'>
        <div style='font-size:0.65rem; color:#64748b; margin-bottom:5px;'>
            {$tgl} &bull; oleh {$h['petugas']}
        </div>
        <div style='font-weight:800; font-size:0.9rem;'>
            <span style='color:#ef4444'>{$h['jurusan_lama']}</span>
            &nbsp;&rarr;&nbsp;
            <span style='color:#10b981'>{$h['jurusan_baru']}</span>
        </div>";
    if (!empty($h['alasan']) && $h['alasan'] != '-') {
        echo "<div style='font-size:0.75rem; color:#94a3b8; margin-top:4px; font-style:italic;'>Alasan: {$h['alasan']}</div>";
    }
    echo "</div>";
}
echo '</div>';
