<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ... kode include config dan baris lainnya di bawahnya ...

/**
 * TPA INDEX - Tes Potensi Akademik
 * SMK Pasundan 2 Bandung
 * Mobile-First Responsive Design - Education Theme
 */
include '../../config.php';

// Handle force login
if (isset($_GET['force_login'])) {
    $force_id = (int)$_GET['force_login'];
    if ($force_id > 0 && isset($_SESSION['role'])) {
        $allowed = ['pendaftaran', 'tu', 'database', 'superuser', 'user'];
        if (in_array($_SESSION['role'], $allowed)) {
            $siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = $force_id"));
            if ($siswa) {
                $_SESSION['tpa_login'] = true;
                $_SESSION['tpa_id_siswa'] = $siswa['id_siswa'];
                $_SESSION['tpa_nama'] = $siswa['nama_lengkap'];
                $_SESSION['tpa_jurusan'] = $siswa['jurusan'];
                $_SESSION['tpa_id_reg'] = $siswa['id_pendaftaran'];
                $_SESSION['tpa_admin_access'] = true;
            }
        }
    }
}

// Check login
if (!isset($_SESSION['tpa_login']) || $_SESSION['tpa_login'] !== true) {
    header("Location: login.php");
    exit();
}

$id_siswa = $_SESSION['tpa_id_siswa'] ?? 0;
$is_admin = $_SESSION['tpa_admin_access'] ?? false;

if (!$id_siswa) {
    header("Location: login.php");
    exit();
}

$siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = $id_siswa"));
if ($siswa['tpa_selesai'] == 1 && !$is_admin) {
    header("Location: hasil.php?id=" . $id_siswa);
    exit();
}

// Load soal
$kategori_list = ['verbal' => [], 'numerik' => [], 'logika' => []];
$soal_data = [];
$soal_query = mysqli_query($conn, "SELECT * FROM tpa_soal WHERE aktif = 1 ORDER BY kategori, nomor");
while ($row = mysqli_fetch_assoc($soal_query)) {
    $kategori_list[$row['kategori']][] = $row;
    $soal_data[$row['id_soal']] = $row;
}

// Load jawaban tersimpan
$jawaban_sudah = [];
$jawaban_query = mysqli_query($conn, "SELECT id_soal, jawaban_pilih FROM tpa_jawaban WHERE id_siswa = $id_siswa");
while ($row = mysqli_fetch_assoc($jawaban_query)) {
    $jawaban_sudah[$row['id_soal']] = $row['jawaban_pilih'];
}

$total_verbal = count($kategori_list['verbal']);
$total_numerik = count($kategori_list['numerik']);
$total_logika = count($kategori_list['logika']);
$total_soal = $total_verbal + $total_numerik + $total_logika;

// Init timer
if (!isset($_SESSION['tpa_mulai'])) {
    $_SESSION['tpa_mulai'] = time();
    $_SESSION['tpa_sisa_waktu'] = 2700;
}
$sisa_waktu = $_SESSION['tpa_sisa_waktu'] ?? 2700;

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_jawaban'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = "Token tidak valid.";
    } else {
        if ($is_admin) {
            foreach ($_POST['jawaban'] as $sid => $jw) {
                $sid = (int)$sid;
                $jw = mysqli_real_escape_string($conn, $jw);
                $soal = $soal_data[$sid] ?? null;
                if ($soal) {
                    $benar = ($jw === $soal['jawaban_benar']) ? 1 : 0;
                    mysqli_query($conn, "INSERT INTO tpa_jawaban (id_siswa, id_soal, jawaban_pilih, benar) VALUES ($id_siswa, $sid, '$jw', $benar) ON DUPLICATE KEY UPDATE jawaban_pilih = '$jw', benar = $benar");
                }
            }
            header("Location: card.php?id=" . $id_siswa . "&preview=1");
            exit();
        }

        $bv = $bn = $bl = 0;
        foreach ($_POST['jawaban'] as $sid => $jw) {
            $sid = (int)$sid;
            $jw = mysqli_real_escape_string($conn, $jw);
            $soal = $soal_data[$sid] ?? null;
            if ($soal) {
                $benar = ($jw === $soal['jawaban_benar']) ? 1 : 0;
                mysqli_query($conn, "INSERT INTO tpa_jawaban (id_siswa, id_soal, jawaban_pilih, benar) VALUES ($id_siswa, $sid, '$jw', $benar) ON DUPLICATE KEY UPDATE jawaban_pilih = '$jw', benar = $benar");
                if ($benar) {
                    if ($soal['kategori'] === 'verbal') $bv++;
                    if ($soal['kategori'] === 'numerik') $bn++;
                    if ($soal['kategori'] === 'logika') $bl++;
                }
            }
        }

        $nv = $total_verbal > 0 ? round(($bv / $total_verbal) * 100) : 0;
$nn = $total_numerik > 0 ? round(($bn / $total_numerik) * 100) : 0;
$nl = $total_logika > 0 ? round(($bl / $total_logika) * 100) : 0;
        $nt = round(($nv + $nn + $nl) / 3);

        mysqli_query($conn, "UPDATE siswa SET tpa_selesai = 1, tpa_tanggal = NOW(), tpa_nilai_total = $nt, tpa_benar_verbal = $bv, tpa_benar_numerik = $bn, tpa_benar_logika = $bl, tpa_jumlah_soal_verbal = $total_verbal, tpa_jumlah_soal_numerik = $total_numerik, tpa_jumlah_soal_logika = $total_logika WHERE id_siswa = $id_siswa");

        unset($_SESSION['tpa_login'], $_SESSION['tpa_id_siswa'], $_SESSION['tpa_nama'], $_SESSION['tpa_jurusan'], $_SESSION['tpa_id_reg'], $_SESSION['tpa_mulai'], $_SESSION['tpa_sisa_waktu'], $_SESSION['tpa_admin_access']);
        header("Location: card.php?id=" . $id_siswa);
        exit();
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TPA - SMK Pasundan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; }
        .tab-btn { padding: 10px 16px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; background: white; color: #64748b; transition: all 0.2s; white-space: nowrap; }
        .tab-btn.active { background: #2563eb; color: white; border-color: #2563eb; }
        .soal-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; margin-bottom: 12px; }
        .option-btn { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; min-height: 56px; cursor: pointer; transition: all 0.2s; }
        .option-btn:hover { background: #eff6ff; border-color: #93c5fd; }
        .option-btn.selected { background: #eff6ff; border-color: #2563eb; }
        .nomor-badge { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0; background: #e2e8f0; color: #64748b; }
        .nomor-badge.answered { background: #2563eb; color: white; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: white; border-top: 1px solid #e2e8f0; padding: 12px 16px; z-index: 50; padding-bottom: max(12px, env(safe-area-inset-bottom)); }
        .btn-submit { background: #10b981; border-radius: 12px; padding: 14px 24px; font-weight: 700; color: white; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); width: 100%; }
        .progress-fill { height: 6px; border-radius: 3px; background: linear-gradient(90deg, #2563eb, #3b82f6); }
        .timer-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 8px 12px; }
    </style>
</head>
<body class="text-slate-800 pb-28">
    <?php if ($is_admin): ?>
    <div style="background: linear-gradient(135deg, #7c3aed, #4f46e5); padding: 8px 16px; text-align: center; font-weight: bold; font-size: 14px; color: white; position: sticky; top: 0; z-index: 60;">
        MODE COMMITTEE - <?= htmlspecialchars($_SESSION['tpa_nama']) ?>
        <a href="admin_hasil.php" style="margin-left: 16px; padding: 4px 12px; background: rgba(255,255,255,0.2); border-radius: 8px; font-size: 12px;">Kembali</a>
    </div>
    <?php endif; ?>

    <header class="sticky top-0 z-50 bg-white border-b border-slate-200">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-brain text-white text-sm"></i>
                    </div>
                    <div>
                        <div class="font-bold text-sm">Tes Potensi Akademik</div>
                        <div class="text-xs text-slate-500"><?= htmlspecialchars($_SESSION['tpa_nama'] ?? 'Siswa') ?></div>
                    </div>
                </div>
                <div class="timer-box flex items-center gap-2">
                    <i class="fas fa-clock text-red-500 text-sm"></i>
                    <span id="timer" class="font-mono font-bold text-lg text-red-600" data-seconds="<?= $sisa_waktu ?>"><?= gmdate("i:s", $sisa_waktu) ?></span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex-1 bg-slate-200 rounded-full h-2">
                    <div id="progress-fill" class="progress-fill h-2 rounded-full transition-all" style="width: 0%"></div>
                </div>
                <span id="answered-count" class="text-xs text-slate-500 whitespace-nowrap">0/<?= $total_soal ?></span>
            </div>
        </div>
        <div class="px-4 pb-3 flex gap-2 overflow-x-auto">
            <button type="button" class="tab-btn active" data-tab="verbal" onclick="showTab('verbal')">
                <i class="fas fa-comment-alt mr-1"></i>Verbal
            </button>
            <button type="button" class="tab-btn" data-tab="numerik" onclick="showTab('numerik')">
                <i class="fas fa-calculator mr-1"></i>Numerik
            </button>
            <button type="button" class="tab-btn" data-tab="logika" onclick="showTab('logika')">
                <i class="fas fa-chess mr-1"></i>Logika
            </button>
        </div>
    </header>

    <main class="px-4 py-4">
        <form method="POST" id="tpa-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <?php foreach (['verbal' => ['color' => 'blue', 'bg' => 'bg-blue-600'], 'numerik' => ['color' => 'emerald', 'bg' => 'bg-emerald-600'], 'logika' => ['color' => 'amber', 'bg' => 'bg-amber-500']] as $kat => $style): ?>
            <div id="section-<?= $kat ?>" class="tab-content">
                <?php $no = 1; foreach ($kategori_list[$kat] as $soal): ?>
                <div class="soal-card">
                    <div class="flex items-start gap-3 mb-4">
                        <div id="num-<?= $soal['id_soal'] ?>" class="nomor-badge <?= isset($jawaban_sudah[$soal['id_soal']]) ? 'answered' : '' ?>"><?= $no ?></div>
                        <p class="flex-1 text-sm font-medium leading-relaxed"><?= htmlspecialchars($soal['pertanyaan']) ?></p>
                    </div>
                    <div class="space-y-2">
                        <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                        <label class="option-btn <?= ($jawaban_sudah[$soal['id_soal']] ?? '') === strtoupper($opt) ? 'selected' : '' ?>">
                            <input type="radio" name="jawaban[<?= $soal['id_soal'] ?>]" value="<?= strtoupper($opt) ?>" class="hidden" <?= ($jawaban_sudah[$soal['id_soal']] ?? '') === strtoupper($opt) ? 'checked' : '' ?> onchange="markAnswer(<?= $soal['id_soal'] ?>, this)">
                            <span class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center font-bold text-sm <?= ($jawaban_sudah[$soal['id_soal']] ?? '') === strtoupper($opt) ? $style['bg'] . ' text-white' : '' ?>"><?= strtoupper($opt) ?></span>
                            <span class="flex-1"><?= htmlspecialchars($soal['opsi_' . $opt]) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php $no++; endforeach; ?>
            </div>
            <?php endforeach; ?>
        </form>
    </main>

    <div class="bottom-nav">
        <div class="flex gap-2 max-w-lg mx-auto">
            <button type="button" onclick="showPrevTab()" class="px-4 py-3 bg-slate-100 rounded-xl text-sm font-bold text-slate-600 flex-1">
                <i class="fas fa-arrow-left mr-1"></i>Prev
            </button>
            <button type="button" onclick="showNextTab()" class="px-4 py-3 bg-blue-600 text-white rounded-xl text-sm font-bold flex-1">
                Next<i class="fas fa-arrow-right ml-1"></i>
            </button>
            <button type="submit" form="tpa-form" name="submit_jawaban" class="btn-submit flex-1">
                <i class="fas fa-check mr-1"></i>Submit
            </button>
        </div>
    </div>

    <script>
        let remaining = <?= $sisa_waktu ?>;
        const timerEl = document.getElementById('timer');

        function updateTimer() {
            if (remaining <= 0) { document.getElementById('tpa-form').submit(); return; }
            remaining--;
            const m = Math.floor(remaining / 60), s = remaining % 60;
            timerEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            if (remaining <= 300) timerEl.parentElement.classList.add('bg-red-100', 'border-red-200');
        }
        setInterval(updateTimer, 1000);

        const tabs = ['verbal', 'numerik', 'logika'];
        let current = 'verbal';

        function showTab(tab) {
            tabs.forEach(t => document.getElementById('section-' + t).classList.add('hidden'));
            document.getElementById('section-' + tab).classList.remove('hidden');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelector('[data-tab="' + tab + '"]').classList.add('active');
            current = tab;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showNextTab() {
            const i = tabs.indexOf(current);
            if (i < tabs.length - 1) showTab(tabs[i + 1]);
        }
        function showPrevTab() {
            const i = tabs.indexOf(current);
            if (i > 0) showTab(tabs[i - 1]);
        }

        function markAnswer(sid, radio) {
            const numEl = document.getElementById('num-' + sid);
            numEl.classList.add('answered');
            const label = radio.closest('.option-btn');
            label.closest('.space-y-2').querySelectorAll('.option-btn').forEach(l => {
                l.classList.remove('selected');
                const sp = l.querySelector('span:first-child');
                sp.className = 'w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center font-bold text-sm';
            });
            label.classList.add('selected');
            const span = label.querySelector('span:first-child');
            span.className = 'w-8 h-8 rounded-lg ' + (current === 'verbal' ? 'bg-blue-600 text-white' : current === 'numerik' ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white') + ' flex items-center justify-center font-bold text-sm';
            updateCount();
        }

        function updateCount() {
            const c = document.querySelectorAll('input[type="radio"]:checked').length;
            const t = <?= $total_soal ?>;
            document.getElementById('answered-count').textContent = c + '/' + t;
            document.getElementById('progress-fill').style.width = (c / t * 100) + '%';
        }

        updateCount();
        document.getElementById('tpa-form').addEventListener('submit', function(e) {
            const c = document.querySelectorAll('input[type="radio"]:checked').length;
            if (c < <?= $total_soal ?> && !confirm('Baru ' + c + ' dari <?= $total_soal ?> soal dijawab. Submit?')) e.preventDefault();
        });
    </script>
</body>
</html>
