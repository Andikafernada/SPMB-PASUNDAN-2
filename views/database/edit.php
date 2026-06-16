<?php
/**
 * EDIT & LENGKAPI DATA SISWA - Admin Page
 * IP Restricted: Only accessible from internal network
 * Theme: Light, Clean, Modern SaaS
 */
include '../../config.php';

// Check admin IP restriction
require_admin_ip();

// Check session
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database','superuser'])) {
    header("Location: ../../panitia/index.php"); 
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("
        <div style='font-family: sans-serif; text-align: center; padding: 50px; background: #f8fafc; color: #334155; height: 100vh;'>
            <h1 style='font-size: 4rem; margin-bottom: 10px;'>🔍</h1>
            <h2>ID Tidak Valid</h2>
            <p>Parameter ID tidak valid atau tidak ditemukan.</p>
            <a href='index.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Kembali ke Dasbor</a>
        </div>
    ");
}

$stmt = mysqli_prepare($conn, "SELECT * FROM siswa WHERE id_siswa=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$d = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// FIX HTTP 500: Menghapus error spasi siluman dan fungsi tak terdefinisi
if (!$d) {
    die("
        <div style='font-family: sans-serif; text-align: center; padding: 50px; background: #f8fafc; color: #334155; height: 100vh;'>
            <h1 style='font-size: 4rem; margin-bottom: 10px;'>🔍</h1>
            <h2>Data Tidak Ditemukan</h2>
            <p>Data siswa tidak ditemukan di database atau ID tidak valid.</p>
            <a href='index.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Kembali ke Dasbor</a>
        </div>
    ");
}

$field_wajib = [
    'jenis_kelamin','nisn','nik','tempat_lahir','tanggal_lahir','agama',
    'nama_jalan','kota','provinsi',
    'nama_ayah','tempat_lahir_ayah','tgl_lahir_ayah','pekerjaan_ayah','nik_ayah',
    'nama_ibu','tempat_lahir_ibu','tgl_lahir_ibu','pekerjaan_ibu','nik_ibu',
    'sekolah_asal'
];

$terisi = array_filter($field_wajib, fn($f) => !empty($d[$f]));
$persen = round(count($terisi) / count($field_wajib) * 100);
$is_du  = ($d['status_siswa'] == 'SUDAH DAFTAR ULANG');

// Helper Nilai BTQ disesuaikan warnanya untuk Light Mode
function getBTQGrade($v) {
    $v = intval($v);
    if ($v >= 80) return 'A'; 
    if ($v >= 65) return 'B';
    if ($v >= 50) return 'C'; 
    if ($v > 0)   return 'D'; 
    return '-';
}

function getBTQClass($v) {
    $v = intval($v);
    if ($v >= 80) return 'text-emerald-700 bg-emerald-50 border-emerald-200';
    if ($v >= 65) return 'text-sky-700 bg-sky-50 border-sky-200';
    if ($v >= 50) return 'text-amber-700 bg-amber-50 border-amber-200';
    if ($v > 0)   return 'text-red-700 bg-red-50 border-red-200';
    return 'text-slate-500 bg-slate-100 border-slate-200';
}

function v($d, $k) { return htmlspecialchars($d[$k] ?? ''); }
function sel($d, $k, $val) { return ($d[$k] ?? '') === $val ? 'selected' : ''; }
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <title>Edit Data | <?= htmlspecialchars(strtoupper($d['nama_lengkap'] ?? '')) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

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

        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }

        /* Floating Input Styles - FIX GARIS MENABRAK TEKS */
        .floating-input:focus ~ .floating-label,
        .floating-input:not(:placeholder-shown) ~ .floating-label {
            transform: translateY(-120%) scale(0.85);
            color: #4f46e5;
            background-color: #ffffff; /* Memblokir garis border input */
            padding: 0 6px;
            border-radius: 4px;
        }

        /* Khusus untuk area header form yang mungkin beda warna background */
        .bg-slate-50 .floating-input:focus ~ .floating-label,
        .bg-slate-50 .floating-input:not(:placeholder-shown) ~ .floating-label {
            background-color: #f8fafc; 
        }

        /* Toggle DU Switch transition */
        .toggle-checkbox:checked { right: 0; border-color: #10b981; }
        .toggle-checkbox:checked + .toggle-label { background-color: #10b981; }
    </style>
</head>
<body class="text-slate-700 relative custom-scroll bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%234f46e5\' fill-opacity=\'0.03\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]">

    <div class="sticky top-0 z-50 flex items-center justify-between px-4 sm:px-8 py-4 bg-white/90 backdrop-blur-md border-b border-slate-200 shadow-sm">
        <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:text-indigo-600 transition-all">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <div class="text-[10px] sm:text-xs text-slate-400 font-extrabold uppercase tracking-widest flex items-center gap-2">
            TIM DATABASE <span class="hidden sm:inline-block w-1.5 h-1.5 rounded-full bg-indigo-500"></span> <span class="text-indigo-600 hidden sm:inline-block"><?= htmlspecialchars(strtoupper($_SESSION['nama'] ?? 'ADMIN')) ?></span>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 relative z-10 pb-32">

        <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 shadow-xl shadow-slate-200/50 relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-50 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10">
                <h1 class="font-outfit text-2xl lg:text-4xl font-black text-slate-900 tracking-tight mb-3"><?= htmlspecialchars(strtoupper($d['nama_lengkap'] ?? '')) ?></h1>

                <div class="flex flex-wrap items-center gap-2 mb-5">
                    <span class="px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px] font-black uppercase tracking-wider rounded-lg"><?= htmlspecialchars($d['id_pendaftaran'] ?? 'Belum ACC TU') ?></span>
                    <span class="px-3 py-1 bg-blue-50 border border-blue-100 text-blue-600 text-[10px] font-black uppercase tracking-wider rounded-lg"><?= htmlspecialchars($d['jurusan'] ?? '') ?></span>
                    <?php if (!empty($d['jurusan_lama'])): ?>
                    <span class="px-3 py-1 bg-slate-100 border border-slate-200 text-slate-500 text-[10px] font-black uppercase tracking-wider rounded-lg">Ex. <?= htmlspecialchars($d['jurusan_lama']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($d['kelas'])): ?>
                    <span class="px-3 py-1 bg-purple-50 border border-purple-100 text-purple-600 text-[10px] font-black uppercase tracking-wider rounded-lg">Kls <?= htmlspecialchars($d['kelas']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-3 bg-slate-50 p-2 pr-4 border border-slate-200 rounded-xl w-fit shadow-sm">
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest pl-2">Status DU</span>
                    <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input type="checkbox" id="du-toggle" <?= $is_du ? 'checked' : '' ?> onchange="toggleDU(this,'<?= htmlspecialchars($d['id_siswa']) ?>')" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-slate-200 appearance-none cursor-pointer z-10 top-0 left-0 transition-all duration-300"/>
                        <label for="du-toggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-slate-200 cursor-pointer transition-colors duration-300"></label>
                    </div>
                    <span id="du-text" class="text-[10px] font-black uppercase tracking-wider <?= $is_du ? 'text-emerald-600' : 'text-slate-400' ?>"><?= $is_du ? 'SUDAH DU' : 'BELUM DU' ?></span>
                </div>
            </div>

            <div class="w-full md:w-64 text-left md:text-right flex flex-col md:items-end relative z-10">
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-1">Kelengkapan Data</div>
                <div class="font-outfit text-5xl font-black <?= $persen >= 100 ? 'text-emerald-500' : 'text-slate-800' ?> leading-none mb-3"><?= $persen ?><span class="text-2xl text-slate-400">%</span></div>
                <div class="w-full bg-slate-100 rounded-full h-2 mb-2 border border-slate-200">
                    <div class="bg-gradient-to-r from-blue-500 to-emerald-400 h-2 rounded-full transition-all duration-1000" style="width: <?= $persen ?>%"></div>
                </div>
                <div class="text-[10px] font-bold text-slate-500"><?= count($terisi) ?> dari <?= count($field_wajib) ?> kolom terisi</div>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl mb-8 flex gap-3 text-sm text-blue-800 shadow-sm">
            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
            <p><b>Referensi Awal:</b> WA <b><?= htmlspecialchars($d['no_hp'] ?? '') ?></b> &bull; Asal: <b><?= htmlspecialchars($d['asal_sekolah'] ?? '') ?></b> &bull; Alamat: <?= htmlspecialchars($d['kelurahan'] ?? '') ?>, <?= htmlspecialchars($d['kecamatan'] ?? '') ?> RT<?= htmlspecialchars($d['rt'] ?? '') ?>/RW<?= htmlspecialchars($d['rw'] ?? '') ?></p>
        </div>

        <form action="proses_crud.php?aksi=edit" method="POST" class="space-y-6">
            <input type="hidden" name="id_siswa" value="<?= htmlspecialchars($d['id_siswa'] ?? '') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">

            <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 shadow-sm">
                <div class="flex items-center gap-3 text-indigo-600 text-sm font-black uppercase tracking-[0.1em] mb-6 pb-4 border-b border-slate-100">
                    <span class="text-2xl">🪪</span> 1. Identitas Calon Siswa
                </div>

                <div class="relative pt-2 mb-5">
                    <input type="text" name="nama_lengkap" id="nama" value="<?= v($d,'nama_lengkap') ?>" placeholder=" " style="text-transform:uppercase;" required
                           class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                    <label for="nama" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nama Lengkap <span class="text-red-500">*</span></label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">Jenis Kelamin <span class="text-red-500">*</span></label>
                        <select name="jenis_kelamin" required class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">— Pilih —</option>
                            <option value="LAKI-LAKI" <?= sel($d,'jenis_kelamin','LAKI-LAKI') ?>>LAKI-LAKI</option>
                            <option value="PEREMPUAN" <?= sel($d,'jenis_kelamin','PEREMPUAN') ?>>PEREMPUAN</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">Agama <span class="text-red-500">*</span></label>
                        <select name="agama" required class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">— Pilih —</option>
                            <?php foreach (['ISLAM','KRISTEN','KATOLIK','HINDU','BUDHA','KONGHUCU'] as $ag): ?>
                            <option value="<?= $ag ?>" <?= sel($d,'agama',$ag) ?>><?= $ag ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="relative pt-2">
                        <input type="text" name="tempat_lahir" id="tmpt" value="<?= v($d,'tempat_lahir') ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="tmpt" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Tempat Lahir <span class="text-red-500">*</span></label>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">Tanggal Lahir <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_lahir" value="<?= $d['tanggal_lahir'] ?? '' ?>" required class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="relative pt-2">
                        <input type="text" name="nisn" id="nisn" value="<?= v($d,'nisn') ?>" placeholder=" " inputmode="numeric" maxlength="10" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="nisn" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">NISN (10 Digit) <span class="text-red-500">*</span></label>
                    </div>
                    <div class="relative pt-2">
                        <input type="text" name="nik" id="nik" value="<?= v($d,'nik') ?>" placeholder=" " inputmode="numeric" maxlength="16" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="nik" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">NIK Sesuai KK <span class="text-red-500">*</span></label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="relative pt-2">
                        <input type="text" name="no_hp" id="hp" value="<?= v($d,'no_hp') ?>" placeholder=" " inputmode="numeric"
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="hp" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nomor WhatsApp</label>
                    </div>
                    <div class="relative pt-2">
                        <input type="text" name="sekolah_asal" id="skl" value="<?= v($d,'sekolah_asal') ?: v($d,'asal_sekolah') ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="skl" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Asal Sekolah <span class="text-red-500">*</span></label>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 shadow-sm">
                <div class="flex items-center gap-3 text-indigo-600 text-sm font-black uppercase tracking-[0.1em] mb-6 pb-4 border-b border-slate-100">
                    <span class="text-2xl">📍</span> 2. Alamat Rumah Lengkap
                </div>

                <div class="relative pt-2 mb-5">
                    <input type="text" name="nama_jalan" id="jln" value="<?= v($d,'nama_jalan') ?: v($d,'alamat') ?>" placeholder=" " style="text-transform:uppercase;" required
                           class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                    <label for="jln" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nama Jalan / Blok <span class="text-red-500">*</span></label>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">RT <span class="text-red-500">*</span></label>
                        <input type="text" name="rt" value="<?= v($d,'rt') ?>" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">RW <span class="text-red-500">*</span></label>
                        <input type="text" name="rw" value="<?= v($d,'rw') ?>" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div class="col-span-2 relative pt-2 md:mt-0 mt-2">
                        <input type="text" name="kelurahan" id="kel" value="<?= v($d,'kelurahan') ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="kel" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kelurahan <span class="text-red-500">*</span></label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="relative pt-2">
                        <input type="text" name="kecamatan" id="kec" value="<?= v($d,'kecamatan') ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="kec" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kecamatan <span class="text-red-500">*</span></label>
                    </div>
                    <div class="relative pt-2">
                        <input type="text" name="kota" id="kota" value="<?= v($d,'kota') ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="kota" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kota / Kabupaten <span class="text-red-500">*</span></label>
                    </div>
                    <div class="relative pt-2">
                        <input type="text" name="provinsi" id="prov" value="<?= v($d,'provinsi') ?: 'JAWA BARAT' ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="prov" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Provinsi <span class="text-red-500">*</span></label>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 shadow-sm">
                <div class="flex items-center gap-3 text-indigo-600 text-sm font-black uppercase tracking-[0.1em] mb-6 pb-4 border-b border-slate-100">
                    <span class="text-2xl">👨‍👩‍👦</span> 3. Data Orang Tua / Wali
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex gap-4 items-center">
                    <div class="text-3xl">👨</div>
                    <div>
                        <div class="font-outfit font-black text-blue-800 text-lg">Data Ayah / Wali Laki-laki</div>
                        <div class="text-[10px] text-blue-500 uppercase tracking-widest font-bold">Sesuai Dokumen KTP/KK</div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="relative pt-2">
                        <input type="text" name="nama_ayah" id="ayah" value="<?= v($d,'nama_ayah') ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="ayah" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nama Ayah <span class="text-red-500">*</span></label>
                    </div>
                    <div class="relative pt-2">
                        <input type="text" name="nik_ayah" id="nik_ayh" value="<?= v($d,'nik_ayah') ?>" placeholder=" " inputmode="numeric" maxlength="16" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="nik_ayh" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">NIK Ayah <span class="text-red-500">*</span></label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                    <div class="relative pt-2">
                        <input type="text" name="tempat_lahir_ayah" id="tmpt_ayh" value="<?= v($d,'tempat_lahir_ayah') ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="tmpt_ayh" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kota Lahir Ayah <span class="text-red-500">*</span></label>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">Tanggal Lahir Ayah <span class="text-red-500">*</span></label>
                        <input type="date" name="tgl_lahir_ayah" value="<?= v($d,'tgl_lahir_ayah') ?>" required class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">Pekerjaan Ayah <span class="text-red-500">*</span></label>
                        <select name="pekerjaan_ayah" required class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">— Pilih —</option>
                            <?php foreach (['PNS','TNI/POLRI','WIRASWASTA','KARYAWAN SWASTA','BURUH','PETANI','PEDAGANG','PENSIUNAN','TIDAK BEKERJA','LAINNYA'] as $pj): ?>
                            <option value="<?= $pj ?>" <?= sel($d,'pekerjaan_ayah',$pj) ?>><?= $pj ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 mb-6 flex gap-4 items-center">
                    <div class="text-3xl">👩</div>
                    <div>
                        <div class="font-outfit font-black text-rose-800 text-lg">Data Ibu / Wali Perempuan</div>
                        <div class="text-[10px] text-rose-500 uppercase tracking-widest font-bold">Sesuai Dokumen KTP/KK</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="relative pt-2">
                        <input type="text" name="nama_ibu" id="ibu" value="<?= v($d,'nama_ibu') ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="ibu" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nama Ibu <span class="text-red-500">*</span></label>
                    </div>
                    <div class="relative pt-2">
                        <input type="text" name="nik_ibu" id="nik_ibu" value="<?= v($d,'nik_ibu') ?>" placeholder=" " inputmode="numeric" maxlength="16" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="nik_ibu" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">NIK Ibu <span class="text-red-500">*</span></label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="relative pt-2">
                        <input type="text" name="tempat_lahir_ibu" id="tmpt_ibu" value="<?= v($d,'tempat_lahir_ibu') ?>" placeholder=" " style="text-transform:uppercase;" required
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="tmpt_ibu" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kota Lahir Ibu <span class="text-red-500">*</span></label>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">Tanggal Lahir Ibu <span class="text-red-500">*</span></label>
                        <input type="date" name="tgl_lahir_ibu" value="<?= v($d,'tgl_lahir_ibu') ?>" required class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">Pekerjaan Ibu <span class="text-red-500">*</span></label>
                        <select name="pekerjaan_ibu" required class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">— Pilih —</option>
                            <?php foreach (['PNS','TNI/POLRI','WIRASWASTA','KARYAWAN SWASTA','BURUH','PETANI','PEDAGANG','PENSIUNAN','IBU RUMAH TANGGA','TIDAK BEKERJA','LAINNYA'] as $pj): ?>
                            <option value="<?= $pj ?>" <?= sel($d,'pekerjaan_ibu',$pj) ?>><?= $pj ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 shadow-sm">
                <div class="flex items-center gap-3 text-indigo-600 text-sm font-black uppercase tracking-[0.1em] mb-6 pb-4 border-b border-slate-100">
                    <span class="text-2xl">🌟</span> 4. Nilai BTQ & Pengkelasan
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">Nilai BTQ (0-100)</label>
                        <div class="relative">
                            <input type="number" name="nilai_btq" id="btq-inp" value="<?= $d['nilai_btq'] ?? 0 ?>" min="0" max="100" oninput="updateGrade(this.value)"
                                   class="w-full pl-4 pr-16 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all">
                            <span id="btq-grade" class="absolute right-3 top-1/2 -translate-y-1/2 px-2.5 py-1 text-[10px] font-black rounded-lg border <?= getBTQClass($d['nilai_btq'] ?? 0) ?> transition-colors"><?= getBTQGrade($d['nilai_btq'] ?? 0) ?></span>
                        </div>
                    </div>
                    <div class="relative pt-2">
                        <input type="text" name="request_kelas" id="req" value="<?= v($d,'request_kelas') ?>" placeholder=" " style="text-transform:uppercase;"
                               class="floating-input w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition-all peer">
                        <label for="req" class="floating-label absolute left-4 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Request Teman Sekelas (Opsional)</label>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2">Kelas (Dari Sistem)</label>
                    <input type="text" value="<?= v($d,'kelas') ?: 'Belum dikelas' ?>" disabled class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-slate-500 text-sm font-bold cursor-not-allowed">
                    <p class="text-[10px] font-semibold text-slate-500 mt-2 ml-1"><i class="fas fa-info-circle text-indigo-400 mr-1"></i> Kelas hanya bisa diatur secara otomatis dari menu Pengkelasan.</p>
                </div>
            </div>

            <?php
            $hist = mysqli_query($conn,"SELECT * FROM history_jurusan WHERE id_siswa='$id' ORDER BY tgl_pindah DESC LIMIT 5");
            if ($hist && mysqli_num_rows($hist) > 0):
            ?>
            <div class="bg-white border border-slate-200 rounded-[2rem] p-6 lg:p-8 shadow-sm">
                <div class="flex items-center gap-3 text-indigo-600 text-sm font-black uppercase tracking-[0.1em] mb-6 pb-4 border-b border-slate-100">
                    <span class="text-2xl">🔄</span> Riwayat Pindah Jurusan
                </div>
                <div class="space-y-4">
                    <?php while ($h = mysqli_fetch_assoc($hist)): ?>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 border-l-4 border-l-amber-400">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1"><?= date('d M Y H:i', strtotime($h['tgl_pindah'])) ?> &bull; <?= htmlspecialchars($h['petugas']) ?></div>
                        <div class="text-sm font-bold text-slate-700 mb-1"><span class="text-red-500 line-through mr-2"><?= htmlspecialchars($h['jurusan_lama']) ?></span> <i class="fas fa-arrow-right text-slate-400 mr-2"></i> <span class="text-emerald-600"><?= htmlspecialchars($h['jurusan_baru']) ?></span></div>
                        <?php if (!empty($h['alasan']) && $h['alasan'] != '-'): ?><div class="text-xs text-slate-500 italic">"<?= htmlspecialchars($h['alasan']) ?>"</div><?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-md border-t border-slate-200 p-4 lg:p-5 z-50 flex flex-col md:flex-row items-center justify-between gap-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center md:text-left">
                    <i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i> Pastikan field bertanda <span class="text-red-500">*</span> terisi.
                </div>
                <button type="submit" class="w-full md:w-auto px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-200 transform hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Simpan Perubahan Data
                </button>
            </div>
        </form>
    </div>

    <script>
    // Live update warna nilai BTQ
    function updateGrade(val) {
        const v = parseInt(val) || 0;
        const el = document.getElementById('btq-grade');
        let txt = '-', cls = 'text-slate-500 bg-slate-100 border-slate-200';
        
        if (v >= 80) { txt = 'A'; cls = 'text-emerald-700 bg-emerald-50 border-emerald-200'; }
        else if (v >= 65) { txt = 'B'; cls = 'text-sky-700 bg-sky-50 border-sky-200'; }
        else if (v >= 50) { txt = 'C'; cls = 'text-amber-700 bg-amber-50 border-amber-200'; }
        else if (v > 0) { txt = 'D'; cls = 'text-red-700 bg-red-50 border-red-200'; }
        
        el.textContent = txt; 
        el.className = `absolute right-3 top-1/2 -translate-y-1/2 px-2.5 py-1 text-[10px] font-black rounded-lg border transition-colors ${cls}`;
    }

    // Toggle Daftar Ulang Auto-Save
    function toggleDU(el, id) {
        const txt = document.getElementById('du-text');
        const st = el.checked ? 'SUDAH DAFTAR ULANG' : 'BELUM DAFTAR ULANG';

        txt.textContent = el.checked ? 'SUDAH DU' : 'BELUM DU';
        txt.className = `text-[10px] font-black uppercase tracking-wider ${el.checked ? 'text-emerald-600' : 'text-slate-400'}`;

        fetch(`proses_update.php?aksi=du_toggle&id=${id}&status=${encodeURIComponent(st)}`).then(() => {
            if (el.checked) {
                Swal.fire({
                    title: 'Daftar Ulang Aktif!',
                    text: 'Status otomatis tersimpan.',
                    icon: 'success', 
                    iconColor: '#10b981',
                    timer: 1500, 
                    showConfirmButton: false,
                    background: '#ffffff', 
                    color: '#334155',
                    customClass: { popup: 'border border-slate-200 rounded-2xl shadow-xl' }
                });
            }
        });
    }

    // Tangkap notifikasi sukses save dari URL
    const st = new URLSearchParams(window.location.search).get('status');
    if (st === 'success_edit') {
        Swal.fire({
            title: 'Data Tersimpan!',
            icon: 'success', 
            iconColor: '#4f46e5',
            timer: 2000, 
            showConfirmButton: false,
            background: '#ffffff', 
            color: '#334155',
            customClass: { popup: 'border border-slate-200 rounded-2xl shadow-xl' }
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?id=<?= $id ?>");
    }
    </script>
</body>
</html>
