<?php
/**
 * TIM PENDAFTARAN - Admin Page
 * IP Restricted: Only accessible from internal network
 */
include '../../config.php';

// Check admin IP restriction
require_admin_ip();

// Check session
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pendaftaran','superuser'])) {
    header("Location: ../../panitia/index.php"); exit();
}

$menu    = isset($_GET['menu']) ? $_GET['menu'] : 'input';
$petugas = $_SESSION['nama'];

// Statistik
$total_all    = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa"));
$total_saya   = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE petugas_pendaftar='".mysqli_real_escape_string($conn,$petugas)."'"));
$today        = date('Y-m-d');
$total_hari   = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE DATE(tgl_daftar)='$today' AND petugas_pendaftar='".mysqli_real_escape_string($conn,$petugas)."'"));
$stats_jur    = mysqli_query($conn, "SELECT jurusan, COUNT(*) as jml FROM siswa GROUP BY jurusan ORDER BY jml DESC");

// Data tabel (hanya milik petugas ini, terbaru)
$data_saya = mysqli_query($conn, "SELECT * FROM siswa WHERE petugas_pendaftar='".mysqli_real_escape_string($conn,$petugas)."' ORDER BY id_siswa DESC LIMIT 50");

// Hitung persentase kontribusi petugas
$kontribusi_pct = $total_all > 0 ? round(($total_saya / $total_all) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pendaftaran | SPMB SMK Pasundan 2</title>

    <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="../../assets/css/quick-wins.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="../../assets/js/quick-wins.js"></script>

    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ['Plus Jakarta Sans', 'sans-serif'],
              outfit: ['Outfit', 'sans-serif'],
            },
            colors: {
                cyber: { cyan: '#06b6d4', blue: '#3b82f6', indigo: '#4f46e5', purple: '#8b5cf6' }
            }
          }
        }
      }
    </script>

    <style>
        /* Animasi Masuk */
        @keyframes fade-in {
            0% { opacity: 0; transform: translateY(15px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        /* Custom Scrollbar */
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }

        /* Pages logic */
        .page-content { display: none; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .page-content.active { display: block; opacity: 1; animation: fadeInTab 0.4s ease-out forwards; }
        @keyframes fadeInTab { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* SELECT2 HYBRID MODE (Light Background, Neon Focus) */
        .select2-container--default .select2-selection--single {
            background-color: #ffffff !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 0.75rem !important;
            height: 3.25rem !important;
            display: flex; align-items: center;
            transition: all 0.3s;
        }
        .select2-container--default.select2-container--open .select2-selection--single,
        .select2-container--default .select2-selection--single:focus {
            border-color: #06b6d4 !important; /* Cyber Cyan */
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15) !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            padding-left: 1rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 3.25rem !important; right: 10px !important;
        }
        .select2-dropdown {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.75rem !important;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(6, 182, 212, 0.2);
            margin-top: 5px;
        }
        .select2-container--default .select2-results__option {
            color: #475569 !important; font-size: 0.875rem; font-weight: 600; padding: 10px 16px;
        }
        .select2-container--default .select2-results__option--selected {
            background-color: #f1f5f9 !important; color: #0f172a !important;
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #06b6d4 !important; color: #fff !important;
        }
        .select2-search--dropdown .select2-search__field {
            background-color: #f8fafc !important; color: #0f172a !important;
            border: 1px solid #e2e8f0 !important; border-radius: 0.5rem !important; padding: 10px 12px; outline: none;
        }
        .select2-search--dropdown .select2-search__field:focus { border-color: #06b6d4 !important; }
    </style>
</head>
<body class="bg-slate-950 text-slate-800 h-screen overflow-hidden flex selection:bg-cyber-cyan selection:text-white">

    <!-- SIDEBAR (DARK MODE - CYBERPUNK) -->
    <aside class="w-72 bg-slate-950 border-r border-slate-800 flex flex-col justify-between p-6 z-20 shadow-2xl hidden md:flex relative overflow-hidden">
        <!-- Glowing Orb di Sidebar -->
        <div class="absolute top-0 left-0 w-full h-40 bg-cyber-indigo rounded-full blur-[80px] opacity-20 pointer-events-none"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-gradient-to-br from-cyber-cyan to-cyber-blue rounded-xl flex items-center justify-center shadow-[0_0_15px_rgba(6,182,212,0.5)] transform -rotate-3">
                    <span class="text-white font-outfit font-black text-sm">P2</span>
                </div>
                <div>
                    <h2 class="font-outfit font-bold text-white text-lg leading-tight">SMK Pasundan 2</h2>
                    <p class="text-[10px] font-bold text-cyber-cyan uppercase tracking-widest">Pendaftaran</p>
                </div>
            </div>

            <!-- Target Pribadi -->
            <div class="mb-8 p-4 bg-slate-900/80 border border-slate-800 rounded-2xl relative overflow-hidden">
                <div class="flex justify-between items-center mb-1 relative z-10">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Target Pribadi</span>
                    <span class="text-xs font-black text-cyber-cyan"><?= $kontribusi_pct ?>%</span>
                </div>
                <p class="text-[9px] text-slate-500 font-medium mb-3 relative z-10">Kontribusi Anda thd total siswa</p>
                <div class="w-full bg-slate-800 rounded-full h-1.5 relative z-10">
                    <div class="bg-gradient-to-r from-cyber-cyan to-cyber-blue h-1.5 rounded-full shadow-[0_0_10px_rgba(6,182,212,0.8)]" style="width: <?= $kontribusi_pct ?>%"></div>
                </div>
            </div>

            <nav class="space-y-2">
                <button onclick="setMenu('input')" class="nav-item w-full flex items-center gap-3 px-4 py-3.5 rounded-xl font-bold text-sm transition-all <?= $menu=='input' ? 'bg-gradient-to-r from-cyber-cyan to-cyber-blue text-white shadow-[0_0_15px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                    <i class="fas fa-user-plus w-5 text-center"></i> <span>Input Siswa Baru</span>
                </button>
                <button onclick="setMenu('data')" class="nav-item w-full flex items-center gap-3 px-4 py-3.5 rounded-xl font-bold text-sm transition-all <?= $menu=='data' ? 'bg-gradient-to-r from-cyber-cyan to-cyber-blue text-white shadow-[0_0_15px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                    <i class="fas fa-list w-5 text-center"></i> <span>Riwayat Input</span>
                </button>
                <button onclick="setMenu('dashboard')" class="nav-item w-full flex items-center gap-3 px-4 py-3.5 rounded-xl font-bold text-sm transition-all <?= $menu=='dashboard' ? 'bg-gradient-to-r from-cyber-cyan to-cyber-blue text-white shadow-[0_0_15px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">
                    <i class="fas fa-chart-pie w-5 text-center"></i> <span>Statistik Global</span>
                </button>
            </nav>
        </div>

        <div class="space-y-4 z-10 relative mb-6">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 text-center">
                <div class="w-8 h-8 bg-slate-800 text-cyber-cyan border border-slate-700 rounded-full mx-auto flex items-center justify-center mb-2">
                    <i class="fas fa-user-tie text-xs"></i>
                </div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Petugas Aktif</p>
                <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($petugas) ?></p>
            </div>
            <a href="../../logout.php" class="flex items-center justify-center gap-2 w-full py-3.5 rounded-xl text-xs font-bold text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition-all group">
                <i class="fas fa-sign-out-alt group-hover:text-red-400"></i> Keluar Dasbor
            </a>
        </div>
    </aside>

    <!-- MAIN WORKSPACE (LIGHT MODE - NYAMAN UNTUK MATA) -->
    <!-- Memberikan margin dan rounded untuk memisahkan area dark dan light -->
    <main class="flex-1 bg-slate-50 md:my-4 md:mr-4 md:rounded-[2rem] shadow-[0_0_40px_rgba(0,0,0,0.5)] flex flex-col h-screen md:h-auto overflow-hidden relative border border-slate-200">

        <!-- Latar belakang pola grid transparan ala teknologi -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMCwwLDAsMC4wMikiLz48L3N2Zz4=')] opacity-50 z-0 pointer-events-none"></div>

        <!-- HEADER MOBILE (Hanya tampil di HP) -->
        <div class="md:hidden bg-white border-b border-slate-200 px-5 py-4 flex items-center justify-between z-20 shadow-sm relative">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-cyber-cyan to-cyber-blue rounded-lg flex items-center justify-center shadow-md">
                    <span class="text-white font-outfit font-black text-xs">P2</span>
                </div>
                <span class="font-outfit font-bold text-slate-900 text-sm">Tim Pendaftaran</span>
            </div>
            <select onchange="setMenu(this.value)" class="text-xs font-bold bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 outline-none text-cyber-blue">
                <option value="input" <?= $menu=='input'?'selected':'' ?>>Input Siswa</option>
                <option value="data" <?= $menu=='data'?'selected':'' ?>>Riwayat</option>
                <option value="dashboard" <?= $menu=='dashboard'?'selected':'' ?>>Statistik</option>
            </select>
        </div>

        <!-- AREA KONTEN UTAMA -->
        <div class="flex-1 p-5 md:p-8 lg:p-10 overflow-y-auto custom-scroll relative z-10 animate-fade-in">

            <!-- Breadcrumb -->
            <nav class="breadcrumb mb-6">
                <a href="../../"><i class="fas fa-home"></i></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current">Pendaftaran</span>
            </nav>

            <!-- PAGE: INPUT SISWA -->
            <div id="page-input" class="page-content <?= $menu=='input' ? 'active' : '' ?>">
                <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <h1 class="text-3xl lg:text-4xl font-outfit font-black text-slate-900 tracking-tight mb-2">Input Siswa Baru</h1>
                        <p class="text-sm font-medium text-slate-500">Lengkapi data dasar. <span class="text-cyber-cyan">Tip: Tekan <kbd>Ctrl+S</kbd> untuk menyimpan.</span></p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 shadow-sm text-xs font-bold text-slate-600">
                        <i class="fas fa-calendar text-cyber-blue"></i> <?= date('d M Y') ?>
                    </div>
                </header>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Stats Card Light -->
                    <div class="bg-white border border-slate-200 p-5 rounded-2xl flex items-center gap-4 shadow-sm hover:shadow-md hover:border-cyber-cyan transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-cyber-blue text-xl group-hover:scale-110 transition-transform"><i class="fas fa-globe"></i></div>
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Global</p><p class="text-2xl font-outfit font-black text-slate-800"><?= $total_all ?></p></div>
                    </div>
                    <div class="bg-white border border-slate-200 p-5 rounded-2xl flex items-center gap-4 shadow-sm hover:shadow-md hover:border-emerald-400 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-500 text-xl group-hover:scale-110 transition-transform"><i class="fas fa-user-check"></i></div>
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Input Saya</p><p class="text-2xl font-outfit font-black text-slate-800"><?= $total_saya ?></p></div>
                    </div>
                    <div class="bg-white border border-slate-200 p-5 rounded-2xl flex items-center gap-4 shadow-sm hover:shadow-md hover:border-amber-400 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500 text-xl group-hover:scale-110 transition-transform"><i class="fas fa-bolt"></i></div>
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Hari Ini</p><p class="text-2xl font-outfit font-black text-slate-800"><?= $total_hari ?></p></div>
                    </div>
                    <div class="bg-gradient-to-br from-cyber-cyan to-cyber-blue border border-transparent p-5 rounded-2xl flex items-center gap-4 shadow-[0_4px_15px_rgba(6,182,212,0.3)] text-white">
                        <div class="w-12 h-12 rounded-xl bg-white/20 border border-white/30 flex items-center justify-center text-white text-xl shadow-inner"><i class="fas fa-star"></i></div>
                        <div>
                            <p class="text-[10px] font-bold text-cyan-100 uppercase tracking-widest">Prestasi</p>
                            <p class="text-xl font-outfit font-black"><?= $kontribusi_pct ?>% Share</p>
                        </div>
                    </div>
                </div>

                <!-- FORM CONTAINER (WHITE & CLEAN) -->
                <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 shadow-xl shadow-slate-200/50 relative overflow-hidden">

                    <!-- Draft Status -->
                    <div id="draft-status" class="hidden absolute top-4 right-4 flex items-center gap-2 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-600 text-xs font-bold">
                        <i class="fas fa-save"></i> <span>Draft tersimpan</span>
                    </div>

                    <form action="simpan.php" method="POST" id="form-daftar" class="relative z-10" onsubmit="btnLoading(this)">

                        <div class="mb-8">
                            <h3 class="text-xs font-black text-cyber-indigo uppercase tracking-[0.1em] mb-4 flex items-center gap-2 border-b border-slate-100 pb-2"><i class="fas fa-address-card"></i> Identitas Calon Siswa</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest ml-1 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                    <input type="text" name="nama" required placeholder="Sesuai Ijazah/Akta Lahir" style="text-transform:uppercase;" class="w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-xl text-slate-900 font-bold text-sm focus:border-cyber-cyan focus:ring-4 focus:ring-cyber-cyan/15 outline-none transition-all shadow-sm placeholder-slate-400">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest ml-1 mb-2">Nomor WhatsApp Aktif <span class="text-red-500">*</span></label>
                                    <input type="number" name="hp" required placeholder="Contoh: 08123456789" class="w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-xl text-slate-900 font-bold text-sm focus:border-cyber-cyan focus:ring-4 focus:ring-cyber-cyan/15 outline-none transition-all shadow-sm placeholder-slate-400">
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-xs font-black text-cyber-indigo uppercase tracking-[0.1em] mb-4 flex items-center gap-2 border-b border-slate-100 pb-2"><i class="fas fa-school"></i> Data Akademik</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest ml-1 mb-2">Jurusan Pilihan <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <select name="jurusan" required class="w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-xl text-slate-900 font-bold text-sm focus:border-cyber-cyan focus:ring-4 focus:ring-cyber-cyan/15 outline-none transition-all shadow-sm appearance-none cursor-pointer">
                                            <option value="" class="text-slate-400">— Pilih Jurusan —</option>
                                            <option value="TPM">Teknik Pemesinan (TPM)</option>
                                            <option value="TKR">Teknik Kendaraan Ringan (TKR)</option>
                                            <option value="TSM">Teknik Sepeda Motor (TSM)</option>
                                            <option value="TKJ">Teknik Komputer & Jaringan (TKJ)</option>
                                            <option value="TAV">Teknik Audio Video (TAV)</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                            <i class="fas fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest ml-1 mb-2">Asal Sekolah <span class="text-red-500">*</span></label>
                                    <select name="asal_sekolah" id="sekolah-select" required style="width:100%"></select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-xs font-black text-cyber-indigo uppercase tracking-[0.1em] mb-4 flex items-center gap-2 border-b border-slate-100 pb-2"><i class="fas fa-map-marker-alt"></i> Alamat Rumah Singkat</h3>
                            <div class="mb-4">
                                <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest ml-1 mb-2">Nama Jalan / Blok / Perumahan <span class="text-red-500">*</span></label>
                                <input type="text" name="alamat_lengkap" required placeholder="Cth: JL. RAYA BANDUNG NO. 12" style="text-transform:uppercase;" class="w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-xl text-slate-900 font-bold text-sm focus:border-cyber-cyan focus:ring-4 focus:ring-cyber-cyan/15 outline-none transition-all shadow-sm placeholder-slate-400">
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest ml-1 mb-2">RT <span class="text-red-500">*</span></label>
                                    <input type="number" name="rt" required placeholder="001" class="w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-xl text-slate-900 font-bold text-sm focus:border-cyber-cyan focus:ring-4 focus:ring-cyber-cyan/15 outline-none transition-all shadow-sm placeholder-slate-400">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest ml-1 mb-2">RW <span class="text-red-500">*</span></label>
                                    <input type="number" name="rw" required placeholder="002" class="w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-xl text-slate-900 font-bold text-sm focus:border-cyber-cyan focus:ring-4 focus:ring-cyber-cyan/15 outline-none transition-all shadow-sm placeholder-slate-400">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest ml-1 mb-2">Kelurahan <span class="text-red-500">*</span></label>
                                    <input type="text" name="kelurahan" required placeholder="Kelurahan" style="text-transform:uppercase;" class="w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-xl text-slate-900 font-bold text-sm focus:border-cyber-cyan focus:ring-4 focus:ring-cyber-cyan/15 outline-none transition-all shadow-sm placeholder-slate-400">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest ml-1 mb-2">Kecamatan <span class="text-red-500">*</span></label>
                                    <input type="text" name="kecamatan" required placeholder="Kecamatan" style="text-transform:uppercase;" class="w-full px-4 py-3.5 bg-white border-2 border-slate-200 rounded-xl text-slate-900 font-bold text-sm focus:border-cyber-cyan focus:ring-4 focus:ring-cyber-cyan/15 outline-none transition-all shadow-sm placeholder-slate-400">
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="btn-submit" class="w-full bg-gradient-to-r from-cyber-cyan to-cyber-blue hover:from-cyan-400 hover:to-blue-500 text-white font-bold text-sm px-6 py-4 rounded-xl shadow-[0_0_20px_rgba(6,182,212,0.4)] transform hover:-translate-y-0.5 active:scale-95 transition-all flex items-center justify-center gap-3">
                            <i class="fas fa-save"></i> SIMPAN DATA PENDAFTARAN
                        </button>
                    </form>
                </div>
            </div>

            <!-- PAGE: RIWAYAT INPUT (LIGHT MODE) -->
            <div id="page-data" class="page-content <?= $menu=='data' ? 'active' : '' ?>">
                <header class="mb-8">
                    <h1 class="text-3xl lg:text-4xl font-outfit font-black text-slate-900 tracking-tight mb-2">Riwayat Input Anda</h1>
                    <p class="text-sm font-medium text-slate-500">Menampilkan maksimal 50 data siswa terakhir yang Anda daftarkan.</p>
                </header>

                <div class="bg-white border border-slate-200 rounded-[2rem] overflow-hidden shadow-xl shadow-slate-200/50">
                    <div class="overflow-x-auto custom-scroll">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">No</th>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Nama & ID</th>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Akademik</th>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Status</th>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (mysqli_num_rows($data_saya) > 0): $n=1; while ($s = mysqli_fetch_assoc($data_saya)): ?>
                                <tr class="hover:bg-cyan-50/50 transition-colors">
                                    <td class="py-4 px-6 text-sm font-bold text-slate-400"><?= $n++ ?></td>
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-slate-800 text-sm uppercase"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                                        <div class="text-[10px] font-mono font-bold text-cyber-blue mt-1"><?= $s['id_pendaftaran'] ?></div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-block px-2.5 py-1 bg-cyan-50 border border-cyan-100 text-cyan-600 text-[9px] font-black uppercase rounded-md mb-1.5"><?= $s['jurusan'] ?></span><br>
                                        <span class="text-xs text-slate-500 font-medium"><i class="fas fa-school mr-1 opacity-50"></i> <?= htmlspecialchars($s['asal_sekolah']) ?></span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?php if ($s['status_siswa'] == 'SUDAH DAFTAR ULANG'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-600 text-[9px] font-black uppercase rounded-md"><i class="fas fa-check"></i> Selesai</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 border border-amber-200 text-amber-600 text-[9px] font-black uppercase rounded-md"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Proses</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-slate-500 font-medium">
                                        <?= $s['tgl_daftar'] ? date('d/m/Y', strtotime($s['tgl_daftar'])) : '-' ?>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="5" class="py-16 text-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 border border-slate-200 text-2xl mx-auto mb-3"><i class="fas fa-inbox"></i></div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Belum ada data</p>
                                </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PAGE: STATISTIK GLOBAL (LIGHT MODE) -->
            <div id="page-dashboard" class="page-content <?= $menu=='dashboard' ? 'active' : '' ?>">
                <header class="mb-8">
                    <h1 class="text-3xl lg:text-4xl font-outfit font-black text-slate-900 tracking-tight mb-2">Statistik Global</h1>
                    <p class="text-sm font-medium text-slate-500">Rekapitulasi otomatis distribusi pendaftar di seluruh jurusan.</p>
                </header>

                <div class="bg-white border border-slate-200 rounded-[2rem] p-8 shadow-xl shadow-slate-200/50 max-w-2xl">
                    <div class="text-[10px] font-black tracking-widest text-cyber-blue uppercase mb-6 flex items-center gap-2"><i class="fas fa-chart-pie"></i> Distribusi Jurusan Aktif</div>

                    <div class="space-y-6">
                        <?php
                        $daftar_jurusan = ['TPM' => ['nama' => 'Teknik Pemesinan', 'warna' => '#10b981'], 'TKR' => ['nama' => 'Teknik Kendaraan Ringan', 'warna' => '#f97316'], 'TSM' => ['nama' => 'Teknik Sepeda Motor', 'warna' => '#ef4444'], 'TKJ' => ['nama' => 'Teknik Komputer & Jaringan', 'warna' => '#06b6d4'], 'TAV' => ['nama' => 'Teknik Audio Video', 'warna' => '#8b5cf6']];
                        foreach($daftar_jurusan as $kode => $info):
                            $qcek = mysqli_query($conn, "SELECT COUNT(*) as jml FROM siswa WHERE jurusan='$kode'");
                            $jml = $qcek ? mysqli_fetch_assoc($qcek)['jml'] : 0;
                            $pct = $total_all > 0 ? round($jml/$total_all*100) : 0;
                        ?>
                        <div>
                            <div class="flex justify-between items-center text-xs font-bold mb-2 uppercase tracking-wide">
                                <span class="text-slate-800 font-black"><?= $kode ?> - <?= $info['nama'] ?></span>
                                <span class="text-slate-600 bg-slate-50 border border-slate-200 px-3 py-1 rounded-lg text-[10px] font-bold"><?= $jml ?> Siswa &bull; <?= $pct ?>%</span>
                            </div>
                            <div class="bg-slate-100 rounded-full h-2.5 overflow-hidden shadow-inner border border-slate-200/50">
                                <div class="h-full rounded-full transition-all duration-1000 ease-out relative overflow-hidden" style="width:<?= $pct ?>%; background: <?= $info['warna'] ?>;">
                                    <div class="absolute inset-0 bg-white/30 w-full h-full animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Init Select2 (Hybrid Mode Overrides via CSS diatas)
        $('#sekolah-select').select2({
            placeholder: 'Ketik & cari nama sekolah...',
            tags: true,
            allowClear: true,
            ajax: {
                url: 'api_sekolah.php', dataType: 'json', delay: 250,
                data: p => ({ q: p.term }),
                processResults: d => ({ results: d })
            }
        });
    });

    // Loading Button Feedback
    function btnLoading(form) {
        const btn = document.getElementById('btn-submit');
        btn.innerHTML = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Memproses...</span>`;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        // Clear draft on successful submit
        localStorage.removeItem('spmb_draft_pendaftaran');
    }

    // Auto-save Draft
    const DRAFT_KEY = 'spmb_draft_pendaftaran';
    const formEl = document.getElementById('form-daftar');
    const draftStatus = document.getElementById('draft-status');

    // Load draft on page load
    if (formEl) {
        const savedDraft = localStorage.getItem(DRAFT_KEY);
        if (savedDraft) {
            try {
                const draft = JSON.parse(savedDraft);
                Object.keys(draft).forEach(name => {
                    const input = formEl.querySelector(`[name="${name}"]`);
                    if (input) input.value = draft[name];
                });
                if (draftStatus) {
                    draftStatus.classList.remove('hidden');
                    draftStatus.classList.add('flex');
                }
            } catch (e) {}
        }

        // Save draft on input change
        let draftTimeout;
        formEl.addEventListener('input', () => {
            clearTimeout(draftTimeout);
            draftTimeout = setTimeout(() => {
                const formData = new FormData(formEl);
                const data = {};
                formData.forEach((value, key) => data[key] = value);
                localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
                if (draftStatus) {
                    draftStatus.classList.remove('hidden');
                    draftStatus.classList.add('flex');
                }
            }, 1000);
        });

        // Clear draft on successful submit
        formEl.addEventListener('submit', () => {
            localStorage.removeItem(DRAFT_KEY);
        });
    }

    // Menu Navigation System
    function setMenu(m) {
        ['input','data','dashboard'].forEach(p => {
            const page = document.getElementById('page-' + p);
            if(p === m) {
                page.style.display = 'block';
                setTimeout(() => page.classList.add('active'), 10);
            } else {
                page.classList.remove('active');
                setTimeout(() => page.style.display = 'none', 300);
            }
        });

        // Toggle Nav Style (Sidebar Dark Mode)
        document.querySelectorAll('.nav-item').forEach(el => {
            el.className = 'nav-item w-full flex items-center gap-3 px-4 py-3.5 rounded-xl font-bold text-sm transition-all text-slate-400 hover:text-white hover:bg-slate-900';
        });

        if(event && event.currentTarget && event.currentTarget.classList.contains('nav-item')) {
             event.currentTarget.className = 'nav-item w-full flex items-center gap-3 px-4 py-3.5 rounded-xl font-bold text-sm transition-all bg-gradient-to-r from-cyber-cyan to-cyber-blue text-white shadow-[0_0_15px_rgba(6,182,212,0.4)]';
        }

        history.replaceState(null,'','?menu=' + m);
    }

    // Custom Notifikasi SweetAlert & CONFETTI (Tetap Terang Agar Matching Form)
    const p = new URLSearchParams(window.location.search);
    if (p.get('status') === 'success') {
        // Add to undo stack
        UndoSystem.push('create', { id: p.get('id') || Date.now() });

        // 1. Tembakkan Confetti
        var duration = 3 * 1000;
        var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 10000 };

        function randomInRange(min, max) { return Math.random() * (max - min) + min; }

        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) { return clearInterval(interval); }
            var particleCount = 50 * (timeLeft / duration);
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
        }, 250);

        // 2. Munculkan Notifikasi Light Mode tapi dengan aksen Neon
        Swal.fire({
            title: '<span class="text-2xl font-black text-cyber-cyan tracking-tight">MANTAP! 🚀</span>',
            html: '<div class="text-slate-500 font-medium text-sm mt-1">Data siswa berhasil ditambahkan. Terus semangat mengejar target!</div><div class="text-xs text-slate-400 mt-2">Tekan "Input Data Berikutnya" atau klik "URUNGKAN" di notifikasi untuk undo.</div>',
            icon: 'success', iconColor: '#06b6d4',
            background: '#ffffff',
            color: '#1e293b',
            confirmButtonColor: '#06b6d4',
            confirmButtonText: 'Input Data Berikutnya',
            customClass: {
                popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                confirmButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl shadow-[0_0_15px_rgba(6,182,212,0.4)]'
            }
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?menu=input");
    }

    if (p.get('status') === 'error') {
        Swal.fire({
            title: '<span class="text-2xl font-black text-red-500 tracking-tight">GAGAL!</span>',
            html: '<div class="text-slate-500 font-medium text-sm">' + (p.get('msg') || 'Terjadi kesalahan saat memproses data.') + '</div>',
            icon: 'error', iconColor: '#ef4444',
            background: '#ffffff',
            color: '#1e293b',
            confirmButtonColor: '#ef4444',
            customClass: {
                popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                confirmButton: 'font-bold tracking-widest text-xs px-6 py-3 rounded-xl shadow-md'
            }
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?menu=input");
    }
    </script>
</body>
</html>
