<?php
/**
 * SERTIFIKAT TPA
 * SMK Pasundan 2 Bandung
 * A4 Landscape — HTML preview + mPDF download
 */
ob_start();
include '../../config.php';

$id_siswa = (int)($_GET['id'] ?? 0);
if (!$id_siswa) { header("Location: login.php"); exit(); }

$siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = $id_siswa"));
if (!$siswa) { header("Location: login.php"); exit(); }

// Allow preview mode (admin bypasses tpa_selesai check)
$is_preview = isset($_GET['preview']) || ($_SESSION['tpa_admin_access'] ?? false);
if ($siswa['tpa_selesai'] != 1 && !$is_preview) {
    header("Location: index.php"); exit();
}

// ── Score calculations ───────────────────────────────────────────────────
if ($is_preview) {
    $jawaban = mysqli_query($conn, "
        SELECT js.jawaban_pilih, s.jawaban_benar, s.kategori
        FROM tpa_jawaban js
        JOIN tpa_soal s ON js.id_soal = s.id_soal
        WHERE js.id_siswa = $id_siswa
    ");
    $bv = $bn = $bl = 0;
    $jv = $jn = $jl = 0;
    while ($j = mysqli_fetch_assoc($jawaban)) {
        $match = ($j['jawaban_pilih'] === $j['jawaban_benar']);
        if ($j['kategori'] === 'verbal')  { if ($match) $bv++; $jv++; }
        if ($j['kategori'] === 'numerik') { if ($match) $bn++; $jn++; }
        if ($j['kategori'] === 'logika')  { if ($match) $bl++; $jl++; }
    }
    $siswa['tpa_benar_verbal']  = $bv;
    $siswa['tpa_benar_numerik'] = $bn;
    $siswa['tpa_benar_logika']  = $bl;
    $siswa['tpa_jumlah_soal_verbal']  = $jv ?: 15;
    $siswa['tpa_jumlah_soal_numerik'] = $jn ?: 15;
    $siswa['tpa_jumlah_soal_logika']  = $jl ?: 10;
    $siswa['tpa_tanggal'] = date('Y-m-d H:i:s');
}

$total_soal  = $siswa['tpa_jumlah_soal_verbal'] + $siswa['tpa_jumlah_soal_numerik'] + $siswa['tpa_jumlah_soal_logika'];
$total_benar = $siswa['tpa_benar_verbal'] + $siswa['tpa_benar_numerik'] + $siswa['tpa_benar_logika'];
$nilai       = (int)($siswa['tpa_nilai_total'] ?? 0);
$nilai_verbal  = $siswa['tpa_jumlah_soal_verbal']  > 0 ? round($siswa['tpa_benar_verbal']  / $siswa['tpa_jumlah_soal_verbal']  * 100) : 0;
$nilai_numerik = $siswa['tpa_jumlah_soal_numerik'] > 0 ? round($siswa['tpa_benar_numerik'] / $siswa['tpa_jumlah_soal_numerik'] * 100) : 0;
$nilai_logika  = $siswa['tpa_jumlah_soal_logika']  > 0 ? round($siswa['tpa_benar_logika']  / $siswa['tpa_jumlah_soal_logika']  * 100) : 0;
$akurasi = $total_soal > 0 ? round(($total_benar / $total_soal) * 100) : 0;

// ── Badge system ─────────────────────────────────────────────────────────
if ($nilai >= 90) {
    $badge = ['label' => 'GENIUS AKADEMIK',       'emoji' => '🏆', 'color' => '#D4A017'];
} elseif ($nilai >= 75) {
    $badge = ['label' => 'BINTANG CEMERLANG',     'emoji' => '⭐', 'color' => '#3B82F6'];
} elseif ($nilai >= 60) {
    $badge = ['label' => 'PEJANGGA BERPOTENSIAL', 'emoji' => '🌟', 'color' => '#10B981'];
} else {
    $badge = ['label' => 'PENANTANG TERAMPIL',    'emoji' => '💪', 'color' => '#8B5CF6'];
}

$tanggal_tpa = date('d F Y', strtotime($siswa['tpa_tanggal'] ?? date('Y-m-d')));
$share_code  = strtoupper(substr($siswa['nama_lengkap'], 0, 3)) . '-' . $id_siswa . '-' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sertifikat TPA — <?= htmlspecialchars($siswa['nama_lengkap']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  @page { size: A4 landscape; margin: 0; }

  body.certificate {
    width: 297mm;
    height: 210mm;
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #FFFDF5;
    color: #0a1628;
    overflow: hidden;
    position: relative;
    max-width: 100vw;
    max-height: 100vh;
    object-fit: contain;
  }

  .top-bar {
    height: 7mm;
    background: linear-gradient(90deg, #C0392B 0%, #D4A017 40%, #D4A017 60%, #C0392B 100%);
  }

  .header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8mm;
    padding: 6mm 0 4mm;
    border-bottom: 1.5px solid #D4A017;
    margin: 0 10mm;
  }
  .school-logo {
    width: 20mm;
    height: 20mm;
    flex-shrink: 0;
  }
  .school-info { text-align: center; }
  .school-name {
    font-family: 'Outfit', sans-serif;
    font-weight: 900;
    font-size: 14pt;
    color: #0a1628;
    letter-spacing: 0.05em;
    line-height: 1.2;
  }
  .school-detail {
    font-size: 7pt;
    color: #6B7280;
    margin-top: 1.5mm;
    line-height: 1.5;
  }

  .cert-title-wrap { text-align: center; margin: 6mm 0 2mm; }
  .cert-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 900;
    font-size: 18pt;
    color: #0a1628;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }
  .cert-subtitle {
    font-size: 8pt;
    color: #9CA3AF;
    letter-spacing: 0.15em;
    margin-top: 1.5mm;
  }

  .presented-wrap { text-align: center; margin: 6mm 0 3mm; }
  .presented-label {
    font-size: 7pt;
    color: #9CA3AF;
    letter-spacing: 0.25em;
    text-transform: uppercase;
  }
  .student-name {
    font-family: 'Outfit', sans-serif;
    font-weight: 900;
    font-size: 26pt;
    color: #0a1628;
    margin: 2mm 0;
    text-decoration: underline;
    text-decoration-color: #D4A017;
    text-underline-offset: 3mm;
    letter-spacing: 0.02em;
  }
  .student-meta {
    font-size: 7.5pt;
    color: #6B7280;
  }

  .scores-grid {
    display: flex;
    justify-content: center;
    gap: 5mm;
    margin: 0 14mm;
  }
  .score-card {
    flex: 1;
    max-width: 58mm;
    background: #FFF8E7;
    border: 1px solid #D4A017;
    border-radius: 3mm;
    padding: 3mm 2mm;
    text-align: center;
  }
  .score-label {
    font-size: 6pt;
    color: #9CA3AF;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    margin-bottom: 1mm;
  }
  .score-value {
    font-family: 'Outfit', sans-serif;
    font-weight: 900;
    font-size: 22pt;
    color: #0a1628;
    line-height: 1;
  }
  .score-sub { font-size: 6pt; color: #6B7280; margin-top: 1mm; }

  .score-card.total {
    background: #0a1628;
    border-color: #0a1628;
  }
  .score-card.total .score-label,
  .score-card.total .score-sub { color: #D4A017; }
  .score-card.total .score-value { color: #D4A017; }

  .achievement-wrap { margin: 5mm 14mm 0; }
  .achievement-box {
    display: flex;
    align-items: center;
    gap: 5mm;
    background: linear-gradient(135deg, #FFF8E7 0%, #FFFDF5 100%);
    border: 2px solid #D4A017;
    border-radius: 4mm;
    padding: 3.5mm 6mm;
  }
  .achievement-emoji { font-size: 30pt; line-height: 1; flex-shrink: 0; }
  .achievement-text { flex: 1; }
  .achievement-label-sm {
    font-size: 6pt;
    color: #9CA3AF;
    letter-spacing: 0.2em;
    text-transform: uppercase;
  }
  .achievement-label {
    font-family: 'Outfit', sans-serif;
    font-weight: 900;
    font-size: 11pt;
    margin: 0.5mm 0;
    letter-spacing: 0.05em;
  }
  .achievement-desc { font-size: 6.5pt; color: #6B7280; }

  .footer {
    position: absolute;
    bottom: 6mm;
    left: 10mm;
    right: 10mm;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    border-top: 0.5px solid #E5E7EB;
    padding-top: 3mm;
    font-size: 6.5pt;
    color: #9CA3AF;
  }
  .footer-left { flex: 1; }
  .footer-center { flex: 1; text-align: center; }
  .footer-right { flex: 1; text-align: right; }
  .sig-line {
    border-top: 1px solid #0a1628;
    margin: 0 auto 2mm;
    width: 48mm;
  }
  .sig-label { font-size: 6pt; color: #6B7280; }

  .corner { position: absolute; width: 18mm; height: 18mm; }
  .corner-tl { top: 8mm;  left:  6mm;  border-top:  2px solid #D4A017; border-left:  2px solid #D4A017; }
  .corner-tr { top: 8mm;  right: 6mm;  border-top:  2px solid #D4A017; border-right: 2px solid #D4A017; }
  .corner-bl { bottom: 6mm; left:  6mm;  border-bottom: 2px solid #D4A017; border-left:  2px solid #D4A017; }
  .corner-br { bottom: 6mm; right: 6mm;  border-bottom: 2px solid #D4A017; border-right: 2px solid #D4A017; }

  .watermark {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    font-family: 'Outfit', sans-serif;
    font-weight: 900;
    font-size: 60pt;
    color: rgba(208,160,23,0.05);
    pointer-events: none;
    white-space: nowrap;
    letter-spacing: 0.05em;
    z-index: 0;
  }

  @media screen {
    body.certificate {
      margin: 20px auto;
      border: 1px solid #D4A017;
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }
  }

  .preview-banner {
    display: none;
    background: #7C3AED;
    color: white;
    text-align: center;
    padding: 3px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
  }
  body.preview .preview-banner { display: block; }

  /* Screen preview action bar */
  .action-bar {
    display: none;
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    padding: 8px;
    gap: 8px;
    z-index: 100;
  }
  body.preview .action-bar { display: flex; }
  .action-bar a {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s;
  }
  .btn-pdf {
    background: linear-gradient(135deg, #EF4444, #DC2626);
    color: white;
  }
  .btn-pdf:hover { opacity: 0.9; }
  .btn-back {
    background: #F1F5F9;
    color: #64748B;
  }
  .btn-back:hover { background: #E2E8F0; }
</style>
</head>
<body class="certificate<?= $is_preview ? ' preview' : '' ?>">

<!-- Preview banner -->
<div class="preview-banner">
  <i class="fas fa-eye"></i> PREVIEW MODE — Bukan dokumen resmi
  <a href="admin_hasil.php" style="color:#FDE68A;margin-left:12px;">← Kembali</a>
</div>

<!-- Action bar for screen preview -->
<div class="action-bar">
  <a href="admin_hasil.php" class="btn-back"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
  <a href="sertifikat.php?id=<?= $id_siswa ?>&download=pdf" class="btn-pdf"><i class="fas fa-file-pdf mr-2"></i>Download PDF</a>
</div>

<!-- Gold top bar -->
<div class="top-bar"></div>

<!-- Corner ornaments -->
<div class="corner corner-tl"></div>
<div class="corner corner-tr"></div>
<div class="corner corner-bl"></div>
<div class="corner corner-br"></div>

<!-- Watermark -->
<div class="watermark">SMK PASUNDAN 2 BANDUNG</div>

<!-- Header -->
<div class="header">
  <svg class="school-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <circle cx="50" cy="50" r="47" fill="none" stroke="#C0392B" stroke-width="2.5"/>
    <circle cx="50" cy="50" r="42" fill="none" stroke="#D4A017" stroke-width="1"/>
    <polygon points="50,8 54,20 67,20 57,28 61,40 50,32 39,40 43,28 33,20 46,20" fill="#D4A017" opacity="0.15"/>
    <text x="50" y="35" text-anchor="middle" font-family="Outfit,sans-serif" font-weight="900" font-size="13" fill="#C0392B">SPMB</text>
    <text x="50" y="50" text-anchor="middle" font-family="Outfit,sans-serif" font-weight="800" font-size="8.5" fill="#0a1628">SMK PASUNDAN 2</text>
    <text x="50" y="61" text-anchor="middle" font-family="Outfit,sans-serif" font-weight="600" font-size="7" fill="#6B7280">BANDUNG</text>
    <text x="50" y="74" text-anchor="middle" font-family="Outfit,sans-serif" font-weight="600" font-size="5.5" fill="#9CA3AF">EST. 1948</text>
    <path d="M18 72 Q50 82 82 72" fill="none" stroke="#D4A017" stroke-width="1.5"/>
  </svg>

  <div class="school-info">
    <div class="school-name">SMK PASUNDAN 2 BANDUNG</div>
    <div class="school-detail">
      Jl. Pelita Karya I No.2, Maleber, Kec. Andir, Kota Bandung, Jawa Barat 40184<br>
      Telp. (022) 7310119 &nbsp;|&nbsp; Email: info@smkpasundan2.sch.id
    </div>
  </div>
</div>

<!-- Certificate title -->
<div class="cert-title-wrap">
  <div class="cert-title">Sertifikat Tes Potensi Akademik</div>
  <div class="cert-subtitle">TAHUN PELAJARAN <?= (date('Y') - 1) . ' / ' . date('Y') ?></div>
</div>

<!-- Presented to -->
<div class="presented-wrap">
  <div class="presented-label">Diberikan Kepada</div>
  <div class="student-name"><?= htmlspecialchars($siswa['nama_lengkap']) ?></div>
  <div class="student-meta">
    NISN: <?= htmlspecialchars($siswa['nisn'] ?? '—') ?>
    &nbsp;|&nbsp;
    ID Pendaftaran: <?= htmlspecialchars($siswa['id_pendaftaran']) ?>
    &nbsp;|&nbsp;
    Jurusan: <?= htmlspecialchars($siswa['jurusan']) ?>
  </div>
</div>

<!-- Scores grid -->
<div class="scores-grid">
  <div class="score-card">
    <div class="score-label">Verbal</div>
    <div class="score-value"><?= $nilai_verbal ?>%</div>
    <div class="score-sub"><?= $siswa['tpa_benar_verbal'] ?>/<?= $siswa['tpa_jumlah_soal_verbal'] ?> benar</div>
  </div>
  <div class="score-card">
    <div class="score-label">Numerik</div>
    <div class="score-value"><?= $nilai_numerik ?>%</div>
    <div class="score-sub"><?= $siswa['tpa_benar_numerik'] ?>/<?= $siswa['tpa_jumlah_soal_numerik'] ?> benar</div>
  </div>
  <div class="score-card">
    <div class="score-label">Logika</div>
    <div class="score-value"><?= $nilai_logika ?>%</div>
    <div class="score-sub"><?= $siswa['tpa_benar_logika'] ?>/<?= $siswa['tpa_jumlah_soal_logika'] ?> benar</div>
  </div>
  <div class="score-card total">
    <div class="score-label">Nilai Total</div>
    <div class="score-value"><?= $nilai ?></div>
    <div class="score-sub"><?= $total_benar ?>/<?= $total_soal ?> (<?= $akurasi ?>%)</div>
  </div>
</div>

<!-- Achievement badge -->
<div class="achievement-wrap">
  <div class="achievement-box">
    <div class="achievement-emoji"><?= $badge['emoji'] ?></div>
    <div class="achievement-text">
      <div class="achievement-label-sm">Predikat Achievement</div>
      <div class="achievement-label" style="color:<?= $badge['color'] ?>"><?= $badge['label'] ?></div>
      <div class="achievement-desc">
        Telah menyelesaikan Tes Potensi Akademik SMK Pasundan 2 Bandung<br>
        Tahun Pelajaran <?= (date('Y') - 1) ?>/<?= date('Y') ?> dengan hasil <?= $akurasi ?>% akurasi
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<div class="footer">
  <div class="footer-left">
    <div>Bandung, <?= $tanggal_tpa ?></div>
    <div style="margin-top:1mm">Kode Verifikasi: <?= $share_code ?></div>
  </div>
  <div class="footer-center">
    <div class="sig-line"></div>
    <div class="sig-label">Panitia TPA SMK Pasundan 2</div>
  </div>
  <div class="footer-right">
    <div>ID: <?= $id_siswa ?></div>
    <div style="margin-top:1mm">SPMB <?= date('Y') ?></div>
  </div>
</div>

</body>
</html>
<?php
// ══════════════════════════════════════════════════════════════════════════
// PDF DOWNLOAD MODE
// ══════════════════════════════════════════════════════════════════════════
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {

    $html = ob_get_clean();

    if (class_exists(\Mpdf\Mpdf::class)) {
        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'           => 'utf-8',
                'format'         => 'A4-L',
                'margin_top'     => 0,
                'margin_right'   => 0,
                'margin_bottom'  => 0,
                'margin_left'    => 0,
                'default_font'   => 'sans-serif',
            ]);

            $mpdf->SetTitle("Sertifikat TPA - {$siswa['nama_lengkap']}");
            $mpdf->SetAuthor("SMK Pasundan 2 Bandung");
            $mpdf->SetSubject("Tes Potensi Akademik <?= (date('Y') - 1) ?>/<?= date('Y') ?>");
            $mpdf->SetCreator("SPMB SMK Pasundan 2 System");

            $mpdf->WriteHTML($html);

            $safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $siswa['nama_lengkap']);
            $filename  = "Sertifikat-TPA-{$safe_name}-{$siswa['id_pendaftaran']}.pdf";

            $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
            exit;

        } catch (\Mpdf\MpdfException $e) {
            error_log("mPDF Error: " . $e->getMessage());
        }
    } else {
        echo '<div style="padding:40px;text-align:center;font-family:sans-serif;">';
        echo '<h2>mPDF belum terinstal</h2>';
        echo '<p>Jalankan: <code>composer require mpdf/mpdf</code></p>';
        echo '<p><a href="?id=' . $id_siswa . '">← Kembali ke Preview</a></p>';
        echo '</div>';
        exit;
    }
}
?>