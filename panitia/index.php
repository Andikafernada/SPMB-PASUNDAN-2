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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        @keyframes fade-in-up {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        
        .floating-input:focus ~ .floating-label,
        .floating-input:not(:placeholder-shown) ~ .floating-label {
            transform: translateY(-110%) scale(0.85);
            color: #4f46e5;
        }

        /* Scrollbar tipis untuk kotak jurusan */
        .thin-scrollbar::-webkit-scrollbar { width: 4px; }
        .thin-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .thin-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="h-screen w-full overflow-hidden flex items-center justify-center p-4 sm:p-6 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%234f46e5\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] bg-slate-50">

    <div class="max-w-5xl w-full h-full max-h-[650px] bg-white rounded-3xl shadow-2xl shadow-indigo-100/50 overflow-hidden flex opacity-0 animate-fade-in border border-slate-100">
        
        <div class="hidden lg:flex flex-1 flex-col bg-slate-50 p-6 lg:p-8 border-r border-slate-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>
            
            <div class="relative z-10 flex flex-col h-full">
                <div class="shrink-0 mb-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-[10px] font-bold uppercase tracking-wider mb-3">
                        <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                        Live Data SPMB
                    </div>
                    
                    <h2 class="font-outfit text-3xl font-black text-slate-900 leading-tight">
                        Progress<br>Penerimaan Siswa
                    </h2>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm mb-4 shrink-0">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Registrasi</div>
                    <div class="flex items-end gap-3">
                        <div class="font-outfit text-4xl font-black text-indigo-600 leading-none">
                            <?php echo $total_siswa; ?>
                        </div>
                        <div class="text-xs text-slate-500 font-medium pb-1">Calon Siswa</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex-1 min-h-0 flex flex-col mb-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3 shrink-0">Distribusi Jurusan</div>
                    <div class="space-y-3 overflow-y-auto thin-scrollbar pr-2 flex-1">
                        <?php mysqli_data_seek($jurusan_data, 0); ?>
                        <?php while($j = mysqli_fetch_assoc($jurusan_data)): ?>
                        <div>
                            <div class="flex justify-between text-xs mb-1 font-semibold text-slate-700">
                                <span><?php echo $j['jurusan']; ?></span>
                                <span><?php echo $j['jumlah']; ?></span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-1.5 rounded-full transition-all duration-1000" style="width: <?php echo getPersen($j['jumlah'], $total_siswa); ?>%"></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="relative w-full h-24 shrink-0 mt-auto pt-2">
                    <canvas id="miniChart"></canvas>
                </div>
            </div>
        </div>

        <div class="flex-1 p-6 sm:p-10 flex flex-col justify-center relative bg-white">
            <div class="max-w-sm w-full mx-auto relative z-10">
                
                <div class="mb-8 text-center lg:text-left">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-md transform -rotate-3 mb-4 mx-auto lg:mx-0">
                        <span class="text-white font-outfit font-black text-lg">P2</span>
                    </div>
                    <h1 id="greeting-text" class="font-outfit text-2xl font-black text-slate-900 mb-1">Selamat Datang!</h1>
                    <p class="text-slate-500 text-xs sm:text-sm">Silakan masuk untuk mengelola data pendaftar.</p>
                </div>

                <?php if($pesan=='salah'): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded-r-lg mb-5 flex gap-2 items-center">
                        <span class="text-red-500 text-sm">⚠️</span>
                        <p class="text-xs font-semibold text-red-700">Kredensial tidak sesuai. Coba lagi.</p>
                    </div>
                <?php elseif($pesan=='terblokir'): ?>
                    <div class="bg-orange-50 border-l-4 border-orange-500 p-3 rounded-r-lg mb-5 flex gap-2 items-center">
                        <span class="text-orange-500 text-sm">⏳</span>
                        <p class="text-xs font-semibold text-orange-700">Terlalu banyak percobaan. Tunggu 15 menit.</p>
                    </div>
                <?php endif; ?>

                <form action="../login_proses.php" method="POST" class="space-y-4" onsubmit="btnLoading(this)">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="relative pt-2">
                        <input type="text" name="username" id="username" placeholder=" " required autocomplete="off"
                            class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-medium outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all peer">
                        <label for="username" class="floating-label absolute left-4 top-5 text-slate-400 text-xs transition-all pointer-events-none bg-white px-1">
                            ID Panitia
                        </label>
                    </div>
                    
                    <div class="relative pt-2">
                        <input type="password" name="password" id="password" placeholder=" " required
                            class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-medium outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all peer">
                        <label for="password" class="floating-label absolute left-4 top-5 text-slate-400 text-xs transition-all pointer-events-none bg-white px-1">
                            Kata Sandi
                        </label>
                    </div>
                    
                    <button type="submit" id="btn-submit" class="w-full py-3.5 px-6 mt-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-200 transition-all hover:-translate-y-0.5 flex justify-center items-center gap-2 group">
                        <span>Masuk ke Dasbor</span>
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>

                <div class="mt-8 text-center text-[10px] text-slate-400">
                    Sistem SPMB &copy; <?php echo date('Y'); ?> SMK Pasundan 2.<br>
                    Tim IT SMKS Pasundan 2 Bandung.
                </div>
            </div>
        </div>
    </div>

    <script>
        const hour = new Date().getHours();
        const greetingEl = document.getElementById('greeting-text');
        if (hour >= 4 && hour < 10) greetingEl.innerHTML = "Selamat Pagi! ☕";
        else if (hour >= 10 && hour < 15) greetingEl.innerHTML = "Selamat Siang! ☀️";
        else if (hour >= 15 && hour < 18) greetingEl.innerHTML = "Selamat Sore! 🌅";
        else greetingEl.innerHTML = "Selamat Malam! 🌙";

        function btnLoading(form) {
            const btn = document.getElementById('btn-submit');
            btn.innerHTML = `<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...`;
            btn.classList.add('opacity-80', 'cursor-not-allowed');
        }

        const ctx = document.getElementById('miniChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 96); // Sesuaikan dengan tinggi canvas h-24 (96px)
        gradient.addColorStop(0, 'rgba(79, 70, 229, 0.25)');
        gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels_bulan); ?>,
                datasets: [{
                    data: <?php echo json_encode($data_pendaftar); ?>,
                    borderColor: '#4f46e5',
                    borderWidth: 2, 
                    fill: true, 
                    backgroundColor: gradient, 
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: '#4f46e5',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false }, 
                    tooltip: { backgroundColor: '#1e293b', padding: 8, displayColors: false } 
                },
                layout: {
                    padding: { top: 5, bottom: 0, left: 0, right: 0 } // Mencegah titik atas terpotong
                },
                scales: {
                    y: { display: false, beginAtZero: true },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 9, weight: 'bold' } } }
                },
                interaction: { mode: 'index', intersect: false }
            }
        });
    </script>
</body>
</html>
