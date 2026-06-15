<?php
/**
 * TPA ADMIN - Hasil TPA Siswa
 * SMK Pasundan 2 Bandung
 * Halaman untuk committee melihat & reset hasil TPA
 */
include '../../config.php';

// Proteksi: harus login
if (!isset($_SESSION['role'])) {
    header("Location: ../../login.php");
    exit();
}

// Allowed roles: pendaftaran, tu, database, superuser, user
$allowed_roles = ['pendaftaran', 'tu', 'database', 'superuser', 'user'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../login.php");
    exit();
}

// Handle Reset TPA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_tpa'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $id_siswa = (int)($_POST['id_siswa'] ?? 0);
        if ($id_siswa > 0) {
            // Reset TPA siswa
            mysqli_query($conn, "UPDATE siswa SET
                tpa_selesai = 0,
                tpa_tanggal = NULL,
                tpa_nilai_total = NULL,
                tpa_benar_verbal = NULL,
                tpa_benar_numerik = NULL,
                tpa_benar_logika = NULL
                WHERE id_siswa = $id_siswa");

            // Hapus jawaban
            mysqli_query($conn, "DELETE FROM tpa_jawaban WHERE id_siswa = $id_siswa");

            $_SESSION['success_message'] = 'TPA siswa berhasil di-reset!';
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Bulk Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_reset'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        // Reset semua TPA yang sudah selesai
        mysqli_query($conn, "UPDATE siswa SET
            tpa_selesai = 0,
            tpa_tanggal = NULL,
            tpa_nilai_total = NULL,
            tpa_benar_verbal = NULL,
            tpa_benar_numerik = NULL,
            tpa_benar_logika = NULL
            WHERE tpa_selesai = 1");

        // Hapus semua jawaban
        mysqli_query($conn, "TRUNCATE TABLE tpa_jawaban");

        $_SESSION['success_message'] = 'Semua TPA berhasil di-reset!';
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Tampilkan success message jika ada
$success_msg = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Ambil filter
$filter_jurusan = isset($_GET['jurusan']) ? mysqli_real_escape_string($conn, $_GET['jurusan']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query - semua siswa yang punya id_pendaftaran
$where = ["id_pendaftaran IS NOT NULL AND id_pendaftaran != ''"];
if ($filter_jurusan) $where[] = "jurusan = '$filter_jurusan'";
if ($filter_status === 'belum') $where[] = "tpa_selesai != 1";
if ($filter_status === 'sudah') $where[] = "tpa_selesai = 1";
if ($search) $where[] = "(nama_lengkap LIKE '%$search%' OR id_pendaftaran LIKE '%$search%')";
$where_clause = implode(' AND ', $where);

$query = "SELECT id_siswa, id_pendaftaran, nama_lengkap, jurusan, asal_sekolah,
           tpa_selesai, tpa_tanggal, tpa_nilai_total, tpa_benar_verbal, tpa_benar_numerik, tpa_benar_logika,
           tpa_jumlah_soal_verbal, tpa_jumlah_soal_numerik, tpa_jumlah_soal_logika
           FROM siswa
           WHERE $where_clause
           ORDER BY tpa_tanggal DESC, nama_lengkap ASC";

$results = mysqli_query($conn, $query);

// Statistik - semua siswa yang punya id_pendaftaran
$stats_query = mysqli_query($conn, "SELECT
    COUNT(CASE WHEN tpa_selesai = 1 THEN 1 END) as sudah_tpa,
    COUNT(CASE WHEN tpa_selesai != 1 OR tpa_selesai IS NULL THEN 1 END) as belum_tpa,
    AVG(CASE WHEN tpa_selesai = 1 THEN tpa_nilai_total END) as rata_nilai,
    MAX(CASE WHEN tpa_selesai = 1 THEN tpa_nilai_total END) as max_nilai,
    MIN(CASE WHEN tpa_selesai = 1 THEN tpa_nilai_total END) as min_nilai
    FROM siswa WHERE id_pendaftaran IS NOT NULL AND id_pendaftaran != ''");
$stats = mysqli_fetch_assoc($stats_query);

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil TPA Siswa | SPMB SMK Pasundan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Space Grotesk', sans-serif; }
        .font-sora { font-family: 'Sora', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur-xl border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <?php
                $back_page = match($_SESSION['role']) {
                    'pendaftaran' => '../pendaftaran/index.php',
                    'tu' => '../tu/index.php',
                    'database' => '../database/index.php',
                    'user' => '../database/index.php',
                    'superuser' => '../database/index.php',
                    default => '../../login.php'
                };
                ?>
                <a href="<?= $back_page ?>" class="text-slate-400 hover:text-white transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-brain text-white"></i>
                    </div>
                    <div>
                        <div class="font-sora font-bold">Manajemen TPA</div>
                        <div class="text-xs text-slate-400">
                            Login sebagai: <span class="text-indigo-400 font-bold uppercase"><?= $_SESSION['role'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="force_login.php" class="px-3 py-1.5 bg-purple-500/20 border border-purple-500/30 rounded-lg text-xs font-bold text-purple-400 hover:bg-purple-500/30 transition">
                    <i class="fas fa-user-secret mr-1"></i>Force Login
                </a>
                <span class="px-3 py-1 bg-emerald-500/20 border border-emerald-500/30 rounded-lg text-xs font-bold text-emerald-400">
                    <?= $stats['sudah_tpa'] ?? 0 ?> Sudah TPA
                </span>
                <span class="px-3 py-1 bg-amber-500/20 border border-amber-500/30 rounded-lg text-xs font-bold text-amber-400">
                    <?= $stats['belum_tpa'] ?? 0 ?> Belum TPA
                </span>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
        <!-- Success Message -->
        <?php if ($success_msg): ?>
        <div class="mb-6 p-4 bg-emerald-500/20 border border-emerald-500/30 rounded-xl text-emerald-400 flex items-center gap-3">
            <i class="fas fa-check-circle text-xl"></i>
            <?= htmlspecialchars($success_msg) ?>
        </div>
        <?php endif; ?>

        <!-- Statistik Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-slate-800/50 border border-white/10 rounded-xl p-4">
                <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Sudah TPA</div>
                <div class="text-2xl font-bold text-emerald-400"><?= $stats['sudah_tpa'] ?? 0 ?></div>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-xl p-4">
                <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Belum TPA</div>
                <div class="text-2xl font-bold text-amber-400"><?= $stats['belum_tpa'] ?? 0 ?></div>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-xl p-4">
                <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Rata-rata Nilai</div>
                <div class="text-2xl font-bold text-indigo-400"><?= round($stats['rata_nilai'] ?? 0) ?></div>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-xl p-4">
                <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Range Nilai</div>
                <div class="text-2xl font-bold text-purple-400"><?= $stats['min_nilai'] ?? 0 ?> - <?= $stats['max_nilai'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Filter & Actions -->
        <div class="bg-slate-800/50 border border-white/10 rounded-xl p-4 mb-6">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-end justify-between">
                <form method="GET" class="flex flex-wrap gap-4 items-end flex-1">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs text-slate-400 mb-1">
                            <i class="fas fa-search mr-1"></i>Cari Nama / ID
                        </label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                               placeholder="Ketik nama atau ID..."
                               class="w-full bg-slate-700 border border-white/10 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                    </div>

                    <!-- Jurusan -->
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Jurusan</label>
                        <select name="jurusan" class="bg-slate-700 border border-white/10 rounded-lg px-3 py-2 text-sm">
                            <option value="">Semua</option>
                            <?php foreach ($jurusan_list as $kode => $nama): ?>
                            <option value="<?= $nama ?>" <?= $filter_jurusan === $nama ? 'selected' : '' ?>><?= $kode ?> - <?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Status TPA</label>
                        <select name="status" class="bg-slate-700 border border-white/10 rounded-lg px-3 py-2 text-sm">
                            <option value="">Semua</option>
                            <option value="sudah" <?= $filter_status === 'sudah' ? 'selected' : '' ?>>Sudah TPA</option>
                            <option value="belum" <?= $filter_status === 'belum' ? 'selected' : '' ?>>Belum TPA</option>
                        </select>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 rounded-lg text-sm font-bold transition">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <a href="index.php" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-bold transition">
                        Reset
                    </a>
                </form>

                <!-- Bulk Actions -->
                <?php if (in_array($_SESSION['role'], ['superuser', 'pendaftaran'])): ?>
                <form method="POST" onsubmit="return confirm('PERINGATAN: Semua data TPA akan dihapus!\n\nApakah Anda yakin ingin reset semua TPA?');">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" name="bulk_reset" class="px-4 py-2 bg-red-500/20 border border-red-500/30 hover:bg-red-500/30 rounded-lg text-sm font-bold text-red-400 transition">
                        <i class="fas fa-trash mr-1"></i> Reset Semua TPA
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-slate-800/50 border border-white/10 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-900/50 border-b border-white/10">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">No</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Jurusan</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-indigo-400 uppercase tracking-wider">Verbal</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-emerald-400 uppercase tracking-wider">Numerik</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-amber-400 uppercase tracking-wider">Logika</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($results)):
                            $nilai_verbal = $row['tpa_jumlah_soal_verbal'] > 0 && $row['tpa_benar_verbal'] !== null
                                ? round(($row['tpa_benar_verbal'] / $row['tpa_jumlah_soal_verbal']) * 100) : '-';
                            $nilai_numerik = $row['tpa_jumlah_soal_numerik'] > 0 && $row['tpa_benar_numerik'] !== null
                                ? round(($row['tpa_benar_numerik'] / $row['tpa_jumlah_soal_numerik']) * 100) : '-';
                            $nilai_logika = $row['tpa_jumlah_soal_logika'] > 0 && $row['tpa_benar_logika'] !== null
                                ? round(($row['tpa_benar_logika'] / $row['tpa_jumlah_soal_logika']) * 100) : '-';

                            // Color based on score
                            if ($row['tpa_nilai_total'] >= 80) $color = 'text-emerald-400';
                            elseif ($row['tpa_nilai_total'] >= 65) $color = 'text-blue-400';
                            elseif ($row['tpa_nilai_total'] >= 50) $color = 'text-amber-400';
                            else $color = 'text-rose-400';
                        ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 py-3 text-slate-400"><?= $no++ ?></td>
                            <td class="px-4 py-3">
                                <div class="font-bold"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($row['id_pendaftaran']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($row['jurusan']) ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($nilai_verbal !== '-'): ?>
                                <span class="px-2 py-1 bg-indigo-500/20 rounded text-xs font-bold text-indigo-400">
                                    <?= $nilai_verbal ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-600">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($nilai_numerik !== '-'): ?>
                                <span class="px-2 py-1 bg-emerald-500/20 rounded text-xs font-bold text-emerald-400">
                                    <?= $nilai_numerik ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-600">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($nilai_logika !== '-'): ?>
                                <span class="px-2 py-1 bg-amber-500/20 rounded text-xs font-bold text-amber-400">
                                    <?= $nilai_logika ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-600">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($row['tpa_selesai'] == 1): ?>
                                <span class="px-3 py-1 bg-white/10 rounded text-sm font-bold <?= $color ?>">
                                    <?= $row['tpa_nilai_total'] ?>
                                </span>
                                <?php else: ?>
                                <span class="px-3 py-1 bg-slate-700/50 rounded text-xs text-slate-500">
                                    <i class="fas fa-clock"></i> Belum
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-400 text-xs">
                                <?= $row['tpa_tanggal'] ? date('d/m/Y H:i', strtotime($row['tpa_tanggal'])) : '-' ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($row['tpa_selesai'] == 1): ?>
                                    <a href="card.php?id=<?= $row['id_siswa'] ?>" target="_blank"
                                       class="px-2 py-1.5 bg-indigo-500/20 border border-indigo-500/30 rounded text-xs font-bold text-indigo-400 hover:bg-indigo-500/30 transition" title="Lihat Card">
                                        <i class="fas fa-medal"></i>
                                    </a>
                                    <a href="hasil.php?id=<?= $row['id_siswa'] ?>" target="_blank"
                                       class="px-2 py-1.5 bg-blue-500/20 border border-blue-500/30 rounded text-xs font-bold text-blue-400 hover:bg-blue-500/30 transition" title="Detail Hasil">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>

                                    <?php if (in_array($_SESSION['role'], ['superuser', 'pendaftaran'])): ?>
                                    <!-- Reset Button (hanya superuser & pendaftaran) -->
                                    <form method="POST" onsubmit="return confirm('Reset TPA untuk <?= htmlspecialchars($row['nama_lengkap']) ?>?\n\nJawaban dan nilai akan dihapus.');" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="id_siswa" value="<?= $row['id_siswa'] ?>">
                                        <button type="submit" name="reset_tpa"
                                                class="px-2 py-1.5 bg-red-500/20 border border-red-500/30 rounded text-xs font-bold text-red-400 hover:bg-red-500/30 transition" title="Reset TPA">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <!-- Belum TPA - Force Login -->
                                    <?php if (in_array($_SESSION['role'], ['superuser', 'pendaftaran', 'user'])): ?>
                                    <a href="index.php?force_login=<?= $row['id_siswa'] ?>"
                                       class="px-2 py-1.5 bg-emerald-500/20 border border-emerald-500/30 rounded text-xs font-bold text-emerald-400 hover:bg-emerald-500/30 transition" title="Buka TPA">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($results) == 0): ?>
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                <i class="fas fa-inbox text-4xl mb-3"></i>
                                <p>Belum ada data siswa dengan kriteria tersebut</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info -->
        <div class="mt-6 p-4 bg-slate-800/50 border border-white/10 rounded-xl text-sm text-slate-400">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-info-circle text-indigo-400"></i>
                <span class="font-bold text-white">Informasi</span>
            </div>
            <ul class="space-y-1 text-xs">
                <li>• <strong>Semua siswa</strong> yang memiliki ID Pendaftaran dapat mengikuti TPA</li>
                <li>• <strong>Reset TPA</strong> hanya bisa dilakukan oleh role <strong>superuser</strong> dan <strong>pendaftaran</strong></li>
                <li>• Siswa yang di-reset bisa login ulang dan mengerjakan TPA kembali</li>
                <li>• Link "Buka TPA" membuka halaman TPA siswa di tab baru</li>
            </ul>
        </div>
    </main>
</body>
</html>
