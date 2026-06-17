<?php
/**
 * SISTEM KEUANGAN PPDB SMK PASUNDAN 2 - Admin Page
 * IP Restricted: Only accessible from internal network
 */
include '../../config.php';

// Check admin IP restriction
require_admin_ip();

// Check session
if(!isset($_SESSION['role']) || (!in_array($_SESSION['role'], ['tu','superuser','superuser1']))) {
    header("Location: ../../panitia/index.php"); exit();
}

// Query: Urutan ASC agar pendaftar pertama muncul di paling atas
$sql_belum = mysqli_query($conn, "SELECT * FROM siswa WHERE status_bayar = 'BELUM' ORDER BY id_siswa ASC");
$sql_lunas = mysqli_query($conn, "SELECT * FROM siswa WHERE status_bayar = 'LUNAS' ORDER BY id_siswa ASC");

$count_belum = mysqli_num_rows($sql_belum);
$count_lunas = mysqli_num_rows($sql_lunas);
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Panitia (TU) | SPMB SMK Pasundan 2</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/quick-wins.js"></script>
    <link href="../../assets/css/quick-wins.css" rel="stylesheet">
    
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
        body { background-color: #f8fafc; }
        
        /* Animasi masuk */
        @keyframes fade-in {
            0% { opacity: 0; transform: translateY(15px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        
        /* Scrollbar tipis yang elegan */
        .custom-scroll::-webkit-scrollbar { width: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        /* Tab Transitions */
        .tab-content { display: none; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .tab-content.active { display: block; opacity: 1; animation: fadeInTab 0.4s ease-out forwards; }
        @keyframes fadeInTab { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        * { -webkit-tap-highlight-color: transparent; }

        /* --- LOGIKA ANIMASI KUCING BERLARI (ANTI-GAGAL) --- */
        @keyframes walkRight {
            0% { left: -60px; transform: scaleX(1); }
            45% { transform: scaleX(1); }
            50% { left: calc(100% + 60px); transform: scaleX(-1); } /* Balik badan saat di ujung kanan */
            55% { transform: scaleX(-1); }
            95% { transform: scaleX(-1); }
            100% { left: -60px; transform: scaleX(1); } /* Kembali ke kiri */
        }

        .walking-cat {
            position: absolute;
            bottom: 4px;
            animation: walkRight 20s linear infinite;
        }
    </style>
</head>
<body class="text-slate-700 h-screen overflow-hidden flex bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%234f46e5\' fill-opacity=\'0.03\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]">

    <aside class="w-72 bg-white border-r border-slate-200 flex flex-col justify-between p-6 z-20 shadow-[4px_0_24px_rgba(0,0,0,0.02)] hidden md:flex relative">
        <div>
            <div class="flex items-center gap-3 mb-12">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-md transform -rotate-3">
                    <span class="text-white font-outfit font-black text-sm">P2</span>
                </div>
                <div>
                    <h2 class="font-outfit font-bold text-slate-900 text-lg leading-tight">SMK Pasundan 2</h2>
                    <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">Tata Usaha (TU)</p>
                </div>
            </div>

            <nav class="space-y-2">
                <a href="#" class="flex items-center gap-3 bg-indigo-50 text-indigo-700 px-4 py-3.5 rounded-xl font-bold shadow-sm border border-indigo-100 transition-all">
                    <i class="fas fa-receipt text-indigo-500"></i>
                    <span class="text-sm">Pembayaran SPP/PPDB</span>
                </a>
            </nav>
        </div>

        <div class="space-y-4 z-10 relative mb-12">
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 text-center">
                <div class="w-8 h-8 bg-slate-200 rounded-full mx-auto flex items-center justify-center mb-2">
                    <i class="fas fa-user-shield text-slate-500 text-xs"></i>
                </div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Admin Aktif</p>
                <p class="text-xs font-bold text-slate-700">SMK Pasundan 2</p>
            </div>
            <a href="../../logout.php" class="flex items-center justify-center gap-2 w-full py-3.5 rounded-xl text-xs font-bold text-slate-500 hover:text-red-600 hover:bg-red-50 border border-transparent transition-all group">
                <i class="fas fa-sign-out-alt group-hover:text-red-500"></i> Keluar Dasbor
            </a>
        </div>

        <div class="absolute bottom-0 left-6 pointer-events-none z-0 flex flex-col items-center select-none">
            <div class="text-[9px] bg-slate-900 text-white font-bold px-1.5 py-0.5 rounded shadow-sm mb-1 animate-bounce">
                Meow~ 🐾
            </div>
            <div class="text-4xl transform translate-y-1">🐱</div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <div class="md:hidden bg-white border-b border-slate-200 px-5 py-4 flex items-center justify-between z-20 shadow-sm">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-lg flex items-center justify-center shadow-md">
                    <span class="text-white font-outfit font-black text-xs">P2</span>
                </div>
                <span class="font-outfit font-bold text-slate-900 text-sm">Otoritas TU</span>
            </div>
            <a href="../../logout.php" class="text-slate-400 hover:text-red-500"><i class="fas fa-sign-out-alt"></i></a>
        </div>

        <div class="flex-1 p-5 md:p-8 lg:p-10 overflow-y-auto custom-scroll relative z-10 animate-fade-in pb-24">

            <!-- Breadcrumb -->
            <nav class="breadcrumb mb-6">
                <a href="../../"><i class="fas fa-home"></i></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current">Verifikasi Pelunasan</span>
            </nav>

            <header class="mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-100 border border-indigo-200 rounded-full mb-3">
                    <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                    <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-widest">Sistem Real-time</span>
                </div>
                <h1 class="font-outfit text-3xl md:text-4xl font-black text-slate-900 mb-2">Verifikasi Pelunasan</h1>
                <p class="text-sm text-slate-500">Kelola antrean pembayaran pendaftaran dan cetak ID Pendaftaran siswa. <span class="text-indigo-500 font-medium">Gunakan <kbd>Ctrl+A</kbd> untuk bulk action.</span></p>
            </header>

            <!-- Bulk Actions Bar -->
            <div id="bulk-actions" class="bulk-actions-bar">
                <div class="flex items-center gap-3">
                    <span class="bulk-count-badge" id="bulk-count">0</span>
                    <span class="text-sm font-bold text-slate-600">terpilih</span>
                </div>
                <div class="h-8 w-px bg-slate-200"></div>
                <button onclick="bulkAccAll()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg transition-colors">
                    <i class="fas fa-check mr-2"></i>ACC Semua
                </button>
                <button onclick="BulkActions.deselectAll()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Batal
                </button>
            </div>

            <div class="grid grid-cols-2 gap-4 md:gap-6 mb-8">
                <div class="bg-white border border-slate-200 p-5 md:p-6 rounded-2xl md:rounded-[2rem] shadow-sm flex flex-col md:flex-row md:items-center gap-4 relative overflow-hidden group hover:border-amber-300 transition-colors">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-100 rounded-full blur-xl group-hover:bg-amber-200 transition-colors"></div>
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500 text-xl shadow-inner z-10 shrink-0">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="z-10">
                        <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Menunggu ACC</p>
                        <p class="font-outfit text-3xl md:text-4xl font-black text-slate-800 leading-none"><?= $count_belum ?></p>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 p-5 md:p-6 rounded-2xl md:rounded-[2rem] shadow-sm flex flex-col md:flex-row md:items-center gap-4 relative overflow-hidden group hover:border-emerald-300 transition-colors">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-100 rounded-full blur-xl group-hover:bg-emerald-200 transition-colors"></div>
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-500 text-xl shadow-inner z-10 shrink-0">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="z-10">
                        <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Sudah Lunas</p>
                        <p class="font-outfit text-3xl md:text-4xl font-black text-slate-800 leading-none"><?= $count_lunas ?></p>
                    </div>
                </div>
            </div>

            <div class="inline-flex bg-white p-1.5 rounded-xl border border-slate-200 mb-6 shadow-sm">
                <button class="tab-btn px-5 md:px-6 py-2.5 rounded-lg text-xs font-bold transition-all bg-indigo-600 text-white shadow-md shadow-indigo-200" onclick="openTab(event, 'belum')">
                    <i class="fas fa-clock mr-1.5 opacity-80"></i> ANTREAN (<?= $count_belum ?>)
                </button>
                <button class="tab-btn px-5 md:px-6 py-2.5 rounded-lg text-xs font-bold text-slate-500 hover:text-slate-800 transition-all" onclick="openTab(event, 'lunas')">
                    <i class="fas fa-check-circle mr-1.5 opacity-80"></i> SELESAI (<?= $count_lunas ?>)
                </button>
            </div>

            <div id="belum" class="tab-content active">
                <?php if($count_belum > 0): ?>
                    <div class="space-y-3 md:space-y-4 pb-20">
                        <?php while($row = mysqli_fetch_assoc($sql_belum)): ?>
                        <div class="bulk-item bg-white border border-slate-200 p-4 md:p-5 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:border-indigo-300 transition-all shadow-sm hover:shadow-md group">
                            <div class="flex items-start md:items-center gap-4">
                                <!-- Checkbox -->
                                <div class="row-checkbox-cell flex items-center">
                                    <input type="checkbox" class="row-checkbox w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" data-id="<?= $row['id_siswa'] ?>" onchange="updateBulkCount()">
                                </div>
                                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 text-sm md:text-base group-hover:text-indigo-500 group-hover:bg-indigo-50 transition-colors shrink-0">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h3 class="font-outfit text-base md:text-lg font-bold text-slate-800 mb-1.5"><?= htmlspecialchars($row['nama_lengkap']) ?></h3>
                                    <div class="flex flex-wrap gap-1.5 md:gap-2">
                                        <span class="px-2.5 py-1 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-700 text-[9px] md:text-[10px] font-bold uppercase tracking-wider"><?= htmlspecialchars($row['jurusan']) ?></span>
                                        <span class="px-2.5 py-1 rounded-md bg-slate-50 border border-slate-200 text-slate-500 text-[9px] md:text-[10px] font-bold uppercase tracking-wider">
                                            <i class="fas fa-school mr-1 opacity-50"></i> <?= htmlspecialchars(!empty($row['asal_sekolah']) ? $row['asal_sekolah'] : ($row['sekolah_asal'] ?? '-')) ?>
                                        </span>
                                        <span class="px-2.5 py-1 rounded-md bg-amber-50 border border-amber-200 text-amber-600 text-[9px] md:text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> PENDING
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button onclick="prosesBayar('<?= $row['id_siswa'] ?>', '<?= addslashes($row['nama_lengkap']) ?>')" class="w-full md:w-auto px-5 py-3 bg-white border-2 border-indigo-100 hover:border-indigo-600 hover:bg-indigo-600 text-indigo-600 hover:text-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all shadow-sm hover:shadow-lg transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                Verifikasi <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-16 bg-slate-50 border border-slate-200 border-dashed rounded-3xl">
                        <div class="w-16 h-16 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-300 text-2xl mb-3 shadow-sm"><i class="fas fa-check"></i></div>
                        <p class="text-slate-400 font-bold tracking-widest uppercase text-xs">Semua antrean bersih</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="lunas" class="tab-content">
                <div class="space-y-3 pb-20 opacity-80">
                    <?php while($row = mysqli_fetch_assoc($sql_lunas)): ?>
                    <div class="bg-white border border-slate-200 p-4 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-500 text-sm shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3 class="font-outfit text-sm md:text-base font-bold text-slate-700 mb-1"><?= htmlspecialchars($row['nama_lengkap']) ?></h3>
                                <div class="flex gap-1.5 md:gap-2">
                                    <span class="px-2 py-0.5 rounded flex items-center text-[9px] md:text-[10px] font-black uppercase text-emerald-600 bg-emerald-50 border border-emerald-100">LUNAS</span>
                                    <span class="px-2 py-0.5 rounded flex items-center text-[9px] md:text-[10px] font-bold uppercase text-slate-500 bg-slate-50 border border-slate-200"><?= htmlspecialchars($row['jurusan']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-2 bg-slate-50 border border-slate-200 text-indigo-600 font-mono text-xs md:text-sm font-bold rounded-lg w-full md:w-auto text-center">
                            <?= htmlspecialchars($row['id_pendaftaran']) ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

        </div>

        <div class="absolute bottom-0 left-0 w-full overflow-hidden pointer-events-none z-0 h-10 bg-slate-100/50 border-t border-slate-200/60">
            <div class="text-2xl walking-cat select-none">🐈</div>
        </div>
    </main>

    <script>
        function openTab(evt, tabName) {
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            const tabBtns = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < tabBtns.length; i++) {
                tabBtns[i].classList.remove("bg-indigo-600", "text-white", "shadow-md", "shadow-indigo-200");
                tabBtns[i].classList.add("text-slate-500");
            }

            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.remove("text-slate-500");
            evt.currentTarget.classList.add("bg-indigo-600", "text-white", "shadow-md", "shadow-indigo-200");
        }

        function prosesBayar(id, nama) {
            Swal.fire({
                title: '<span class="font-outfit font-black text-slate-800 tracking-tight">Otorisasi Pembayaran</span>',
                html: `<p class="text-sm text-slate-500 mb-3">Verifikasi pelunasan biaya untuk pendaftar:</p>
                       <div class="bg-indigo-50 border border-indigo-100 p-3.5 rounded-xl text-base md:text-lg font-bold text-indigo-700 uppercase tracking-wide">
                           ${nama}
                       </div>`,
                icon: 'question',
                iconColor: '#4f46e5',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#f1f5f9',
                confirmButtonText: 'VERIFIKASI SEKARANG',
                cancelButtonText: '<span class="text-slate-600">BATAL</span>',
                customClass: {
                    popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                    confirmButton: 'font-bold tracking-widest text-[10px] md:text-xs px-5 md:px-6 py-3 rounded-xl shadow-md',
                    cancelButton: 'font-bold tracking-widest text-[10px] md:text-xs px-5 md:px-6 py-3 rounded-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: '<span class="font-outfit font-bold text-slate-800">Memproses Data...</span>',
                        text: 'Menghasilkan ID Pendaftaran & Trigger n8n Webhook.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    window.location.href = 'proses_acc.php?id=' + id;
                }
            })
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('id_reg') && urlParams.get('status') === 'success') {
            const waStatus = urlParams.get('wa');
            let waBadgeHtml = '';

            if (waStatus === 'sent') {
                waBadgeHtml = `
                    <div class="mt-5 flex items-center justify-center gap-2 text-emerald-700 bg-emerald-50 border border-emerald-200 px-4 py-2.5 rounded-lg text-[10px] font-bold uppercase tracking-wider">
                        <i class="fab fa-whatsapp text-emerald-500 text-base"></i> Notifikasi Terkirim (n8n)
                    </div>`;
            } else {
                waBadgeHtml = `
                    <div class="mt-5 flex items-center justify-center gap-2 text-amber-700 bg-amber-50 border border-amber-200 px-4 py-2.5 rounded-lg text-[10px] font-bold uppercase tracking-wider">
                        <i class="fas fa-exclamation-triangle text-amber-500 text-base"></i> WA Gagal Diteruskan
                    </div>`;
            }

            Swal.fire({
                title: '<span class="font-outfit font-black text-emerald-600 tracking-tight text-2xl">BERHASIL!</span>',
                html: `<div class="mt-1 text-slate-500 text-sm">Status pendaftar diubah menjadi <b>LUNAS</b>.</div>
                       <div class="mt-6 text-[10px] font-bold text-slate-400 tracking-widest uppercase mb-1.5">ID Pendaftaran Resmi</div>
                       <div class="bg-slate-50 border border-slate-200 py-3 rounded-xl text-xl font-mono font-black text-indigo-600 tracking-widest shadow-inner">
                           ${urlParams.get('id_reg')}
                       </div>
                       ${waBadgeHtml}`,
                icon: 'success',
                iconColor: '#10b981',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'SELESAI',
                customClass: {
                    popup: 'border border-slate-200 rounded-3xl shadow-2xl',
                    confirmButton: 'font-bold tracking-widest text-xs px-8 py-3 rounded-xl'
                }
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }

        // Bulk Actions
        async function bulkAccAll() {
            const selectedIds = BulkActions.getSelected();
            if (selectedIds.length === 0) {
                showToast('Pilih siswa terlebih dahulu', 'warning');
                return;
            }

            const result = await Swal.fire({
                title: 'Konfirmasi Bulk ACC',
                html: `<p class="text-slate-600 mb-2">Yakin ingin ACC <b>${selectedIds.length}</b> pendaftar sekaligus?</p>
                       <p class="text-xs text-slate-400">Setiap pendaftar akan mendapat ID Pendaftaran dan notifikasi WA.</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'YA, ACC SEMUA',
                cancelButtonText: 'BATAL',
                confirmButtonColor: '#4f46e5'
            });

            if (!result.isConfirmed) return;

            LoadingOverlay.show(`Memproses ${selectedIds.length} pendaftar...`);

            // Process each one
            let success = 0;
            let failed = 0;

            for (const id of selectedIds) {
                try {
                    const response = await fetch(`proses_acc.php?id=${id}`);
                    if (response.ok) success++;
                    else failed++;
                } catch (e) {
                    failed++;
                }
                LoadingOverlay.update(`Memproses ${success + failed}/${selectedIds.length}...`);
            }

            LoadingOverlay.hide();

            await Swal.fire({
                title: 'Selesai!',
                html: `<p class="text-emerald-600 font-bold">${success} berhasil di-ACC</p>
                       ${failed > 0 ? `<p class="text-red-500 text-sm mt-1">${failed} gagal</p>` : ''}`,
                icon: success > 0 ? 'success' : 'error'
            });

            UndoSystem.push('acc', { count: success });
            window.location.reload();
        }

        // Init Bulk Actions
        BulkActions.init({
            checkboxClass: '.row-checkbox',
            itemClass: '.bulk-item',
            countDisplay: '#bulk-count',
            actionsContainer: '#bulk-actions'
        });

        // Keyboard shortcut for bulk select all
        KeyboardShortcuts.init({
            'ctrl+b': () => {
                BulkActions.selectAll();
                showToast(`${BulkActions.getSelected().length} item dipilih`, 'info');
            }
        });
    </script>
</body>
</html>
