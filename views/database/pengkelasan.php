<?php
/**
 * SISTEM PENGKELASAN OTOMATIS - Admin Page
 * IP Restricted: Only accessible from internal network
 */
include '../../config.php';

// Check admin IP restriction
require_admin_ip();

// Check session
if(!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'database') {
    header("Location: ../../panitia/index.php"); exit();
}

// Pastikan kolom ada
mysqli_query($conn, "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nilai_btq INT DEFAULT 0");
mysqli_query($conn, "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS kelas VARCHAR(20) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS request_kelas VARCHAR(150) DEFAULT NULL");

// Ambil jurusan yang tersedia
$jurusan_list = [];
$res_jur = mysqli_query($conn, "SELECT DISTINCT jurusan FROM siswa WHERE jurusan IS NOT NULL AND jurusan != '' ORDER BY jurusan");
while ($j = mysqli_fetch_assoc($res_jur)) { $jurusan_list[] = $j['jurusan']; }

// Filter jurusan yang dipilih
$selected_jur = isset($_GET['jur']) ? $_GET['jur'] : ($jurusan_list[0] ?? 'TPM');
$sel_jur_esc  = mysqli_real_escape_string($conn, $selected_jur);

// Ambil data siswa jurusan terpilih
$res = mysqli_query($conn, "SELECT id_siswa, nama_lengkap, jenis_kelamin, nilai_btq, kelas, request_kelas
    FROM siswa WHERE jurusan='$sel_jur_esc' ORDER BY nilai_btq DESC, nama_lengkap ASC");

$siswa_all = [];
while ($s = mysqli_fetch_assoc($res)) { $siswa_all[] = $s; }
$total = count($siswa_all);

// Cek apakah sudah dikelas
$sudah_dikelas = !empty(array_filter(array_column($siswa_all, 'kelas')));

// ===== ALGORITMA PENGKELASAN =====
function buatKelas($siswa_all, $kapasitas = 36) {
    $laki   = array_filter($siswa_all, fn($s) => strtoupper($s['jenis_kelamin']) == 'LAKI-LAKI');
    $pr     = array_filter($siswa_all, fn($s) => strtoupper($s['jenis_kelamin']) == 'PEREMPUAN');
    $lainnya= array_filter($siswa_all, fn($s) => !in_array(strtoupper($s['jenis_kelamin']), ['LAKI-LAKI','PEREMPUAN']));

    usort($laki,    fn($a,$b) => $b['nilai_btq'] <=> $a['nilai_btq']);
    usort($pr,      fn($a,$b) => $b['nilai_btq'] <=> $a['nilai_btq']);
    usort($lainnya, fn($a,$b) => $b['nilai_btq'] <=> $a['nilai_btq']);

    $laki    = array_values($laki);
    $pr      = array_values($pr);
    $lainnya = array_values($lainnya);

    $total    = count($siswa_all);
    $n_kelas  = max(1, (int)ceil($total / $kapasitas));

    $kelas_data = [];
    for ($i = 0; $i < $n_kelas; $i++) {
        $kelas_data[chr(65 + $i)] = ['L' => [], 'P' => [], 'X' => []];
    }

    $huruf = array_keys($kelas_data);

    foreach ($laki as $idx => $s) {
        $k = $huruf[$idx % $n_kelas];
        $kelas_data[$k]['L'][] = $s;
    }
    foreach ($pr as $idx => $s) {
        $k = $huruf[$idx % $n_kelas];
        $kelas_data[$k]['P'][] = $s;
    }
    foreach ($lainnya as $idx => $s) {
        $min_k = $huruf[0];
        foreach ($huruf as $k) {
            $total_k = count($kelas_data[$k]['L']) + count($kelas_data[$k]['P']) + count($kelas_data[$k]['X']);
            $total_min = count($kelas_data[$min_k]['L']) + count($kelas_data[$min_k]['P']) + count($kelas_data[$min_k]['X']);
            if ($total_k < $total_min) $min_k = $k;
        }
        $kelas_data[$min_k]['X'][] = $s;
    }

    return $kelas_data;
}

function terapkanRequest(&$kelas_data, $siswa_all) {
    $id_to_kelas = [];
    foreach ($kelas_data as $huruf => $grp) {
        foreach (array_merge($grp['L'], $grp['P'], $grp['X']) as $s) {
            $id_to_kelas[$s['id_siswa']] = $huruf;
        }
    }

    $nama_to_id = [];
    foreach ($siswa_all as $s) {
        $nama_to_id[strtoupper(trim($s['nama_lengkap']))] = $s['id_siswa'];
    }

    foreach ($siswa_all as $s) {
        if (empty($s['request_kelas'])) continue;
        $req_nama   = strtoupper(trim($s['request_kelas']));
        $req_id     = $nama_to_id[$req_nama] ?? null;
        if (!$req_id) continue;

        $kelas_saya = $id_to_kelas[$s['id_siswa']] ?? null;
        $kelas_teman= $id_to_kelas[$req_id] ?? null;

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
    return $kelas_data;
}

$kelas_preview = buatKelas($siswa_all);
$kelas_preview = terapkanRequest($kelas_preview, $siswa_all);

$stat_preview = [];
foreach ($kelas_preview as $huruf => $grp) {
    $stat_preview[$huruf] = [
        'L'     => count($grp['L']),
        'P'     => count($grp['P']),
        'X'     => count($grp['X']),
        'total' => count($grp['L']) + count($grp['P']) + count($grp['X']),
        'avg_btq' => 0
    ];
    $all_s = array_merge($grp['L'], $grp['P'], $grp['X']);
    if (count($all_s) > 0) {
        $stat_preview[$huruf]['avg_btq'] = round(array_sum(array_column($all_s, 'nilai_btq')) / count($all_s), 1);
    }
}

$belum_btq = array_filter($siswa_all, fn($s) => intval($s['nilai_btq']) == 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PENGKELASAN | SMK PASUNDAN 2</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
        body { background-color: #020617; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: rgba(56, 189, 248, 0.2); border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background-color: rgba(56, 189, 248, 0.5); }
    </style>
</head>
<body class="text-slate-200 relative selection:bg-blue-500/30 custom-scroll min-h-screen flex flex-col">

    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] right-[-5%] w-[40%] h-[40%] rounded-full bg-blue-700/10 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-5%] w-[30%] h-[50%] rounded-full bg-indigo-600/10 blur-[120px]"></div>
    </div>

    <div class="sticky top-0 z-50 flex items-center justify-between px-6 lg:px-10 py-4 bg-slate-950/80 backdrop-blur-xl border-b border-slate-800/80 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900/50 hover:bg-slate-800 border border-slate-700 hover:border-slate-600 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition-all">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <div class="hidden md:block font-outfit text-lg font-black text-white tracking-tight">SMK <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400">PASUNDAN 2</span></div>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($total > 0): ?>
            <a href="export_kelas.php?jur=<?= urlencode($selected_jur) ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 text-xs font-black uppercase tracking-wider rounded-xl transition-all">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <?php endif; ?>
        </div>
    </div>

    <main class="max-w-7xl mx-auto w-full px-6 lg:px-8 py-8 relative z-10 flex-grow">
        
        <header class="mb-8">
            <h1 class="font-outfit text-3xl lg:text-4xl font-black text-white tracking-tight mb-2">Sistem Pengkelasan</h1>
            <p class="text-sm font-medium text-slate-400">Otomatisasi pengkelasan berdasarkan nilai BTQ, rasio Laki-laki/Perempuan, dan *Request* Teman Sekelas.</p>
        </header>

        <div class="flex flex-wrap gap-2 mb-6">
            <?php foreach($jurusan_list as $jn): ?>
            <a href="?jur=<?= urlencode($jn) ?>" class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all <?= $selected_jur==$jn ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'bg-slate-900/50 text-slate-400 border border-slate-800 hover:bg-slate-800 hover:text-white' ?>">
                <?= $jn ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (count($belum_btq) > 0): ?>
        <div class="flex items-start gap-4 p-4 mb-6 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 shadow-inner">
            <i class="fas fa-exclamation-triangle text-xl mt-0.5"></i>
            <div>
                <p class="text-sm font-bold">Perhatian: <?= count($belum_btq) ?> siswa belum memiliki nilai BTQ.</p>
                <p class="text-xs mt-1 text-amber-500/80">Hasil pengkelasan mungkin tidak optimal. Silakan lengkapi di menu Edit Data.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($sudah_dikelas): ?>
        <div class="flex items-start gap-4 p-4 mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 shadow-inner">
            <i class="fas fa-check-circle text-xl mt-0.5"></i>
            <div>
                <p class="text-sm font-bold">Kelas untuk <?= $selected_jur ?> sudah tersimpan.</p>
                <p class="text-xs mt-1 text-emerald-500/80">Data di bawah ini menampilkan *Preview* jika Anda menyimpannya ulang saat ini.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($total == 0): ?>
        <div class="flex flex-col items-center justify-center py-20 bg-slate-900/40 backdrop-blur-xl border border-slate-800/50 rounded-[2rem]">
            <i class="fas fa-folder-open text-4xl text-slate-600 mb-4"></i>
            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Tidak ada data pendaftar untuk <?= $selected_jur ?></p>
        </div>
        <?php else: ?>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-slate-900/40 backdrop-blur-xl border border-slate-800/50 p-4 rounded-2xl flex flex-col shadow-lg">
                <span class="text-[9px] font-extrabold text-slate-500 uppercase tracking-widest mb-1">Total Siswa</span>
                <span class="text-2xl font-outfit font-black text-white"><?= $total ?></span>
            </div>
            <div class="bg-slate-900/40 backdrop-blur-xl border border-slate-800/50 p-4 rounded-2xl flex flex-col shadow-lg">
                <span class="text-[9px] font-extrabold text-slate-500 uppercase tracking-widest mb-1">Jumlah Kelas</span>
                <span class="text-2xl font-outfit font-black text-white"><?= count($kelas_preview) ?></span>
            </div>
            <div class="bg-blue-900/10 backdrop-blur-xl border border-blue-500/20 p-4 rounded-2xl flex flex-col shadow-lg">
                <span class="text-[9px] font-extrabold text-blue-500 uppercase tracking-widest mb-1">Laki-Laki</span>
                <span class="text-2xl font-outfit font-black text-blue-400"><?= count(array_filter($siswa_all, fn($s) => strtoupper($s['jenis_kelamin'])=='LAKI-LAKI')) ?></span>
            </div>
            <div class="bg-purple-900/10 backdrop-blur-xl border border-purple-500/20 p-4 rounded-2xl flex flex-col shadow-lg">
                <span class="text-[9px] font-extrabold text-purple-500 uppercase tracking-widest mb-1">Perempuan</span>
                <span class="text-2xl font-outfit font-black text-purple-400"><?= count(array_filter($siswa_all, fn($s) => strtoupper($s['jenis_kelamin'])=='PEREMPUAN')) ?></span>
            </div>
            <div class="bg-amber-900/10 backdrop-blur-xl border border-amber-500/20 p-4 rounded-2xl flex flex-col shadow-lg">
                <span class="text-[9px] font-extrabold text-amber-500 uppercase tracking-widest mb-1">Rata-rata BTQ</span>
                <span class="text-2xl font-outfit font-black text-amber-400"><?= $total>0 ? round(array_sum(array_column($siswa_all,'nilai_btq'))/$total,1) : 0 ?></span>
            </div>
            <div class="bg-emerald-900/10 backdrop-blur-xl border border-emerald-500/20 p-4 rounded-2xl flex flex-col shadow-lg">
                <span class="text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest mb-1">Request Sekelas</span>
                <span class="text-2xl font-outfit font-black text-emerald-400"><?= count(array_filter($siswa_all, fn($s)=>!empty($s['request_kelas']))) ?></span>
            </div>
        </div>

        <div class="bg-slate-900/40 backdrop-blur-xl border border-slate-800/50 p-4 lg:p-6 rounded-[2rem] shadow-xl flex flex-col lg:flex-row gap-4 items-center justify-between mb-8">
            <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                <div class="flex items-center gap-3 bg-slate-950/50 border border-slate-800 px-4 py-2.5 rounded-xl">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Kapasitas/Kelas</label>
                    <input type="number" id="kapasitas-inp" value="36" min="10" max="50" class="w-16 bg-transparent text-white font-bold text-sm outline-none border-b border-slate-600 focus:border-blue-500 text-center">
                </div>
                <div class="text-xs font-bold text-slate-400">
                    Preview: <span class="text-white"><?= $total ?></span> Siswa &rarr; <span class="text-sky-400 text-lg font-black" id="n-kelas-label"><?= count($kelas_preview) ?></span> Kelas
                </div>
            </div>
            
            <div class="flex flex-wrap gap-3 w-full lg:w-auto">
                <button onclick="refreshPreview()" class="flex-1 lg:flex-none px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-md">
                    <i class="fas fa-eye mr-2 text-sky-400"></i> Preview
                </button>
                <button onclick="simpanKelas()" class="flex-1 lg:flex-none px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-emerald-500/20 transform hover:-translate-y-0.5 transition-all">
                    <i class="fas fa-save mr-2"></i> Simpan
                </button>
                <?php if ($sudah_dikelas): ?>
                <button onclick="resetKelas()" class="flex-1 lg:flex-none px-6 py-3 bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-red-400 text-xs font-black uppercase tracking-widest rounded-xl transition-all">
                    <i class="fas fa-undo mr-2"></i> Reset
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($kelas_preview as $huruf => $grp):
                $semua_s = array_merge($grp['L'], $grp['P'], $grp['X']);
                usort($semua_s, fn($a,$b) => $b['nilai_btq'] <=> $a['nilai_btq']);
            ?>
            <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800/60 rounded-[2rem] overflow-hidden shadow-xl flex flex-col h-[500px]">
                
                <div class="bg-slate-950/60 p-5 border-b border-slate-800/80">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="font-outfit text-2xl font-black text-white"><?= $selected_jur ?> <?= $huruf ?></div>
                            <div class="text-[10px] font-bold text-sky-400 uppercase tracking-widest mt-1">Kelas <?= $huruf ?></div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2.5 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-black rounded-lg">L <?= count($grp['L']) ?></span>
                        <span class="px-2.5 py-1 bg-purple-500/10 border border-purple-500/20 text-purple-400 text-[10px] font-black rounded-lg">P <?= count($grp['P']) ?></span>
                        <span class="px-2.5 py-1 bg-slate-800 border border-slate-700 text-slate-300 text-[10px] font-black rounded-lg">Total <?= $stat_preview[$huruf]['total'] ?></span>
                        <span class="px-2.5 py-1 bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-black rounded-lg">BTQ ∅ <?= $stat_preview[$huruf]['avg_btq'] ?></span>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scroll p-2">
                    <div class="divide-y divide-slate-800/50">
                        <?php $rank=1; foreach ($semua_s as $s):
                            $nb = intval($s['nilai_btq']);
                            $grade = $nb>=80?'A':($nb>=65?'B':($nb>=50?'C':($nb>0?'D':'0')));
                            $jk_short = strtoupper(substr($s['jenis_kelamin']??'X',0,1));
                            $jk_short = in_array($jk_short,['L','P']) ? $jk_short : 'X';
                            
                            $av_bg = $jk_short=='L' ? 'bg-blue-500/10 text-blue-400' : ($jk_short=='P' ? 'bg-purple-500/10 text-purple-400' : 'bg-slate-800 text-slate-400');
                            
                            $btq_bg = 'bg-slate-800 text-slate-400';
                            if($grade=='A') $btq_bg='bg-emerald-500/10 text-emerald-400';
                            elseif($grade=='B') $btq_bg='bg-sky-500/10 text-sky-400';
                            elseif($grade=='C') $btq_bg='bg-amber-500/10 text-amber-400';
                            elseif($grade=='D') $btq_bg='bg-red-500/10 text-red-400';
                        ?>
                        <div class="flex items-center gap-3 p-3 hover:bg-slate-800/40 transition-colors rounded-xl">
                            <span class="text-[10px] font-black text-slate-600 w-4 text-right"><?= $rank++ ?></span>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black <?= $av_bg ?>"><?= $jk_short ?></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-white truncate flex items-center gap-1.5">
                                    <?= strtoupper($s['nama_lengkap']) ?>
                                    <?php if(!empty($s['request_kelas'])): ?>
                                    <i class="fas fa-link text-purple-400 text-[10px]" title="Request: <?= $s['request_kelas'] ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">BTQ: <?= $nb > 0 ? $nb : '-' ?></div>
                            </div>
                            <span class="px-2 py-1 rounded-md text-[9px] font-black <?= $btq_bg ?>"><?= $grade ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <script>
    const selectedJur  = "<?= $selected_jur ?>";
    const totalSiswa   = <?= $total ?>;

    function refreshPreview() {
        const kap = document.getElementById('kapasitas-inp').value;
        Swal.fire({ title: 'Memuat Preview...', background: '#0f172a', color: '#fff', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
        window.location.href = `?jur=${encodeURIComponent(selectedJur)}&kap=${kap}`;
    }

    function simpanKelas() {
        const kap = document.getElementById('kapasitas-inp').value;
        Swal.fire({
            title: '<span class="text-2xl font-black text-white uppercase tracking-tight">Simpan Kelas?</span>',
            html: `Kelas untuk <b class="text-sky-400">${selectedJur}</b> akan disimpan permanen ke database.<br><span class="text-sm text-slate-400 mt-2 inline-block">Siswa yang sudah memiliki kelas akan di-overwrite.</span>`,
            icon: 'question', iconColor: '#10b981',
            showCancelButton: true, cancelButtonText: 'Batal',
            confirmButtonText: 'YA, SIMPAN DATA', confirmButtonColor: '#10b981', cancelButtonColor: '#1e293b',
            background: '#0f172a', color: '#fff',
            customClass: {
                popup: 'border border-slate-700/50 rounded-3xl shadow-2xl shadow-emerald-900/20',
                confirmButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl border border-emerald-500/30',
                cancelButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl text-slate-300'
            }
        }).then(r => {
            if (r.isConfirmed) {
                Swal.fire({ title: 'Menyimpan...', background: '#0f172a', color: '#fff', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                window.location.href = `proses_kelas.php?aksi=simpan&jur=${encodeURIComponent(selectedJur)}&kap=${kap}`;
            }
        });
    }

    function resetKelas() {
        Swal.fire({
            title: '<span class="text-2xl font-black text-white uppercase tracking-tight">Reset Kelas?</span>',
            html: `Semua data kelas <b class="text-red-400">${selectedJur}</b> akan dihapus dari database!`,
            icon: 'warning', iconColor: '#ef4444',
            showCancelButton: true, cancelButtonText: 'Batal',
            confirmButtonText: 'YA, RESET!', confirmButtonColor: '#ef4444', cancelButtonColor: '#1e293b',
            background: '#0f172a', color: '#fff',
            customClass: {
                popup: 'border border-slate-700/50 rounded-3xl shadow-2xl shadow-red-900/20',
                confirmButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl border border-red-500/30',
                cancelButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl text-slate-300'
            }
        }).then(r => {
            if (r.isConfirmed) {
                Swal.fire({ title: 'Mereset...', background: '#0f172a', color: '#fff', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                window.location.href = `proses_kelas.php?aksi=reset&jur=${encodeURIComponent(selectedJur)}`;
            }
        });
    }

    document.getElementById('kapasitas-inp')?.addEventListener('input', function() {
        const kap = parseInt(this.value) || 36;
        document.getElementById('n-kelas-label').textContent = Math.ceil(totalSiswa / kap);
    });

    // Notif
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('status') === 'saved') {
        const n = urlParams.get('n') || 0;
        Swal.fire({
            title: '<span class="text-2xl font-black text-emerald-400 tracking-tight">Tersimpan!</span>',
            html: `<div class="text-slate-300 font-medium text-sm">Pembagian kelas berhasil.<br><b>${n}</b> data siswa telah diperbarui.</div>`,
            icon: 'success', iconColor: '#10b981', confirmButtonColor: '#38bdf8',
            background: '#0f172a', color: '#fff',
            customClass: { popup: 'border border-slate-700/50 rounded-3xl shadow-2xl shadow-emerald-900/20', confirmButton: 'font-bold tracking-widest text-xs px-8 py-3 rounded-xl' }
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?jur=" + encodeURIComponent(selectedJur));
    } else if(urlParams.get('status') === 'reset_ok') {
        Swal.fire({
            title: '<span class="text-2xl font-black text-emerald-400 tracking-tight">Di-reset!</span>',
            html: '<div class="text-slate-300 font-medium text-sm">Semua data kelas berhasil dikosongkan.</div>',
            icon: 'success', iconColor: '#10b981', confirmButtonColor: '#38bdf8',
            background: '#0f172a', color: '#fff',
            customClass: { popup: 'border border-slate-700/50 rounded-3xl shadow-2xl shadow-emerald-900/20', confirmButton: 'font-bold tracking-widest text-xs px-8 py-3 rounded-xl' }
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?jur=" + encodeURIComponent(selectedJur));
    }
    </script>
</body>
</html>