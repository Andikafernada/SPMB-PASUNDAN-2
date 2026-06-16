<?php
session_start();
include 'config.php'; // WAJIB DIBUKA: Koneksi database aktif

// ==========================================
// LOGIKA BACKEND: CEK STATUS PENDAFTARAN
// ==========================================
$status_siswa = null;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cek_status'])) {
    $id_pendaftaran = htmlspecialchars(trim($_POST['id_pendaftaran']));

    $query = "SELECT id_siswa, id_pendaftaran, nama_lengkap, status_bayar, tpa_selesai FROM siswa WHERE id_pendaftaran = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $id_pendaftaran);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $status_siswa = $row;
        } else {
            $error_msg = "Waduh, ID Pendaftaran tidak ditemukan. Cek lagi ketikannya ya!";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Terjadi kesalahan pada sistem database kami.";
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon.svg">
    <link rel="apple-touch-icon" href="favicon.svg">

    <title>SPMB 2026 | SMK Pasundan 2 Bandung - Pendaftaran Gelombang 2</title>
    <meta name="description" content="Pendaftaran Gelombang 2 SMK Pasundan 2 Bandung telah dibuka! Raih masa depanmu dengan ekosistem pendidikan vokasi dan fasilitas industri modern.">
    <meta name="keywords" content="SMK Pasundan 2, PPDB SMK Bandung, Sekolah Vokasi, TKJ, TKR, TPM, TSM, TAV, SPMB 2026">
    <meta name="author" content="SMK Pasundan 2 Bandung">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://spmb.smkpasundan2.sch.id">

    <!-- Open Graph -->
    <meta property="og:title" content="Pendaftaran Siswa Baru - SMK Pasundan 2 Bandung">
    <meta property="og:description" content="Gelombang 2 telah dibuka! Gabung sekarang di ekosistem vokasi modern dengan fasilitas standar industri.">
    <meta property="og:image" content="https://i.ibb.co/3WwYV6t/logo-pasundan-placeholder.png">
    <meta property="og:url" content="https://spmb.smkpasundan2.sch.id">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SPMB SMK Pasundan 2 Bandung">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Pendaftaran Siswa Baru - SMK Pasundan 2 Bandung">
    <meta name="twitter:description" content="Gelombang 2 telah dibuka! Gabung sekarang di ekosistem vokasi modern.">

    <script src="https://cdn.tailwindcss.com"></script>
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
        .bento-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid rgba(0,0,0,0.05); }
        .bento-card:hover { transform: translateY(-5px) scale(1.01); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1); z-index: 10; border-color: rgba(59, 130, 246, 0.3); }
        .bg-pattern { background-image: radial-gradient(rgba(0,0,0,0.05) 1px, transparent 1px); background-size: 20px 20px; }

        /* Modal & FAQ Animations */
        .modal-enter { animation: modalFadeIn 0.3s ease-out forwards; }
        .modal-leave { animation: modalFadeOut 0.2s ease-in forwards; }
        @keyframes modalFadeIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        @keyframes modalFadeOut { from { opacity: 1; transform: scale(1) translateY(0); } to { opacity: 0; transform: scale(0.95) translateY(10px); } }
        .faq-content { max-height: 0; overflow: hidden; transition: max-height 0.4s ease-in-out; }
        .faq-icon { transition: transform 0.3s ease; }
        .faq-active .faq-content { max-height: 200px; }
        .faq-active .faq-icon { transform: rotate(180deg); color: #2563eb; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden selection:bg-blue-500 selection:text-white">

    <nav id="navbar" class="fixed w-full z-50 glass-nav transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="#" class="flex items-center gap-3 group">
                    <div class="w-12 h-12 flex items-center justify-center group-hover:scale-105 transition-transform duration-300">
                        <img src="logo.png" alt="Logo SMK Pasundan 2" class="w-full h-full object-contain drop-shadow-md">
                    </div>
                    <div>
                        <div class="font-outfit font-black text-lg text-slate-900 leading-none">SMK Pasundan 2</div>
                        <div class="text-[10px] text-blue-600 font-bold uppercase tracking-wider">Bandung</div>
                    </div>
                </a>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#jurusan" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition">Jurusan</a>
                    <a href="#kuis" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition">Kuis Minat</a>
                    <a href="#faq-section" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition">FAQ</a>
                    <a href="#cek-status" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition">Cek Status</a>
                </div>
                <div class="flex items-center gap-3">
                    <a href="panitia/index.php" class="hidden sm:flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                        <i class="fas fa-shield-halved text-blue-400"></i> Panitia
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative pt-32 pb-16 md:pt-40 md:pb-20 min-h-[95vh] flex flex-col items-center justify-center overflow-hidden">
        <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="max-w-5xl mx-auto px-4 relative z-10 text-center flex flex-col items-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-50 border border-orange-200 shadow-sm text-orange-600 text-xs font-bold mb-8 animate-fade-in-up">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                </span>
                Pendaftaran Gelombang 2 Terbatas!
            </div>

            <h1 class="text-5xl md:text-7xl font-outfit font-black text-slate-900 leading-[1.1] mb-6 tracking-tight animate-fade-in-up" style="animation-delay: 0.1s;">
                Kembangkan Potensi, <br class="hidden md:block">
                Raih <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 relative">
                    Masa Depanmu
                    <svg class="absolute w-full h-3 -bottom-1 left-0 text-blue-400 opacity-50" viewBox="0 0 100 10" preserveAspectRatio="none"><path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="transparent"/></svg>
                </span>
            </h1>

            <p class="text-slate-600 text-base md:text-lg max-w-2xl mb-8 font-medium animate-fade-in-up" style="animation-delay: 0.2s;">
                Ekosistem pendidikan vokasi modern. 5 Jurusan produktif yang dirancang khusus untuk mencetak generasi inovator dan tenaga ahli siap kerja.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 w-full max-w-md sm:max-w-none animate-fade-in-up mb-8" style="animation-delay: 0.3s;">
                <a href="public/daftar.php" class="w-full sm:w-auto flex items-center justify-center py-4 px-8 border border-transparent text-base font-bold rounded-2xl text-white bg-blue-600 hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1">
                    <i class="fas fa-rocket mr-2"></i> Daftar Gelombang 2
                </a>
                <a href="views/tpa/login.php" class="group relative w-full sm:w-auto flex items-center justify-center py-4 px-8 border border-amber-200 text-base font-black rounded-2xl text-amber-900 bg-gradient-to-r from-amber-300 to-amber-500 hover:from-amber-400 hover:to-amber-600 shadow-lg transition transform hover:-translate-y-1">
                    <div class="absolute -top-3 -right-3 bg-red-500 text-white text-[10px] font-black uppercase px-2 py-1 rounded-lg animate-pulse-fast shadow-md">Live Exam</div>
                    <i class="fas fa-laptop-code text-xl mr-2 group-hover:scale-110 transition-transform"></i> Portal TPA
                </a>
            </div>

            <div class="mt-4 pt-6 border-t border-slate-200/50 w-full max-w-md animate-fade-in-up" style="animation-delay: 0.4s;">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Penutupan Gelombang 2 Dalam:</p>
                <div class="flex justify-center gap-3 text-center">
                    <div class="flex flex-col"><span id="cd-days" class="text-2xl font-black text-slate-800 bg-white shadow-sm border border-slate-100 rounded-xl px-3 py-2 w-14">--</span><span class="text-[9px] uppercase font-bold text-slate-400 mt-1">Hari</span></div>
                    <div class="text-xl font-black text-slate-300 py-2">:</div>
                    <div class="flex flex-col"><span id="cd-hours" class="text-2xl font-black text-slate-800 bg-white shadow-sm border border-slate-100 rounded-xl px-3 py-2 w-14">--</span><span class="text-[9px] uppercase font-bold text-slate-400 mt-1">Jam</span></div>
                    <div class="text-xl font-black text-slate-300 py-2">:</div>
                    <div class="flex flex-col"><span id="cd-mins" class="text-2xl font-black text-slate-800 bg-white shadow-sm border border-slate-100 rounded-xl px-3 py-2 w-14">--</span><span class="text-[9px] uppercase font-bold text-slate-400 mt-1">Menit</span></div>
                    <div class="text-xl font-black text-slate-300 py-2">:</div>
                    <div class="flex flex-col"><span id="cd-secs" class="text-2xl font-black text-orange-600 bg-orange-50 shadow-sm border border-orange-100 rounded-xl px-3 py-2 w-14">--</span><span class="text-[9px] uppercase font-bold text-orange-400 mt-1">Detik</span></div>
                </div>
            </div>
        </div>
    </section>

    <section id="kuis" class="py-16 bg-slate-900 relative overflow-hidden text-white">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDkyNTUsMjU1LDAuMSkiLz48L3N2Zz4=')] opacity-30"></div>
        <div class="absolute right-0 top-1/2 -translate-y-1/2 w-96 h-96 bg-blue-600/20 rounded-full blur-[100px]"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10">
            <div class="flex flex-col md:flex-row items-center justify-between gap-10 bg-white/5 border border-white/10 rounded-3xl p-8 md:p-12 backdrop-blur-md reveal">
                <div class="flex-1">
                    <div class="inline-block px-3 py-1 bg-blue-500/20 text-blue-300 text-xs font-bold rounded-full mb-4 border border-blue-500/30">
                        🎮 Sistem Matchmaking Jurusan
                    </div>
                    <h2 class="text-3xl md:text-4xl font-outfit font-black mb-4">Masih Bingung Pilih Jurusan?</h2>
                    <p class="text-slate-400 mb-8 max-w-lg">Mainkan kuis psikologi singkat kami dan temukan "Hero Class" yang paling cocok dengan kepribadian dan minat bakatmu!</p>

                    <button onclick="openKuis()" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 font-bold rounded-xl shadow-[0_0_20px_rgba(37,99,235,0.4)] transition-all hover:scale-105">
                        <i class="fas fa-gamepad"></i> Mulai Kuis Minat
                    </button>
                </div>
                <div class="flex-1 flex justify-center relative">
                    <div class="w-64 h-64 relative animate-float">
                        <div class="absolute inset-0 border-4 border-dashed border-blue-500/50 rounded-full animate-[spin_10s_linear_infinite]"></div>
                        <div class="absolute inset-4 border-2 border-indigo-400/30 rounded-full animate-[spin_15s_linear_infinite_reverse]"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-user-astronaut text-8xl text-transparent bg-clip-text bg-gradient-to-b from-blue-400 to-indigo-600 filter drop-shadow-[0_0_15px_rgba(59,130,246,0.8)]"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="jurusan" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-16 reveal">
                <h2 class="text-4xl font-outfit font-black text-slate-900 mb-4">Program Keahlian</h2>
                <p class="text-slate-500 max-w-2xl mx-auto">Kurikulum tersinkronisasi industri dengan fasilitas lab standar profesional yang didesain untuk masa depan.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 auto-rows-[auto]">
                <div class="bento-card bg-slate-50 rounded-3xl p-8 cursor-pointer reveal md:col-span-2 md:row-span-2 relative overflow-hidden group flex flex-col justify-between min-h-[300px]">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-blue-100 rounded-full blur-3xl -mr-20 -mt-20 transition-all group-hover:bg-blue-200"></div>
                    <div class="relative z-10 mb-6">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6 text-blue-600 shadow-inner"><i class="fas fa-network-wired text-3xl"></i></div>
                        <div class="text-xs font-black tracking-widest text-blue-500 mb-2">FAVORIT • TKJ</div>
                        <h3 class="text-3xl font-outfit font-black text-slate-800 mb-3 leading-tight">Teknik Komputer <br>& Jaringan</h3>
                        <p class="text-slate-600 text-sm leading-relaxed max-w-sm">Ahli infrastruktur jaringan, routing, administrasi server Linux, hingga dasar keamanan siber (Cyber Security).</p>
                    </div>
                </div>
                <div class="bento-card bg-slate-50 rounded-3xl p-8 cursor-pointer reveal md:col-span-2 relative overflow-hidden group">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4 text-orange-600"><i class="fas fa-car-side text-xl"></i></div>
                    <div class="text-xs font-black tracking-widest text-slate-400 mb-1">TKR</div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Kendaraan Ringan</h3>
                    <p class="text-slate-500 text-sm">Servis & modifikasi mesin otomotif roda empat berteknologi tinggi.</p>
                </div>
                <div class="bento-card bg-slate-50 rounded-3xl p-8 cursor-pointer reveal md:col-span-1 lg:col-span-1 bg-pattern relative group">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mb-4 text-red-600"><i class="fas fa-motorcycle text-xl"></i></div>
                    <div class="text-xs font-black tracking-widest text-slate-400 mb-1">TSM</div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Sepeda Motor</h3>
                    <p class="text-slate-500 text-sm">Injeksi & sasis roda dua standar bengkel resmi.</p>
                </div>
                <div class="bento-card bg-slate-50 rounded-3xl p-8 cursor-pointer reveal md:col-span-1 lg:col-span-1 relative group">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4 text-purple-600"><i class="fas fa-video text-xl"></i></div>
                    <div class="text-xs font-black tracking-widest text-slate-400 mb-1">TAV</div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Audio Video</h3>
                    <p class="text-slate-500 text-sm">Elektronika digital & rekayasa sinyal media.</p>
                </div>
                <div class="bento-card bg-emerald-900 text-white rounded-3xl p-8 cursor-pointer reveal md:col-span-3 lg:col-span-4 flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden group">
                    <div class="absolute right-0 bottom-0 text-9xl text-emerald-800 opacity-30 transform translate-x-10 translate-y-10 group-hover:rotate-12 transition-transform duration-700"><i class="fas fa-cogs"></i></div>
                    <div class="relative z-10 max-w-2xl">
                        <div class="flex items-center gap-3 mb-3"><span class="px-3 py-1 bg-emerald-800 text-emerald-300 text-xs font-bold rounded-full">TPM</span><h3 class="text-2xl font-bold">Teknik Pemesinan</h3></div>
                        <p class="text-emerald-100 text-sm">Menguasai alat manufaktur industri modern, pemrograman CNC, dan presisi pembentukan logam.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq-section" class="py-24 bg-slate-50 relative border-t border-slate-200/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12 reveal">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-600 mb-4"><i class="fas fa-question text-xl"></i></div>
                <h2 class="text-3xl font-outfit font-black text-slate-900 mb-4">Pertanyaan yang Sering Diajukan</h2>
            </div>

            <div class="space-y-4 reveal">
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden faq-item cursor-pointer shadow-sm hover:shadow-md transition-shadow">
                    <div class="px-6 py-5 flex justify-between items-center bg-white">
                        <h4 class="font-bold text-slate-800 pr-8">Kapan Pendaftaran Gelombang 2 Ditutup?</h4>
                        <i class="fas fa-chevron-down text-slate-400 faq-icon flex-shrink-0"></i>
                    </div>
                    <div class="faq-content bg-slate-50/50 px-6">
                        <p class="text-slate-600 text-sm pb-5 leading-relaxed">Pendaftaran Gelombang 2 akan segera ditutup ketika kuota kursi tersisa sudah penuh. Mengingat antusiasme yang tinggi, kami menyarankan Anda untuk segera mendaftar secara online melalui website ini.</p>
                    </div>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden faq-item cursor-pointer shadow-sm hover:shadow-md transition-shadow">
                    <div class="px-6 py-5 flex justify-between items-center bg-white">
                        <h4 class="font-bold text-slate-800 pr-8">Apakah ada sistem cicilan untuk biaya masuk?</h4>
                        <i class="fas fa-chevron-down text-slate-400 faq-icon flex-shrink-0"></i>
                    </div>
                    <div class="faq-content bg-slate-50/50 px-6">
                        <p class="text-slate-600 text-sm pb-5 leading-relaxed">Tentu. Kami memiliki sistem administrasi yang fleksibel. Anda dapat berdiskusi langsung dengan panitia PPDB kami untuk skema pembayaran yang paling meringankan.</p>
                    </div>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden faq-item cursor-pointer shadow-sm hover:shadow-md transition-shadow">
                    <div class="px-6 py-5 flex justify-between items-center bg-white">
                        <h4 class="font-bold text-slate-800 pr-8">Bagaimana proses Tes Potensi Akademik (TPA)?</h4>
                        <i class="fas fa-chevron-down text-slate-400 faq-icon flex-shrink-0"></i>
                    </div>
                    <div class="faq-content bg-slate-50/50 px-6">
                        <p class="text-slate-600 text-sm pb-5 leading-relaxed">TPA dilakukan 100% secara online melalui sistem e-Learning kami (Portal TPA). Setelah Anda mendapatkan ID Pendaftaran dan menyelesaikan administrasi awal, akses ujian akan otomatis terbuka di menu "Cek Status".</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="cek-status" class="py-24 bg-slate-950 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[800px] h-[800px] bg-blue-600/10 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/3"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 relative z-10">
            <div class="text-center mb-12 reveal">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-900/50 border border-blue-500/30 mb-6"><i class="fas fa-radar text-2xl text-blue-400 animate-pulse"></i></div>
                <h2 class="text-4xl font-outfit font-black text-white mb-4">Lacak Status Pendaftaran</h2>
                <p class="text-slate-400">Masukkan ID Pendaftaran Anda untuk membuka akses ujian dan mengecek kelengkapan berkas.</p>
            </div>
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 md:p-12 shadow-2xl reveal relative overflow-hidden">
                <form method="POST" action="#cek-status" class="max-w-lg mx-auto mb-8 relative group">
                    <div class="relative flex items-center">
                        <i class="fas fa-id-card absolute left-5 text-slate-400 group-focus-within:text-blue-500 transition-colors z-10"></i>
                        <input type="text" name="id_pendaftaran" placeholder="Contoh: SPMB26-001" required class="w-full pl-14 pr-32 py-5 bg-slate-900/50 border-2 border-slate-700 text-white rounded-2xl focus:outline-none focus:border-blue-500 focus:bg-slate-900 transition-all uppercase font-bold shadow-inner placeholder-slate-600">
                        <button type="submit" name="cek_status" class="absolute right-2 top-2 bottom-2 px-8 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition transform hover:scale-105 active:scale-95">CEK</button>
                    </div>
                </form>

                <?php if ($error_msg): ?>
                    <div class="p-4 bg-red-500/20 border border-red-500/50 text-red-200 rounded-2xl text-center font-medium text-sm animate-pulse max-w-lg mx-auto">
                        <i class="fas fa-exclamation-triangle mr-2"></i><?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <?php if ($status_siswa): ?>
                    <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-6 shadow-xl animate-fade-in-up max-w-2xl mx-auto">
                        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-700">
                            <div class="w-14 h-14 bg-slate-700 rounded-full flex items-center justify-center text-white text-xl font-bold border border-slate-600 shadow-inner">
                                <?= substr($status_siswa['nama_lengkap'], 0, 1) ?>
                            </div>
                            <div>
                                <div class="text-xs text-blue-400 font-bold uppercase tracking-wider mb-1">Data Ditemukan</div>
                                <div class="text-xl font-outfit font-black text-white"><?= htmlspecialchars($status_siswa['nama_lengkap']) ?></div>
                            </div>
                        </div>

                        <?php if (strtoupper($status_siswa['status_bayar']) === 'LUNAS'): ?>
                            <?php if ($status_siswa['tpa_selesai'] == 0): ?>
                                <div class="bg-gradient-to-r from-blue-900/50 to-indigo-900/50 border border-blue-500/30 p-6 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-6 relative overflow-hidden">
                                    <div class="relative z-10 text-center sm:text-left">
                                        <h4 class="font-black text-white text-lg mb-1">Akses Ujian Terbuka! 🎯</h4>
                                    </div>
                                    <form action="views/tpa/login.php" method="POST" class="w-full sm:w-auto relative z-10">
                                        <input type="hidden" name="id_pendaftaran" value="<?= htmlspecialchars($status_siswa['id_pendaftaran']) ?>">
                                        <button type="submit" class="w-full px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-[0_0_20px_rgba(37,99,235,0.6)]">Mulai TPA</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="bg-emerald-900/30 border border-emerald-500/30 p-5 rounded-xl flex items-center justify-between gap-4">
                                    <div><h4 class="font-bold text-emerald-300">Ujian TPA Selesai</h4></div>
                                    <a href="views/tpa/hasil.php?id=<?= $status_siswa['id_siswa'] ?>" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg">Lihat Hasil</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="bg-slate-900/50 border border-slate-700 p-5 rounded-xl text-slate-400">
                                <h4 class="font-bold text-slate-300">TPA Terkunci</h4>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php $wa_admin = $_ENV['WA_ADMIN'] ?? '6280000000000'; ?>
    <a href="https://wa.me/<?= $wa_admin ?>?text=Halo%20Bang%20Jangkrik,%20mau%20konsultasi%20PPDB!" target="_blank" class="fixed bottom-6 right-6 z-50 group flex flex-col items-end gap-2 animate-float">
        <div class="opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 bg-white border border-slate-200 text-slate-800 text-xs font-bold px-4 py-3 rounded-2xl rounded-br-none shadow-xl">
            <span class="text-amber-600">Minta wangsit jurusan?</span><br>Tanya Bang Jangkrik sini! 🦗🔮
        </div>
        <div class="w-16 h-16 bg-gradient-to-tr from-green-500 to-emerald-400 hover:from-green-400 hover:to-emerald-300 rounded-full flex items-center justify-center shadow-2xl border-4 border-white transition-transform duration-300 transform group-hover:scale-110 group-hover:rotate-12 cursor-pointer relative">
            <span class="text-3xl filter drop-shadow-md">🦗</span>
            <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 border-2 border-white rounded-full animate-pulse"></div>
        </div>
    </a>

    <footer class="bg-slate-950 py-8 text-center border-t border-slate-800/50 relative z-20">
        <p class="text-slate-500 text-sm font-medium">&copy; <?= date('Y') ?> SMK Pasundan 2 Bandung. Hak Cipta Dilindungi.</p>
    </footer>

    <div id="kuis-modal" class="fixed inset-0 z-[100] bg-slate-900/80 backdrop-blur-sm hidden flex items-center justify-center px-4">
        <div id="kuis-box" class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden relative">
            <div class="bg-blue-600 p-6 text-white relative">
                <button onclick="closeKuis()" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 transition"><i class="fas fa-times"></i></button>
                <div class="text-xs font-bold tracking-widest text-blue-200 mb-1">MINI GAME</div>
                <h3 class="text-2xl font-outfit font-black">Cari Hero Class-mu!</h3>
            </div>

            <div class="p-6 md:p-8" id="kuis-content">
                </div>
        </div>
    </div>

    <script>
        // 1. NAV SCROLL & REVEAL ANIMATION
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

        const revealOnScroll = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });
        document.querySelectorAll('.reveal').forEach(el => revealOnScroll.observe(el));

        // 2. COUNTDOWN TIMER GELOMBANG 2 (Target 14 Hari dari sekarang)
        const targetDate = new Date();
        targetDate.setDate(targetDate.getDate() + 14);

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = targetDate - now;

            if (distance < 0) return; // Waktu Habis

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("cd-days").innerText = days.toString().padStart(2, '0');
            document.getElementById("cd-hours").innerText = hours.toString().padStart(2, '0');
            document.getElementById("cd-mins").innerText = minutes.toString().padStart(2, '0');
            document.getElementById("cd-secs").innerText = seconds.toString().padStart(2, '0');
        }
        setInterval(updateCountdown, 1000);
        updateCountdown();

        // 3. FAQ ACCORDION LOGIC
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            item.addEventListener('click', () => {
                const isActive = item.classList.contains('faq-active');
                // Tutup semua dulu
                faqItems.forEach(el => el.classList.remove('faq-active'));
                // Buka jika sebelumnya tertutup
                if (!isActive) item.classList.add('faq-active');
            });
        });

        // 4. LOGIKA KUIS MINAT INTERAKTIF
        const kuisModal = document.getElementById('kuis-modal');
        const kuisBox = document.getElementById('kuis-box');
        const kuisContent = document.getElementById('kuis-content');

        const questions = [
            {
                q: "Saat melihat barang elektronik mati, insting pertamamu?",
                options: [
                    { t: "Bongkar casing dan cek mesinnya.", v: "hardware" },
                    { t: "Cek kabel jaringan dan software-nya.", v: "software" },
                    { t: "Bikin video tutorial cara memperbaikinya.", v: "media" }
                ]
            },
            {
                q: "Kamu lebih suka bekerja dengan...",
                options: [
                    { t: "Oli, Besi, & Kendaraan.", v: "hardware" },
                    { t: "Kamera, Audio, & Editing.", v: "media" },
                    { t: "Coding, Server, & Internet.", v: "software" }
                ]
            },
            {
                q: "Suasana kerja impianmu di masa depan?",
                options: [
                    { t: "Di studio penyiaran atau event besar.", v: "media" },
                    { t: "Di ruangan server / kantor IT ber-AC.", v: "software" },
                    { t: "Di pabrik / bengkel industri ternama.", v: "hardware" }
                ]
            }
        ];

        let currentQ = 0;
        let scores = { hardware: 0, software: 0, media: 0 };

        function openKuis() {
            currentQ = 0;
            scores = { hardware: 0, software: 0, media: 0 };
            kuisModal.classList.remove('hidden');
            kuisBox.classList.remove('modal-leave');
            kuisBox.classList.add('modal-enter');
            renderQuestion();
        }

        function closeKuis() {
            kuisBox.classList.remove('modal-enter');
            kuisBox.classList.add('modal-leave');
            setTimeout(() => { kuisModal.classList.add('hidden'); }, 200);
        }

        function renderQuestion() {
            if (currentQ >= questions.length) { showResult(); return; }

            let qData = questions[currentQ];
            let html = `
                <div class="text-sm font-bold text-slate-400 mb-2">Pertanyaan ${currentQ + 1} dari ${questions.length}</div>
                <h4 class="text-xl font-bold text-slate-800 mb-6">${qData.q}</h4>
                <div class="space-y-3">
            `;

            qData.options.forEach((opt, index) => {
                html += `<button onclick="answerKuis('${opt.v}')" class="w-full text-left p-4 rounded-xl border-2 border-slate-100 hover:border-blue-500 hover:bg-blue-50 transition-all font-medium text-slate-600 hover:text-blue-700">
                    ${String.fromCharCode(65 + index)}. ${opt.t}
                </button>`;
            });
            html += `</div>`;
            kuisContent.innerHTML = html;
        }

        function answerKuis(value) {
            scores[value]++;
            currentQ++;
            renderQuestion();
        }

        function showResult() {
            // Tentukan pemenang (tertinggi)
            let resultType = Object.keys(scores).reduce((a, b) => scores[a] > scores[b] ? a : b);

            let heroName = ""; let heroDesc = ""; let icon = ""; let color = "";

            if (resultType === 'software') {
                heroName = "Cyber Wizard (TKJ)";
                heroDesc = "Cocok banget! Logikamu jalan buat ngurusin server, jaringan, dan peretasan putih.";
                icon = "fa-network-wired"; color = "text-blue-500 bg-blue-100";
            } else if (resultType === 'media') {
                heroName = "Sound Maestro (TAV)";
                heroDesc = "Kreativitasmu butuh wadah. Cocok masuk tim broadcasting dan rekayasa media digital!";
                icon = "fa-video"; color = "text-purple-500 bg-purple-100";
            } else {
                heroName = "Mechanic Warrior (TPM/TKR/TSM)";
                heroDesc = "Fisik dan teknikmu seimbang. Kamu lahir untuk menaklukkan mesin dan manufaktur otomotif.";
                icon = "fa-cogs"; color = "text-orange-500 bg-orange-100";
            }

            kuisContent.innerHTML = `
                <div class="text-center animate-fade-in-up">
                    <div class="w-20 h-20 mx-auto rounded-full ${color} flex items-center justify-center mb-4">
                        <i class="fas ${icon} text-4xl"></i>
                    </div>
                    <h4 class="text-2xl font-outfit font-black text-slate-800 mb-2">Class: ${heroName}</h4>
                    <p class="text-slate-600 mb-8">${heroDesc}</p>
                    <a href="public/daftar.php" class="block w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition">
                        Daftar Sebagai Hero Ini Sekarang
                    </a>
                </div>
            `;
        }
    </script>
</body>
</html>
