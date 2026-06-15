<?php
/**
 * LENGKAPI DATA SISWA - Self-Service via Link WA
 * Akses publik via ?id_reg=SPMB26-XXX
 * Tema: Light, Clean, Warm & Interactive (OrangTua & Siswa Friendly)
 */
date_default_timezone_set('Asia/Jakarta'); // WIB
include 'config.php';

/* ── LOOKUP SISWA ─────────────────────────────────────── */
$id_reg = trim(mysqli_real_escape_string($conn, $_GET['id_reg'] ?? ''));
if (!$id_reg) { header("Location: /"); exit; }

$d = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM siswa WHERE id_pendaftaran = '$id_reg' LIMIT 1"
));
if (!$d) {
    http_response_code(404);
?><!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tidak Ditemukan</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-6 font-sans">
<div class="text-center max-w-sm">
  <div class="text-6xl mb-4">🔍</div>
  <h1 class="text-2xl font-black mb-2">Waduh, Linknya Nyasar!</h1>
  <p class="text-slate-500 text-sm">Kode <strong class="text-indigo-600"><?= htmlspecialchars($_GET['id_reg']??'') ?></strong> tidak ada di sistem kami. Coba pastikan lagi link yang kamu klik dari WhatsApp ya.</p>
</div></body></html>
<?php exit; }

$id_siswa = $d['id_siswa'];

/* ── CEK APAKAH SUDAH LENGKAP (locked) ───────────────── */
$wajib = ['jenis_kelamin','nisn','nik','tempat_lahir','tanggal_lahir','agama',
           'nama_jalan','kota','provinsi',
           'nama_ayah','tempat_lahir_ayah','tgl_lahir_ayah','pekerjaan_ayah','nik_ayah',
           'nama_ibu','tempat_lahir_ibu','tgl_lahir_ibu','pekerjaan_ibu','nik_ibu'];
$terisi  = array_filter($wajib, fn($f) => !empty($d[$f]));
$persen  = round(count($terisi) / count($wajib) * 100);
$is_locked = ($persen >= 100); // sudah penuh → read-only

/* ── PROSES SIMPAN ────────────────────────────────────── */
$pesan = '';
$pesan_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $pesan = 'Sesi kamu sudah habis nih. Coba refresh halamannya ya.';
        $pesan_type = 'error';
    } else {
        $esc  = fn($v) => mysqli_real_escape_string($conn, strtoupper(trim($v ?? '')));
        $escR = fn($v) => mysqli_real_escape_string($conn, trim($v ?? ''));
        $date = fn($v) => !empty($v) ? "'" . mysqli_real_escape_string($conn, $v) . "'" : "NULL";

        $nama           = $esc($_POST['nama_lengkap']);
        $jk             = $escR($_POST['jenis_kelamin']);
        $tempat_lhr     = $esc($_POST['tempat_lahir']);
        $tgl_lhr        = $date($_POST['tanggal_lahir']);
        $agama          = $escR($_POST['agama']);
        $no_hp          = $escR($_POST['no_hp']);
        $nisn           = $escR($_POST['nisn']);
        $nik            = $escR($_POST['nik']);
        $sekolah_asal   = $esc($_POST['sekolah_asal']);
        $nama_jalan     = $esc($_POST['nama_jalan']);
        $rt             = $escR($_POST['rt']);
        $rw             = $escR($_POST['rw']);
        $kelurahan      = $esc($_POST['kelurahan']);
        $kecamatan      = $esc($_POST['kecamatan']);
        $kota           = $esc($_POST['kota']);
        $provinsi       = $esc($_POST['provinsi']);
        $nama_ayah      = $esc($_POST['nama_ayah']);
        $nik_ayah       = $escR($_POST['nik_ayah']);
        $ttl_ayah       = $esc($_POST['tempat_lahir_ayah']);
        $tgl_ayah       = $date($_POST['tgl_lahir_ayah']);
        $pekerjaan_ayah = $escR($_POST['pekerjaan_ayah']);
        $nama_ibu       = $esc($_POST['nama_ibu']);
        $nik_ibu        = $escR($_POST['nik_ibu']);
        $ttl_ibu        = $esc($_POST['tempat_lahir_ibu']);
        $tgl_ibu        = $date($_POST['tgl_lahir_ibu']);
        $pekerjaan_ibu  = $escR($_POST['pekerjaan_ibu']);
        $req_kelas      = $esc($_POST['request_kelas']);

        if (!$nama || !$jk || !$agama || !$nisn || !$nik) {
            $pesan = 'Ups! Masih ada data bertanda bintang (*) yang belum kamu isi.';
            $pesan_type = 'error';
        } else {
            $sql = "UPDATE siswa SET
                nama_lengkap       = '$nama', jenis_kelamin      = '$jk',
                tempat_lahir       = '$tempat_lhr', tanggal_lahir      = $tgl_lhr,
                agama              = '$agama', no_hp              = '$no_hp',
                nisn               = '$nisn', nik                = '$nik',
                sekolah_asal       = '$sekolah_asal', nama_jalan         = '$nama_jalan',
                alamat             = '$nama_jalan', rt                 = '$rt',
                rw                 = '$rw', kelurahan          = '$kelurahan',
                kecamatan          = '$kecamatan', kota               = '$kota',
                provinsi           = '$provinsi', nama_ayah          = '$nama_ayah',
                nik_ayah           = '$nik_ayah', tempat_lahir_ayah  = '$ttl_ayah',
                tgl_lahir_ayah     = $tgl_ayah, pekerjaan_ayah     = '$pekerjaan_ayah',
                nama_ibu           = '$nama_ibu', nik_ibu            = '$nik_ibu',
                tempat_lahir_ibu   = '$ttl_ibu', tgl_lahir_ibu      = $tgl_ibu,
                pekerjaan_ibu      = '$pekerjaan_ibu', request_kelas      = '$req_kelas'
                WHERE id_siswa     = '$id_siswa'";

            if (mysqli_query($conn, $sql)) {
                header("Location: ?id_reg=$id_reg&saved=1");
                exit;
            } else {
                $pesan = 'Aduh, sistem sedang sibuk: ' . mysqli_error($conn);
                $pesan_type = 'error';
            }
        }
    }
}

$saved = isset($_GET['saved']);
$csrf_token = generate_csrf_token();

// Re-fetch fresh data
$d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = '$id_siswa' LIMIT 1"));
$terisi  = array_filter($wajib, fn($f) => !empty($d[$f]));
$persen  = round(count($terisi) / count($wajib) * 100);
$is_locked = ($persen >= 100);

function v($d,$k){ return htmlspecialchars($d[$k]??''); }
function sel($d,$k,$val){ return ($d[$k]??'')===$val ? 'selected' : ''; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biodata SPMB – <?= htmlspecialchars($d['nama_lengkap']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

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
  body { background-color: #f8fafc; scroll-behavior: smooth; }
  
  /* Animasi Transisi Halus */
  .fade-up { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
  @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  
  .toast-enter { animation: slideDown 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
  @keyframes slideDown { from { opacity: 0; transform: translateY(-20px) translateX(-50%); } to { opacity: 1; transform: translateY(0) translateX(-50%); } }

  /* Floating Input Styles (Bersih & Rapi) */
  .floating-input:focus ~ .floating-label,
  .floating-input:not(:placeholder-shown) ~ .floating-label {
      transform: translateY(-120%) scale(0.85);
      color: #4f46e5;
      background-color: #ffffff;
      padding: 0 8px;
      border-radius: 4px;
      font-weight: 800;
  }
  .bg-slate-50 .floating-input:focus ~ .floating-label,
  .bg-slate-50 .floating-input:not(:placeholder-shown) ~ .floating-label {
      background-color: #f8fafc; 
  }
</style>
</head>
<body class="text-slate-700 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%234f46e5\' fill-opacity=\'0.03\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] pb-28">

<?php if ($saved): ?>
<div class="fixed top-20 left-1/2 -translate-x-1/2 bg-indigo-600 text-white px-8 py-4 rounded-full shadow-2xl shadow-indigo-600/40 font-bold text-sm z-[100] toast-enter flex items-center gap-3" id="toastEl">
    <span class="text-2xl">💖</span> Wah Hebat! Datamu berhasil disimpan.
</div>
<script>
  setTimeout(() => { const t=document.getElementById('toastEl'); if(t){t.style.opacity='0';t.style.transition='opacity 0.5s';setTimeout(()=>t.remove(),500);} }, 4000);
  <?php if ($is_locked): ?>
  // Pesta Confetti Kemenangan!
  var duration = 3000; var end = Date.now() + duration;
  (function frame() { confetti({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#4f46e5', '#38bdf8'] });
  confetti({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#10b981', '#f59e0b'] });
  if (Date.now() < end) requestAnimationFrame(frame); }());
  <?php endif; ?>
</script>
<?php endif; ?>

<nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-xl border-b border-slate-200 shadow-sm transition-all duration-300">
  <div class="max-w-4xl mx-auto px-5 py-3.5 flex items-center justify-between">
    <div>
      <h1 id="greetingTitle" class="font-outfit font-black text-slate-900 leading-tight">Halo, <?= htmlspecialchars($d['nama_lengkap']) ?>! 👋</h1>
      <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">Lengkapi Biodata SPMB 2026</p>
    </div>
    <div class="flex items-center gap-4">
      <span class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 border border-indigo-100 text-indigo-600 rounded-full text-xs font-bold tracking-widest font-mono">
        <i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($id_reg) ?>
      </span>
      
      <div class="relative w-11 h-11 flex items-center justify-center group" title="<?= $persen ?>% Lengkap">
        <svg class="transform -rotate-90 w-11 h-11">
          <circle cx="22" cy="22" r="18" stroke="currentColor" stroke-width="3.5" fill="transparent" class="text-slate-100" />
          <circle cx="22" cy="22" r="18" stroke="currentColor" stroke-width="3.5" fill="transparent" 
                  stroke-dasharray="<?= round(2*3.14159*18) ?>" 
                  stroke-dashoffset="<?= round(2*3.14159*18 * (1 - $persen/100)) ?>"
                  class="transition-all duration-1000 ease-out <?= $persen>=100 ? 'text-emerald-500' : 'text-indigo-600' ?>" />
        </svg>
        <span class="absolute text-[10px] font-black <?= $persen>=100 ? 'text-emerald-600' : 'text-indigo-700' ?>"><?= $persen ?>%</span>
      </div>
    </div>
  </div>
</nav>

<main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8 fade-up">

  <div class="bg-gradient-to-br from-white to-indigo-50/50 border border-indigo-100 rounded-3xl p-6 sm:p-8 shadow-sm mb-6 relative overflow-hidden">
    <div class="absolute -right-10 -top-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-6 relative z-10">
      <div>
        <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1.5 flex items-center gap-1.5"><i class="fas fa-star text-amber-400"></i> Pilihan Masa Depanmu</p>
        <h2 class="font-outfit text-2xl sm:text-3xl font-black text-slate-800"><?= htmlspecialchars($d['jurusan']) ?></h2>
      </div>
      
      <div class="sm:text-right bg-white/60 backdrop-blur px-5 py-4 rounded-2xl border border-white">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status Pengisian</p>
        <div class="font-outfit text-2xl sm:text-3xl font-black <?= $persen>=100 ? 'text-emerald-500' : 'text-slate-800' ?> leading-none mb-1">
          <?= count($terisi) ?><span class="text-base text-slate-400">/<?= count($wajib) ?> Kolom</span>
        </div>
        <p class="text-[10px] font-bold <?= $persen>=100 ? 'text-emerald-600' : 'text-indigo-500' ?>">
          <?= $persen>=100 ? 'Sempurna! Siap daftar ulang.' : 'Yuk, selesaikan '. (count($wajib)-count($terisi)) .' kolom lagi!' ?>
        </p>
      </div>
    </div>
  </div>

  <?php if ($is_locked): ?>
  <div class="bg-emerald-50 border-2 border-emerald-100 rounded-3xl p-6 mb-8 flex flex-col sm:flex-row gap-5 items-center sm:items-start shadow-sm fade-up" style="animation-delay: 0.1s;">
    <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-3xl shrink-0">🎉</div>
    <div class="text-center sm:text-left">
      <h3 class="font-outfit font-black text-emerald-800 text-xl mb-1.5">Kerja Bagus! Datamu Sudah Lengkap</h3>
      <p class="text-sm text-emerald-700 leading-relaxed">Terima kasih ya sudah mengisi biodata dengan lengkap. Data ini sudah kami kunci agar aman. Kalau ternyata ada salah ketik, tenang saja, Kakak/Bapak/Ibu bisa konfirmasi langsung ke Panitia saat datang ke sekolah.</p>
    </div>
  </div>
  <?php endif; ?>

  <div class="bg-amber-50 border-2 border-amber-100 rounded-3xl p-6 sm:p-8 mb-8 shadow-sm fade-up" style="animation-delay: 0.2s;">
    <h3 class="font-outfit font-black text-amber-700 text-lg mb-3 flex items-center gap-2"><span class="text-2xl">📢</span> Langkah Selanjutnya: Daftar Ulang</h3>
    <p class="text-sm text-amber-900/80 mb-5 leading-relaxed">Formulir online ini adalah langkah awal yang hebat. Namun, <strong>kamu tetap wajib datang ke SMK Pasundan 2 Bandung</strong> untuk melakukan proses Daftar Ulang secara resmi dan menyerahkan berkas fisik ya.</p>
    
    <div class="bg-white border border-amber-200/60 rounded-2xl p-5">
      <h4 class="text-xs font-bold text-amber-600 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="fas fa-folder-open"></i> Siapkan Dokumen Ini ke Dalam Map:</h4>
      <div class="grid sm:grid-cols-2 gap-3 text-sm text-slate-700">
        <div class="flex items-start gap-2.5"><span class="text-emerald-500 font-black mt-0.5">✓</span> Fotokopi Ijazah / SKL (Dilegalisir) 2 Lembar</div>
        <div class="flex items-start gap-2.5"><span class="text-emerald-500 font-black mt-0.5">✓</span> Fotokopi Kartu Keluarga (KK) 2 Lembar</div>
        <div class="flex items-start gap-2.5"><span class="text-emerald-500 font-black mt-0.5">✓</span> Fotokopi Akta Kelahiran 2 Lembar</div>
        <div class="flex items-start gap-2.5"><span class="text-emerald-500 font-black mt-0.5">✓</span> Pas Foto 3x4 Latar Merah (4 Lembar)</div>
      </div>
    </div>
  </div>

  <?php if ($pesan_type==='error'): ?>
  <div class="bg-red-50 border-2 border-red-200 text-red-600 px-6 py-4 rounded-2xl font-bold text-sm mb-6 flex items-center gap-3">
    <span class="text-2xl">🙀</span> <?= htmlspecialchars($pesan) ?>
  </div>
  <?php endif; ?>

  <form method="POST" id="mainForm" <?= $is_locked?'style="pointer-events:none;opacity:0.8;filter:grayscale(20%);"':'' ?> class="fade-up" style="animation-delay: 0.3s;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div class="bg-white border border-slate-200 rounded-[2rem] p-6 sm:p-8 shadow-sm mb-6">
      <div class="flex items-center gap-3 mb-8 border-b border-slate-100 pb-4">
        <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-lg"><i class="fas fa-address-card"></i></div>
        <div>
          <h3 class="font-outfit font-black text-slate-800 text-lg">1. Identitas Diri Kamu</h3>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Sesuai Akta Lahir & KK</p>
        </div>
      </div>

      <div class="relative pt-2 mb-6">
        <input type="text" name="nama_lengkap" id="nama" value="<?= v($d,'nama_lengkap') ?>" placeholder=" " style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?> required
               class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
        <label for="nama" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nama Lengkap Sesuai Akta <span class="text-red-500">*</span></label>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2">Jenis Kelamin <span class="text-red-500">*</span></label>
          <select name="jenis_kelamin" <?= $is_locked?'disabled':'' ?> required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all disabled:bg-slate-100 appearance-none cursor-pointer">
            <option value="">— Pilih Kelamin —</option>
            <option value="LAKI-LAKI" <?= sel($d,'jenis_kelamin','LAKI-LAKI') ?>>Laki-laki (L)</option>
            <option value="PEREMPUAN" <?= sel($d,'jenis_kelamin','PEREMPUAN') ?>>Perempuan (P)</option>
          </select>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2">Agama <span class="text-red-500">*</span></label>
          <select name="agama" <?= $is_locked?'disabled':'' ?> required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all disabled:bg-slate-100 appearance-none cursor-pointer">
            <option value="">— Pilih Agama —</option>
            <?php foreach (['ISLAM','KRISTEN','KATOLIK','HINDU','BUDHA','KONGHUCU'] as $ag): ?>
            <option value="<?= $ag ?>" <?= sel($d,'agama',$ag) ?>><?= ucfirst(strtolower($ag)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div class="relative pt-2">
          <input type="text" name="tempat_lahir" id="tmpt" value="<?= v($d,'tempat_lahir') ?>" placeholder=" " style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?> required
                 class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
          <label for="tmpt" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kota Tempat Lahir <span class="text-red-500">*</span></label>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2">Tanggal Lahir <span class="text-red-500">*</span></label>
          <input type="date" name="tanggal_lahir" value="<?= $d['tanggal_lahir'] ?? '' ?>" required <?= $is_locked?'disabled':'' ?> class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all disabled:bg-slate-100">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div>
          <div class="relative pt-2">
            <input type="text" name="nisn" id="nisn" value="<?= v($d,'nisn') ?>" placeholder=" " inputmode="numeric" maxlength="10" <?= $is_locked?'disabled':'' ?> required
                   class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
            <label for="nisn" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">NISN Nasional <span class="text-red-500">*</span></label>
          </div>
          <p class="text-[10px] text-indigo-500 mt-2 font-bold ml-2 flex items-center gap-1.5"><i class="fas fa-lightbulb"></i> 10 digit angka, lihat di raport SMP.</p>
        </div>
        <div>
          <div class="relative pt-2">
            <input type="text" name="nik" id="nik" value="<?= v($d,'nik') ?>" placeholder=" " inputmode="numeric" maxlength="16" <?= $is_locked?'disabled':'' ?> required
                   class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
            <label for="nik" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">NIK (Nomor KTP/KK) <span class="text-red-500">*</span></label>
          </div>
          <p class="text-[10px] text-amber-600 mt-2 font-bold ml-2 flex items-center gap-1.5"><i class="fas fa-exclamation-triangle"></i> Wajib 16 digit sesuai Kartu Keluarga (KK).</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="relative pt-2">
          <input type="tel" name="no_hp" id="hp" value="<?= v($d,'no_hp') ?>" placeholder=" " inputmode="numeric" <?= $is_locked?'disabled':'' ?> required
                 class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
          <label for="hp" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nomor WhatsApp Aktif <span class="text-red-500">*</span></label>
        </div>
        <div class="relative pt-2">
          <input type="text" name="sekolah_asal" id="skl" value="<?= v($d,'sekolah_asal') ?: v($d,'asal_sekolah') ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
                 class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
          <label for="skl" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Asal Sekolah (SMP/MTs) <span class="text-red-500">*</span></label>
        </div>
      </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-[2rem] p-6 sm:p-8 shadow-sm mb-6">
      <div class="flex items-center gap-3 mb-8 border-b border-slate-100 pb-4">
        <div class="w-10 h-10 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center text-lg"><i class="fas fa-map-marked-alt"></i></div>
        <div>
          <h3 class="font-outfit font-black text-slate-800 text-lg">2. Alamat Rumah Saat Ini</h3>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Alamat tempat tinggal</p>
        </div>
      </div>

      <div class="relative pt-2 mb-6">
        <input type="text" name="nama_jalan" id="jln" value="<?= v($d,'nama_jalan') ?: v($d,'alamat') ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
               class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
        <label for="jln" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nama Jalan / Perumahan / Gang <span class="text-red-500">*</span></label>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 mb-6">
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2">RT <span class="text-red-500">*</span></label>
          <input type="text" name="rt" value="<?= v($d,'rt') ?>" placeholder="001" inputmode="numeric" maxlength="3" <?= $is_locked?'disabled':'' ?> required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all disabled:bg-slate-100 text-center">
        </div>
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2">RW <span class="text-red-500">*</span></label>
          <input type="text" name="rw" value="<?= v($d,'rw') ?>" placeholder="002" inputmode="numeric" maxlength="3" <?= $is_locked?'disabled':'' ?> required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all disabled:bg-slate-100 text-center">
        </div>
        <div class="col-span-2 relative pt-2 sm:mt-0 mt-2">
          <input type="text" name="kelurahan" id="kel" value="<?= v($d,'kelurahan') ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
                 class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
          <label for="kel" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Desa / Kelurahan <span class="text-red-500">*</span></label>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="relative pt-2">
          <input type="text" name="kecamatan" id="kec" value="<?= v($d,'kecamatan') ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
                 class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
          <label for="kec" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kecamatan <span class="text-red-500">*</span></label>
        </div>
        <div class="relative pt-2">
          <input type="text" name="kota" id="kota" value="<?= v($d,'kota') ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
                 class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
          <label for="kota" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kota / Kabupaten <span class="text-red-500">*</span></label>
        </div>
        <div class="relative pt-2">
          <input type="text" name="provinsi" id="prov" value="<?= v($d,'provinsi') ?: 'JAWA BARAT' ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
                 class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
          <label for="prov" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Provinsi <span class="text-red-500">*</span></label>
        </div>
      </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-[2rem] p-6 sm:p-8 shadow-sm mb-6">
      <div class="flex items-center gap-3 mb-8 border-b border-slate-100 pb-4">
        <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-lg"><i class="fas fa-users"></i></div>
        <div>
          <h3 class="font-outfit font-black text-slate-800 text-lg">3. Profil Orang Tua / Wali</h3>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Sesuai KTP & KK</p>
        </div>
      </div>

      <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-5 mb-8">
        <h4 class="font-bold text-blue-800 flex items-center gap-2 mb-5"><span class="text-2xl">👨</span> Data Ayah Kandung</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
          <div class="relative pt-2">
            <input type="text" name="nama_ayah" id="ayah" value="<?= v($d,'nama_ayah') ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
                   class="floating-input w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
            <label for="ayah" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nama Ayah <span class="text-red-500">*</span></label>
          </div>
          <div class="relative pt-2">
            <input type="text" name="nik_ayah" id="nik_ayh" value="<?= v($d,'nik_ayah') ?>" placeholder=" " inputmode="numeric" maxlength="16" required <?= $is_locked?'disabled':'' ?>
                   class="floating-input w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
            <label for="nik_ayh" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">NIK Ayah (16 Digit) <span class="text-red-500">*</span></label>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div class="relative pt-2">
            <input type="text" name="tempat_lahir_ayah" id="tmpt_ayh" value="<?= v($d,'tempat_lahir_ayah') ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
                   class="floating-input w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
            <label for="tmpt_ayh" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kota Lahir <span class="text-red-500">*</span></label>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2">Tgl Lahir Ayah <span class="text-red-500">*</span></label>
            <input type="date" name="tgl_lahir_ayah" value="<?= v($d,'tgl_lahir_ayah') ?>" required <?= $is_locked?'disabled':'' ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all disabled:bg-slate-100">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2">Pekerjaan Ayah <span class="text-red-500">*</span></label>
            <select name="pekerjaan_ayah" required <?= $is_locked?'disabled':'' ?> class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all disabled:bg-slate-100 appearance-none cursor-pointer">
              <option value="">— Pilih —</option>
              <?php foreach (['PNS','TNI/POLRI','WIRASWASTA','KARYAWAN SWASTA','BURUH','PETANI','PEDAGANG','PENSIUNAN','TIDAK BEKERJA','LAINNYA'] as $pj): ?>
              <option value="<?= $pj ?>" <?= sel($d,'pekerjaan_ayah',$pj) ?>><?= $pj ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-5">
        <h4 class="font-bold text-rose-800 flex items-center gap-2 mb-5"><span class="text-2xl">👩</span> Data Ibu Kandung</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
          <div class="relative pt-2">
            <input type="text" name="nama_ibu" id="ibu" value="<?= v($d,'nama_ibu') ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
                   class="floating-input w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
            <label for="ibu" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Nama Ibu Kandung <span class="text-red-500">*</span></label>
          </div>
          <div class="relative pt-2">
            <input type="text" name="nik_ibu" id="nik_ibu" value="<?= v($d,'nik_ibu') ?>" placeholder=" " inputmode="numeric" maxlength="16" required <?= $is_locked?'disabled':'' ?>
                   class="floating-input w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
            <label for="nik_ibu" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">NIK Ibu (16 Digit) <span class="text-red-500">*</span></label>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div class="relative pt-2">
            <input type="text" name="tempat_lahir_ibu" id="tmpt_ibu" value="<?= v($d,'tempat_lahir_ibu') ?>" placeholder=" " style="text-transform:uppercase;" required <?= $is_locked?'disabled':'' ?>
                   class="floating-input w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
            <label for="tmpt_ibu" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Kota Lahir <span class="text-red-500">*</span></label>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2">Tgl Lahir Ibu <span class="text-red-500">*</span></label>
            <input type="date" name="tgl_lahir_ibu" value="<?= v($d,'tgl_lahir_ibu') ?>" required <?= $is_locked?'disabled':'' ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all disabled:bg-slate-100">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2">Pekerjaan Ibu <span class="text-red-500">*</span></label>
            <select name="pekerjaan_ibu" required <?= $is_locked?'disabled':'' ?> class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all disabled:bg-slate-100 appearance-none cursor-pointer">
              <option value="">— Pilih —</option>
              <?php foreach (['PNS','TNI/POLRI','WIRASWASTA','KARYAWAN SWASTA','BURUH','PETANI','PEDAGANG','PENSIUNAN','IBU RUMAH TANGGA','TIDAK BEKERJA','LAINNYA'] as $pj): ?>
              <option value="<?= $pj ?>" <?= sel($d,'pekerjaan_ibu',$pj) ?>><?= $pj ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-[2rem] p-6 sm:p-8 shadow-sm mb-6">
      <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
        <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-lg"><i class="fas fa-magic"></i></div>
        <div>
          <h3 class="font-outfit font-black text-slate-800 text-lg">4. Preferensi & Harapan</h3>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Opsional tapi seru</p>
        </div>
      </div>
      <div class="relative pt-2">
        <input type="text" name="request_kelas" id="req" value="<?= v($d,'request_kelas') ?>" placeholder=" " style="text-transform:uppercase;" <?= $is_locked?'disabled':'' ?>
               class="floating-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-bold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-50 outline-none transition-all peer disabled:bg-slate-100">
        <label for="req" class="floating-label absolute left-5 top-5 text-slate-400 text-xs font-bold uppercase tracking-widest transition-all pointer-events-none bg-transparent px-1">Request Nama Teman Sekelas</label>
      </div>
      <p class="text-xs text-indigo-500 mt-2 font-medium ml-2">Punya bestie dari SMP? Tulis di sini ya! Kami usahakan kalian sekelas (tapi rahasia ya 😉).</p>
    </div>

  </form>

  <?php if ($is_locked): ?>
  <div class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-xl mb-6 fade-up" style="animation-delay: 0.4s;">
    <h3 class="font-outfit font-black text-indigo-600 text-lg mb-2 flex items-center gap-2"><span class="text-2xl">🎫</span> Tiket Boarding SPMB</h3>
    <p class="text-sm text-slate-500 mb-6">Simpan atau unduh tiket elegan ini sebagai bukti kalau kamu sudah terdaftar resmi.</p>

    <div id="kartuPreview" class="relative bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 text-white overflow-hidden shadow-inner">
      <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-xl"></div>
      <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-sky-400/20 rounded-full blur-xl"></div>

      <div class="relative z-10 flex justify-between items-start border-b border-white/20 pb-4 mb-4">
        <div>
          <h4 class="font-outfit font-black text-xl tracking-tight">SMK Pasundan 2 Bandung</h4>
          <p class="text-[10px] text-indigo-200 uppercase tracking-widest font-bold">Bukti Pengisian Data SPMB 2026/2027</p>
        </div>
        <div class="bg-emerald-500 text-white px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider flex items-center gap-1 shadow-sm">
          <i class="fas fa-check-circle"></i> Terverifikasi
        </div>
      </div>

      <div class="relative z-10 mb-6">
        <p class="text-[10px] text-indigo-200 uppercase tracking-widest font-bold mb-1">Nama Calon Siswa</p>
        <h2 class="font-outfit text-2xl md:text-3xl font-black uppercase"><?= htmlspecialchars($d['nama_lengkap']) ?></h2>
      </div>

      <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-black/20 rounded-xl p-3">
          <p class="text-[9px] text-indigo-200 uppercase tracking-widest font-bold mb-1">No. Pendaftaran</p>
          <p class="font-mono text-sm font-bold text-sky-300"><?= htmlspecialchars($id_reg) ?></p>
        </div>
        <div class="bg-black/20 rounded-xl p-3">
          <p class="text-[9px] text-indigo-200 uppercase tracking-widest font-bold mb-1">Jurusan</p>
          <p class="text-sm font-bold text-white"><?= htmlspecialchars($d['jurusan']) ?></p>
        </div>
        <div class="col-span-2 bg-black/20 rounded-xl p-3">
          <p class="text-[9px] text-indigo-200 uppercase tracking-widest font-bold mb-1">Asal Sekolah</p>
          <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($d['sekolah_asal'] ?: $d['asal_sekolah'] ?: '-') ?></p>
        </div>
      </div>

      <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-4 border-t border-white/20 pt-4">
        <div class="text-center md:text-left">
          <p class="text-[9px] text-indigo-200 uppercase tracking-widest font-bold mb-0.5">Tanggal Submit Data</p>
          <p class="text-xs font-bold text-white">✅ <?= date('d F Y, H:i') ?> WIB</p>
        </div>
        <div class="text-[10px] font-medium text-indigo-200 text-center md:text-right max-w-xs leading-tight">
          *Ini adalah dokumen sah digital. Jangan lupa daftar ulang ke sekolah ya!
        </div>
      </div>
    </div>

    <button class="w-full mt-6 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold text-sm px-6 py-4 rounded-xl shadow-lg shadow-amber-200 transform hover:-translate-y-0.5 active:scale-95 transition-all flex items-center justify-center gap-2" onclick="downloadKartuPDF()">
      <i class="fas fa-file-pdf"></i> Unduh Tiket Pendaftaran (PDF)
    </button>
  </div>
  <?php endif; ?>

</main>

<?php if (!$is_locked): ?>
<div class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-xl border-t border-slate-200 p-4 sm:p-5 z-50 shadow-[0_-10px_30px_rgba(0,0,0,0.05)]">
  <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
    <div class="hidden sm:block flex-1">
      <p class="text-xs font-bold text-slate-500">Sudah yakin benar semua?</p>
      <p class="text-[10px] text-slate-400">Pastikan field <span class="text-red-500 font-bold">*</span> sudah terisi ya.</p>
    </div>
    <button type="button" class="w-full sm:w-auto bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-black text-sm px-10 py-4 rounded-xl shadow-xl shadow-indigo-200 transition-all flex items-center justify-center gap-2 transform hover:-translate-y-1 active:scale-95" id="btnSubmit" onclick="submitForm()">
      <i class="fas fa-paper-plane"></i> Simpan & Kunci Biodata
    </button>
  </div>
</div>
<?php endif; ?>

<div class="fixed bottom-24 sm:bottom-28 right-6 pointer-events-none z-40 flex flex-col items-end select-none transition-all duration-300" id="mascot-container">
    <div id="mascot-bubble" class="text-[11px] bg-indigo-600 text-white font-bold px-4 py-2.5 rounded-2xl shadow-xl mb-2 relative transition-all duration-300 transform origin-bottom-right">
        <div class="absolute -bottom-1.5 right-6 w-3 h-3 bg-indigo-600 transform rotate-45"></div>
        <span id="mascot-text">Semangat lengkapi datanya ya! 🐾</span>
    </div>
    <div class="text-5xl drop-shadow-lg transition-transform duration-300 hover:scale-125 cursor-pointer pointer-events-auto mr-1" onclick="pokeMascot()" id="mascot-icon">🐱</div>
</div>

<script>
// --- LOGIKA SAPAAN DINAMIS ---
const hour = new Date().getHours();
let greet = 'Halo';
if (hour >= 4 && hour < 10) greet = 'Selamat Pagi';
else if (hour >= 10 && hour < 15) greet = 'Selamat Siang';
else if (hour >= 15 && hour < 18) greet = 'Selamat Sore';
else greet = 'Selamat Malam';
document.getElementById('greetingTitle').innerHTML = `${greet}, <br class="sm:hidden"><span class="text-indigo-600"><?= htmlspecialchars($d['nama_lengkap']) ?></span>! 👋`;

// --- LOGIKA KUCING INTERAKTIF ---
const mascotText = document.getElementById('mascot-text');
const mascotIcon = document.getElementById('mascot-icon');
const defaultText = "Semangat lengkapi datanya ya! 🐾";

// Kamus tips dari Kucing
const hints = {
    'nama_lengkap': 'Tulis nama lengkap sesuai Akta Kelahiran ya! 📝',
    'jenis_kelamin': 'Dipilih jenis kelaminnya Kak 👦👧',
    'agama': 'Dipilih juga agamanya ya 🙏',
    'tempat_lahir': 'Lahir di kota mana nih? 🏙️',
    'tanggal_lahir': 'Jangan sampai salah tanggal ulang tahunmu 🎂',
    'nik': 'Hati-hati ketik NIK, coba intip Kartu Keluarga (KK) 🔍',
    'nisn': 'NISN itu 10 angka, biasanya ada di Ijazah atau Raport 📖',
    'no_hp': 'Pastikan nomor WA-nya aktif untuk info daftar ulang 📱',
    'sekolah_asal': 'Alumni dari SMP/MTs mana nih? 🏫',
    'nama_jalan': 'Tulis alamat selengkap mungkin ya biar gampang dicari 🛵',
    'nama_ayah': 'Siapa nama pahlawan lelakimu? Tulis nama Ayah ya 👨',
    'nama_ibu': 'Tulis nama ibunda tercinta yang benar ya, Ibu hebat! 👩‍👦',
    'request_kelas': 'Wah, mau sekelas sama siapa nih? Bisikin dong 😉'
};

document.querySelectorAll('input, select').forEach(el => {
    el.addEventListener('focus', function() {
        let name = this.getAttribute('name');
        if (hints[name]) {
            mascotText.innerHTML = hints[name];
            mascotIcon.innerHTML = '😺';
        } else {
            mascotText.innerHTML = "Ketik pelan-pelan saja, pastikan benar. ✍️";
            mascotIcon.innerHTML = '😸';
        }
    });
    el.addEventListener('blur', function() {
        mascotText.innerHTML = defaultText;
        mascotIcon.innerHTML = '🐱';
    });
});

function pokeMascot() {
    mascotIcon.innerHTML = '🙀';
    mascotText.innerHTML = 'Aw! Jangan sentuh-sentuh, fokus isi data dong! 😹';
    setTimeout(() => { mascotIcon.innerHTML = '🐱'; mascotText.innerHTML = defaultText; }, 2500);
}

// --- LOGIKA SUBMIT FORM ---
function submitForm() {
  const btn = document.getElementById('btnSubmit');

  // Validasi JS Sederhana
  const checks = [
    ['nama_lengkap', 'Nama lengkap wajib diisi'],
    ['jenis_kelamin', 'Jenis kelamin wajib dipilih'],
    ['agama', 'Agama wajib dipilih'],
    ['nisn', 'NISN wajib diisi'],
    ['nik', 'NIK wajib diisi'],
    ['nama_ayah', 'Nama ayah wajib diisi'],
    ['nama_ibu', 'Nama ibu wajib diisi'],
  ];
  for (const [name, msg] of checks) {
    const el = document.querySelector(`[name="${name}"]`);
    if (el && !el.value.trim()) {
      el.style.borderColor = '#ef4444';
      el.style.boxShadow   = '0 0 0 4px rgba(239,68,68,.15)';
      el.scrollIntoView({ behavior:'smooth', block:'center' });
      el.focus();
      setTimeout(() => { el.style.borderColor=''; el.style.boxShadow=''; }, 2500);
      mascotIcon.innerHTML = '😿';
      mascotText.innerHTML = 'Ups! Kolom bertanda merah belum diisi tuh.';
      return;
    }
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
  document.getElementById('mainForm').submit();
}
</script>

<?php if ($is_locked): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
async function downloadKartuPDF() {
  const btn = event.currentTarget;
  btn.disabled = true;
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan PDF...';

  try {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a5' });

    // A5 Size: 148 x 210 mm
    const pw = 148, ph = 210, margin = 10, cw = pw - margin * 2;

    // AMBIL DATA LANGSUNG DARI PHP (Anti-Gagal)
    const namaSiswa = <?= json_encode(strtoupper($d['nama_lengkap'] ?? '-')) ?>;
    const noReg = <?= json_encode($id_reg ?? '-') ?>;
    const jurusanSiswa = <?= json_encode(strtoupper($d['jurusan'] ?? '-')) ?>;
    const asalSekolah = <?= json_encode(strtoupper($d['sekolah_asal'] ?: ($d['asal_sekolah'] ?? '-'))) ?>;
    const tglSubmit = <?= json_encode(date('d F Y, H:i') . ' WIB') ?>;

    // Background Putih Bersih
    pdf.setFillColor(255, 255, 255);
    pdf.rect(0, 0, pw, ph, 'F');
    
    // Header Biru Indigo
    pdf.setFillColor(79, 70, 229); 
    pdf.rect(0, 0, pw, 26, 'F');

    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(14);
    pdf.setTextColor(255, 255, 255);
    pdf.text('SMK PASUNDAN 2 BANDUNG', margin, 12);
    
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(8);
    pdf.setTextColor(199, 210, 254);
    pdf.text('TIKET BUKTI PENGISIAN DATA SPMB 2026/2027', margin, 18);

    // Badge Terverifikasi
    pdf.setFillColor(16, 185, 129);
    pdf.roundedRect(pw - margin - 28, 8, 28, 7, 1, 1, 'F');
    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(6);
    pdf.setTextColor(255, 255, 255);
    pdf.text('TERVERIFIKASI', pw - margin - 25, 12.5);

    // Bagian Nama
    let y = 36;
    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(8);
    pdf.setTextColor(100, 116, 139);
    pdf.text('NAMA CALON PESERTA DIDIK', margin, y);
    
    y += 6;
    pdf.setFontSize(14);
    pdf.setTextColor(15, 23, 42);
    pdf.text(namaSiswa, margin, y);

    // Kotak Info Grid
    y += 10;
    pdf.setFillColor(248, 250, 252);
    pdf.setDrawColor(226, 232, 240);
    pdf.roundedRect(margin, y, cw, 40, 2, 2, 'FD');

    pdf.setFontSize(7);
    pdf.setTextColor(148, 163, 184);
    pdf.text('NO. PENDAFTARAN', margin + 5, y + 8);
    pdf.text('JURUSAN', margin + 65, y + 8);
    pdf.text('ASAL SEKOLAH', margin + 5, y + 25);
    pdf.text('STATUS DATA', margin + 65, y + 25);

    pdf.setFontSize(9);
    pdf.setTextColor(79, 70, 229);
    pdf.text(noReg, margin + 5, y + 13);
    
    pdf.setTextColor(15, 23, 42);
    pdf.text(jurusanSiswa, margin + 65, y + 13);
    
    // Potong teks nama sekolah jika terlalu panjang agar tidak merusak layout PDF
    let slines = pdf.splitTextToSize(asalSekolah, 55);
    pdf.text(slines[0] || '-', margin + 5, y + 30);
    
    pdf.setTextColor(16, 185, 129);
    pdf.text('100% LENGKAP', margin + 65, y + 30);

    // Tanggal
    y += 48;
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(8);
    pdf.setTextColor(100, 116, 139);
    pdf.text('Tanggal Submit Data:', margin, y);
    pdf.setFont('helvetica', 'bold');
    pdf.setTextColor(15, 23, 42);
    pdf.text(tglSubmit, margin + 32, y);

    // Garis Putus-putus (Sobekan Tiket)
    y += 8;
    pdf.setLineDashPattern([2, 2], 0);
    pdf.line(margin, y, pw - margin, y);
    pdf.setLineDashPattern([], 0);

    // KOTAK PESAN PENYEMANGAT
    y += 6;
    pdf.setFillColor(240, 249, 255); 
    pdf.setDrawColor(186, 230, 253); 
    pdf.roundedRect(margin, y, cw, 17, 2, 2, 'FD');
    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(8);
    pdf.setTextColor(2, 132, 199); 
    pdf.text('🌟 SATU LANGKAH LEBIH DEKAT MENUJU MIMPIMU!', margin + 4, y + 6.5);
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(7);
    pdf.setTextColor(71, 85, 105);
    pdf.text('Terima kasih sudah memilih SMK Pasundan 2. Tetap semangat, jaga', margin + 4, y + 11.5);
    pdf.text('kesehatan, dan persiapkan dirimu untuk menjadi yang terbaik!', margin + 4, y + 15);

    // INFO DAFTAR ULANG & CHECKLIST
    y += 21;
    pdf.setFillColor(254, 252, 232); 
    pdf.setDrawColor(253, 230, 138); 
    pdf.roundedRect(margin, y, cw, 42, 2, 2, 'FD');
    
    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(9);
    pdf.setTextColor(217, 119, 6);
    pdf.text('PERHATIAN: WAJIB DAFTAR ULANG', margin + 4, y + 7);

    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(7.5);
    pdf.setTextColor(71, 85, 105);
    const instruksi = "Calon siswa WAJIB datang ke sekolah untuk daftar ulang fisik dengan membawa persyaratan berikut di dalam map:";
    const instruksiLines = pdf.splitTextToSize(instruksi, cw - 8);
    pdf.text(instruksiLines, margin + 4, y + 12);

    y += 20;
    const syarat = [
      'Fotokopi Ijazah / SKL & KK (Masing-masing 2 lembar)',
      'Fotokopi Akta Kelahiran & KTP Ortu (Masing-masing 2 lembar)',
      'Pas foto 3x4 cm latar merah (4 lembar)',
      'Membawa dokumen asli untuk verifikasi akhir.'
    ];
    
    pdf.setFont('helvetica', 'normal');
    syarat.forEach(item => {
      // Menggambar Kotak Checkbox [ ]
      pdf.setDrawColor(148, 163, 184); 
      pdf.setLineWidth(0.3);
      pdf.rect(margin + 4, y - 2.5, 3, 3);
      
      pdf.text(item, margin + 9, y);
      y += 5;
    });

    // SIMULASI BARCODE TIKET
    y += 3;
    pdf.setFillColor(15, 23, 42); 
    let startX = pw / 2 - 25;
    for(let i = 0; i < 40; i++) {
        let barWidth = Math.random() > 0.5 ? 0.6 : 0.3; 
        let space = Math.random() > 0.3 ? 1.2 : 0.6; 
        if (Math.random() > 0.2) {
            pdf.rect(startX, y, barWidth, 8, 'F');
        }
        startX += space;
    }

    // Footer
    y = ph - 8;
    pdf.setFontSize(6);
    pdf.setTextColor(148, 163, 184);
    pdf.text('Dokumen digenerate sah secara otomatis oleh Sistem SPMB SMK Pasundan 2.', pw / 2, y, { align: 'center' });

    pdf.save('Tiket_SPMB_' + noReg + '.pdf');

  } catch(err) {
    console.error(err);
    alert('Terjadi kesalahan sistem saat membuat PDF. Hubungi panitia.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}
</script>
<?php endif; ?>
</body>
</html>
