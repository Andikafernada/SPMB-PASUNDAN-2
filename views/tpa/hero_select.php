<?php
/**
 * TPA HERO SELECT - SMK Pasundan 2 Bandung
 * Gamified Hero Selection - MMORPG Style
 */
session_start();
include '../../config.php';

// Check: harus sudah login TPA (dari login.php)
if (!isset($_SESSION['tpa_login']) || $_SESSION['tpa_login'] !== true) {
    header("Location: login.php");
    exit();
}

$id_siswa = $_SESSION['tpa_id_siswa'] ?? 0;
$siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = $id_siswa"));

if (!$siswa) {
    header("Location: login.php");
    exit();
}

// Ambil data siswa
$nama = $siswa['nama_lengkap'];
$jurusan = $siswa['jurusan'];
$id_reg = $siswa['id_pendaftaran'];

// DATA HERO UNTUK SETIAP JURUSAN
$heroes = [
    'TPM' => [
        'id' => 'tpm',
        'name' => 'Tukang Besi (TPM)',
        'title' => 'The Machinist Master',
        'class' => 'Teknik Pemesinan',
        'icon' => '⚙️',
        'color' => '#dc2626',
        'color_light' => '#fef2f2',
        'border_color' => 'border-red-500',
        'bg_gradient' => 'from-red-600 to-red-800',
        'description' => 'Spesialis dalam mesin dan permesinan. Mengubah logam menjadi karya masterpiece!',
        'stats' => [
            ['name' => 'Presisi', 'value' => 95, 'color' => '#dc2626'],
            ['name' => 'Teknik', 'value' => 88, 'color' => '#f59e0b'],
            ['name' => 'Kreativitas', 'value' => 75, 'color' => '#10b981'],
            ['name' => 'Kekuatan', 'value' => 82, 'color' => '#6366f1'],
        ],
        'skills' => ['Olah Besi', 'Kalibrasi Mesin', 'Pengerjaan Presisi'],
        'weapon' => '🔧 Kunci Pas & Bor Mesin',
    ],
    'TKR' => [
        'id' => 'tkr',
        'name' => 'Montir Handal (TKR)',
        'title' => 'The Vehicle Sage',
        'class' => 'Teknik Kendaraan Ringan',
        'icon' => '🚗',
        'color' => '#2563eb',
        'color_light' => '#eff6ff',
        'border_color' => 'border-blue-500',
        'bg_gradient' => 'from-blue-600 to-blue-800',
        'description' => 'Ahli kendaraan bermotor. Bisa menangani mesin mobil dengan satu mata tertutup!',
        'stats' => [
            ['name' => 'Diagnosa', 'value' => 92, 'color' => '#2563eb'],
            ['name' => 'Kecantikan', 'value' => 85, 'color' => '#ec4899'],
            ['name' => 'Kecepatan', 'value' => 88, 'color' => '#f59e0b'],
            ['name' => 'Ketelitian', 'value' => 90, 'color' => '#10b981'],
        ],
        'skills' => ['Servis Mesin', 'Diagnosa Kerusakan', 'Tuning Performa'],
        'weapon' => '🔧 Obeng & Kunci Ring',
    ],
    'TSM' => [
        'id' => 'tsm',
        'name' => 'Rider Mechanic (TSM)',
        'title' => 'The Two-Wheel Warrior',
        'class' => 'Teknik Sepeda Motor',
        'icon' => '🏍️',
        'color' => '#059669',
        'color_light' => '#ecfdf5',
        'border_color' => 'border-emerald-500',
        'bg_gradient' => 'from-emerald-600 to-emerald-800',
        'description' => 'Ninja jalanan dengan skill mesin tingkat dewa. Motor mati sekali kick langsung nyala!',
        'stats' => [
            ['name' => 'Agilitas', 'value' => 95, 'color' => '#059669'],
            ['name' => 'Reflek', 'value' => 93, 'color' => '#dc2626'],
            ['name' => 'Teknik', 'value' => 87, 'color' => '#2563eb'],
            ['name' => 'Speed', 'value' => 90, 'color' => '#f59e0b'],
        ],
        'skills' => ['Overhaul Mesin', 'Modifikasi Racing', 'Electric Tuning'],
        'weapon' => '🔧 Tool Kit Racing',
    ],
    'TKJ' => [
        'id' => 'tkj',
        'name' => 'Cyber Wizard (TKJ)',
        'title' => 'The Network Archmage',
        'class' => 'Teknik Komputer & Jaringan',
        'icon' => '💻',
        'color' => '#7c3aed',
        'color_light' => '#f5f3ff',
        'border_color' => 'border-violet-500',
        'bg_gradient' => 'from-violet-600 to-violet-800',
        'description' => 'Sihir digital yang bisa meretas dunia. Koneksi terputus? Sekali ketik langsung hidup!',
        'stats' => [
            ['name' => 'Coding', 'value' => 94, 'color' => '#7c3aed'],
            ['name' => 'Logika', 'value' => 96, 'color' => '#2563eb'],
            ['name' => 'Jaringan', 'value' => 92, 'color' => '#059669'],
            ['name' => 'Problem Solving', 'value' => 98, 'color' => '#dc2626'],
        ],
        'skills' => ['Programming', 'Network Security', 'Cloud Computing'],
        'weapon' => '⌨️ Keyboard Mechanical + Mouse Gaming',
    ],
    'TAV' => [
        'id' => 'tav',
        'name' => 'Sound Maestro (TAV)',
        'title' => 'The Audio Alchemist',
        'class' => 'Teknik Audio Video',
        'icon' => '🎛️',
        'color' => '#db2777',
        'color_light' => '#fdf2f8',
        'border_color' => 'border-pink-500',
        'bg_gradient' => 'from-pink-600 to-pink-800',
        'description' => 'Tukang campur suara yang bisa bikin audio cinema di garasi. Bass nendang, treble jernih!',
        'stats' => [
            ['name' => 'Mixing', 'value' => 93, 'color' => '#db2777'],
            ['name' => 'Kreativitas', 'value' => 95, 'color' => '#f59e0b'],
            ['name' => 'Teknik', 'value' => 85, 'color' => '#2563eb'],
            ['name' => 'Artistik', 'value' => 92, 'color' => '#7c3aed'],
        ],
        'skills' => ['Audio Mixing', 'Video Editing', 'Sound Design'],
        'weapon' => '🎛️ Mixing Console & DAW',
    ],
];

// Helper get_kode_jurusan internal jika belum ada di config.php
if (!function_exists('get_kode_jurusan')) {
    function get_kode_jurusan($nama_jurusan) {
        if (strpos($nama_jurusan, 'Pemesinan') !== false) return 'TPM';
        if (strpos($nama_jurusan, 'Kendaraan Ringan') !== false) return 'TKR';
        if (strpos($nama_jurusan, 'Sepeda Motor') !== false) return 'TSM';
        if (strpos($nama_jurusan, 'Komputer') !== false) return 'TKJ';
        if (strpos($nama_jurusan, 'Audio') !== false) return 'TAV';
        return 'TPM';
    }
}

$kode_jurusan = get_kode_jurusan($jurusan);
$default_hero = $heroes[$kode_jurusan] ?? $heroes['TPM'];

// Handle pemilihan hero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_hero'])) {
    $hero_id = mysqli_real_escape_string($conn, $_POST['hero_id'] ?? '');

    // Simpan hero yang dipilih
    $_SESSION['tpa_hero'] = $heroes[$hero_id] ?? $default_hero;

    // Redirect ke TPA test
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pilih Hero - TPA SMK Pasundan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Orbitron:wght@500;600;700;800;900&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-game { font-family: 'Orbitron', sans-serif; }
        .font-title { font-family: 'Rajdhani', sans-serif; }
        body { background: linear-gradient(180deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%); min-height: 100vh; overflow-x: hidden; }
        .stars { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; overflow: hidden; z-index: 0; }
        .stars::before { content: ''; position: absolute; top: 0; left: 0; width: 200%; height: 200%; background-image: radial-gradient(2px 2px at 20px 30px, #fff, transparent), radial-gradient(1px 1px at 90px 40px, #fff, transparent), radial-gradient(2px 2px at 160px 120px, rgba(255,255,255,0.9), transparent); background-repeat: repeat; background-size: 500px 500px; animation: stars 100s linear infinite; }
        @keyframes stars { from { transform: translateY(0); } to { transform: translateY(-50%); } }
        .hero-card { background: rgba(30,30,50,0.9); border: 2px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px); transition: all 0.4s ease; cursor: pointer; }
        .hero-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .hero-card.selected { border-color: var(--glow-color); box-shadow: 0 0 20px var(--glow-color); }
        .stat-bar { background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; height: 8px; }
        .stat-fill { height: 100%; border-radius: 4px; transition: width 1s ease-out; width: 0; }
        .hero-icon { font-size: 4rem; filter: drop-shadow(0 0 10px currentColor); }
        .skill-badge { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
        .btn-choose { background: linear-gradient(135deg, #3b82f6, #6366f1); box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4); }
    </style>
</head>
<body class="text-white antialiased relative">
    <div class="stars"></div>
    <div class="relative z-10 min-h-screen">
        <header class="py-6 px-4 text-center">
            <div class="inline-flex items-center gap-3 bg-white/5 backdrop-blur-sm rounded-full px-6 py-2 border border-white/10">
                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                <span class="text-sm text-gray-300">Sistem Hero Selection</span>
            </div>
        </header>

        <div class="text-center px-4 mb-8">
            <p class="text-gray-400 text-sm mb-2">Selamat Datang,</p>
            <h1 class="font-game text-2xl md:text-3xl font-bold text-white mb-2"><?= htmlspecialchars($nama) ?></h1>
            <p class="text-gray-400 text-sm"><i class="fas fa-ticket-alt mr-1"></i>ID: <?= htmlspecialchars($id_reg) ?></p>
        </div>

        <main class="px-4 pb-8">
            <div class="text-center mb-8">
                <h2 class="font-game text-xl md:text-2xl font-bold text-white mb-2">🎮 PILIH HERO ANDA</h2>
                <p class="text-gray-400 text-sm">Silahkan sesuaikan atau konfirmasi pemilihan hero Anda</p>
            </div>

            <form method="POST" id="hero-form">
                <input type="hidden" name="hero_id" id="selected-hero" value="<?= $default_hero['id'] ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                    <?php foreach ($heroes as $code => $hero): 
                        $is_default = ($code === $kode_jurusan);
                    ?>
                    <div class="hero-card rounded-2xl p-6 relative overflow-hidden <?= $is_default ? 'selected' : '' ?>"
                         style="--glow-color: <?= $hero['color'] ?>;"
                         data-hero-id="<?= $hero['id'] ?>"
                         onclick="selectHero('<?= $hero['id'] ?>')">
                        
                        <div class="text-center mb-4">
                            <div class="hero-icon mb-3" style="color: <?= $hero['color'] ?>"><?= $hero['icon'] ?></div>
                            <h3 class="font-game text-lg font-bold text-white mb-1"><?= $hero['name'] ?></h3>
                            <p class="text-xs text-gray-400 font-title tracking-wider"><?= $hero['title'] ?></p>
                        </div>

                        <div class="text-center mb-4">
                            <span class="inline-block px-4 py-1 rounded-full text-sm font-bold" style="background: <?= $hero['color_light'] ?>; color: <?= $hero['color'] ?>;">
                                ⚔️ <?= $hero['class'] ?>
                            </span>
                        </div>

                        <p class="text-gray-300 text-sm text-center mb-4"><?= $hero['description'] ?></p>

                        <div class="space-y-2 mb-4">
                            <?php foreach ($hero['stats'] as $stat): ?>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 w-24 truncate"><?= $stat['name'] ?></span>
                                <div class="stat-bar flex-1">
                                    <div class="stat-fill" style="width: <?= $stat['value'] ?>%; background: <?= $stat['color'] ?>"></div>
                                </div>
                                <span class="text-xs font-bold" style="color: <?= $stat['color'] ?>"><?= $stat['value'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-10">
                    <button type="submit" name="select_hero" class="btn-choose px-12 py-4 rounded-xl text-white font-bold text-lg transition">
                        <i class="fas fa-gamepad mr-2"></i> MULAI QUEST TPA <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        function selectHero(heroId) {
            document.getElementById('selected-hero').value = heroId;
            document.querySelectorAll('.hero-card').forEach(card => {
                card.classList.remove('selected');
            });
            const activeCard = document.querySelector(`[data-hero-id="${heroId}"]`);
            if(activeCard) activeCard.classList.add('selected');
        }
    </script>
</body>
</html>
