<?php
// Config sudah include session_start()
// Jika ada session sebelumnya, redirect sesuai role
if(isset($_SESSION['role'])) {
    if($_SESSION['role'] == 'pendaftaran') header("Location: ../views/pendaftaran/index.php");
    elseif($_SESSION['role'] == 'tu') header("Location: ../views/tu/index.php");
    elseif($_SESSION['role'] == 'database') header("Location: ../views/database/index.php");
    elseif($_SESSION['role'] == 'superuser') header("Location: ../views/database/index.php");
    exit();
}

include '../config.php';

// Get CSRF token
$csrf_token = generate_csrf_token();
$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';

// Ambil Total Siswa
$res_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa");
$total_siswa = mysqli_fetch_assoc($res_total)['total'] ?? 0;

// Ambil Statistik Jurusan
$jurusan_query = "SELECT jurusan, COUNT(*) as jumlah FROM siswa WHERE jurusan IS NOT NULL AND jurusan != '' GROUP BY jurusan ORDER BY jumlah DESC";
$jurusan_data = mysqli_query($conn, $jurusan_query);

// --- QUERY DATA BULANAN ---
$bulan_query = "SELECT
                    DATE_FORMAT(tgl_daftar, '%b') as nama_bulan,
                    COUNT(*) as jumlah
                FROM siswa
                WHERE tgl_daftar IS NOT NULL
                GROUP BY MONTH(tgl_daftar)
                ORDER BY MONTH(tgl_daftar) ASC LIMIT 6";
$bulan_res = mysqli_query($conn, $bulan_query);

$labels_bulan = [];
$data_pendaftar = [];

while($b = mysqli_fetch_assoc($bulan_res)) {
    $labels_bulan[] = $b['nama_bulan'];
    $data_pendaftar[] = (int)$b['jumlah'];
}

// TRIK: Jika baru ada 1 bulan
if(count($labels_bulan) == 1) {
    array_unshift($labels_bulan, "Mar");
    array_unshift($data_pendaftar, 0);
}

// Fallback jika kosong
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
    <title>Sistem Panitia | SPMB SMK Pasundan 2</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ['Plus Jakarta Sans', 'sans-serif'],
              outfit: ['Outfit', 'sans-serif'],
            },
            animation: {
                'blob': 'blob 7s infinite',
                'fade-in-up': 'fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards'
            },
            keyframes: {
                blob: {
                    '0%': { transform: 'translate(0px, 0px) scale(1)' },
                    '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                    '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                    '100%': { transform: 'translate(0px, 0px) scale(1)' }
                },
                fadeInUp: {
                    '0%': { opacity: 0, transform: 'translateY(20px)' },
                    '100%': { opacity: 1, transform: 'translateY(0)' }
                }
            }
          }
        }
      }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden selection:bg-indigo-500 selection:text-white">

    <div class="flex flex-col lg:flex-row min-h-screen">
        
        <div class="w-full lg:w-[55%] bg-slate-950 relative overflow-hidden p-8 md:p-12 lg:p-16 flex flex-col justify-center border-r border-slate-800">
            
            <div class="absolute top-0 -left-4 w-72 h-72 bg-indigo-600 rounded-full mix-blend-screen filter blur-[120px] opacity-30 animate-blob"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 bg-blue-600 rounded-full mix-blend-screen filter blur-[120px] opacity-20 animate-blob animation-delay-2000"></div>

            <div class="relative z-10 max-w-lg mx-auto lg:mx-0 w-full animate-fade-in-up">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold mb-6">
                    <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                    Live Data SPMB
                </div>

                <h2 class="font-outfit font-black text-4xl md:text-5xl text-white mb-2 leading-tight">
                    PROGRESS <br>PENERIMAAN
                </h2>
                
                <div class="font-outfit font-black text-6xl md:text-7xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-blue-500 mb-8 tracking-tighter">
                    <?php echo $total_siswa; ?> 
                    <span class="block text-sm font-sans tracking-[0.2em] text-slate-400 mt-2 uppercase">Total Calon Siswa Terdaftar</span>
                </div>

                <div class="mb-10 space-y-4">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-3">Distribusi Jurusan</div>
                    <?php mysqli_data_seek($jurusan_data, 0); ?>
                    <?php while($j = mysqli_fetch_assoc($jurusan_data)): ?>
                    <div>
                        <div class="flex justify-between text-xs font-bold mb-1.5">
                            <span class="text-slate-300"><?php echo $j['jurusan']; ?></span>
                            <span class="text-indigo-400"><?php echo $j['jumlah']; ?> SISWA</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-blue-400 rounded-full shadow-[0_0_10px_rgba(99,102,241,0.5)] transition-all duration-1000" style="width:<?php echo getPersen($j['jumlah'], $total_siswa); ?>%"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="w-full h-36 bg-white/5 border border-white/10 p-4 rounded-2xl backdrop-blur-sm">
                    <div class="relative h-full w-full">
                        <canvas id="miniChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-[45%] bg-white relative p-8 md:p-12 lg:p-16 flex items-center justify-center">
            
            <a href="../index.php" class="absolute top-6 right-6 md:top-8 md:right-8 inline-flex items-center gap-2 px-4 py-2 bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-slate-500 hover:text-indigo-600 text-xs font-bold rounded-xl transition-all shadow-sm hover:shadow-md z-50">
                <i class="fas fa-arrow-left"></i> <span class="hidden sm:inline">Kembali ke Beranda</span><span class="sm:hidden">Kembali</span>
            </a>

            <div class="w-full max-w-sm animate-fade-in-up" style="animation-delay: 0.2s;">
                
                <div class="mb-8 text-center lg:text-left">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-4 mx-auto lg:mx-0 shadow-inner transform -rotate-3">
                        <span class="font-outfit font-black text-xl">P2</span>
                    </div>
                    <h1 id="greeting-text" class="font-outfit font-black text-3xl text-slate-900 mb-2">Selamat Datang!</h1>
                    <p class="text-slate-500 text-sm">Silakan masuk untuk mengelola data pendaftar pada sistem panitia.</p>
                </div>

                <?php if($pesan=='salah'): ?>
                    <div class="flex items-center gap-3 p-4 mb-6 bg-red-50 text-red-600 border border-red-200 rounded-2xl text-sm font-bold animate-pulse">
                        <i class="fas fa-exclamation-circle text-lg"></i> Kredensial tidak sesuai. Coba lagi.
                    </div>
                <?php elseif($pesan=='terblokir'): ?>
                    <div class="flex items-center gap-3 p-4 mb-6 bg-orange-50 text-orange-600 border border-orange-200 rounded-2xl text-sm font-bold animate-pulse">
                        <i class="fas fa-user-lock text-lg"></i> Terlalu banyak percobaan. Tunggu 15 menit.
                    </div>
                <?php elseif($pesan=='invalid'): ?>
                    <div class="flex items-center gap-3 p-4 mb-6 bg-amber-50 text-amber-600 border border-amber-200 rounded-2xl text-sm font-bold animate-pulse">
                        <i class="fas fa-exclamation-triangle text-lg"></i> Permintaan tidak valid.
                    </div>
                <?php endif; ?>

                <form action="../login_proses.php" method="POST" class="space-y-5" onsubmit="btnLoading(this)">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2"><i class="fas fa-id-badge mr-1 text-slate-400"></i> ID Panitia</label>
                        <input type="text" name="username" id="username" placeholder="Masukkan ID Panitia" required autocomplete="off"
                               class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2"><i class="fas fa-key mr-1 text-slate-400"></i> Kata Sandi</label>
                        <input type="password" name="password" id="password" placeholder="Masukkan kata sandi" required
                               class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 transition-all">
                    </div>

                    <button type="submit" id="btn-submit" class="w-full flex items-center justify-center gap-2 py-4 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white text-sm font-bold rounded-2xl shadow-lg shadow-indigo-500/30 transition-transform transform hover:-translate-y-1 group">
                        <span>Masuk ke Dasbor</span>
                        <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>

                <div class="mt-10 text-center">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-relaxed">
                        Sistem SPMB &copy; <?php echo date('Y'); ?> SMK Pasundan 2<br>Tim IT SMKS Pasundan 2 Bandung
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Logika Sapaan Berdasarkan Waktu
        const hour = new Date().getHours();
        const greetingEl = document.getElementById('greeting-text');
        if (hour >= 4 && hour < 10) greetingEl.innerHTML = "Selamat Pagi! ☕";
        else if (hour >= 10 && hour < 15) greetingEl.innerHTML = "Selamat Siang! ☀️";
        else if (hour >= 15 && hour < 18) greetingEl.innerHTML = "Selamat Sore! 🌅";
        else greetingEl.innerHTML = "Selamat Malam! 🌙";

        // Logika Animasi Tombol Loading
        function btnLoading(form) {
            const btn = document.getElementById('btn-submit');
            btn.innerHTML = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Memproses...</span>`;
            btn.classList.add('opacity-80', 'cursor-not-allowed');
        }

        // Script Grafik Chart.js (Tema Dark Mode)
        const ctx = document.getElementById('miniChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 144);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)'); // Indigo-500 dengan opacity
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels_bulan); ?>,
                datasets: [{
                    data: <?php echo json_encode($data_pendaftar); ?>,
                    borderColor: '#818cf8', // Indigo-400
                    borderWidth: 3,
                    fill: true,
                    backgroundColor: gradient,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointHitRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#1e293b', padding: 10, displayColors: false, titleFont: {family: 'Outfit'}, bodyFont: {family: 'Outfit'} }
                },
                layout: {
                    padding: { top: 10, bottom: 0, left: 0, right: 0 } 
                },
                scales: {
                    y: { display: false, beginAtZero: true },
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' } } 
                    }
                },
                interaction: { mode: 'index', intersect: false }
            }
        });
    </script>
</body>
</html>
