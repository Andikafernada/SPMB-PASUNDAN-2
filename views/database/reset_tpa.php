<?php
/**
 * RESET TPA - Reset Nilai TPA Siswa
 * SMK Pasundan 2 Bandung
 * Halaman untuk admin database me-reset nilai TPA siswa
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../config.php';

// FIX: Definisikan daftar jurusan dengan aman
$jurusan_list = [
    'TPM' => 'Teknik Pemesinan',
    'TKR' => 'Teknik Kendaraan Ringan',
    'TSM' => 'Teknik Sepeda Motor',
    'TKJ' => 'Teknik Komputer & Jaringan',
    'TAV' => 'Teknik Audio Video'
];

// Proteksi session
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database', 'superuser'])) {
    header("Location: ../../login.php");
    exit();
}

// Handle Reset Single TPA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_single'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (function_exists('verify_csrf_token') && verify_csrf_token($csrf)) {
        $id_siswa = (int)($_POST['id_siswa'] ?? 0);
        if ($id_siswa > 0) {
            $q = mysqli_query($conn, "SELECT nama_lengkap FROM siswa WHERE id_siswa = $id_siswa");
            $nama = $q ? (mysqli_fetch_assoc($q)['nama_lengkap'] ?? 'Unknown') : 'Unknown';

            mysqli_begin_transaction($conn);
            try {
                // Reset TPA siswa
                mysqli_query($conn, "UPDATE siswa SET
                    tpa_selesai = 0, tpa_tanggal = NULL, tpa_nilai_total = NULL,
                    tpa_benar_verbal = NULL, tpa_benar_numerik = NULL, tpa_benar_logika = NULL
                    WHERE id_siswa = $id_siswa");

                // Hapus jawaban TPA
                mysqli_query($conn, "DELETE FROM tpa_jawaban WHERE id_siswa = $id_siswa");

                // Log aktivitas
                $admin_id = $_SESSION['id_user'] ?? 0;
                $log_exists = mysqli_query($conn, "SHOW TABLES LIKE 'audit_log'");
                if ($log_exists && mysqli_num_rows($log_exists) > 0) {
                    $nama_esc = mysqli_real_escape_string($conn, $nama);
                    mysqli_query($conn, "INSERT INTO audit_log (user_id, action, details, created_at)
                        VALUES ($admin_id, 'RESET_TPA', 'Reset TPA siswa: $nama_esc (ID: $id_siswa)', NOW())");
                }

                mysqli_commit($conn);
                $_SESSION['success_message'] = "TPA siswa <strong>" . htmlspecialchars($nama) . "</strong> berhasil di-reset!";
            } catch (Exception $e) {
                mysqli_rollback($conn);
            }
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
}

// Handle Bulk Reset by Jurusan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_reset_jurusan'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (function_exists('verify_csrf_token') && verify_csrf_token($csrf)) {
        $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan'] ?? '');

        mysqli_begin_transaction($conn);
        try {
            $cek = mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE tpa_selesai = 1 AND jurusan = '$jurusan'");
            $count = $cek ? mysqli_num_rows($cek) : 0;

            mysqli_query($conn, "UPDATE siswa SET
                tpa_selesai = 0, tpa_tanggal = NULL, tpa_nilai_total = NULL,
                tpa_benar_verbal = NULL, tpa_benar_numerik = NULL, tpa_benar_logika = NULL
                WHERE tpa_selesai = 1 AND jurusan = '$jurusan'");

            mysqli_query($conn, "DELETE tj FROM tpa_jawaban tj
                INNER JOIN siswa s ON tj.id_siswa = s.id_siswa
                WHERE s.jurusan = '$jurusan'");

            $admin_id = $_SESSION['id_user'] ?? 0;
            $log_exists = mysqli_query($conn, "SHOW TABLES LIKE 'audit_log'");
            if ($log_exists && mysqli_num_rows($log_exists) > 0) {
                mysqli_query($conn, "INSERT INTO audit_log (user_id, action, details, created_at)
                    VALUES ($admin_id, 'RESET_TPA_BULK', 'Reset TPA $count siswa jurusan $jurusan', NOW())");
            }
            
            mysqli_commit($conn);
            $_SESSION['success_message'] = "<strong>$count siswa</strong> jurusan <strong>$jurusan</strong> berhasil di-reset!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
}

// Handle Bulk Reset Semua
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_reset_all'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (function_exists('verify_csrf_token') && verify_csrf_token($csrf)) {
        mysqli_begin_transaction($conn);
        try {
            $cek = mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE tpa_selesai = 1");
            $count = $cek ? mysqli_num_rows($cek) : 0;

            mysqli_query($conn, "UPDATE siswa SET
                tpa_selesai = 0, tpa_tanggal = NULL, tpa_nilai_total = NULL,
                tpa_benar_verbal = NULL, tpa_benar_numerik = NULL, tpa_benar_logika = NULL
                WHERE tpa_selesai = 1");

            mysqli_query($conn, "DELETE FROM tpa_jawaban");

            $admin_id = $_SESSION['id_user'] ?? 0;
            $log_exists = mysqli_query($conn, "SHOW TABLES LIKE 'audit_log'");
            if ($log_exists && mysqli_num_rows($log_exists) > 0) {
                mysqli_query($conn, "INSERT INTO audit_log (user_id, action, details, created_at)
                    VALUES ($admin_id, 'RESET_TPA_ALL', 'Reset semua TPA ($count siswa)', NOW())");
            }

            mysqli_commit($conn);
            $_SESSION['success_message'] = "<strong>Semua TPA ($count siswa)</strong> berhasil di-reset!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
}

// Tampilkan success message jika ada
$success_msg = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Ambil filter
$filter_jurusan = isset($_GET['jurusan']) ? mysqli_real_escape_string($conn, $_GET['jurusan']) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query
$where = ["id_pendaftaran IS NOT NULL AND id_pendaftaran != ''"];
if ($filter_jurusan) $where[] = "jurusan = '$filter_jurusan'";
if ($filter_status === 'sudah') $where[] = "tpa_selesai = 1";
if ($filter_status === 'belum') $where[] = "(tpa_selesai != 1 OR tpa_selesai IS NULL)";
if ($search) $where[] = "(nama_lengkap LIKE '%$search%' OR id_pendaftaran LIKE '%$search%')";
$where_clause = implode(' AND ', $where);

$query = "SELECT id_siswa, id_pendaftaran, nama_lengkap, jurusan, kelas, asal_sekolah,
           tpa_selesai, tpa_tanggal, tpa_nilai_total, tpa_benar_verbal, tpa_benar_numerik, tpa_benar_logika,
           tpa_jumlah_soal_verbal, tpa_jumlah_soal_numerik, tpa_jumlah_soal_logika
           FROM siswa
           WHERE $where_clause
           ORDER BY tpa_tanggal DESC, nama_lengkap ASC";
$results = mysqli_query($conn, $query);

// Statistik Keseluruhan
$stats_query = mysqli_query($conn, "SELECT
    COUNT(CASE WHEN tpa_selesai = 1 THEN 1 END) as sudah_tpa,
    COUNT(CASE WHEN (tpa_selesai != 1 OR tpa_selesai IS NULL) THEN 1 END) as belum_tpa,
    AVG(CASE WHEN tpa_selesai = 1 THEN tpa_nilai_total END) as rata_nilai
    FROM siswa WHERE id_pendaftaran IS NOT NULL AND id_pendaftaran != ''");
$stats = $stats_query ? mysqli_fetch_assoc($stats_query) : ['sudah_tpa' => 0, 'belum_tpa' => 0, 'rata_nilai' => 0];

// Statistik per jurusan
$stats_by_jur = [];
foreach ($jurusan_list as $kode => $nama) {
    $q = mysqli_query($conn, "SELECT
        COUNT(CASE WHEN tpa_selesai = 1 THEN 1 END) as sudah,
        COUNT(CASE WHEN (tpa_selesai != 1 OR tpa_selesai IS NULL) THEN 1 END) as belum
        FROM siswa WHERE id_pendaftaran IS NOT NULL AND id_pendaftaran != '' AND jurusan = '$kode'");
    $stats_by_jur[$kode] = $q ? mysqli_fetch_assoc($q) : ['sudah' => 0, 'belum' => 0];
}

$csrf_token = function_exists('generate_csrf_token') ? generate_csrf_token() : md5(uniqid());
$admin_nama = $_SESSION['nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset TPA Siswa | SMK Pasundan 2</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ['Plus Jakarta Sans', 'sans-serif'],
              outfit: ['Outfit', 'sans-serif'],
            }
          }
        }
      }
    </script>

    <style>
        body { background-color: #f8fafc; }
        @keyframes fade-in { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
    </style>
</head>
<body class="text-slate-700 h-screen overflow-hidden flex bg-[url('data:image/svg+xml,%3Csvg width=%2760%27%20height=%2760%27%20viewBox=%270%200%2060%2060%27%20xmlns=%27http://www.w3.org/2000/svg%27%3E%3Cg%20fill=%27none%27%20fill-rule=%27evenodd%27%3E%3Cg%20fill=%27%234f46e5%27%20fill-opacity=%270.03%27%3E%3Cpath%20d=%27M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%27/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] selection:bg-indigo-100 selection:text-indigo-900">

    <aside class="w-72 bg-white border-r border-slate-200 flex flex-col justify-between p-6 z-20 shadow-[4px_0_24px_rgba(0,0,0,0.02)] hidden md:flex relative">
        <div>
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-700 rounded-xl flex items-center justify-center shadow-md transform -rotate-3">
                    <span class="text-white font-outfit font-black text-sm">P2</span>
                </div>
                <div>
                    <h2 class="font-outfit font-bold text-slate-900 text-lg leading-tight">SMK Pasundan 2</h2>
                    <p class="text-[10px] font-bold text-red-600 uppercase tracking-widest">Reset TPA</p>
                </div>
            </div>

            <nav class="space-y-1.5">
                <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-all">
                    <i class="fas fa-database w-5 text-center"></i> <span>Data Pendaftar</span>
                </a>
                <a href="pengkelasan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-all">
                    <i class="fas fa-layer-group w-5 text-center"></i> <span>Pengkelasan</span>
                </a>
                <a href="../analisis/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-purple-600 hover:text-purple-700 hover:bg-purple-50 transition-all border border-transparent">
                    <i class="fas fa-chart-pie w-5 text-center"></i> <span>Analisis Lanjut</span>
                </a>
                <a href="wa_manager.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 transition-all border border-transparent">
                    <i class="fab fa-whatsapp w-5 text-center text-lg"></i> <span>WA Manager</span>
                </a>
                <a href="reset_tpa.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm bg-red-500 text-white shadow-md shadow-red-200 transition-all">
                    <i class="fas fa-redo w-5 text-center"></i> <span>Reset TPA</span>
                </a>
            </nav>
        </div>

        <div class="space-y-4 z-10 relative mb-6">
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 text-center">
                <div class="w-8 h-8 bg-red-100 text-red-600 rounded-full mx-auto flex items-center justify-center mb-2 shadow-sm">
                    <i class="fas fa-user-shield text-xs"></i>
                </div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Otoritas Admin</p>
                <p class="text-xs font-bold text-slate-700 truncate"><?= htmlspecialchars($admin_nama) ?></p>
            </div>
            <a href="../../logout.php" class="flex items-center justify-center gap-2 w-full py-3.5 rounded-xl text-xs font-bold text-slate-500 hover:text-red-600 hover:bg-red-50 border border-transparent transition-all group">
                <i class="fas fa-sign-out-alt group-hover:text-red-500"></i> Keluar Dasbor
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <div class="p-6 md:p-8 lg:p-10 pb-4 flex-shrink-0 animate-fade-in">
            <header class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 border border-red-200 rounded-full mb-3">
                        <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                        <span class="text-[10px] font-bold text-red-700 uppercase tracking-widest">Reset TPA</span>
                    </div>
                    <h1 class="text-3xl lg:text-4xl font-outfit font-black text-slate-900 tracking-tight mb-1">Reset Nilai TPA</h1>
                    <p class="text-sm font-medium text-slate-500">Reset nilai TPA siswa yang error dan mulai kembali.</p>
                </div>
            </header>

            <div class="grid grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex flex-col shadow-sm">
                    <span class="text-[9px] font-extrabold text-emerald-600 uppercase tracking-widest mb-1 flex items-center gap-1"><i class="fas fa-check-circle"></i> Sudah TPA</span>
                    <span class="text-2xl font-outfit font-black text-emerald-700"><?= $stats['sudah_tpa'] ?? 0 ?></span>
                </div>
                <div class="bg-amber-50 border border-amber-100 p-4 rounded-2xl flex flex-col shadow-sm">
                    <span class="text-[9px] font-extrabold text-amber-600 uppercase tracking-widest mb-1 flex items-center gap-1"><i class="fas fa-clock"></i> Belum TPA</span>
                    <span class="text-2xl font-outfit font-black text-amber-700"><?= $stats['belum_tpa'] ?? 0 ?></span>
                </div>
                <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-2xl flex flex-col shadow-sm">
                    <span class="text-[9px] font-extrabold text-indigo-600 uppercase tracking-widest mb-1 flex items-center gap-1"><i class="fas fa-chart-line"></i> Rata-rata</span>
                    <span class="text-2xl font-outfit font-black text-indigo-700"><?= round($stats['rata_nilai'] ?? 0) ?></span>
                </div>
                <div class="col-span-3 bg-gradient-to-br from-red-500 to-orange-500 border border-red-400 p-4 rounded-2xl flex flex-col shadow-md text-white">
                    <span class="text-[9px] font-extrabold text-red-200 uppercase tracking-widest mb-1 flex items-center gap-1"><i class="fas fa-exclamation-triangle"></i> Total Reset</span>
                    <span class="text-2xl font-outfit font-black"><?= ($stats['sudah_tpa'] ?? 0) + ($stats['belum_tpa'] ?? 0) ?></span>
                </div>
            </div>

            <div class="bg-white border border-red-200 rounded-2xl p-6 mb-6 shadow-sm">
                <h3 class="font-outfit font-bold text-slate-800 text-lg mb-4 flex items-center gap-2">
                    <i class="fas fa-bolt text-red-500"></i> Aksi Massal Reset TPA
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <form method="POST" class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <h4 class="font-bold text-slate-700 text-sm mb-3"><i class="fas fa-layer-group mr-2 text-purple-500"></i>Reset per Jurusan</h4>
                        <div class="flex gap-3">
                            <select name="jurusan" required class="flex-1 bg-white border border-slate-300 text-slate-700 text-sm rounded-lg px-3 py-2.5 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
                                <option value="">-- Pilih Jurusan --</option>
                                <?php foreach ($jurusan_list as $kode => $nama): ?>
                                <option value="<?= $kode ?>"><?= $kode ?> - <?= $nama ?> (<?= $stats_by_jur[$kode]['sudah'] ?? 0 ?> TPA)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="bulk_reset_jurusan" onclick="return confirm('PERINGATAN: Semua TPA di jurusan ini akan di-reset!\n\nLanjutkan?');" class="px-4 py-2.5 bg-purple-500 hover:bg-purple-600 text-white text-sm font-bold rounded-lg transition shadow-sm">
                                <i class="fas fa-redo mr-1"></i> Reset
                            </button>
                        </div>
                    </form>

                    <form method="POST" class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <h4 class="font-bold text-slate-700 text-sm mb-3"><i class="fas fa-exclamation-circle mr-2 text-red-500"></i>Reset Semua TPA</h4>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Hapus semua nilai TPA dan jawaban siswa</span>
                            <button type="submit" name="bulk_reset_all" onclick="return confirm('PERINGATAN SEVERE!\n\nSemua data TPA akan dihapus permanen!\n\nApakah Anda YAKIN?');" class="px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-bold rounded-lg transition shadow-sm">
                                <i class="fas fa-trash mr-1"></i> Reset All
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between">

                <div class="flex flex-wrap items-center gap-2 bg-white border border-slate-200 p-1.5 rounded-xl shadow-sm">
                    <a href="?status=" class="px-4 py-2 rounded-lg text-[10px] font-bold transition-all <?= !$filter_status ? 'bg-slate-800 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' ?>">SEMUA</a>
                    <a href="?status=sudah" class="px-4 py-2 rounded-lg text-[10px] font-bold transition-all <?= $filter_status=='sudah' ? 'bg-emerald-500 text-white shadow-md' : 'text-slate-500 hover:bg-emerald-50 hover:text-emerald-600' ?>"><i class="fas fa-check-circle mr-1"></i> SUDAH TPA</a>
                    <a href="?status=belum" class="px-4 py-2 rounded-lg text-[10px] font-bold transition-all <?= $filter_status=='belum' ? 'bg-amber-500 text-white shadow-md' : 'text-slate-500 hover:bg-amber-50 hover:text-amber-600' ?>"><i class="fas fa-clock mr-1"></i> BELUM TPA</a>
                </div>

                <div class="flex items-center gap-3 w-full xl:w-auto">
                    <select onchange="window.location.href='?status=<?= $filter_status ?>&search=<?= urlencode($search) ?>&jurusan='+this.value" class="bg-white border border-slate-200 text-indigo-600 text-xs font-bold rounded-xl px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 appearance-none cursor-pointer shadow-sm">
                        <option value="">SEMUA JURUSAN</option>
                        <?php foreach ($jurusan_list as $kode => $nama): ?>
                        <option value="<?= $kode ?>" <?= $filter_jurusan==$kode?'selected':'' ?>><?= $kode ?> (<?= $stats_by_jur[$kode]['sudah'] ?? 0 ?>)</option>
                        <?php endforeach; ?>
                    </select>

                    <form method="GET" action="" class="relative flex-1 xl:w-64">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
                        <input type="hidden" name="jurusan" value="<?= htmlspecialchars($filter_jurusan) ?>">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama atau ID..." class="w-full bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-xl pl-10 pr-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all shadow-sm">
                        <i class="fas fa-search absolute left-3.5 top-3.5 text-slate-400"></i>
                    </form>
                </div>
            </div>
        </div>

        <div class="px-6 md:px-8 lg:px-10 pb-8 flex-1 overflow-hidden flex flex-col animate-fade-in">
            <div class="bg-white border border-slate-200 rounded-[2rem] flex-1 flex flex-col overflow-hidden shadow-xl shadow-slate-200/50">

                <div class="grid grid-cols-[50px_2fr_1fr_1fr_1fr_1fr_140px] gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <div class="text-center">No</div>
                    <div>Data Siswa</div>
                    <div>Jurusan</div>
                    <div>Status TPA</div>
                    <div class="text-center">Nilai Total</div>
                    <div class="text-center">Tanggal</div>
                    <div class="text-right pr-2">Aksi</div>
                </div>

                <div class="overflow-y-auto custom-scroll flex-1 p-2">
                    <div class="divide-y divide-slate-100">
                        <?php if($results && mysqli_num_rows($results)>0): $n=1; while($s=mysqli_fetch_assoc($results)):
                            $nilai_total = $s['tpa_nilai_total'] ?? '-';
                            $tanggal = $s['tpa_tanggal'] ? date('d/m/Y H:i', strtotime($s['tpa_tanggal'])) : '-';
                        ?>
                        <div class="grid grid-cols-[50px_2fr_1fr_1fr_1fr_1fr_140px] gap-4 px-4 py-3 items-center hover:bg-slate-50/80 transition-colors group rounded-xl">
                            <div class="text-center text-xs font-bold text-slate-400"><?= $n++ ?></div>

                            <div>
                                <div class="font-bold text-slate-800 text-sm uppercase truncate"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                                <div class="text-[10px] font-mono font-bold text-indigo-500 mt-1"><?= htmlspecialchars($s['id_pendaftaran'] ?? '') ?></div>
                                <?php if(!empty($s['kelas'])): ?>
                                <span class="inline-block mt-1 px-2 py-0.5 bg-purple-50 border border-purple-100 text-purple-600 text-[9px] font-black uppercase rounded">Kls <?= htmlspecialchars($s['kelas']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div>
                                <span class="inline-block px-2.5 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 text-[9px] font-black uppercase tracking-wider rounded-md"><?= htmlspecialchars($s['jurusan']) ?></span>
                            </div>

                            <div>
                                <?php if($s['tpa_selesai'] == 1): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 border border-emerald-200 text-emerald-600 text-[9px] font-black uppercase tracking-wider rounded-md">
                                        <i class="fas fa-check-circle"></i> Sudah TPA
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-600 text-[9px] font-black uppercase tracking-wider rounded-md">
                                        <i class="fas fa-clock"></i> Belum
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="text-center">
                                <?php if($nilai_total !== '-'): ?>
                                    <?php
                                    $color_class = 'bg-emerald-100 text-emerald-700';
                                    if ($nilai_total < 50) $color_class = 'bg-red-100 text-red-700';
                                    elseif ($nilai_total < 65) $color_class = 'bg-amber-100 text-amber-700';
                                    elseif ($nilai_total < 80) $color_class = 'bg-blue-100 text-blue-700';
                                    ?>
                                    <span class="inline-block px-3 py-1 <?= $color_class ?> text-xs font-black rounded-md"><?= htmlspecialchars($nilai_total) ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </div>

                            <div class="text-center text-[10px] text-slate-500">
                                <?= $tanggal ?>
                            </div>

                            <div class="flex items-center justify-end gap-1.5">
                                <?php if($s['tpa_selesai'] == 1): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="id_siswa" value="<?= $s['id_siswa'] ?>">
                                        <button type="submit" name="reset_single" onclick="return confirm('Reset TPA untuk:\n\n<?= addslashes($s['nama_lengkap']) ?>\n\nNilai dan jawaban akan dihapus. Siswa bisa mengerjakan TPA ulang.');" class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all shadow-sm" title="Reset TPA">
                                            <i class="fas fa-redo text-xs"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="w-9 h-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center" title="Belum TPA - Tidak perlu di-reset">
                                        <i class="fas fa-minus text-xs"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                        <div class="py-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 border border-slate-200 rounded-full flex items-center justify-center text-slate-300 text-2xl mx-auto mb-3 shadow-sm"><i class="fas fa-clipboard-list"></i></div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tidak ada data ditemukan</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Tampilkan success message dari URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1' && '<?= addslashes($success_msg) ?>') {
        Swal.fire({
            title: '<span class="text-2xl font-black text-emerald-600 tracking-tight">Berhasil!</span>',
            html: '<div class="text-slate-500 font-medium text-sm"><?= addslashes($success_msg) ?></div>',
            icon: 'success',
            iconColor: '#10b981',
            confirmButtonColor: '#4f46e5',
            background: '#ffffff', color: '#334155',
            customClass: {
                popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                confirmButton: 'font-bold tracking-widest text-xs px-8 py-3 rounded-xl shadow-md'
            }
        });
        // Hapus query string
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Hapus query string jika ada success di URL
    if (urlParams.has('success')) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    </script>
</body>
</html>
