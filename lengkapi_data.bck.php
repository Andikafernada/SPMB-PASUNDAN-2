<?php
/**
 * LENGKAPI DATA SISWA - Self-Service via Link WA
 * Akses publik via ?id_reg=SPMB26-XXX
 * Data masuk ke tabel siswa, identik dengan edit.php tim database
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
<body class="bg-[#07080f] text-white min-h-screen flex items-center justify-center p-6">
<div class="text-center max-w-sm">
  <div class="text-6xl mb-4">🔍</div>
  <h1 class="text-2xl font-black mb-2">ID Tidak Ditemukan</h1>
  <p class="text-gray-400 text-sm">Kode <strong class="text-white"><?= htmlspecialchars($_GET['id_reg']??'') ?></strong> tidak ada di sistem kami. Pastikan link yang kamu akses sudah benar.</p>
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
        $pesan = 'Permintaan tidak valid. Refresh dan coba lagi.';
        $pesan_type = 'error';
    } else {
        // Helper
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

        // Validasi minimal
        if (!$nama || !$jk || !$agama || !$nisn || !$nik) {
            $pesan = 'Masih ada data wajib yang kosong. Periksa kembali form.';
            $pesan_type = 'error';
        } else {
            $sql = "UPDATE siswa SET
                nama_lengkap       = '$nama',
                jenis_kelamin      = '$jk',
                tempat_lahir       = '$tempat_lhr',
                tanggal_lahir      = $tgl_lhr,
                agama              = '$agama',
                no_hp              = '$no_hp',
                nisn               = '$nisn',
                nik                = '$nik',
                sekolah_asal       = '$sekolah_asal',
                nama_jalan         = '$nama_jalan',
                alamat             = '$nama_jalan',
                rt                 = '$rt',
                rw                 = '$rw',
                kelurahan          = '$kelurahan',
                kecamatan          = '$kecamatan',
                kota               = '$kota',
                provinsi           = '$provinsi',
                nama_ayah          = '$nama_ayah',
                nik_ayah           = '$nik_ayah',
                tempat_lahir_ayah  = '$ttl_ayah',
                tgl_lahir_ayah     = $tgl_ayah,
                pekerjaan_ayah     = '$pekerjaan_ayah',
                nama_ibu           = '$nama_ibu',
                nik_ibu            = '$nik_ibu',
                tempat_lahir_ibu   = '$ttl_ibu',
                tgl_lahir_ibu      = $tgl_ibu,
                pekerjaan_ibu      = '$pekerjaan_ibu',
                request_kelas      = '$req_kelas'
                WHERE id_siswa     = '$id_siswa'";

            if (mysqli_query($conn, $sql)) {
                // Refresh data & hitung ulang kelengkapan
                header("Location: ?id_reg=$id_reg&saved=1");
                exit;
            } else {
                $pesan = 'Gagal menyimpan: ' . mysqli_error($conn);
                $pesan_type = 'error';
            }
        }
    }
}

$saved = isset($_GET['saved']);
$csrf_token = generate_csrf_token();

// Re-fetch fresh data setelah simpan
$d = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM siswa WHERE id_siswa = '$id_siswa' LIMIT 1"
));
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
<title>Lengkapi Biodata – <?= htmlspecialchars($d['nama_lengkap']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:     #07080f;
    --panel:  #0d0f1a;
    --border: rgba(255,255,255,.07);
    --accent: #6c63ff;
    --sky:    #38c6f5;
    --green:  #22d37f;
    --warn:   #f5a623;
    --red:    #f87171;
  }
  *, *::before, *::after { box-sizing: border-box; }
  body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: #cdd3ef; margin: 0; }
  .display { font-family: 'Space Grotesk', sans-serif; }

  /* sticky header */
  .top-bar {
    position: sticky; top: 0; z-index: 50;
    background: rgba(7,8,15,.9); backdrop-filter: blur(18px);
    border-bottom: 1px solid var(--border);
    padding: 14px 20px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
  }
  .badge-id {
    font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: .75rem;
    background: rgba(108,99,255,.15); border: 1px solid rgba(108,99,255,.35);
    color: #a5a0ff; padding: 5px 12px; border-radius: 99px;
  }

  /* progress ring */
  .ring-wrap { position: relative; width: 52px; height: 52px; flex-shrink: 0; }
  .ring-wrap svg { transform: rotate(-90deg); }
  .ring-pct { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
              font-size: .65rem; font-weight: 900; color: #fff; font-family: 'Space Grotesk', sans-serif; }

  /* hero card */
  .hero-card {
    background: var(--panel); border: 1px solid var(--border); border-radius: 20px;
    padding: 24px; margin: 16px; margin-bottom: 0;
  }

  /* section card */
  .sec-card {
    background: var(--panel); border: 1px solid var(--border); border-radius: 20px;
    padding: 22px 20px; margin: 12px 16px;
  }
  .sec-title {
    display: flex; align-items: center; gap: 10px;
    font-family: 'Space Grotesk', sans-serif; font-size: .8rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .1em; color: var(--sky);
    padding-bottom: 14px; border-bottom: 1px solid var(--border); margin-bottom: 18px;
  }
  .sec-icon { font-size: 1.1rem; }

  /* fields */
  .field { margin-bottom: 16px; }
  .field label {
    display: block; font-size: .68rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .08em; color: #6b7280; margin-bottom: 7px;
  }
  .field label .req { color: var(--red); }
  .field input, .field select, .field textarea {
    width: 100%; padding: 13px 16px;
    background: rgba(255,255,255,.04); border: 1.5px solid rgba(255,255,255,.08);
    border-radius: 13px; color: #fff; font-family: inherit;
    font-size: .92rem; font-weight: 500; transition: border-color .2s, box-shadow .2s;
    -webkit-appearance: none;
  }
  .field input::placeholder { color: #3a3f58; }
  .field input:focus, .field select:focus {
    outline: none; border-color: var(--accent);
    box-shadow: 0 0 0 4px rgba(108,99,255,.12);
  }
  .field input[disabled], .field select[disabled] {
    opacity: .45; cursor: not-allowed;
  }
  .field select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center; background-size: 16px;
    cursor: pointer;
  }
  .field select option { background: #0d0f1a; }
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; }
  @media(max-width:480px){ .grid-2 { grid-template-columns: 1fr; } .grid-4 { grid-template-columns: 1fr 1fr; } }

  /* sub-header ortu */
  .ortu-head {
    display: flex; align-items: center; gap: 12px;
    background: rgba(255,255,255,.03); border: 1px solid var(--border);
    border-radius: 14px; padding: 14px 16px; margin-bottom: 16px;
  }
  .ortu-head .emoji { font-size: 1.8rem; }
  .ortu-head .lbl { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: .95rem; color: #fff; }
  .ortu-head .sub { font-size: .68rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .07em; }
  .divider { height: 1px; background: var(--border); margin: 20px 0; }

  /* info banner */
  .info-banner {
    background: rgba(56,198,245,.07); border: 1px solid rgba(56,198,245,.2);
    border-radius: 14px; padding: 14px 16px;
    font-size: .8rem; color: #93c5fd; font-weight: 600;
    display: flex; gap: 10px; align-items: flex-start;
    margin: 12px 16px;
  }

  /* toast saved */
  .toast-saved {
    position: fixed; top: 72px; left: 50%; transform: translateX(-50%);
    background: var(--green); color: #064e3b;
    font-family: 'Space Grotesk', sans-serif; font-weight: 800; font-size: .85rem;
    padding: 11px 22px; border-radius: 99px;
    box-shadow: 0 6px 20px rgba(34,211,127,.4);
    z-index: 200; white-space: nowrap;
    animation: toastIn .4s cubic-bezier(.34,1.56,.64,1) forwards;
  }
  @keyframes toastIn { from { opacity:0; transform:translateX(-50%) translateY(-8px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }

  /* bottom bar */
  .bottom-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: rgba(7,8,15,.95); backdrop-filter: blur(18px);
    border-top: 1px solid var(--border);
    padding: 14px 16px; z-index: 50;
    display: flex; align-items: center; gap: 12px;
  }
  .btn-submit {
    flex: 1; padding: 15px;
    background: linear-gradient(135deg, #22d37f, #0ea5e9);
    color: #fff; font-family: 'Space Grotesk', sans-serif;
    font-weight: 800; font-size: .95rem; border: none;
    border-radius: 14px; cursor: pointer;
    transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(34,211,127,.35); }
  .btn-submit:disabled { opacity: .5; cursor: not-allowed; transform: none; }

  /* daftar ulang banner */
  .daftar-ulang-banner {
    background: linear-gradient(135deg, rgba(245,166,35,.10) 0%, rgba(248,113,113,.07) 100%);
    border: 1.5px solid rgba(245,166,35,.35);
    border-radius: 18px; padding: 20px 20px; margin: 12px 16px;
  }
  .daftar-ulang-banner .du-title {
    font-family: 'Space Grotesk', sans-serif; font-weight: 800; font-size: 1rem;
    color: #fbbf24; display: flex; align-items: center; gap: 8px; margin-bottom: 10px;
  }
  .daftar-ulang-banner .du-body {
    font-size: .82rem; color: #d1d5db; line-height: 1.65; font-weight: 500;
  }
  .daftar-ulang-banner .du-body strong { color: #fbbf24; font-weight: 700; }
  .daftar-ulang-banner .du-syarat {
    margin-top: 12px; padding: 12px 14px;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
    border-radius: 12px;
  }
  .daftar-ulang-banner .du-syarat .sy-title {
    font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .09em;
    color: #fbbf24; margin-bottom: 8px;
  }
  .daftar-ulang-banner .du-syarat ul {
    list-style: none; padding: 0; margin: 0;
  }
  .daftar-ulang-banner .du-syarat ul li {
    font-size: .79rem; color: #d1d5db; font-weight: 500; padding: 3px 0;
    padding-left: 18px; position: relative; line-height: 1.5;
  }
  .daftar-ulang-banner .du-syarat ul li::before {
    content: '›'; position: absolute; left: 0; color: #fbbf24; font-weight: 900;
  }

  /* kartu bukti */
  .kartu-section {
    margin: 12px 16px; padding: 22px 20px;
    background: var(--panel); border: 1px solid var(--border); border-radius: 20px;
  }
  .btn-download-pdf {
    width: 100%; padding: 15px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: #fff; font-family: 'Space Grotesk', sans-serif;
    font-weight: 800; font-size: .95rem; border: none;
    border-radius: 14px; cursor: pointer; margin-top: 14px;
    transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  .btn-download-pdf:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(245,158,11,.35); }

  /* locked overlay */
  .locked-banner {
    background: rgba(34,211,127,.08); border: 1px solid rgba(34,211,127,.25);
    border-radius: 16px; padding: 18px 20px; margin: 12px 16px;
    display: flex; gap: 14px; align-items: center;
  }
  .err-banner {
    background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2);
    border-radius: 14px; padding: 14px 16px; margin: 0 16px 4px;
    font-size: .85rem; color: var(--red); font-weight: 600;
  }
</style>
</head>
<body>

<?php if ($saved): ?>
<div class="toast-saved" id="toastEl">✅ Data berhasil disimpan!</div>
<script>setTimeout(()=>{ const t=document.getElementById('toastEl'); if(t){t.style.opacity='0';t.style.transition='opacity .4s';setTimeout(()=>t.remove(),400);} }, 3000);</script>
<?php endif; ?>

<!-- TOP BAR -->
<div class="top-bar">
  <div>
    <div class="display" style="font-size:.85rem;font-weight:800;color:#fff;line-height:1.1;"><?= htmlspecialchars($d['nama_lengkap']) ?></div>
    <div style="font-size:.68rem;color:#6b7280;font-weight:600;margin-top:2px;">Lengkapi Biodata SPMB 2026</div>
  </div>
  <div style="display:flex;align-items:center;gap:10px;">
    <span class="badge-id"><?= htmlspecialchars($id_reg) ?></span>
    <!-- Progress ring -->
    <div class="ring-wrap">
      <svg width="52" height="52" viewBox="0 0 52 52">
        <circle cx="26" cy="26" r="21" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="4"/>
        <circle cx="26" cy="26" r="21" fill="none"
          stroke="<?= $persen>=100 ? '#22d37f' : '#6c63ff' ?>"
          stroke-width="4" stroke-linecap="round"
          stroke-dasharray="<?= round(2*3.14159*21) ?>"
          stroke-dashoffset="<?= round(2*3.14159*21 * (1 - $persen/100)) ?>"/>
      </svg>
      <div class="ring-pct"><?= $persen ?>%</div>
    </div>
  </div>
</div>

<!-- HERO CARD -->
<div class="hero-card">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div>
      <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#6b7280;margin-bottom:4px;">Jurusan Pilihan</div>
      <div class="display" style="font-size:1.5rem;font-weight:800;color:#fff;"><?= htmlspecialchars($d['jurusan']) ?></div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#6b7280;margin-bottom:4px;">Kelengkapan</div>
      <div class="display" style="font-size:1.5rem;font-weight:800;color:<?= $persen>=100?'#22d37f':'#fff' ?>;"><?= count($terisi) ?><span style="font-size:.9rem;color:#6b7280;">/<?= count($wajib) ?></span></div>
    </div>
  </div>
  <!-- progress bar -->
  <div style="margin-top:14px;background:rgba(255,255,255,.05);border-radius:6px;height:6px;overflow:hidden;">
    <div style="height:100%;width:<?= $persen ?>%;background:<?= $persen>=100?'#22d37f':'linear-gradient(90deg,#6c63ff,#38c6f5)' ?>;border-radius:6px;transition:width .8s ease;"></div>
  </div>
  <div style="font-size:.72rem;font-weight:600;color:#6b7280;margin-top:8px;">
    <?php if ($is_locked): ?>
      ✅ Semua data sudah lengkap. Hubungi panitia jika perlu koreksi.
    <?php else: ?>
      Lengkapi <?= count($wajib)-count($terisi) ?> kolom yang masih kosong.
    <?php endif; ?>
  </div>
</div>

<?php if ($is_locked): ?>
<!-- LOCKED STATE -->
<div class="locked-banner">
  <div style="font-size:2rem;flex-shrink:0;">🔒</div>
  <div>
    <div class="display" style="font-weight:800;color:#fff;font-size:1rem;margin-bottom:4px;">Biodata Sudah Lengkap</div>
    <div style="font-size:.8rem;color:#6b7280;line-height:1.5;">Data kamu sudah tersimpan dan tidak bisa diedit sendiri. Jika ada kesalahan, hubungi panitia SPMB langsung di sekolah.</div>
  </div>
</div>
<?php endif; ?>

<!-- BANNER DAFTAR ULANG (selalu tampil) -->
<div class="daftar-ulang-banner">
  <div class="du-title">
    📢 Pemberitahuan Penting — Daftar Ulang
  </div>
  <div class="du-body">
    <p style="margin:0 0 8px;">Yth. Calon Peserta Didik Baru dan Orang Tua/Wali,</p>
    <p style="margin:0 0 8px;">Formulir ini <strong>hanya merupakan media pelengkap data administrasi awal</strong> dalam proses Seleksi Penerimaan Murid Baru (SPMB) SMK Pasundan 2 Bandung Tahun Pelajaran 2026/2027. Pengisian formulir ini <strong>bukan merupakan bukti kelulusan seleksi</strong> maupun pengganti proses daftar ulang secara resmi.</p>
    <p style="margin:0;"><strong>Seluruh calon peserta didik baru yang dinyatakan diterima wajib melakukan daftar ulang secara langsung ke sekolah</strong> dengan melengkapi persyaratan administrasi yang telah ditentukan.</p>
  </div>
  <div class="du-syarat">
    <div class="sy-title">📋 Persyaratan Daftar Ulang</div>
    <ul>
      <li>Fotokopi Ijazah / Surat Keterangan Lulus (SKL) yang telah dilegalisir (2 lembar)</li>
      <li>Fotokopi Kartu Keluarga (KK) yang masih berlaku (2 lembar)</li>
      <li>Fotokopi Akta Kelahiran (2 lembar)</li>
      <li>Fotokopi KTP Orang Tua / Wali (2 lembar)</li>
      <li>Pas foto terbaru ukuran 3×4 cm berlatar belakang merah (4 lembar)</li>
      <li>Surat keterangan sehat dari dokter / puskesmas</li>
      <li>Membawa berkas asli untuk keperluan verifikasi</li>
    </ul>
  </div>
  <div style="margin-top:12px;font-size:.75rem;color:#9ca3af;font-weight:600;">
    ⏰ Informasi jadwal daftar ulang akan disampaikan melalui WhatsApp yang terdaftar. Pastikan nomor WhatsApp Anda aktif.
  </div>
</div>

<?php if ($is_locked): ?>
<!-- KARTU BUKTI PENGISIAN -->
<div class="kartu-section">
  <div class="sec-title"><span class="sec-icon">🪪</span> Kartu Bukti Pengisian Data</div>
  <div style="font-size:.82rem;color:#9ca3af;line-height:1.6;margin-bottom:6px;">
    Kartu ini merupakan bukti bahwa Anda telah menyelesaikan pengisian data administrasi secara daring. 
    Simpan kartu ini dan tunjukkan kepada panitia saat melakukan daftar ulang di sekolah.
  </div>
  <!-- Preview Kartu -->
  <div id="kartuPreview" style="
    background: linear-gradient(135deg, #0d1b2a 0%, #1a2744 100%);
    border: 1px solid rgba(99,102,241,.35); border-radius: 16px; padding: 20px;
    margin-top: 14px; font-family: 'Plus Jakarta Sans', sans-serif;
    position: relative; overflow: hidden;
  ">
    <!-- decorative -->
    <div style="position:absolute;top:-30px;right:-30px;width:140px;height:140px;background:radial-gradient(circle,rgba(99,102,241,.2),transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-40px;left:-20px;width:100px;height:100px;background:radial-gradient(circle,rgba(56,198,245,.12),transparent 70%);pointer-events:none;"></div>

    <!-- header kartu -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid rgba(255,255,255,.1);">
      <div style="width:40px;height:40px;background:linear-gradient(135deg,#6366f1,#38c6f5);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">🏫</div>
      <div>
        <div style="font-family:'Space Grotesk',sans-serif;font-weight:800;font-size:.85rem;color:#fff;line-height:1.1;">SMK Pasundan 2 Bandung</div>
        <div style="font-size:.65rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.07em;">Bukti Pengisian Data SPMB 2026/2027</div>
      </div>
      <div style="margin-left:auto;text-align:right;">
        <div style="font-size:.6rem;color:#6366f1;font-weight:800;text-transform:uppercase;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);padding:4px 8px;border-radius:6px;">TERVERIFIKASI</div>
      </div>
    </div>

    <!-- nama siswa -->
    <div style="margin-bottom:14px;">
      <div style="font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-bottom:3px;">Nama Calon Peserta Didik</div>
      <div style="font-family:'Space Grotesk',sans-serif;font-weight:800;font-size:1.15rem;color:#fff;"><?= htmlspecialchars($d['nama_lengkap']) ?></div>
    </div>

    <!-- grid info -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
      <div style="background:rgba(255,255,255,.04);border-radius:10px;padding:10px 12px;">
        <div style="font-size:.6rem;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:.08em;margin-bottom:3px;">No. Pendaftaran</div>
        <div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:.82rem;color:#6366f1;"><?= htmlspecialchars($id_reg) ?></div>
      </div>
      <div style="background:rgba(255,255,255,.04);border-radius:10px;padding:10px 12px;">
        <div style="font-size:.6rem;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:.08em;margin-bottom:3px;">Jurusan</div>
        <div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:.82rem;color:#38c6f5;"><?= htmlspecialchars($d['jurusan']) ?></div>
      </div>
      <div style="background:rgba(255,255,255,.04);border-radius:10px;padding:10px 12px;">
        <div style="font-size:.6rem;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:.08em;margin-bottom:3px;">Sekolah Asal</div>
        <div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:.78rem;color:#e2e8f0;"><?= htmlspecialchars($d['sekolah_asal'] ?: $d['asal_sekolah'] ?: '-') ?></div>
      </div>
      <div style="background:rgba(255,255,255,.04);border-radius:10px;padding:10px 12px;">
        <div style="font-size:.6rem;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:.08em;margin-bottom:3px;">Kelengkapan Data</div>
        <div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:.82rem;color:#22d37f;">✅ 100% Lengkap</div>
      </div>
    </div>

    <!-- tanggal pengisian -->
    <div style="background:rgba(34,211,127,.07);border:1px solid rgba(34,211,127,.2);border-radius:10px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;">
      <div>
        <div style="font-size:.62rem;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:.08em;margin-bottom:2px;">Tanggal Pengisian</div>
        <div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:.82rem;color:#22d37f;"><?= date('d F Y, H:i') ?> WIB</div>
      </div>
      <div style="font-size:1.5rem;">✅</div>
    </div>

    <!-- footer disclaimer -->
    <div style="margin-top:12px;font-size:.62rem;color:#6b7280;line-height:1.5;text-align:center;font-weight:500;">
      Dokumen ini hanya sebagai bukti pengisian data daring. Daftar ulang tetap wajib dilakukan secara langsung ke sekolah.
    </div>
  </div>

  <button class="btn-download-pdf" onclick="downloadKartuPDF()">
    📄 Unduh Kartu Bukti (PDF)
  </button>
</div>
<?php endif; ?>

<?php if ($pesan_type==='error'): ?>
<div class="err-banner">⚠️ <?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<!-- INFO BANNER -->
<div class="info-banner">
  <span style="font-size:1rem;flex-shrink:0;">ℹ️</span>
  <span>Isi dengan data <strong>sesuai dokumen resmi</strong> (KTP/Kartu Keluarga). Data ini digunakan untuk keperluan administrasi sekolah.</span>
</div>

<!-- ════════════════════════════════════════ FORM ════ -->
<form method="POST" id="mainForm" <?= $is_locked?'style="pointer-events:none;opacity:.7;"':'' ?>>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

<!-- ── 1. IDENTITAS ── -->
<div class="sec-card">
  <div class="sec-title"><span class="sec-icon">🪪</span> 1. Identitas Calon Siswa</div>

  <div class="field">
    <label>Nama Lengkap <span class="req">*</span></label>
    <input type="text" name="nama_lengkap" value="<?= v($d,'nama_lengkap') ?>" placeholder="Sesuai akta kelahiran" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?> required>
  </div>

  <div class="grid-2">
    <div class="field">
      <label>Jenis Kelamin <span class="req">*</span></label>
      <select name="jenis_kelamin" <?= $is_locked?'disabled':'' ?> required>
        <option value="">— Pilih —</option>
        <option value="LAKI-LAKI" <?= sel($d,'jenis_kelamin','LAKI-LAKI') ?>>Laki-laki</option>
        <option value="PEREMPUAN" <?= sel($d,'jenis_kelamin','PEREMPUAN') ?>>Perempuan</option>
      </select>
    </div>
    <div class="field">
      <label>Agama <span class="req">*</span></label>
      <select name="agama" <?= $is_locked?'disabled':'' ?> required>
        <option value="">— Pilih —</option>
        <?php foreach (['ISLAM','KRISTEN','KATOLIK','HINDU','BUDHA','KONGHUCU'] as $ag): ?>
        <option value="<?= $ag ?>" <?= sel($d,'agama',$ag) ?>><?= ucfirst(strtolower($ag)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Tempat Lahir <span class="req">*</span></label>
      <input type="text" name="tempat_lahir" value="<?= v($d,'tempat_lahir') ?>" placeholder="Kota / Kab" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>Tanggal Lahir <span class="req">*</span></label>
      <input type="date" name="tanggal_lahir" value="<?= v($d,'tanggal_lahir') ?>" style="color-scheme:dark" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>NISN <span class="req">*</span></label>
      <input type="text" name="nisn" value="<?= v($d,'nisn') ?>" placeholder="10 digit" inputmode="numeric" maxlength="10" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>NIK (Sesuai KK) <span class="req">*</span></label>
      <input type="text" name="nik" value="<?= v($d,'nik') ?>" placeholder="16 digit" inputmode="numeric" maxlength="16" <?= $is_locked?'disabled':'' ?>>
    </div>
  </div>

  <div class="field">
    <label>Nomor WhatsApp</label>
    <input type="tel" name="no_hp" value="<?= v($d,'no_hp') ?>" placeholder="08xxxxxxxxxx" inputmode="numeric" <?= $is_locked?'disabled':'' ?>>
  </div>
  <div class="field">
    <label>Sekolah Asal (SMP/MTs) <span class="req">*</span></label>
    <input type="text" name="sekolah_asal" value="<?= v($d,'sekolah_asal') ?: v($d,'asal_sekolah') ?>" placeholder="Nama sekolah asal" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
  </div>
</div>

<!-- ── 2. ALAMAT ── -->
<div class="sec-card">
  <div class="sec-title"><span class="sec-icon">📍</span> 2. Alamat Rumah</div>

  <div class="field">
    <label>Nama Jalan / Blok / Gang <span class="req">*</span></label>
    <input type="text" name="nama_jalan" value="<?= v($d,'nama_jalan') ?: v($d,'alamat') ?>" placeholder="Cth: JL. MERDEKA NO. 10" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
  </div>
  <div class="grid-4">
    <div class="field">
      <label>RT</label>
      <input type="text" name="rt" value="<?= v($d,'rt') ?>" placeholder="001" inputmode="numeric" maxlength="3" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>RW</label>
      <input type="text" name="rw" value="<?= v($d,'rw') ?>" placeholder="001" inputmode="numeric" maxlength="3" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>Kelurahan</label>
      <input type="text" name="kelurahan" value="<?= v($d,'kelurahan') ?>" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>Kecamatan</label>
      <input type="text" name="kecamatan" value="<?= v($d,'kecamatan') ?>" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    </div>
  </div>
  <div class="grid-2">
    <div class="field">
      <label>Kota / Kabupaten <span class="req">*</span></label>
      <input type="text" name="kota" value="<?= v($d,'kota') ?>" placeholder="Kota Bandung" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>Provinsi <span class="req">*</span></label>
      <input type="text" name="provinsi" value="<?= v($d,'provinsi') ?: 'JAWA BARAT' ?>" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    </div>
  </div>
</div>

<!-- ── 3. DATA ORANG TUA ── -->
<div class="sec-card">
  <div class="sec-title"><span class="sec-icon">👨‍👩‍👦</span> 3. Data Orang Tua / Wali</div>

  <!-- AYAH -->
  <div class="ortu-head">
    <div class="emoji">👨</div>
    <div>
      <div class="lbl">Data Ayah / Wali Laki-laki</div>
      <div class="sub">Sesuai KTP / Kartu Keluarga</div>
    </div>
  </div>
  <div class="grid-2">
    <div class="field">
      <label>Nama Ayah <span class="req">*</span></label>
      <input type="text" name="nama_ayah" value="<?= v($d,'nama_ayah') ?>" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>NIK Ayah</label>
      <input type="text" name="nik_ayah" value="<?= v($d,'nik_ayah') ?>" placeholder="16 digit" inputmode="numeric" maxlength="16" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>Tempat Lahir Ayah</label>
      <input type="text" name="tempat_lahir_ayah" value="<?= v($d,'tempat_lahir_ayah') ?>" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>Tanggal Lahir Ayah</label>
      <input type="date" name="tgl_lahir_ayah" value="<?= v($d,'tgl_lahir_ayah') ?>" style="color-scheme:dark" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field" style="grid-column:1/-1">
      <label>Pekerjaan Ayah</label>
      <select name="pekerjaan_ayah" <?= $is_locked?'disabled':'' ?>>
        <option value="">— Pilih —</option>
        <?php foreach (['PNS','TNI/POLRI','WIRASWASTA','KARYAWAN SWASTA','BURUH','PETANI','PEDAGANG','PENSIUNAN','TIDAK BEKERJA','LAINNYA'] as $pj): ?>
        <option value="<?= $pj ?>" <?= sel($d,'pekerjaan_ayah',$pj) ?>><?= $pj ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="divider"></div>

  <!-- IBU -->
  <div class="ortu-head" style="background:rgba(167,139,250,.05);border-color:rgba(167,139,250,.15);">
    <div class="emoji">👩</div>
    <div>
      <div class="lbl">Data Ibu / Wali Perempuan</div>
      <div class="sub">Sesuai KTP / Kartu Keluarga</div>
    </div>
  </div>
  <div class="grid-2">
    <div class="field">
      <label>Nama Ibu <span class="req">*</span></label>
      <input type="text" name="nama_ibu" value="<?= v($d,'nama_ibu') ?>" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>NIK Ibu</label>
      <input type="text" name="nik_ibu" value="<?= v($d,'nik_ibu') ?>" placeholder="16 digit" inputmode="numeric" maxlength="16" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>Tempat Lahir Ibu</label>
      <input type="text" name="tempat_lahir_ibu" value="<?= v($d,'tempat_lahir_ibu') ?>" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field">
      <label>Tanggal Lahir Ibu</label>
      <input type="date" name="tgl_lahir_ibu" value="<?= v($d,'tgl_lahir_ibu') ?>" style="color-scheme:dark" <?= $is_locked?'disabled':'' ?>>
    </div>
    <div class="field" style="grid-column:1/-1">
      <label>Pekerjaan Ibu</label>
      <select name="pekerjaan_ibu" <?= $is_locked?'disabled':'' ?>>
        <option value="">— Pilih —</option>
        <?php foreach (['PNS','TNI/POLRI','WIRASWASTA','KARYAWAN SWASTA','BURUH','PETANI','PEDAGANG','PENSIUNAN','IBU RUMAH TANGGA','TIDAK BEKERJA','LAINNYA'] as $pj): ?>
        <option value="<?= $pj ?>" <?= sel($d,'pekerjaan_ibu',$pj) ?>><?= $pj ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<!-- ── 4. PREFERENSI ── -->
<div class="sec-card" style="margin-bottom:90px;">
  <div class="sec-title"><span class="sec-icon">✨</span> 4. Preferensi (Opsional)</div>
  <div class="field">
    <label>Request Teman Sekelas</label>
    <input type="text" name="request_kelas" value="<?= v($d,'request_kelas') ?>" placeholder="Nama teman yang ingin sekelas (opsional)" style="text-transform:uppercase" <?= $is_locked?'disabled':'' ?>>
    <div style="font-size:.68rem;color:#6b7280;margin-top:6px;font-weight:600;">Tidak ada jaminan dipenuhi, tapi panitia akan mempertimbangkan.</div>
  </div>
</div>

</form><!-- end form -->

<!-- BOTTOM BAR -->
<?php if (!$is_locked): ?>
<div class="bottom-bar">
  <div style="font-size:.7rem;font-weight:700;color:#6b7280;line-height:1.4;flex-shrink:1;">
    Field <span style="color:var(--red);">*</span> wajib diisi
  </div>
  <button type="button" class="btn-submit" id="btnSubmit" onclick="submitForm()">
    💾 Simpan Biodata
  </button>
</div>
<?php else: ?>
<div class="bottom-bar" style="justify-content:center;">
  <div style="font-size:.8rem;font-weight:700;color:#22d37f;display:flex;align-items:center;gap:8px;">
    ✅ Data sudah lengkap — terima kasih!
  </div>
</div>
<?php endif; ?>

<script>
function submitForm() {
  const btn = document.getElementById('btnSubmit');

  // Validasi simpel
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
      el.style.borderColor = 'var(--red)';
      el.style.boxShadow   = '0 0 0 4px rgba(248,113,113,.15)';
      el.scrollIntoView({ behavior:'smooth', block:'center' });
      el.focus();
      setTimeout(() => { el.style.borderColor=''; el.style.boxShadow=''; }, 2500);
      return;
    }
  }

  btn.disabled = true;
  btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:3px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;"></span> Menyimpan...';
  document.getElementById('mainForm').submit();
}
</script>

<?php if ($is_locked): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
async function downloadKartuPDF() {
  const btn = event.currentTarget;
  btn.disabled = true;
  btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:3px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;margin-right:8px;"></span> Menyiapkan PDF...';

  try {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a5' });

    // A5 dimensions: 148 x 210 mm
    const pw = 148, ph = 210;
    const margin = 10;
    const cw = pw - margin * 2;

    // ── background
    pdf.setFillColor(10, 14, 30);
    pdf.rect(0, 0, pw, ph, 'F');

    // ── decorative gradient header bar
    pdf.setFillColor(99, 102, 241);
    pdf.rect(0, 0, pw, 22, 'F');

    // ── school logo placeholder circle
    pdf.setFillColor(255, 255, 255, 0.2);
    pdf.setDrawColor(255,255,255);
    pdf.circle(margin + 7, 11, 7, 'F');
    pdf.setFontSize(10);
    pdf.setTextColor(255,255,255);
    pdf.text('🏫', margin + 4.5, 13.5);

    // ── school name
    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(11);
    pdf.setTextColor(255, 255, 255);
    pdf.text('SMK PASUNDAN 2 BANDUNG', margin + 18, 9);
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(7);
    pdf.setTextColor(200, 210, 255);
    pdf.text('BUKTI PENGISIAN DATA SPMB 2026/2027', margin + 18, 14.5);

    // ── verified badge
    pdf.setFillColor(34, 197, 94);
    pdf.roundedRect(pw - margin - 26, 6, 26, 10, 2, 2, 'F');
    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(7);
    pdf.setTextColor(4, 47, 25);
    pdf.text('✓ TERVERIFIKASI', pw - margin - 24, 12.5);

    // ── nama siswa section
    let y = 32;
    pdf.setFillColor(20, 27, 55);
    pdf.roundedRect(margin, y, cw, 22, 4, 4, 'F');
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(7);
    pdf.setTextColor(148, 163, 184);
    pdf.text('NAMA CALON PESERTA DIDIK', margin + 5, y + 7);
    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(13);
    pdf.setTextColor(255, 255, 255);
    const namaText = <?= json_encode(strtoupper($d['nama_lengkap'])) ?>;
    pdf.text(namaText, margin + 5, y + 17);

    // ── info grid
    y += 27;
    const gridItems = [
      ['NO. PENDAFTARAN', <?= json_encode($id_reg) ?>, [99,102,241]],
      ['JURUSAN', <?= json_encode(strtoupper($d['jurusan'])) ?>, [56,198,245]],
      ['SEKOLAH ASAL', <?= json_encode(strtoupper($d['sekolah_asal'] ?: $d['asal_sekolah'] ?: '-')) ?>, [255,255,255]],
      ['KELENGKAPAN DATA', '100% LENGKAP', [34,211,127]],
    ];

    for (let i = 0; i < gridItems.length; i += 2) {
      const half = cw / 2 - 3;
      for (let j = 0; j < 2 && i+j < gridItems.length; j++) {
        const xOff = margin + j * (half + 6);
        pdf.setFillColor(20, 27, 55);
        pdf.roundedRect(xOff, y, half, 18, 3, 3, 'F');
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(6);
        pdf.setTextColor(148, 163, 184);
        pdf.text(gridItems[i+j][0], xOff + 4, y + 6);
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(8);
        pdf.setTextColor(...gridItems[i+j][2]);
        const val = gridItems[i+j][1];
        const maxW = half - 8;
        const lines = pdf.splitTextToSize(val, maxW);
        pdf.text(lines[0], xOff + 4, y + 13.5);
      }
      y += 22;
    }

    // ── tanggal pengisian
    pdf.setFillColor(12, 40, 25);
    pdf.setDrawColor(34, 197, 94);
    pdf.setLineWidth(0.5);
    pdf.roundedRect(margin, y, cw, 16, 3, 3, 'FD');
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(6.5);
    pdf.setTextColor(148, 163, 184);
    pdf.text('TANGGAL PENGISIAN DATA', margin + 5, y + 6.5);
    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(9);
    pdf.setTextColor(34, 211, 127);
    pdf.text('✅  <?= date('d F Y, H:i') ?> WIB', margin + 5, y + 13);

    // ── separator
    y += 22;
    pdf.setDrawColor(40, 50, 80);
    pdf.setLineWidth(0.3);
    pdf.line(margin, y, pw - margin, y);
    y += 7;

    // ── instruksi daftar ulang
    pdf.setFillColor(30, 20, 5);
    pdf.setDrawColor(245, 158, 11);
    pdf.setLineWidth(0.5);
    pdf.roundedRect(margin, y, cw, 8, 2, 2, 'FD');
    pdf.setFont('helvetica', 'bold');
    pdf.setFontSize(7.5);
    pdf.setTextColor(245, 158, 11);
    pdf.text('⚠️  WAJIB DAFTAR ULANG LANGSUNG KE SEKOLAH', margin + 4, y + 5.5);
    y += 13;

    const instruksiLines = [
      'Kartu ini bukan bukti kelulusan seleksi. Seluruh calon peserta didik baru',
      'yang dinyatakan diterima wajib melakukan daftar ulang secara langsung',
      'ke SMK Pasundan 2 Bandung dengan membawa persyaratan berikut:',
    ];
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(7);
    pdf.setTextColor(200, 210, 220);
    instruksiLines.forEach(line => { pdf.text(line, margin, y); y += 5; });
    y += 2;

    const syarat = [
      'Fotokopi Ijazah / SKL yang dilegalisir (2 lembar)',
      'Fotokopi Kartu Keluarga (2 lembar)',
      'Fotokopi Akta Kelahiran (2 lembar)',
      'Fotokopi KTP Orang Tua / Wali (2 lembar)',
      'Pas foto 3×4 cm berlatar merah (4 lembar)',
      'Surat keterangan sehat dari dokter / puskesmas',
      'Berkas asli untuk verifikasi',
    ];
    pdf.setFontSize(7);
    pdf.setTextColor(200, 210, 220);
    syarat.forEach(item => {
      pdf.setTextColor(245, 158, 11);
      pdf.text('›', margin + 2, y);
      pdf.setTextColor(200, 210, 220);
      pdf.text(item, margin + 7, y);
      y += 5;
    });

    // ── footer
    y = ph - 12;
    pdf.setFontSize(6.5);
    pdf.setTextColor(80, 100, 130);
    pdf.setFont('helvetica', 'normal');
    pdf.text('Informasi jadwal daftar ulang akan dikirimkan melalui WhatsApp terdaftar.', margin, y);
    pdf.text('Dokumen ini digenerate secara otomatis oleh sistem SPMB SMK Pasundan 2 Bandung.', margin, y + 5);

    // ── save
    const filename = 'Kartu_Bukti_SPMB_' + <?= json_encode($id_reg) ?> + '.pdf';
    pdf.save(filename);

  } catch(err) {
    console.error(err);
    alert('Gagal membuat PDF. Silakan coba lagi.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '📄 Unduh Kartu Bukti (PDF)';
  }
}
</script>
<?php endif; ?>
<style>@keyframes spin{to{transform:rotate(360deg);}}</style>
</body>
</html>
