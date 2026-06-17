<?php
/**
 * DATABASE MASTER - Admin Page
 * IP Restricted: Only accessible from internal network
 * Theme: Light, Clean, Modern SaaS Dashboard
 */
include '../../config.php';

// Check admin IP restriction
require_admin_ip();

// Check session
if(!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database','superuser','superuser1'])) {
    header("Location: ../../panitia/index.php"); exit();
}

// ===== STATISTIK =====
$total_semua    = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa"));
$total_du       = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE status_siswa = 'SUDAH DAFTAR ULANG'"));
$total_belum_du = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE status_siswa != 'SUDAH DAFTAR ULANG'"));
$total_lengkap  = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE nisn IS NOT NULL AND nisn != '' AND nik IS NOT NULL AND nik != '' AND jenis_kelamin IS NOT NULL AND jenis_kelamin != ''"));
$q_arsip        = mysqli_query($conn, "SELECT id_siswa FROM arsip_siswa");
$total_arsip    = $q_arsip ? mysqli_num_rows($q_arsip) : 0;

// Statistik untuk pendaftaran public
$total_public    = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE sumber_data = 'public'"));
$public_pending   = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE sumber_data = 'public' AND status_pendaftaran = 'pending'"));
$public_lunas     = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE sumber_data = 'public' AND status_pendaftaran = 'lunas'"));

// Statistik per jurusan
$stat_jurusan = mysqli_query($conn, "SELECT jurusan, COUNT(*) as jml FROM siswa GROUP BY jurusan ORDER BY jml DESC");

// ===== FILTER VIEW =====
$view   = isset($_GET['view'])   ? $_GET['view']   : 'SEMUA';
$search = isset($_GET['cari'])   ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
$jur    = isset($_GET['jur'])    ? mysqli_real_escape_string($conn, $_GET['jur'])  : '';

$where = "WHERE 1=1";
if($search) $where .= " AND (nama_lengkap LIKE '%$search%' OR id_pendaftaran LIKE '%$search%' OR asal_sekolah LIKE '%$search%')";
if($jur)    $where .= " AND jurusan = '$jur'";

if($view == 'LENGKAPI') {
    $where .= " AND (nisn IS NULL OR nisn='' OR nik IS NULL OR nik='' OR jenis_kelamin IS NULL OR jenis_kelamin='')";
    $query_tbl = "SELECT * FROM siswa $where ORDER BY id_siswa DESC";
} elseif($view == 'DU') {
    $where .= " AND status_siswa = 'SUDAH DAFTAR ULANG'";
    $query_tbl = "SELECT * FROM siswa $where ORDER BY id_siswa DESC";
} elseif($view == 'PUBLIC') {
    $where .= " AND sumber_data = 'public'";
    $query_tbl = "SELECT * FROM siswa $where ORDER BY id_siswa DESC";
} elseif($view == 'ARSIP') {
    $query_tbl = "SELECT * FROM arsip_siswa ORDER BY id_siswa DESC";
} else {
    $query_tbl = "SELECT * FROM siswa $where ORDER BY id_siswa DESC";
}
$res_siswa = mysqli_query($conn, $query_tbl);

// Notif dari URL
$status_msg = isset($_GET['status']) ? $_GET['status'] : '';
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Data & Analisis | SMK Pasundan 2</title>
    
    <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/quick-wins.css" rel="stylesheet">
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

        /* Animasi Masuk */
        @keyframes fade-in { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        /* Custom Scrollbar */
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
    </style>
</head>
<body class="text-slate-700 h-screen overflow-hidden flex bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%234f46e5\' fill-opacity=\'0.03\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] selection:bg-indigo-100 selection:text-indigo-900">

    <aside class="w-72 bg-white border-r border-slate-200 flex flex-col justify-between p-6 z-20 shadow-[4px_0_24px_rgba(0,0,0,0.02)] hidden md:flex relative">
        <div>
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-md transform -rotate-3">
                    <span class="text-white font-outfit font-black text-sm">P2</span>
                </div>
                <div>
                    <h2 class="font-outfit font-bold text-slate-900 text-lg leading-tight">SMK Pasundan 2</h2>
                    <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">Tim Database</p>
                </div>
            </div>

            <nav class="space-y-1.5">
                <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm bg-indigo-600 text-white shadow-md shadow-indigo-200 transition-all">
                    <i class="fas fa-database w-5 text-center"></i> <span>Data Pendaftar</span>
                </a>
                <a href="pengkelasan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-all">
                    <i class="fas fa-layer-group w-5 text-center"></i> <span>Pengkelasan</span>
                </a>
                <a href="reset_tpa.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-red-600 hover:text-red-700 hover:bg-red-50 transition-all">
                    <i class="fas fa-redo w-5 text-center"></i> <span>Reset TPA</span>
                </a>
                <a href="../analisis/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-purple-600 hover:text-purple-700 hover:bg-purple-50 transition-all border border-transparent">
                    <i class="fas fa-chart-pie w-5 text-center"></i> <span>Analisis Lanjut</span>
                </a>
                <a href="wa_manager.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 transition-all border border-transparent">
                    <i class="fab fa-whatsapp w-5 text-center text-lg"></i> <span>WA Manager</span>
                </a>
                <a href="wa_broadcast.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-teal-600 hover:text-teal-700 hover:bg-teal-50 transition-all border border-transparent">
                    <i class="fas fa-bullhorn w-5 text-center"></i> <span>Broadcast WA</span>
                </a>
            </nav>
        </div>

        <div class="space-y-4 z-10 relative mb-6">
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 text-center">
                <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full mx-auto flex items-center justify-center mb-2 shadow-sm">
                    <i class="fas fa-shield-alt text-xs"></i>
                </div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Otoritas Admin</p>
                <p class="text-xs font-bold text-slate-700 truncate"><?= htmlspecialchars($_SESSION['nama']) ?></p>
            </div>
            <a href="../../logout.php" class="flex items-center justify-center gap-2 w-full py-3.5 rounded-xl text-xs font-bold text-slate-500 hover:text-red-600 hover:bg-red-50 border border-transparent transition-all group">
                <i class="fas fa-sign-out-alt group-hover:text-red-500"></i> Keluar Dasbor
            </a>
        </div>

        <div class="absolute bottom-1 left-6 pointer-events-none z-0 flex flex-col items-center select-none">
            <div class="text-[9px] bg-slate-800 text-white font-bold px-1.5 py-0.5 rounded shadow-sm mb-1 animate-bounce">
                Awas kehapus! 💾
            </div>
            <div class="text-3xl transform -scale-x-100">🐈‍⬛</div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <div class="p-6 md:p-8 lg:p-10 pb-4 flex-shrink-0 animate-fade-in">

            <?php
            $breadcrumbs = [
                ['label' => 'Home', 'url' => '../../'],
                ['label' => 'Database', 'icon' => 'fas fa-database', 'active' => true]
            ];
            include '../../components/breadcrumb.php';
            ?>

            <header class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-100 border border-indigo-200 rounded-full mb-3">
                        <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                        <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-widest">Central Database</span>
                    </div>
                    <h1 class="text-3xl lg:text-4xl font-outfit font-black text-slate-900 tracking-tight mb-1">Master Data Siswa</h1>
                    <p class="text-sm font-medium text-slate-500">Pusat kendali pendaftar, otorisasi, dan sinkronisasi arsip.</p>
                </div>
            </header>

            <div class="grid grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="bg-white border border-slate-200 p-4 rounded-2xl flex flex-col shadow-sm group hover:border-indigo-300 transition-colors">
                    <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest mb-1">Total Siswa</span>
                    <span class="text-2xl font-outfit font-black text-slate-800"><?= $total_semua ?></span>
                </div>
                <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex flex-col shadow-sm group hover:border-emerald-300 transition-colors">
                    <span class="text-[9px] font-extrabold text-emerald-600 uppercase tracking-widest mb-1 flex items-center gap-1"><i class="fas fa-check-circle"></i> Sudah DU</span>
                    <span class="text-2xl font-outfit font-black text-emerald-700"><?= $total_du ?></span>
                </div>
                <div class="bg-amber-50 border border-amber-100 p-4 rounded-2xl flex flex-col shadow-sm group hover:border-amber-300 transition-colors">
                    <span class="text-[9px] font-extrabold text-amber-600 uppercase tracking-widest mb-1 flex items-center gap-1"><i class="fas fa-clock"></i> Belum DU</span>
                    <span class="text-2xl font-outfit font-black text-amber-700"><?= $total_belum_du ?></span>
                </div>
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl flex flex-col shadow-sm group hover:border-blue-300 transition-colors">
                    <span class="text-[9px] font-extrabold text-blue-600 uppercase tracking-widest mb-1 flex items-center gap-1"><i class="fas fa-file-alt"></i> Data Lengkap</span>
                    <span class="text-2xl font-outfit font-black text-blue-700"><?= $total_lengkap ?></span>
                </div>
                <div class="bg-red-50 border border-red-100 p-4 rounded-2xl flex flex-col shadow-sm group hover:border-red-300 transition-colors">
                    <span class="text-[9px] font-extrabold text-red-600 uppercase tracking-widest mb-1 flex items-center gap-1"><i class="fas fa-archive"></i> Arsip (Cabut)</span>
                    <span class="text-2xl font-outfit font-black text-red-700"><?= $total_arsip ?></span>
                </div>
                <div class="bg-gradient-to-br from-indigo-600 to-purple-600 border border-indigo-500 p-4 rounded-2xl flex flex-col shadow-md text-white">
                    <span class="text-[9px] font-extrabold text-indigo-200 uppercase tracking-widest mb-1 flex items-center gap-1"><i class="fas fa-percentage"></i> Tingkat Lengkap</span>
                    <span class="text-2xl font-outfit font-black"><?= $total_semua>0 ? round($total_lengkap/$total_semua*100) : 0 ?>%</span>
                </div>
            </div>

            <div class="flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between">
                
                <div class="flex flex-wrap items-center gap-2 bg-white border border-slate-200 p-1.5 rounded-xl shadow-sm">
                    <a href="?view=SEMUA&jur=<?= $jur ?>" class="px-4 py-2 rounded-lg text-[10px] font-bold transition-all <?= $view=='SEMUA' ? 'bg-slate-800 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' ?>">SEMUA</a>
                    
                    <a href="?view=LENGKAPI&jur=<?= $jur ?>" class="px-4 py-2 rounded-lg text-[10px] font-bold transition-all <?= $view=='LENGKAPI' ? 'bg-amber-500 text-white shadow-md' : 'text-slate-500 hover:bg-amber-50 hover:text-amber-600' ?>">PERLU DILENGKAPI</a>
                    
                    <a href="?view=DU&jur=<?= $jur ?>" class="px-4 py-2 rounded-lg text-[10px] font-bold transition-all <?= $view=='DU' ? 'bg-emerald-500 text-white shadow-md' : 'text-slate-500 hover:bg-emerald-50 hover:text-emerald-600' ?>">SUDAH DU</a>
                    
                    <a href="?view=PUBLIC" class="px-4 py-2 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1 <?= $view=='PUBLIC' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-600' ?>">PUBLIC <span class="bg-white/20 px-1.5 rounded-md"><?= $total_public ?></span></a>
                    
                    <a href="?view=ARSIP" class="px-4 py-2 rounded-lg text-[10px] font-bold transition-all <?= $view=='ARSIP' ? 'bg-red-500 text-white shadow-md' : 'text-slate-500 hover:bg-red-50 hover:text-red-600' ?>">ARSIP</a>
                </div>

                <div class="flex items-center gap-3 w-full xl:w-auto">
                    <select onchange="window.location.href='?view=<?= $view ?>&cari=<?= $search ?>&jur='+this.value" class="bg-white border border-slate-200 text-indigo-600 text-xs font-bold rounded-xl px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 appearance-none cursor-pointer shadow-sm">
                        <option value="">SEMUA JURUSAN</option>
                        <?php
                        $daftar_jurusan = ['TPM','TKR','TSM','TKJ','TAV'];
                        foreach($daftar_jurusan as $dj):
                            $qcek = mysqli_query($conn, "SELECT COUNT(*) as jml FROM siswa WHERE jurusan='$dj'");
                            $jml = $qcek ? mysqli_fetch_assoc($qcek)['jml'] : 0;
                        ?>
                        <option value="<?= $dj ?>" <?= $jur==$dj?'selected':'' ?>><?= $dj ?> (<?= $jml ?>)</option>
                        <?php endforeach; ?>
                    </select>

                    <a href="pengkelasan.php" class="hidden sm:flex items-center justify-center w-10 h-10 bg-indigo-50 text-indigo-600 text-sm rounded-xl border border-indigo-100 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Buat Kelas">
                        <i class="fas fa-layer-group"></i>
                    </a>
                    
                    <a href="export_gelombang_1.php" class="hidden sm:flex items-center justify-center w-10 h-10 bg-emerald-50 text-emerald-600 text-sm rounded-xl border border-emerald-100 hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Export ke Excel">
                        <i class="fas fa-file-excel"></i>
                    </a>

                    <form method="GET" action="" class="relative flex-1 xl:w-64">
                        <input type="hidden" name="view" value="<?= $view ?>">
                        <input type="hidden" name="jur" value="<?= $jur ?>">
                        <input type="text" name="cari" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama atau ID..." class="w-full bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-xl pl-10 pr-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all shadow-sm">
                        <i class="fas fa-search absolute left-3.5 top-3.5 text-slate-400"></i>
                    </form>
                </div>
            </div>
        </div>

        <div class="px-6 md:px-8 lg:px-10 pb-8 flex-1 overflow-hidden flex flex-col animate-fade-in">
            <div class="bg-white border border-slate-200 rounded-[2rem] flex-1 flex flex-col overflow-hidden shadow-xl shadow-slate-200/50">

                <div class="grid grid-cols-[50px_2.5fr_1.5fr_1.5fr_1.5fr_160px] gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <div class="text-center">No</div>
                    <div>Data Calon Siswa</div>
                    <div>Akademik</div>
                    <div>Status DU</div>
                    <div>Kelengkapan</div>
                    <div class="text-right pr-2">Aksi</div>
                </div>

                <div class="overflow-y-auto custom-scroll flex-1 p-2">
                    <div class="divide-y divide-slate-100">
                        <?php if($res_siswa && mysqli_num_rows($res_siswa)>0): $n=1; while($s=mysqli_fetch_assoc($res_siswa)):
                            $lengkap = (!empty($s['nisn']) && !empty($s['nik']) && !empty($s['jenis_kelamin']));
                            $is_du   = ($s['status_siswa'] == 'SUDAH DAFTAR ULANG');
                        ?>
                        <div class="grid grid-cols-[50px_2.5fr_1.5fr_1.5fr_1.5fr_160px] gap-4 px-4 py-3 items-center hover:bg-slate-50/80 transition-colors group rounded-xl">
                            <div class="text-center text-xs font-bold text-slate-400"><?= $n++ ?></div>

                            <div>
                                <div class="font-bold text-slate-800 text-sm uppercase truncate"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                                <div class="text-[10px] font-mono font-bold text-indigo-500 mt-1"><?= $s['id_pendaftaran'] ?: $s['kode_billing'] ?></div>
                                <?php if(!empty($s['jurusan_lama'])): ?>
                                <div class="text-[9px] font-bold text-amber-500 mt-1 flex items-center gap-1"><i class="fas fa-exchange-alt"></i> Ex. <?= $s['jurusan_lama'] ?></div>
                                <?php endif; ?>
                                <?php if(($s['sumber_data'] ?? '') === 'public'): ?>
                                <div class="text-[9px] font-bold text-purple-500 mt-1 flex items-center gap-1"><i class="fas fa-globe"></i> DAFTAR MANDIRI</div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <span class="inline-block px-2.5 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 text-[9px] font-black uppercase tracking-wider rounded-md mb-1.5"><?= $s['jurusan'] ?></span>
                                <?php if(!empty($s['kelas'])): ?>
                                <span class="inline-block ml-1 px-2 py-1 bg-purple-50 border border-purple-100 text-purple-600 text-[9px] font-black uppercase rounded-md">Kls <?= $s['kelas'] ?></span>
                                <?php endif; ?>
                                <div class="text-[10px] text-slate-500 font-medium truncate w-40" title="<?= htmlspecialchars($s['asal_sekolah']) ?>"><i class="fas fa-school opacity-50 mr-1"></i> <?= htmlspecialchars($s['asal_sekolah']) ?></div>
                            </div>

                            <div>
                                <?php if($is_du): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 border border-emerald-200 text-emerald-600 text-[9px] font-black uppercase tracking-wider rounded-md"><i class="fas fa-check-circle"></i> Sudah DU</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 border border-slate-200 text-slate-500 text-[9px] font-black uppercase tracking-wider rounded-md"><i class="fas fa-clock"></i> Belum DU</span>
                                <?php endif; ?>
                                <?php if(($s['sumber_data'] ?? '') === 'public'): ?>
                                    <?php
                                    $pendaftaran_status = $s['status_pendaftaran'] ?? 'pending';
                                    $status_class = match($pendaftaran_status) {
                                        'lunas' => 'bg-emerald-50 border-emerald-200 text-emerald-600',
                                        'ditolak' => 'bg-red-50 border-red-200 text-red-600',
                                        default => 'bg-amber-50 border-amber-200 text-amber-600'
                                    };
                                    $status_text = match($pendaftaran_status) {
                                        'lunas' => 'BYR LUNAS',
                                        'ditolak' => 'BYR DITOLAK',
                                        default => 'BYR PENDING'
                                    };
                                    ?>
                                    <div class="mt-1.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 <?= $status_class ?> text-[8px] font-black uppercase tracking-wider rounded"><?= $status_text ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <?php if($lengkap): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 border border-blue-200 text-blue-600 text-[9px] font-black uppercase tracking-wider rounded-md"><i class="fas fa-shield-check"></i> Lengkap 100%</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-600 text-[9px] font-black uppercase tracking-wider rounded-md"><i class="fas fa-exclamation-triangle"></i> Kurang Data</span>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center justify-end gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <?php if($view != 'ARSIP'): ?>
                                    <a href="edit.php?id=<?= $s['id_siswa'] ?>" class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-500 hover:bg-indigo-500 hover:text-white flex items-center justify-center transition-all shadow-sm" title="Lengkapi / Edit Data"><i class="fas fa-edit text-xs"></i></a>

                                    <?php if(!$is_du): ?>
                                    <button onclick="konfirmasiDU('<?= $s['id_siswa'] ?>','<?= addslashes($s['nama_lengkap']) ?>')" class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-500 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-all shadow-sm" title="ACC Daftar Ulang"><i class="fas fa-check-circle text-xs"></i></button>
                                    <?php endif; ?>

                                    <button onclick="pindahJurusan('<?= $s['id_siswa'] ?>','<?= $s['jurusan'] ?>')" class="w-8 h-8 rounded-lg bg-purple-50 border border-purple-100 text-purple-500 hover:bg-purple-500 hover:text-white flex items-center justify-center transition-all shadow-sm" title="Pindah Jurusan"><i class="fas fa-exchange-alt text-xs"></i></button>

                                    <button onclick="lihatHistory('<?= $s['id_siswa'] ?>','<?= addslashes($s['nama_lengkap']) ?>')" class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-100 text-amber-500 hover:bg-amber-500 hover:text-white flex items-center justify-center transition-all shadow-sm" title="Riwayat Pindah"><i class="fas fa-history text-xs"></i></button>

                                    <button onclick="handleCabut('<?= $s['id_siswa'] ?>','<?= addslashes($s['nama_lengkap']) ?>')" class="w-8 h-8 rounded-lg bg-red-50 border border-red-100 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all shadow-sm" title="Cabut Berkas (Arsipkan)"><i class="fas fa-user-times text-xs"></i></button>
                                <?php else: ?>
                                    <button onclick="restoreData('<?= $s['id_siswa'] ?>','<?= addslashes($s['nama_lengkap']) ?>')" class="px-4 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-500 hover:text-white text-[10px] font-black uppercase tracking-wider flex items-center gap-2 transition-all shadow-sm">
                                        <i class="fas fa-undo"></i> Restore Berkas
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                        <div class="py-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 border border-slate-200 rounded-full flex items-center justify-center text-slate-300 text-2xl mx-auto mb-3 shadow-sm"><i class="fas fa-folder-open"></i></div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tidak ada data ditemukan</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    const urlParams = new URLSearchParams(window.location.search);
    const st = urlParams.get('status');
    const msgs = {
        success_edit:   ['Berhasil Disimpan!', 'Data profil siswa berhasil diperbarui.', 'success'],
        success_pindah: ['Pindah Jurusan Sukses!', 'Jurusan diubah & history tersimpan.', 'success'],
        success_cabut:  ['Berkas Dicabut!', 'Siswa telah dipindahkan ke tabel arsip.', 'success'],
        success_du:     ['Daftar Ulang Terverifikasi!', 'Status siswa diubah menjadi SUDAH DAFTAR ULANG.', 'success'],
        success_restore:['Restore Berhasil!', 'Data siswa dikembalikan ke tabel aktif.', 'success'],
        error:          ['Aksi Gagal!', 'Terjadi kesalahan pada sistem. Silakan coba lagi.', 'error']
    };

    if(st && msgs[st]) {
        Swal.fire({
            title: `<span class="text-2xl font-black ${msgs[st][2]==='success'?'text-emerald-600':'text-red-500'} tracking-tight">${msgs[st][0]}</span>`,
            html: `<div class="text-slate-500 font-medium text-sm">${msgs[st][1]}</div>`,
            icon: msgs[st][2],
            iconColor: msgs[st][2]==='success' ? '#10b981' : '#ef4444',
            confirmButtonColor: '#4f46e5',
            background: '#ffffff', color: '#334155',
            customClass: {
                popup: `border border-slate-200 rounded-3xl shadow-2xl`,
                confirmButton: 'font-bold tracking-widest text-xs px-8 py-3 rounded-xl shadow-md'
            }
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?view=<?= $view ?>&jur=<?= $jur ?>");
    }

    function konfirmasiDU(id, nama) {
        Swal.fire({
            title: '<span class="text-2xl font-black text-slate-800 uppercase tracking-tight">Otorisasi Daftar Ulang</span>',
            html: `<p class="text-sm text-slate-500 font-medium mb-3">Tandai status penyelesaian administrasi daftar ulang untuk:</p>
                   <div class="bg-emerald-50 border border-emerald-100 p-3.5 rounded-xl text-lg font-black text-emerald-600 uppercase tracking-wide inline-block w-full">
                       ${nama}
                   </div>`,
            icon: 'question', iconColor: '#10b981',
            showCancelButton: true, cancelButtonText: 'BATAL',
            confirmButtonText: 'YA, KONFIRMASI', confirmButtonColor: '#10b981', cancelButtonColor: '#f1f5f9',
            customClass: {
                popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                confirmButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl shadow-md text-white',
                cancelButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl text-slate-500'
            }
        }).then(r => { if(r.isConfirmed) window.location.href = `proses_update.php?aksi=du&id=${id}`; });
    }

    function pindahJurusan(id, jurSekarang) {
        Swal.fire({
            title: '<span class="text-2xl font-black text-slate-800 uppercase tracking-tight">Pindah Jurusan</span>',
            html: `<p class="text-xs text-slate-500 font-medium mb-2">Jurusan saat ini:</p>
                   <span class="inline-block px-3 py-1 bg-slate-100 border border-slate-200 text-indigo-600 text-xs font-black rounded-lg mb-4">${jurSekarang}</span>`,
            input: 'select',
            inputOptions: { 'TPM':'Teknik Pemesinan','TKR':'Teknik Kendaraan Ringan','TSM':'Teknik Sepeda Motor','TKJ':'Teknik Komputer & Jaringan','TAV':'Teknik Audio Video' },
            inputPlaceholder: 'Pilih Jurusan Baru...',
            showCancelButton: true, cancelButtonText: 'BATAL', confirmButtonText: 'LANJUTKAN',
            confirmButtonColor: '#8b5cf6', cancelButtonColor: '#f1f5f9',
            customClass: {
                popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                confirmButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl shadow-md text-white',
                cancelButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl text-slate-500',
                input: 'bg-white border border-slate-300 text-slate-800 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-100 text-sm outline-none'
            },
            preConfirm: (val) => { if(!val || val === jurSekarang) { Swal.showValidationMessage('Pilih jurusan yang berbeda!'); } return val; }
        }).then(r => {
            if(r.value) {
                Swal.fire({
                    title: '<span class="text-xl font-black text-slate-800 uppercase tracking-tight">Alasan Pindah</span>',
                    input: 'textarea', inputPlaceholder: 'Contoh: Kemauan anak, salah pilih di awal...',
                    showCancelButton: true, cancelButtonText: 'BATAL', confirmButtonText: 'SIMPAN PERUBAHAN',
                    confirmButtonColor: '#8b5cf6', cancelButtonColor: '#f1f5f9',
                    customClass: {
                        popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                        confirmButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl shadow-md text-white',
                        cancelButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl text-slate-500',
                        input: 'bg-white border border-slate-300 text-slate-800 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-100 text-sm outline-none p-4'
                    }
                }).then(r2 => {
                    if(r2.isConfirmed) {
                        Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                        window.location.href = `proses_update.php?aksi=pindah_jurusan&id=${id}&jurusan_baru=${r.value}&jurusan_lama=${jurSekarang}&alasan=${encodeURIComponent(r2.value||'-')}`;
                    }
                });
            }
        });
    }

    function lihatHistory(id, nama) {
        Swal.fire({
            title: `<span class="text-xl font-black text-slate-800 uppercase tracking-tight">Riwayat Pindah Jurusan</span>`,
            html: `<p class="text-xs text-slate-500 font-medium mb-4">${nama}</p><div id="hist-content" class="min-h-[100px] flex items-center justify-center"><i class="fas fa-circle-notch fa-spin text-2xl text-indigo-500"></i></div>`,
            width: 500, confirmButtonColor: '#4f46e5', confirmButtonText: 'TUTUP',
            customClass: { popup: 'border border-slate-200 rounded-3xl shadow-2xl', confirmButton: 'font-bold tracking-widest text-xs px-8 py-3 rounded-xl shadow-md text-white' }
        });
        fetch(`get_history.php?id=${id}`).then(r=>r.text()).then(html => { document.getElementById('hist-content').innerHTML = html; });
    }

    function handleCabut(id, nama) {
        Swal.fire({
            title: '<span class="text-2xl font-black text-slate-800 uppercase tracking-tight">Cabut Berkas?</span>',
            html: `<b class="text-lg text-red-500">${nama}</b><br><span class="text-sm text-slate-500 mt-2 inline-block">Siswa akan dipindahkan ke ARSIP dan data tidak akan terhapus sepenuhnya.</span>`,
            input: 'textarea', inputPlaceholder: 'Tuliskan alasan pengunduran diri / cabut berkas...',
            icon: 'warning', iconColor: '#ef4444',
            showCancelButton: true, cancelButtonText: 'BATAL',
            confirmButtonText: 'CABUT BERKAS', confirmButtonColor: '#ef4444', cancelButtonColor: '#f1f5f9',
            customClass: {
                popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                confirmButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl shadow-md text-white',
                cancelButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl text-slate-500',
                input: 'bg-white border border-slate-300 text-slate-800 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-100 text-sm outline-none p-4'
            },
            preConfirm: (val) => { if(!val.trim()) { Swal.showValidationMessage('Alasan wajib diisi!'); } return val; }
        }).then(r => {
            if(r.isConfirmed) {
                Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                window.location.href = `proses_update.php?aksi=cabut&id=${id}&alasan=${encodeURIComponent(r.value)}`;
            }
        });
    }

    function restoreData(id, nama) {
        Swal.fire({
            title: '<span class="text-2xl font-black text-slate-800 uppercase tracking-tight">Restore Siswa?</span>',
            html: `<b class="text-lg text-emerald-500">${nama}</b><br><span class="text-sm text-slate-500 mt-2 inline-block">Data akan dikembalikan dari Arsip ke Tabel Aktif.</span>`,
            icon: 'question', iconColor: '#10b981',
            showCancelButton: true, cancelButtonText: 'BATAL', confirmButtonColor: '#10b981', cancelButtonColor: '#f1f5f9',
            confirmButtonText: 'YA, RESTORE DATA',
            customClass: {
                popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                confirmButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl shadow-md text-white',
                cancelButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl text-slate-500'
            }
        }).then(r => { if(r.isConfirmed) window.location.href = `proses_update.php?aksi=restore&id=${id}`; });
    }
    </script>
</body>
</html>
