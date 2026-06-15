<?php
session_start();
// include 'config.php'; // Uncomment untuk produksi

// ==========================================
// LOGIKA BACKEND: CEK STATUS PENDAFTARAN
// ==========================================
$status_siswa = null;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cek_status'])) {
    $id_pendaftaran = htmlspecialchars(trim($_POST['id_pendaftaran']));
    
    // SIMULASI DB - Ganti dengan query MySQLi Prepare Anda
    if ($id_pendaftaran === 'SPMB-001') {
        $status_siswa = [
            'id_siswa' => 1, 
            'id_pendaftaran' => 'SPMB-001', 
            'nama_lengkap' => 'Siswa Testing', 
            'status_bayar' => 'LUNAS', 
            'tpa_selesai' => 0
        ];
    } else {
        $error_msg = "Waduh, ID Pendaftaran tidak ditemukan. Cek lagi ketikannya ya!";
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPMB 2026 | SMK Pasundan 2 Bandung</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- KONFIGURASI ANIMASI CUSTOM TAILWIND -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'], outfit: ['Outfit', 'sans-serif'] },
                    animation: {
                        'blob': 'blob 7s infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'fade-in-up': 'fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                        'pulse-fast': 'pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite'
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
                            '50%': { transform: 'translateY(-20px)' }
                        },
                        fadeInUp: {
                            '0%': { opacity: 0, transform: 'translateY(40px)' },
                            '100%': { opacity: 1, transform: 'translateY(0)' }
                        }
                    }
                }
            }
        }
    </script>

    <style>
        .glass-nav { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.3); }
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s cubic-bezier(0.5, 0, 0, 1); }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .card-hover:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.15); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden selection:bg-blue-500 selection:text-white">

    <!-- ================= NAVBAR (Akses Panitia & Marketing) ================= -->
    <nav id="navbar" class="fixed w-full z-50 glass-nav transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <a href="#" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:rotate-6 transition-transform">
                        <span class="text-white font-black text-xs">P2</span>
                    </div>
                    <div>
                        <div class="font-outfit font-black text-lg text-slate-900 leading-none">SMK Pasundan 2</div>
                        <div class="text-[10px] text-blue-600 font-bold uppercase tracking-wider">Bandung</div>
                    </div>
                </a>
                
                <!-- Nav Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#jurusan" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition">Jurusan</a>
                    <a href="#kuis" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition">Kuis Minat</a>
                    <a href="#cek-status" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition">Cek Status</a>
                </div>

                <!-- Akses Cepat & Panitia -->
                <div class="flex items-center gap-3">
                    <a href="login.php" class="hidden sm:flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                        <i class="fas fa-shield-halved text-blue-400"></i> Panitia
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ================= HERO SECTION (Dynamic Visuals) ================= -->
    <section class="relative pt-32 pb-20 md:pt-40 md:pb-32 min-h-[90vh] flex items-center justify-center overflow-hidden">
        <!-- Animated Background Blobs -->
        <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="max-w-5xl mx-auto px-4 relative z-10 text-center flex flex-col items-center">
            
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-blue-100 shadow-sm text-blue-600 text-xs font-bold mb-8 animate-fade-in-up">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                Pendaftaran Gelombang 1 Dibuka!
            </div>

            <h1 class="text-5xl md:text-7xl font-outfit font-black text-slate-900 leading-[1.1] mb-6 tracking-tight animate-fade-in-up" style="animation-delay: 0.1s;">
                Kembangkan Potensi, <br class="hidden md:block">
                Raih <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 relative">
                    Masa Depanmu
                    <svg class="absolute w-full h-3 -bottom-1 left-0 text-blue-400 opacity-50" viewBox="0 0 100 10" preserveAspectRatio="none"><path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="transparent"/></svg>
                </span>
            </h1>
            
            <p class="text-slate-600 text-base md:text-lg max-w-2xl mb-10 font-medium animate-fade-in-up" style="animation-delay: 0.2s;">
                Ekosistem pendidikan vokasi modern. 5 Jurusan produktif yang dirancang khusus untuk mencetak generasi inovator dan tenaga ahli siap kerja.
            </p>

            <!-- Action Buttons (Marketing & Operasional Kelas) -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 w-full max-w-md sm:max-w-none animate-fade-in-up" style="animation-delay: 0.3s;">
                
                <!-- CTA DAFTAR REGULER (Marketing Utama) -->
                <a href="public/daftar.php" class="w-full sm:w-auto flex items-center justify-center py-4 px-8 border border-transparent text-base font-bold rounded-2xl text-white bg-blue-600 hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1">
                    <i class="fas fa-rocket mr-2"></i> Daftar Sekarang
                </a>

                <!-- CTA TPA SEMENTARA (Operasional Kelas - Sangat Mencolok) -->
                <a href="views/tpa/login.php" class="group relative w-full sm:w-auto flex items-center justify-center py-4 px-8 border border-amber-200 text-base font-black rounded-2xl text-amber-900 bg-gradient-to-r from-amber-300 to-amber-500 hover:from-amber-400 hover:to-amber-600 shadow-lg transition transform hover:-translate-y-1">
                    <div class="absolute -top-3 -right-3 bg-red-500 text-white text-[10px] font-black uppercase px-2 py-1 rounded-lg animate-pulse-fast shadow-md">
                        Live Exam
                    </div>
                    <i class="fas fa-laptop-code text-xl mr-2 group-hover:scale-110 transition-transform"></i>
                    Portal TPA
                </a>
            </div>
        </div>
    </section>

    <!-- ================= 5 JURUSAN (Interactive Cards) ================= -->
    <section id="jurusan" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-16 reveal">
                <h2 class="text-4xl font-outfit font-black text-slate-900 mb-4">Program Keahlian</h2>
                <p class="text-slate-500 max-w-2xl mx-auto">Kurikulum tersinkronisasi industri dengan fasilitas lab standar profesional.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Data Jurusan -->
                <?php
                $jurusans = [
                    ['id' => 'TKJ', 'nama' => 'Teknik Komputer & Jaringan', 'icon' => 'fa-network-wired', 'color' => 'blue', 'desc' => 'Infrastruktur jaringan, routing, server Linux, & keamanan siber.'],
                    ['id' => 'TPM', 'nama' => 'Teknik Pemesinan', 'icon' => 'fa-cogs', 'color' => 'emerald', 'desc' => 'Manufaktur industri, CNC, dan presisi pembentukan logam.'],
                    ['id' => 'TKR', 'nama' => 'Teknik Kendaraan Ringan', 'icon' => 'fa-car-side', 'color' => 'orange', 'desc' => 'Servis & modifikasi mesin otomotif roda empat berteknologi tinggi.'],
                    ['id' => 'TSM', 'nama' => 'Teknik Sepeda Motor', 'icon' => 'fa-motorcycle', 'color' => 'red', 'desc' => 'Perawatan mesin, injeksi, dan sasis roda dua standar bengkel resmi.'],
                    ['id' => 'TAV', 'nama' => 'Teknik Audio Video', 'icon' => 'fa-video', 'color' => 'purple', 'desc' => 'Elektronika digital, broadcasting, dan rekayasa sinyal media.']
                ];
                
                foreach ($jurusans as $index => $j):
                    $colorBg = "bg-{$j['color']}-50";
                    $colorIcon = "text-{$j['color']}-600";
                    $delay = $index * 100;
                ?>
                <div class="bg-slate-50 rounded-3xl p-8 border border-slate-100 card-hover transition duration-300 cursor-pointer reveal" style="transition-delay: <?= $delay ?>ms;">
                    <div class="w-16 h-16 <?= $colorBg ?> rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas <?= $j['icon'] ?> text-3xl <?= $colorIcon ?>"></i>
                    </div>
                    <div class="text-xs font-black tracking-widest text-slate-400 mb-2"><?= $j['id'] ?></div>
                    <h3 class="text-xl font-bold text-slate-800 mb-3 leading-tight"><?= $j['nama'] ?></h3>
                    <p class="text-slate-500 text-sm leading-relaxed"><?= $j['desc'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ================= CEK STATUS & UNLOCK TPA (The Magic Reveal) ================= -->
    <section id="cek-status" class="py-24 bg-slate-900 relative overflow-hidden">
        <!-- Abstract BG Elements -->
        <div class="absolute top-0 right-0 w-[800px] h-[800px] bg-blue-600/10 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/3"></div>
        
        <div class="max-w-4xl mx-auto px-4 sm:px-6 relative z-10">
            <div class="text-center mb-12 reveal">
                <i class="fas fa-radar text-4xl text-blue-500 mb-4 animate-pulse"></i>
                <h2 class="text-4xl font-outfit font-black text-white mb-4">Lacak Status Pendaftaran</h2>
                <p class="text-slate-400">Masukkan ID Pendaftaran Anda untuk membuka akses ujian dan mengecek kelengkapan berkas.</p>
            </div>

            <!-- Form Cek -->
            <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-8 md:p-12 shadow-2xl reveal">
                <form method="POST" action="#cek-status" class="max-w-lg mx-auto mb-8 relative group">
                    <div class="relative flex items-center">
                        <i class="fas fa-id-card absolute left-5 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                        <input type="text" name="id_pendaftaran" placeholder="Contoh: SPMB-001" required 
                               class="w-full pl-14 pr-32 py-5 bg-white border-2 border-transparent rounded-2xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all uppercase font-bold text-slate-700 shadow-inner">
                        <button type="submit" name="cek_status" class="absolute right-2 top-2 bottom-2 px-8 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition transform hover:scale-105 active:scale-95">
                            CEK
                        </button>
                    </div>
                </form>

                <?php if ($error_msg): ?>
                    <div class="p-4 bg-red-500/20 border border-red-500/50 text-red-200 rounded-2xl text-center font-medium text-sm animate-pulse">
                        <i class="fas fa-exclamation-triangle mr-2"></i><?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <?php if ($status_siswa): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-xl animate-fade-in-up">
                        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100">
                            <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center text-blue-600 text-xl font-bold border border-blue-100">
                                <?= substr($status_siswa['nama_lengkap'], 0, 1) ?>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Data Ditemukan</div>
                                <div class="text-xl font-outfit font-black text-slate-800"><?= htmlspecialchars($status_siswa['nama_lengkap']) ?></div>
                            </div>
                            <div class="ml-auto text-right">
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full">
                                    <?= htmlspecialchars($status_siswa['status_bayar']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- UNLOCK TPA LOGIC -->
                        <?php if ($status_siswa['status_bayar'] === 'LUNAS'): ?>
                            <?php if ($status_siswa['tpa_selesai'] == 0): ?>
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 p-6 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-6 relative overflow-hidden">
                                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl"></div>
                                    <div class="relative z-10 text-center sm:text-left">
                                        <h4 class="font-black text-blue-900 text-lg mb-1">Akses Ujian Terbuka! 🚀</h4>
                                        <p class="text-sm text-blue-700">Silakan kerjakan Tes Potensi Akademik sekarang.</p>
                                    </div>
                                    <form action="views/tpa/login.php" method="POST" class="w-full sm:w-auto relative z-10">
                                        <input type="hidden" name="id_pendaftaran" value="<?= htmlspecialchars($status_siswa['id_pendaftaran']) ?>">
                                        <button type="submit" class="w-full px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/40 transition transform hover:-translate-y-1">
                                            Mulai TPA
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="bg-emerald-50 border border-emerald-200 p-5 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600"><i class="fas fa-check"></i></div>
                                        <div>
                                            <h4 class="font-bold text-emerald-900">Ujian TPA Selesai</h4>
                                            <p class="text-xs text-emerald-600">Terima kasih telah mengerjakan.</p>
                                        </div>
                                    </div>
                                    <a href="views/tpa/hasil.php?id=<?= $status_siswa['id_siswa'] ?>" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition">Lihat Hasil</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="bg-slate-50 border border-slate-200 p-5 rounded-xl flex items-center gap-4 text-slate-500">
                                <i class="fas fa-lock text-2xl"></i>
                                <div>
                                    <h4 class="font-bold text-slate-700">TPA Terkunci</h4>
                                    <p class="text-sm">Selesaikan pembayaran untuk membuka akses ujian.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ================= WIDGET BANG JANGKRIK (WA) ================= -->
    <a href="https://wa.me/6281234567890?text=Halo%20Bang%20Jangkrik,%20mau%20konsultasi%20PPDB!" target="_blank" 
       class="fixed bottom-6 right-6 z-50 group flex flex-col items-end gap-2 animate-float">
        <!-- Tooltip Wangsit -->
        <div class="opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 bg-white border border-slate-200 text-slate-800 text-xs font-bold px-4 py-3 rounded-2xl rounded-br-none shadow-xl">
            <span class="text-amber-600">Minta wangsit jurusan?</span><br>Tanya Bang Jangkrik sini! 🦗🔧
        </div>
        <!-- Button Maskot -->
        <div class="w-16 h-16 bg-gradient-to-tr from-green-500 to-emerald-400 hover:from-green-400 hover:to-emerald-300 rounded-full flex items-center justify-center shadow-2xl border-4 border-white transition-transform duration-300 transform group-hover:scale-110 group-hover:rotate-12 cursor-pointer relative">
            <span class="text-3xl filter drop-shadow-md">🦗</span>
            <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 border-2 border-white rounded-full animate-pulse"></div>
        </div>
    </a>

    <!-- Footer -->
    <footer class="bg-slate-950 py-8 text-center border-t border-slate-800">
        <p class="text-slate-500 text-sm font-medium">&copy; <?= date('Y') ?> SMK Pasundan 2 Bandung. Hak Cipta Dilindungi.</p>
    </footer>

    <!-- SCRIPT SCROLL REVEAL & NAVBAR BEHAVIOR -->
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('navbar');
            if (window.scrollY > 20) {
                nav.classList.add('shadow-sm');
                nav.style.background = 'rgba(255, 255, 255, 0.85)';
            } else {
                nav.classList.remove('shadow-sm');
                nav.style.background = 'rgba(255, 255, 255, 0.75)';
            }
        });

        // Scroll Reveal Animation (Intersection Observer)
        const revealElements = document.querySelectorAll('.reveal');
        
        const revealOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };

        const revealOnScroll = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, revealOptions);

        revealElements.forEach(el => revealOnScroll.observe(el));
    </script>
</body>
</html>
