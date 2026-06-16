<?php
/**
 * TPA INDEX - Tes Potensi Akademik
 * SMK Pasundan 2 Bandung
 * Mobile-First Responsive Design - Education Theme
 * Updated: Optimalisasi Batch Insert, Real-Time Timer, dan Keamanan Transaction
 */
include '../../config.php';

// Handle force login (Untuk Panitia)
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

// ==========================================
// 1. MANAJEMEN WAKTU (ANTI-CHEAT REFRESH)
// ==========================================
$waktu_maksimal = 2700; // 45 menit dalam detik

if (!isset($_SESSION['tpa_mulai'])) {
    $_SESSION['tpa_mulai'] = time();
}

// Hitung waktu yang benar-benar telah berlalu sejak pertama kali mulai
$waktu_berjalan = time() - $_SESSION['tpa_mulai'];
$sisa_waktu = $waktu_maksimal - $waktu_berjalan;

if ($sisa_waktu < 0) {
    $sisa_waktu = 0; // Waktu habis
}

// ==========================================
// 2. LOAD DATA SOAL & JAWABAN TERSIMPAN
// ==========================================
$kategori_list = ['verbal' => [], 'numerik' => [], 'logika' => []];
$soal_data = [];
$soal_query = mysqli_query($conn, "SELECT * FROM tpa_soal WHERE aktif = 1 ORDER BY kategori, nomor");

while ($row = mysqli_fetch_assoc($soal_query)) {
    $kategori_list[$row['kategori']][] = $row;
    $soal_data[$row['id_soal']] = $row;
}

$jawaban_sudah = [];
$jawaban_query = mysqli_query($conn, "SELECT id_soal, jawaban_pilih FROM tpa_jawaban WHERE id_siswa = $id_siswa");
while ($row = mysqli_fetch_assoc($jawaban_query)) {
    $jawaban_sudah[$row['id_soal']] = $row['jawaban_pilih'];
}

$total_verbal = count($kategori_list['verbal']);
$total_numerik = count($kategori_list['numerik']);
$total_logika = count($kategori_list['logika']);
$total_soal = $total_verbal + $total_numerik + $total_logika;

// ==========================================
// 3. PROSES SUBMIT (BATCH INSERT + TRANSACTION)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_jawaban'])) {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $error = "Sesi telah habis, silakan ulangi.";
    } else {
        $bv = $bn = $bl = 0;
        $values = [];
        $params = [];
        $types = "";

        // Siapkan data batch untuk di-insert sekaligus
        if (isset($_POST['jawaban']) && is_array($_POST['jawaban'])) {
            foreach ($_POST['jawaban'] as $sid => $jw) {
                $sid = (int)$sid;
                $jw = strtoupper(substr(trim($jw), 0, 1)); // Pastikan hanya 1 huruf (A/B/C/D)
                $soal = $soal_data[$sid] ?? null;

                if ($soal && in_array($jw, ['A', 'B', 'C', 'D'])) {
                    $benar = ($jw === $soal['jawaban_benar']) ? 1 : 0;

                    // Bangun placeholder untuk Prepared Statement
                    $values[] = "(?, ?, ?, ?)";
                    $types .= "iisi";
                    $params[] = $id_siswa;
                    $params[] = $sid;
                    $params[] = $jw;
                    $params[] = $benar;

                    if (!$is_admin && $benar) {
                        if ($soal['kategori'] === 'verbal') $bv++;
                        if ($soal['kategori'] === 'numerik') $bn++;
                        if ($soal['kategori'] === 'logika') $bl++;
                    }
                }
            }
        }

        // Eksekusi Transaction agar Database aman dan tidak terpotong
        mysqli_begin_transaction($conn);
        try {
            // 1. Simpan semua jawaban dalam SATU kali eksekusi query
            if (!empty($values)) {
                $sql = "INSERT INTO tpa_jawaban (id_siswa, id_soal, jawaban_pilih, benar)
                        VALUES " . implode(", ", $values) . "
                        ON DUPLICATE KEY UPDATE jawaban_pilih = VALUES(jawaban_pilih), benar = VALUES(benar)";

                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, $types, ...$params);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }

            // 2. Kalkulasi Nilai dan update profil siswa
            if (!$is_admin) {
                $nv = $total_verbal > 0 ? round(($bv / $total_verbal) * 100) : 0;
                $nn = $total_numerik > 0 ? round(($bn / $total_numerik) * 100) : 0;
                $nl = $total_logika > 0 ? round(($bl / $total_logika) * 100) : 0;
                $nt = round(($nv + $nn + $nl) / 3);

                $stmt_update = mysqli_prepare($conn, "UPDATE siswa SET tpa_selesai = 1, tpa_tanggal = NOW(), tpa_nilai_total = ?, tpa_benar_verbal = ?, tpa_benar_numerik = ?, tpa_benar_logika = ?, tpa_jumlah_soal_verbal = ?, tpa_jumlah_soal_numerik = ?, tpa_jumlah_soal_logika = ? WHERE id_siswa = ?");
                mysqli_stmt_bind_param($stmt_update, "iiiiiiii", $nt, $bv, $bn, $bl, $total_verbal, $total_numerik, $total_logika, $id_siswa);
                mysqli_stmt_execute($stmt_update);
                mysqli_stmt_close($stmt_update);
            }

            mysqli_commit($conn); // Simpan permanen ke database

            // Hapus sesi ujian agar tidak bisa di-back
            if (!$is_admin) {
                unset($_SESSION['tpa_login'], $_SESSION['tpa_id_siswa'], $_SESSION['tpa_nama'], $_SESSION['tpa_jurusan'], $_SESSION['tpa_id_reg'], $_SESSION['tpa_mulai'], $_SESSION['tpa_admin_access']);
            }

            $redirect_url = $is_admin ? "card.php?id={$id_siswa}&preview=1" : "card.php?id={$id_siswa}";
            header("Location: " . $redirect_url);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn); // Batalkan jika terjadi error server
            $error = "Terjadi kegagalan sistem saat menyimpan. Silakan coba lagi.";
        }
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
        .btn-submit { background: #10b981; border-radius: 12px; padding: 14px 24px; font-weight: 700; color: white; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); width: 100%; transition: transform 0.2s; }
        .btn-submit:active { transform: scale(0.95); }
        .progress-fill { height: 6px; border-radius: 3px; background: linear-gradient(90deg, #2563eb, #3b82f6); }
        .timer-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 8px 12px; transition: background 0.3s; }
        .timer-box.warning { background: #fee2e2; border-color: #ef4444; animation: pulse 1.5s infinite; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Hero Theme Variables */
        <?php
        $hero = $_SESSION['tpa_hero'] ?? null;
        $hero_color = $hero['color'] ?? '#3b82f6';
        $hero_icon = $hero['icon'] ?? '🎮';
        ?>
        .hero-header { background: linear-gradient(135deg, <?= $hero_color ?>15 0%, <?= $hero_color ?>05 100%); border-bottom: 2px solid <?= $hero_color ?>30; }
        .hero-badge { background: <?= $hero_color ?>; box-shadow: 0 4px 15px <?= $hero_color ?>40; }
    </style>
</head>
<body class="text-slate-800 pb-28">
    <?php if (isset($error)): ?>
        <div class="fixed top-0 left-0 right-0 bg-red-600 text-white text-center py-2 z-50 font-bold"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
    <div style="background: linear-gradient(135deg, #7c3aed, #4f46e5); padding: 8px 16px; text-align: center; font-weight: bold; font-size: 14px; color: white; position: sticky; top: 0; z-index: 60;">
        MODE COMMITTEE - <?= htmlspecialchars($_SESSION['tpa_nama']) ?>
        <a href="admin_hasil.php" style="margin-left: 16px; padding: 4px 12px; background: rgba(255,255,255,0.2); border-radius: 8px; font-size: 12px;">Kembali</a>
    </div>
    <?php endif; ?>

    <?php if ($hero): ?>
    <div class="hero-header px-4 py-2 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="hero-badge w-10 h-10 rounded-xl flex items-center justify-center text-xl text-white">
                <?= $hero_icon ?>
            </div>
            <div>
                <div class="font-bold text-sm" style="color: <?= $hero_color ?>"><?= htmlspecialchars($hero['name']) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($hero['title']) ?></div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-xs text-slate-500">Petualangan Dimulai!</div>
            <div class="text-xs font-bold" style="color: <?= $hero_color ?>">
                <i class="fas fa-play mr-1"></i>QUEST TPA
            </div>
        </div>
    </div>
    <?php endif; ?>

    <header class="sticky top-0 z-50 bg-white border-b border-slate-200 shadow-sm">
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
                <div id="timer-container" class="timer-box flex items-center gap-2">
                    <i class="fas fa-clock text-red-500 text-sm"></i>
                    <span id="timer" class="font-mono font-bold text-lg text-red-600"><?= gmdate("i:s", $sisa_waktu) ?></span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex-1 bg-slate-200 rounded-full h-2">
                    <div id="progress-fill" class="progress-fill h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <span id="answered-count" class="text-xs font-bold text-slate-500 whitespace-nowrap">0/<?= $total_soal ?></span>
            </div>
        </div>
        <div class="px-4 pb-3 flex gap-2 overflow-x-auto snap-x">
            <button type="button" class="tab-btn active snap-start" data-tab="verbal" onclick="showTab('verbal')">
                <i class="fas fa-comment-alt mr-1"></i>Verbal
            </button>
            <button type="button" class="tab-btn snap-start" data-tab="numerik" onclick="showTab('numerik')">
                <i class="fas fa-calculator mr-1"></i>Numerik
            </button>
            <button type="button" class="tab-btn snap-start" data-tab="logika" onclick="showTab('logika')">
                <i class="fas fa-chess mr-1"></i>Logika
            </button>
        </div>
    </header>

    <main class="px-4 py-4 max-w-2xl mx-auto">
        <form method="POST" id="tpa-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <?php foreach (['verbal' => ['color' => 'blue', 'bg' => 'bg-blue-600'], 'numerik' => ['color' => 'emerald', 'bg' => 'bg-emerald-600'], 'logika' => ['color' => 'amber', 'bg' => 'bg-amber-500']] as $kat => $style): ?>
            <div id="section-<?= $kat ?>" class="tab-content <?= $kat !== 'verbal' ? 'hidden' : '' ?>">
                <?php $no = 1; foreach ($kategori_list[$kat] as $soal): ?>
                <div class="soal-card">
                    <div class="flex items-start gap-3 mb-4">
                        <div id="num-<?= $soal['id_soal'] ?>" class="nomor-badge <?= isset($jawaban_sudah[$soal['id_soal']]) ? 'answered' : '' ?>"><?= $no ?></div>
                        <p class="flex-1 text-sm font-medium leading-relaxed"><?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?></p>
                    </div>
                    <div class="space-y-2">
                        <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                        <?php $is_selected = ($jawaban_sudah[$soal['id_soal']] ?? '') === strtoupper($opt); ?>
                        <label class="option-btn <?= $is_selected ? 'selected' : '' ?>">
                            <input type="radio" name="jawaban[<?= $soal['id_soal'] ?>]" value="<?= strtoupper($opt) ?>" class="hidden" <?= $is_selected ? 'checked' : '' ?> onchange="markAnswer(<?= $soal['id_soal'] ?>, this)">
                            <span class="w-8 h-8 rounded-lg <?= $is_selected ? $style['bg'] . ' text-white' : 'bg-slate-100 text-slate-500' ?> flex items-center justify-center font-bold text-sm transition-colors"><?= strtoupper($opt) ?></span>
                            <span class="flex-1 text-sm font-medium text-slate-700"><?= htmlspecialchars($soal['opsi_' . $opt]) ?></span>
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
        <div class="flex gap-2 max-w-2xl mx-auto">
            <button type="button" onclick="showPrevTab()" class="px-4 py-3 bg-slate-100 rounded-xl text-sm font-bold text-slate-600 flex-1 hover:bg-slate-200 transition">
                <i class="fas fa-arrow-left mr-1"></i>Prev
            </button>
            <button type="button" onclick="showNextTab()" class="px-4 py-3 bg-blue-100 text-blue-700 rounded-xl text-sm font-bold flex-1 hover:bg-blue-200 transition">
                Next<i class="fas fa-arrow-right ml-1"></i>
            </button>
            <button type="submit" form="tpa-form" name="submit_jawaban" class="btn-submit flex-[1.5] shadow-emerald-500/30 hover:bg-emerald-600">
                <i class="fas fa-check-circle mr-1"></i>Selesai
            </button>
        </div>
    </div>

    <script>
        // Logika Real Timer Sesuai Backend
        let remaining = <?= $sisa_waktu ?>;
        const timerEl = document.getElementById('timer');
        const timerContainer = document.getElementById('timer-container');
        const formEl = document.getElementById('tpa-form');
        let isSubmitting = false;

        function updateTimer() {
            if (isSubmitting) return;
            if (remaining <= 0) {
                isSubmitting = true;
                alert("Waktu ujian telah habis! Sistem akan menyimpan jawaban Anda secara otomatis.");
                formEl.submit();
                return;
            }

            remaining--;
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            timerEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');

            // Visual Warning jika waktu sisa < 5 Menit
            if (remaining <= 300 && !timerContainer.classList.contains('warning')) {
                timerContainer.classList.add('warning');
            }
        }
        setInterval(updateTimer, 1000);

        // Tab Navigation
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

        // Marking & Progress Calculation (SUDAH DIPERBAIKI)
        function markAnswer(sid, radio) {
            const numEl = document.getElementById('num-' + sid);
            numEl.classList.add('answered');

            const label = radio.closest('.option-btn');
            const group = label.closest('.space-y-2');

            // Reset others in the group
            group.querySelectorAll('.option-btn').forEach(l => {
                l.classList.remove('selected');
                const sp = l.querySelector('span'); // PERBAIKAN DI SINI
                if(sp) {
                    sp.className = 'w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-sm transition-colors';
                }
            });

            // Set active
            label.classList.add('selected');
            const span = label.querySelector('span'); // PERBAIKAN DI SINI
            const bgColor = current === 'verbal' ? 'bg-blue-600' : current === 'numerik' ? 'bg-emerald-600' : 'bg-amber-500';
            if(span) {
                span.className = `w-8 h-8 rounded-lg ${bgColor} text-white flex items-center justify-center font-bold text-sm transition-colors`;
            }

            updateCount();
        }

        function updateCount() {
            const totalAnswered = document.querySelectorAll('input[type="radio"]:checked').length;
            const totalSoal = <?= $total_soal ?>;

            document.getElementById('answered-count').textContent = totalAnswered + '/' + totalSoal;
            const percentage = totalSoal > 0 ? (totalAnswered / totalSoal * 100) : 0;
            document.getElementById('progress-fill').style.width = percentage + '%';
        }

        updateCount(); // Run on load to set initial state

        // Konfirmasi Submit
        formEl.addEventListener('submit', function(e) {
            if (isSubmitting) return; // Prevent double trigger if timer just hits 0

            const c = document.querySelectorAll('input[type="radio"]:checked').length;
            if (c < <?= $total_soal ?>) {
                if (!confirm(`Kamu baru menjawab ${c} dari <?= $total_soal ?> soal. Yakin ingin mengakhiri ujian sekarang?`)) {
                    e.preventDefault();
                    return;
                }
            } else {
                if (!confirm(`Yakin sudah selesai dan ingin mengumpulkan jawaban?`)) {
                    e.preventDefault();
                    return;
                }
            }

            isSubmitting = true;
            document.querySelector('.btn-submit').innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...';
        });
    </script>
</body>
</html>
