<?php
/**
 * ACHIEVEMENT CARD - TPA Graduate Card
 * SMK Pasundan 2 Bandung
 * Gamified Hero Theme - MMORPG Style
 */
include '../../config.php';

$id_siswa = (int)($_GET['id'] ?? 0);
if (!$id_siswa) {
    header("Location: login.php");
    exit();
}

$is_preview = isset($_GET['preview']);
$is_admin_access = $_SESSION['tpa_admin_access'] ?? false;

$siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = $id_siswa"));
if (!$siswa) {
    header("Location: login.php");
    exit();
}

if ($siswa['tpa_selesai'] != 1 && !$is_preview) {
    header("Location: index.php");
    exit();
}

// Hero data based on major
$heroes = [
    'TPM' => ['icon' => '⚙️', 'color' => '#dc2626', 'name' => 'Tukang Besi (TPM)', 'title' => 'The Machinist Master'],
    'TKR' => ['icon' => '🚗', 'color' => '#2563eb', 'name' => 'Montir Handal (TKR)', 'title' => 'The Vehicle Sage'],
    'TSM' => ['icon' => '🏍️', 'color' => '#059669', 'name' => 'Rider Mechanic (TSM)', 'title' => 'The Two-Wheel Warrior'],
    'TKJ' => ['icon' => '💻', 'color' => '#7c3aed', 'name' => 'Cyber Wizard (TKJ)', 'title' => 'The Network Archmage'],
    'TAV' => ['icon' => '🎛️', 'color' => '#db2777', 'name' => 'Sound Maestro (TAV)', 'title' => 'The Audio Alchemist'],
];

$kode_jurusan = get_kode_jurusan($siswa['jurusan']);
$hero = $heroes[$kode_jurusan] ?? $heroes['TPM'];

if ($is_preview) {
    $jawaban = mysqli_query($conn, "SELECT js.jawaban_pilih, s.jawaban_benar, s.kategori FROM tpa_jawaban js JOIN tpa_soal s ON js.id_soal = s.id_soal WHERE js.id_siswa = $id_siswa");
    $benar_v = $benar_n = $benar_l = 0;
    $jml_v = $jml_n = $jml_l = 0;
    while ($j = mysqli_fetch_assoc($jawaban)) {
        if ($j['jawaban_pilih'] === $j['jawaban_benar']) {
            if ($j['kategori'] === 'verbal') $benar_v++;
            if ($j['kategori'] === 'numerik') $benar_n++;
            if ($j['kategori'] === 'logika') $benar_l++;
        }
        if ($j['kategori'] === 'verbal') $jml_v++;
        if ($j['kategori'] === 'numerik') $jml_n++;
        if ($j['kategori'] === 'logika') $jml_l++;
    }
    $siswa['tpa_benar_verbal'] = $benar_v;
    $siswa['tpa_benar_numerik'] = $benar_n;
    $siswa['tpa_benar_logika'] = $benar_l;
    $siswa['tpa_jumlah_soal_verbal'] = $jml_v;
    $siswa['tpa_jumlah_soal_numerik'] = $jml_n;
    $siswa['tpa_jumlah_soal_logika'] = $jml_l;
    $siswa['tpa_tanggal'] = date('Y-m-d H:i:s');
}

$total_soal = $siswa['tpa_jumlah_soal_verbal'] + $siswa['tpa_jumlah_soal_numerik'] + $siswa['tpa_jumlah_soal_logika'];
$total_benar = $siswa['tpa_benar_verbal'] + $siswa['tpa_benar_numerik'] + $siswa['tpa_benar_logika'];
$akurasi = $total_soal > 0 ? round(($total_benar / $total_soal) * 100) : 0;
$nilai = $siswa['tpa_nilai_total'] ?? 0;

if ($nilai >= 90) {
    $badge = ['label' => 'GENIUS AKADEMIK', 'emoji' => '🏆', 'color1' => 'from-amber-400', 'color2' => 'to-orange-500'];
} elseif ($nilai >= 75) {
    $badge = ['label' => 'BINTANG CEMERLANG', 'emoji' => '⭐', 'color1' => 'from-blue-400', 'color2' => 'to-indigo-500'];
} elseif ($nilai >= 60) {
    $badge = ['label' => 'PEJANGGA BERPOTENSI', 'emoji' => '🌟', 'color1' => 'from-emerald-400', 'color2' => 'to-teal-500'];
} else {
    $badge = ['label' => 'PENANTANG TERAMPIL', 'emoji' => '💪', 'color1' => 'from-purple-400', 'color2' => 'to-pink-500'];
}

$share_code = strtoupper(substr($siswa['nama_lengkap'], 0, 3)) . '-' . $id_siswa . '-' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Achievement Card - SMK Pasundan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-heading { font-family: 'Outfit', sans-serif; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade { animation: fadeInUp 0.5s ease-out forwards; }

        @keyframes confetti {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
        .confetti-piece { position: fixed; width: 8px; height: 8px; top: -10px; animation: confetti 3s linear forwards; border-radius: 2px; }

        .card-border {
            background: linear-gradient(135deg, <?= $hero['color'] ?>, <?= $hero['color'] ?>88, #a855f7, <?= $hero['color'] ?>);
            background-size: 300% 300%;
            animation: gradient-shift 4s ease infinite;
        }
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .hero-glow {
            filter: drop-shadow(0 0 20px <?= $hero['color'] ?>80);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .hero-float { animation: float 3s ease-in-out infinite; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <div id="confetti-container"></div>

    <?php if ($is_preview): ?>
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 py-2 px-4 text-center text-sm font-bold text-white sticky top-0 z-60">
        <i class="fas fa-eye mr-2"></i>
        PREVIEW MODE
        <a href="admin_hasil.php" class="ml-4 px-3 py-1 bg-white/20 rounded-lg text-xs">Kembali</a>
    </div>
    <?php endif; ?>

    <!-- Header with Hero Theme -->
    <header class="sticky top-0 z-50" style="background: linear-gradient(135deg, <?= $hero['color'] ?>20 0%, white 100%); border-bottom: 2px solid <?= $hero['color'] ?>30;">
        <div class="max-w-lg mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="<?= $is_preview ? 'admin_hasil.php' : 'login.php' ?>" class="text-slate-500 hover:text-slate-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background: linear-gradient(135deg, <?= $hero['color'] ?> 0%, <?= $hero['color'] ?>cc 100%); box-shadow: 0 4px 15px <?= $hero['color'] ?>40;">
                    <?= $hero['icon'] ?>
                </div>
                <div>
                    <div class="font-heading font-bold text-sm" style="color: <?= $hero['color'] ?>">Hero Card</div>
                    <div class="text-xs text-slate-500"><?= $hero['name'] ?></div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-6">
        <?php if (!$is_preview): ?>
        <div class="text-center mb-6 animate-fade">
            <div class="text-6xl mb-3 hero-glow hero-float"><?= $hero['icon'] ?></div>
            <h1 class="font-heading text-2xl font-black text-slate-900 mb-2">Quest Completed!</h1>
            <p class="text-slate-500">Kamu telah menyelesaikan TPA dan membuka Achievement!</p>
        </div>
        <?php else: ?>
        <div class="text-center mb-6 animate-fade">
            <div class="text-6xl mb-3"><?= $hero['icon'] ?></div>
            <h1 class="font-heading text-2xl font-black text-slate-900 mb-2">Preview Hero Card</h1>
            <p class="text-slate-500">Mode Committee</p>
        </div>
        <?php endif; ?>

        <!-- Achievement Card (PERBAIKAN STRUKTUR HTML DI SINI) -->
        <div class="mb-6 animate-fade" style="animation-delay: 0.1s;">
            <div class="card-border rounded-3xl p-[3px]">
                <div id="achievement-card" class="rounded-[22px] p-6 md:p-8 relative overflow-hidden" style="background: linear-gradient(145deg, #1a1a2e 0%, #0f0f23 100%);">
                    
                    <!-- Hero Icon Background Glow -->
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-[150px] opacity-10 hero-glow">
                        <?= $hero['icon'] ?>
                    </div>

                    <div class="relative z-10">
                        <!-- Header Certificate -->
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl" style="background: linear-gradient(135deg, <?= $hero['color'] ?> 0%, <?= $hero['color'] ?>cc 100%); box-shadow: 0 4px 15px <?= $hero['color'] ?>40;">
                                    <?= $hero['icon'] ?>
                                </div>
                                <div class="text-xs">
                                    <div class="font-bold text-white">SMK Pasundan 2</div>
                                    <div class="text-slate-400">Bandung</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] uppercase tracking-wider" style="color: <?= $hero['color'] ?>">Hero Certificate</div>
                                <div class="text-xs text-slate-500">TPA 2026</div>
                            </div>
                        </div>

                        <!-- Badge Ranking -->
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br <?= $badge['color1'] ?> <?= $badge['color2'] ?> mb-3 shadow-lg">
                                <span class="text-3xl"><?= $badge['emoji'] ?></span>
                            </div>
                            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">Achievement Unlocked</div>
                            <h2 class="text-lg font-extrabold bg-gradient-to-r <?= $badge['color1'] ?> <?= $badge['color2'] ?> bg-clip-text text-transparent">
                                <?= $badge['label'] ?>
                            </h2>
                        </div>

                        <!-- Character Name & Class -->
                        <div class="text-center mb-6">
                            <div class="text-[10px] uppercase tracking-[0.15em] text-slate-400 mb-1">Presented To</div>
                            <h3 class="text-2xl font-black text-white mb-3"><?= htmlspecialchars($siswa['nama_lengkap']) ?></h3>
                            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-bold" style="background: <?= $hero['color'] ?>30; color: <?= $hero['color'] ?>; border: 1px solid <?= $hero['color'] ?>50;">
                                <i class="fas fa-shield-alt"></i>
                                <?= htmlspecialchars($hero['name']) ?>
                            </span>
                        </div>

                        <!-- Score Panel -->
                        <div class="bg-white/5 rounded-2xl p-4 mb-5 border border-white/10">
                            <div class="flex items-center justify-around">
                                <div class="text-center">
                                    <div class="text-4xl font-black bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent"><?= $nilai ?></div>
                                    <div class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">Nilai</div>
                                </div>
                                <div class="w-px h-10 bg-white/10"></div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-emerald-400"><?= $total_benar ?></div>
                                    <div class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">Benar</div>
                                </div>
                                <div class="w-px h-10 bg-white/10"></div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-slate-300"><?= $akurasi ?>%</div>
                                    <div class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">Akurasi</div>
                                </div>
                            </div>
                        </div>

                        <!-- Skill Categories -->
                        <div class="grid grid-cols-3 gap-3 mb-5">
                            <div class="bg-indigo-500/20 border border-indigo-500/30 rounded-xl p-2 text-center backdrop-blur-sm">
                                <div class="text-sm font-bold text-indigo-400"><?= $siswa['tpa_benar_verbal'] ?>/<?= $siswa['tpa_jumlah_soal_verbal'] ?></div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">Verbal</div>
                            </div>
                            <div class="bg-emerald-500/20 border border-emerald-500/30 rounded-xl p-2 text-center backdrop-blur-sm">
                                <div class="text-sm font-bold text-emerald-400"><?= $siswa['tpa_benar_numerik'] ?>/<?= $siswa['tpa_jumlah_soal_numerik'] ?></div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">Numerik</div>
                            </div>
                            <div class="bg-amber-500/20 border border-amber-500/30 rounded-xl p-2 text-center backdrop-blur-sm">
                                <div class="text-sm font-bold text-amber-400"><?= $siswa['tpa_benar_logika'] ?>/<?= $siswa['tpa_jumlah_soal_logika'] ?></div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">Logika</div>
                            </div>
                        </div>

                        <!-- Footer Certificate -->
                        <div class="flex items-center justify-between pt-3 border-t border-white/10 text-xs">
                            <div class="text-slate-500 font-mono"><?= $share_code ?></div>
                            <div class="text-slate-400"><?= date('d F Y', strtotime($siswa['tpa_tanggal'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$is_preview): ?>
        <!-- Share Buttons -->
        <div class="bg-white rounded-2xl p-4 mb-4 animate-fade" style="animation-delay: 0.2s;">
            <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
                <i class="fas fa-share-alt text-blue-500"></i>
                Bagikan ke Social Media
            </h3>
            <div class="grid grid-cols-4 gap-2">
                <button onclick="downloadCard()" class="flex flex-col items-center gap-1 p-3 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                    <i class="fas fa-download text-blue-600"></i>
                    <span class="text-[10px] font-semibold">Download</span>
                </button>
                <button onclick="shareWhatsApp()" class="flex flex-col items-center gap-1 p-3 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                    <i class="fab fa-whatsapp text-green-500 text-lg"></i>
                    <span class="text-[10px] font-semibold">WhatsApp</span>
                </button>
                <button onclick="shareInstagram()" class="flex flex-col items-center gap-1 p-3 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                    <i class="fab fa-instagram text-pink-500 text-lg"></i>
                    <span class="text-[10px] font-semibold">Instagram</span>
                </button>
                <button onclick="copyLink()" class="flex flex-col items-center gap-1 p-3 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                    <i class="fas fa-link text-slate-600"></i>
                    <span class="text-[10px] font-semibold">Salin Link</span>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="flex flex-col gap-2 animate-fade" style="animation-delay: 0.3s;">
            <?php if ($is_preview): ?>
            <a href="admin_hasil.php" class="text-center px-6 py-4 bg-purple-500 text-white font-bold rounded-xl">
                <i class="fas fa-shield-alt mr-2"></i>Kembali ke Admin
            </a>
            <?php else: ?>
            <a href="sertifikat.php?id=<?= $id_siswa ?>" class="text-center px-6 py-4 bg-gradient-to-r from-amber-400 to-yellow-500 text-white font-bold rounded-xl shadow-lg hover:opacity-90 transition">
                <i class="fas fa-scroll mr-2"></i>Download Sertifikat PDF
            </a>
            <a href="../../index.php" class="text-center px-6 py-4 bg-blue-600 text-white font-bold rounded-xl shadow-lg">
                <i class="fas fa-home mr-2"></i>Beranda
            </a>
            <a href="login.php" class="text-center px-6 py-4 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
            <?php endif; ?>
        </div>
    </main>

    <footer class="py-6 text-center text-sm text-slate-500">
        &copy; <?= date('Y') ?> SMK Pasundan 2 Bandung
    </footer>

    <script>
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            const colors = ['#fbbf24', '#34d399', '#60a5fa', '#f472b6', '#a78bfa'];
            for (let i = 0; i < 30; i++) {
                const c = document.createElement('div');
                c.className = 'confetti-piece';
                c.style.left = Math.random() * 100 + '%';
                c.style.background = colors[Math.floor(Math.random() * colors.length)];
                c.style.animationDelay = Math.random() * 2 + 's';
                container.appendChild(c);
            }
            setTimeout(() => { container.innerHTML = ''; }, 5000);
        }

        async function downloadCard() {
            const card = document.getElementById('achievement-card');
            const canvas = await html2canvas(card, { backgroundColor: null, scale: 2 });
            const link = document.createElement('a');
            link.download = 'Achievement-TPA-<?= htmlspecialchars($siswa['nama_lengkap']) ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }

        function shareWhatsApp() {
            const text = encodeURIComponent(`🎉 Aku baru saja menyelesaikan TPA SMK Pasundan 2!\n\n🏆 Achievement: <?= $badge['label'] ?>\n📊 Nilai: <?= $nilai ?>\n\nYuk daftar juga di SMK Pasundan 2 Bandung!`);
            window.open(`https://wa.me/?text=${text}`, '_blank');
        }

        function shareInstagram() {
            alert('📸 Download card dulu, lalu upload ke Instagram Story!');
            downloadCard();
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href);
            alert('Link berhasil disalin!');
        }

        window.addEventListener('load', createConfetti);
    </script>
</body>
</html>
