<?php
// Redirect if already logged in
if(isset($_SESSION['role'])) {
    $redirects = [
        'pendaftaran' => 'views/pendaftaran/index.php',
        'tu' => 'views/tu/index.php',
        'database' => 'views/database/index.php',
        'superuser' => 'views/database/index.php',
        'user' => 'views/database/index.php'
    ];
    if(isset($redirects[$_SESSION['role']])) {
        header("Location: " . $redirects[$_SESSION['role']]);
        exit();
    }
}

include 'config.php';

$csrf_token = generate_csrf_token();
$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';

$res_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa");
$total_siswa = mysqli_fetch_assoc($res_total)['total'] ?? 0;

$jurusan_query = "SELECT jurusan, COUNT(*) as jumlah FROM siswa WHERE jurusan IS NOT NULL AND jurusan != '' GROUP BY jurusan ORDER BY jumlah DESC";
$jurusan_data = mysqli_query($conn, $jurusan_query);

$bulan_query = "SELECT DATE_FORMAT(tgl_daftar, '%b') as nama_bulan, COUNT(*) as jumlah FROM siswa WHERE tgl_daftar IS NOT NULL GROUP BY MONTH(tgl_daftar) ORDER BY MONTH(tgl_daftar) ASC LIMIT 6";
$bulan_res = mysqli_query($conn, $bulan_query);

$labels_bulan = [];
$data_pendaftar = [];
while($b = mysqli_fetch_assoc($bulan_res)) {
    $labels_bulan[] = $b['nama_bulan'];
    $data_pendaftar[] = (int)$b['jumlah'];
}

if(count($labels_bulan) == 1) {
    array_unshift($labels_bulan, "Mar");
    array_unshift($data_pendaftar, 0);
}
if(empty($labels_bulan)) { $labels_bulan = ['Mar', 'Apr']; $data_pendaftar = [0, 0]; }

function getPersen($jumlah, $total) {
    return ($total > 0) ? ($jumlah / $total) * 100 : 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Committee - SMK Pasundan 2 Bandung</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'], outfit: ['Outfit', 'sans-serif'] },
                    animation: {
                        'blob': 'blob 7s infinite',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' }
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden selection:bg-blue-500 selection:text-white">

    <div class="flex flex-col md:flex-row min-h-screen">
        
        <div class="w-full md:w-[55%] lg:w-[60%] bg-slate-950 relative overflow-hidden p-8 md:p-16 flex flex-col justify-center">
            
            <div class="absolute top-0 -left-4 w-72 h-72 bg-blue-600 rounded-full mix-blend-screen filter blur-[120px] opacity-30 animate-blob"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 bg-indigo-600 rounded-full mix-blend-screen filter blur-[120px] opacity-20 animate-blob animation-delay-2000"></div>

            <div class="relative z-10 max-w-lg">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold mb-6">
                    <i class="fas fa-shield-halved"></i> Portal Panitia PPDB
                </div>

                <h1 class="font-outfit font-black text-4xl md:text-5xl lg:text-6xl text-white mb-2 leading-tight uppercase">SMK <br>PASUNDAN 2</h1>
                
                <div class="font-outfit font-black text-6xl md:text-8xl text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-500 mb-8 tracking-tighter">
                    <?= $total_siswa ?> 
                    <span class="block text-sm font-sans tracking-[0.2em] text-slate-400 mt-2 uppercase">Pendaftar Baru 2026</span>
                </div>

                <div class="mb-10 space-y-4">
                    <?php mysqli_data_seek($jurusan_data, 0); ?>
                    <?php while($j = mysqli_fetch_assoc($jurusan_data)): ?>
                    <div>
                        <div class="flex justify-between text-xs font-bold mb-1.5">
                            <span class="text-slate-300"><?= $j['jurusan'] ?></span>
                            <span class="text-blue-400"><?= $j['jumlah'] ?> SISWA</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-400 rounded-full shadow-[0_0_10px_rgba(59,130,246,0.5)] transition-all duration-1000" style="width:<?= getPersen($j['jumlah'], $total_siswa) ?>%"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="w-full h-40 bg-white/5 border border-white/10 p-4 rounded-2xl backdrop-blur-sm">
                    <p class="text-[10px] font-bold text-slate-400 mb-2 tracking-widest uppercase">Tren Pendaftaran Bulanan</p>
                    <div class="relative h-28 w-full">
                        <canvas id="luxuryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full md:w-[45%] lg:w-[40%] bg-white relative p-8 md:p-12 flex items-center justify-center border-l border-slate-200">
            
            <a href="index.php" class="absolute top-6 right-6 md:top-8 md:right-8 inline-flex items-center gap-2 px-4 py-2 bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 text-slate-500 hover:text-blue-600 text-xs font-bold rounded-xl transition-all shadow-sm hover:shadow-md z-50">
                <i class="fas fa-arrow-left"></i> <span class="hidden sm:inline">Kembali ke Beranda</span><span class="sm:hidden">Kembali</span>
            </a>

            <div class="w-full max-w-sm animate-fade-in-up">
                
                <div class="mb-8 text-center md:text-left">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mb-4 mx-auto md:mx-0 shadow-inner">
                        <i class="fas fa-lock text-xl"></i>
                    </div>
                    <h2 class="font-outfit font-black text-3xl text-slate-900 mb-2">Masuk Sistem</h2>
                    <p class="text-slate-500 text-sm">Silakan masukkan kredensial Anda untuk mengakses dashboard panitia.</p>
                </div>

                <?php if($pesan=='salah'): ?>
                    <div class="flex items-center gap-3 p-4 mb-6 bg-red-50 text-red-600 border border-red-200 rounded-2xl text-sm font-bold animate-pulse">
                        <i class="fas fa-exclamation-circle text-lg"></i> Kredensial tidak sesuai.
                    </div>
                <?php elseif($pesan=='terblokir'): ?>
                    <div class="flex items-center gap-3 p-4 mb-6 bg-orange-50 text-orange-600 border border-orange-200 rounded-2xl text-sm font-bold animate-pulse">
                        <i class="fas fa-user-lock text-lg"></i> Terlalu banyak percobaan. Coba lagi 15 menit.
                    </div>
                <?php elseif($pesan=='invalid'): ?>
                    <div class="flex items-center gap-3 p-4 mb-6 bg-amber-50 text-amber-600 border border-amber-200 rounded-2xl text-sm font-bold animate-pulse">
                        <i class="fas fa-exclamation-triangle text-lg"></i> Permintaan tidak valid.
                    </div>
                <?php endif; ?>

                <form action="login_proses.php" method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2"><i class="fas fa-user mr-1 text-slate-400"></i> Username</label>
                        <input type="text" name="username" placeholder="Masukkan username" required autocomplete="username"
                               class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2"><i class="fas fa-key mr-1 text-slate-400"></i> Password</label>
                        <input type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password"
                               class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all">
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-bold rounded-2xl shadow-lg shadow-blue-500/30 transition-transform transform hover:-translate-y-1">
                        <i class="fas fa-sign-in-alt"></i> Masuk Sekarang
                    </button>
                </form>

                <div class="mt-10 text-center">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-relaxed">
                        &copy; <?= date('Y') ?> SMK PASUNDAN 2 BANDUNG<br>SISTEM OLEH ANDIKA FERNANDA
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('luxuryChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 160);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels_bulan) ?>,
                datasets: [{
                    data: <?= json_encode($data_pendaftar) ?>,
                    borderColor: '#60a5fa',
                    borderWidth: 3,
                    fill: true,
                    backgroundColor: gradient,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointHitRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                        ticks: { color: '#64748b', stepSize: 1, font: { size: 10, weight: '600' } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 10, weight: '600' } }
                    }
                }
            }
        });
    </script>
</body>
</html>
