<?php
ob_start(); // Mencegah error 'Headers Already Sent'
/**
 * TPA HERO SELECT - SMK Pasundan 2 Bandung
 * MLBB Style with Pure CSS Hologram Avatars
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

// DATA HERO (Menggunakan FontAwesome Icon, tanpa file gambar)
$heroes = [
    'TPM' => [
        'id' => 'tpm',
        'name' => 'Tukang Besi',
        'title' => 'The Machinist Master',
        'class' => 'Teknik Pemesinan',
        'fa_icon' => 'fa-cogs', 
        'avatar' => '⚙️', 
        'color' => '#dc2626',
        'description' => 'Spesialis dalam mesin dan permesinan. Mengubah logam menjadi karya masterpiece!',
        'stats' => [
            ['name' => 'Presisi', 'value' => 95, 'color' => '#dc2626'],
            ['name' => 'Teknik', 'value' => 88, 'color' => '#f59e0b'],
            ['name' => 'Kreativitas', 'value' => 75, 'color' => '#10b981'],
        ]
    ],
    'TKR' => [
        'id' => 'tkr',
        'name' => 'Montir Handal',
        'title' => 'The Vehicle Sage',
        'class' => 'Teknik Kendaraan Ringan',
        'fa_icon' => 'fa-car-side',
        'avatar' => '🚗',
        'color' => '#2563eb',
        'description' => 'Ahli kendaraan bermotor. Bisa menangani mesin mobil dengan satu mata tertutup!',
        'stats' => [
            ['name' => 'Diagnosa', 'value' => 92, 'color' => '#2563eb'],
            ['name' => 'Kecepatan', 'value' => 88, 'color' => '#f59e0b'],
            ['name' => 'Ketelitian', 'value' => 90, 'color' => '#10b981'],
        ]
    ],
    'TSM' => [
        'id' => 'tsm',
        'name' => 'Rider Mechanic',
        'title' => 'The Two-Wheel Warrior',
        'class' => 'Teknik Sepeda Motor',
        'fa_icon' => 'fa-motorcycle',
        'avatar' => '🏍️',
        'color' => '#059669',
        'description' => 'Ninja jalanan dengan skill mesin tingkat dewa. Motor mati sekali kick langsung nyala!',
        'stats' => [
            ['name' => 'Agilitas', 'value' => 95, 'color' => '#059669'],
            ['name' => 'Reflek', 'value' => 93, 'color' => '#dc2626'],
            ['name' => 'Speed', 'value' => 90, 'color' => '#f59e0b'],
        ]
    ],
    'TKJ' => [
        'id' => 'tkj',
        'name' => 'Cyber Wizard',
        'title' => 'The Network Archmage',
        'class' => 'Teknik Komputer & Jaringan',
        'fa_icon' => 'fa-network-wired',
        'avatar' => '💻',
        'color' => '#7c3aed',
        'description' => 'Sihir digital yang bisa meretas dunia. Koneksi terputus? Sekali ketik langsung hidup!',
        'stats' => [
            ['name' => 'Coding', 'value' => 94, 'color' => '#7c3aed'],
            ['name' => 'Logika', 'value' => 96, 'color' => '#2563eb'],
            ['name' => 'Jaringan', 'value' => 92, 'color' => '#059669'],
        ]
    ],
    'TAV' => [
        'id' => 'tav',
        'name' => 'Sound Maestro',
        'title' => 'The Audio Alchemist',
        'class' => 'Teknik Audio Video',
        'fa_icon' => 'fa-headphones',
        'avatar' => '🎛️',
        'color' => '#db2777',
        'description' => 'Tukang campur suara yang bisa bikin audio cinema di garasi. Bass nendang, treble jernih!',
        'stats' => [
            ['name' => 'Mixing', 'value' => 93, 'color' => '#db2777'],
            ['name' => 'Teknik', 'value' => 85, 'color' => '#2563eb'],
            ['name' => 'Artistik', 'value' => 92, 'color' => '#7c3aed'],
        ]
    ],
];

// Helper untuk mendapatkan kode jurusan yang sesuai
if (!function_exists('get_kode_jurusan')) {
    function get_kode_jurusan($nama_jurusan) {
        if (strpos($nama_jurusan, 'Pemesinan') !== false) return 'TPM';
        if (strpos($nama_jurusan, 'Kendaraan Ringan') !== false) return 'TKR';
        if (strpos($nama_jurusan, 'Sepeda Motor') !== false) return 'TSM';
        if (strpos($nama_jurusan, 'Komputer') !== false) return 'TKJ';
        if (strpos($nama_jurusan, 'Audio') !== false) return 'TAV';
        return 'TPM'; // Default fallback
    }
}

$kode_jurusan = get_kode_jurusan($jurusan);
$default_hero = $heroes[$kode_jurusan] ?? $heroes['TPM'];

// Handle pemilihan hero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_hero'])) {
    $hero_id = mysqli_real_escape_string($conn, $_POST['hero_id'] ?? '');
    
    // Simpan hero yang dipilih ke dalam sesi
    $_SESSION['tpa_hero'] = $heroes[$hero_id] ?? $default_hero;
    
    // Rencana A: Redirect pakai PHP
    header("Location: index.php");
    
    // Rencana B: Paksa pindah pakai JavaScript (Jika PHP diblokir)
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Hero - TPA SMK Pasundan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-game { font-family: 'Orbitron', sans-serif; }
        
        body { background: radial-gradient(circle at center, #1a1a2e 0%, #0f0f15 100%); min-height: 100vh; overflow: hidden; color: white; }
        
        /* CSS ANIMATION UNTUK HOLOGRAM HERO */
        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }
        @keyframes spin-slow { 100% { transform: rotate(360deg); } }
        @keyframes spin-reverse-slow { 100% { transform: rotate(-360deg); } }
        
        .animate-float { animation: float 4s ease-in-out infinite; }
        .animate-spin-slow { animation: spin-slow 15s linear infinite; }
        .animate-spin-reverse-slow { animation: spin-reverse-slow 10s linear infinite; }

        .hologram-container { transition: opacity 0.4s ease; }
        .hologram-container.fade-out { opacity: 0; transform: scale(0.8); }

        /* Roster Selection Menu */
        .roster-item { 
            width: 60px; height: 60px; border: 2px solid rgba(255,255,255,0.2); 
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            background: #2a2a40; transition: all 0.3s ease; cursor: pointer;
            display: flex; justify-content: center; align-items: center; font-size: 1.5rem;
            filter: grayscale(100%); opacity: 0.6;
        }
        .roster-item:hover { filter: grayscale(0%); opacity: 1; transform: translateY(-5px); }
        .roster-item.active { filter: grayscale(0%); opacity: 1; border-color: var(--hero-color); box-shadow: 0 0 15px var(--hero-color); background: var(--hero-color); transform: scale(1.1); }
        
        .stat-bar { background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; overflow: hidden; }
        .stat-fill { height: 100%; border-radius: 4px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); width: 0; }
        
        .btn-lock { transition: all 0.3s; }
        .btn-lock:hover { filter: brightness(1.2); transform: scale(1.02); }
    </style>
</head>
<body class="flex flex-col">

    <header class="absolute top-0 w-full p-6 flex justify-between items-center z-50">
        <div>
            <h1 class="font-game text-xl text-gray-300">Welcome, <span class="text-white font-bold"><?= htmlspecialchars($nama) ?></span></h1>
            <p class="text-xs text-yellow-400 font-game tracking-widest"><?= htmlspecialchars($id_reg) ?></p>
        </div>
        <div class="px-6 py-2 bg-black/50 border border-white/10 rounded-full backdrop-blur-md text-sm font-game">
            SELECT YOUR HERO
        </div>
    </header>

    <main class="flex-1 flex flex-col md:flex-row relative h-screen w-full items-center justify-center pt-10 px-10">
        
        <div class="w-full md:w-1/2 flex justify-center items-center h-[50vh] md:h-full relative z-10">
            <div id="hero-glow" class="absolute w-64 h-64 md:w-96 md:h-96 rounded-full blur-[80px] opacity-20 transition-colors duration-500" style="background-color: <?= $default_hero['color'] ?>"></div>
            
            <div id="hologram-box" class="hologram-container relative flex justify-center items-center w-64 h-64 md:w-80 md:h-80 text-[<?= $default_hero['color'] ?>]" style="color: <?= $default_hero['color'] ?>;">
                
                <div class="absolute inset-0 rounded-full border-[3px] border-dashed border-current opacity-60 animate-spin-slow" style="filter: drop-shadow(0 0 10px currentColor);"></div>
                
                <div class="absolute inset-6 rounded-full border border-solid border-current opacity-40 animate-spin-reverse-slow"></div>
                
                <div class="absolute bottom-0 w-3/4 h-1/2 bg-gradient-to-t from-current to-transparent opacity-20 rounded-full blur-md" style="transform: perspective(200px) rotateX(60deg);"></div>

                <i id="main-hero-icon" class="fas <?= $default_hero['fa_icon'] ?> text-8xl md:text-9xl animate-float" style="filter: drop-shadow(0 0 25px currentColor);"></i>
            </div>
        </div>

        <div class="w-full md:w-1/2 flex flex-col justify-center h-full z-20 pl-0 md:pl-10 pb-20 md:pb-0 text-center md:text-left">
            <h3 id="hero-class" class="text-yellow-400 font-game text-sm tracking-widest uppercase mb-1"><?= $default_hero['class'] ?></h3>
            <h2 id="hero-name" class="font-game text-4xl md:text-6xl font-black italic mb-2 tracking-tighter" style="color: white; text-shadow: 2px 2px 0px <?= $default_hero['color'] ?>;"><?= strtoupper($default_hero['name']) ?></h2>
            <p id="hero-title" class="text-gray-400 text-md md:text-lg mb-4 md:mb-6 border-b-2 md:border-b-0 md:border-l-4 pb-2 md:pb-0 md:pl-3 mx-auto md:mx-0 w-fit" style="border-color: <?= $default_hero['color'] ?>;"><?= $default_hero['title'] ?></p>
            
            <p id="hero-desc" class="text-gray-300 mb-6 max-w-md text-sm leading-relaxed mx-auto md:mx-0"><?= $default_hero['description'] ?></p>

            <div id="hero-stats" class="space-y-4 max-w-md mb-8 mx-auto md:mx-0 w-full">
                <?php foreach ($default_hero['stats'] as $stat): ?>
                <div>
                    <div class="flex justify-between text-xs mb-1 font-bold">
                        <span><?= $stat['name'] ?></span>
                    </div>
                    <div class="stat-bar">
                        <div class="stat-fill" style="width: <?= $stat['value'] ?>%; background-color: <?= $stat['color'] ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <form action="" method="POST" class="w-full max-w-md mx-auto md:mx-0">
                <input type="hidden" name="hero_id" id="selected-hero" value="<?= $default_hero['id'] ?>">
                <button type="submit" name="select_hero" id="btn-submit" class="btn-lock w-full py-4 rounded-lg font-game font-bold text-xl uppercase tracking-widest text-white shadow-[0_0_20px_currentColor]" style="background-color: <?= $default_hero['color'] ?>; color: <?= $default_hero['color'] ?>;">
                    <span class="text-white">LOCK HERO</span>
                </button>
            </form>
        </div>
    </main>

    <div class="absolute bottom-6 w-full flex justify-center gap-3 md:gap-5 z-50">
        <?php foreach ($heroes as $code => $hero): ?>
            <div class="roster-item <?= ($hero['id'] == $default_hero['id']) ? 'active' : '' ?>" 
                 style="--hero-color: <?= $hero['color'] ?>"
                 onclick='changeHero(<?= json_encode($hero) ?>, this)'>
                 <?= $hero['avatar'] ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function changeHero(heroData, element) {
            document.getElementById('selected-hero').value = heroData.id;

            document.querySelectorAll('.roster-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            // Animasikan perubahan Hologram
            const hologramBox = document.getElementById('hologram-box');
            hologramBox.classList.add('fade-out');
            
            setTimeout(() => {
                // Ganti Icon FontAwesome
                const iconElement = document.getElementById('main-hero-icon');
                iconElement.className = `fas ${heroData.fa_icon} text-8xl md:text-9xl animate-float`;
                
                // Ganti Warna Hologram
                hologramBox.style.color = heroData.color;
                document.getElementById('hero-glow').style.backgroundColor = heroData.color;

                hologramBox.classList.remove('fade-out');
            }, 400);

            // Update Text
            document.getElementById('hero-class').innerText = heroData.class;
            document.getElementById('hero-name').innerText = heroData.name.toUpperCase();
            document.getElementById('hero-name').style.textShadow = `2px 2px 0px ${heroData.color}`;
            document.getElementById('hero-title').innerText = heroData.title;
            document.getElementById('hero-title').style.borderColor = heroData.color;
            document.getElementById('hero-desc').innerText = heroData.description;
            
            // Update Tombol Submit
            const btn = document.getElementById('btn-submit');
            btn.style.backgroundColor = heroData.color;
            btn.style.color = heroData.color;

            // Update Stats
            const statsContainer = document.getElementById('hero-stats');
            let statsHtml = '';
            heroData.stats.forEach(stat => {
                statsHtml += `
                <div>
                    <div class="flex justify-between text-xs mb-1 font-bold">
                        <span>${stat.name}</span>
                    </div>
                    <div class="stat-bar">
                        <div class="stat-fill" style="width: 0%; background-color: ${stat.color}"></div>
                    </div>
                </div>`;
            });
            statsContainer.innerHTML = statsHtml;

            setTimeout(() => {
                const fills = statsContainer.querySelectorAll('.stat-fill');
                fills.forEach((fill, index) => {
                    fill.style.width = heroData.stats[index].value + '%';
                });
            }, 50);
        }

        window.onload = () => {
            const fills = document.querySelectorAll('.stat-fill');
            fills.forEach(fill => {
                const width = fill.style.width;
                fill.style.width = '0%';
                setTimeout(() => { fill.style.width = width; }, 100);
            });
        };
    </script>
</body>
</html>
