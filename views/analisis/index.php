<?php
/**
 * DASHBOARD ANALISIS SPMB - SMK PASUNDAN 2
 * Insight: sebaran sekolah, kecamatan, jurusan, tren bulanan, perbandingan tahunan
 */
session_start();
include '../../config.php';

$allowed_analisis = ['database','superuser'];
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed_analisis)) {
    header("Location: ../../panitia/index.php"); exit();
}

// Tahun yang tersedia (dari arsip + aktif)
$tahun_arsip = [];
$res_th = mysqli_query($conn, "SELECT DISTINCT tahun_spmb FROM spmb_arsip_tahunan ORDER BY tahun_spmb DESC");
while ($r = mysqli_fetch_assoc($res_th)) { $tahun_arsip[] = $r['tahun_spmb']; }

$tahun_aktif = date('Y');
$tahun_dipilih = isset($_GET['tahun']) ? intval($_GET['tahun']) : $tahun_aktif;

// Sumber data: aktif atau arsip?
$dari_arsip = in_array($tahun_dipilih, $tahun_arsip);
$tbl = $dari_arsip ? "spmb_arsip_tahunan" : "siswa";
$where_tahun = $dari_arsip ? "WHERE tahun_spmb='$tahun_dipilih'" : "WHERE 1=1";
$and_tahun   = $dari_arsip ? "AND tahun_spmb='$tahun_dipilih'" : "";

// ===== STATISTIK UTAMA =====
$total       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $tbl $where_tahun"))['n'];
$total_du    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $tbl $where_tahun AND status_siswa='SUDAH DAFTAR ULANG'"))['n'] ?? 0;
$total_laki  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $tbl $where_tahun AND jenis_kelamin='LAKI-LAKI'"))['n'] ?? 0;
$total_pr    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $tbl $where_tahun AND jenis_kelamin='PEREMPUAN'"))['n'] ?? 0;
$pct_du      = $total > 0 ? round($total_du / $total * 100) : 0;

// ===== PER JURUSAN =====
$jur_data = [];
$res_jur = mysqli_query($conn, "SELECT jurusan, COUNT(*) n,
    SUM(CASE WHEN jenis_kelamin='LAKI-LAKI' THEN 1 ELSE 0 END) as L,
    SUM(CASE WHEN jenis_kelamin='PEREMPUAN' THEN 1 ELSE 0 END) as P,
    ROUND(AVG(nilai_btq),1) as avg_btq
    FROM $tbl $where_tahun AND jurusan IS NOT NULL AND jurusan!=''
    GROUP BY jurusan ORDER BY n DESC");
while ($r = mysqli_fetch_assoc($res_jur)) { $jur_data[] = $r; }

// ===== TOP SEKOLAH ASAL =====
$sekolah_data = [];
$res_sk = mysqli_query($conn, "SELECT asal_sekolah, COUNT(*) n
    FROM $tbl $where_tahun AND asal_sekolah IS NOT NULL AND asal_sekolah!=''
    GROUP BY asal_sekolah ORDER BY n DESC LIMIT 15");
while ($r = mysqli_fetch_assoc($res_sk)) { $sekolah_data[] = $r; }

// ===== TOP KECAMATAN =====
$kec_data = [];
$res_kec = mysqli_query($conn, "SELECT kecamatan, COUNT(*) n
    FROM $tbl $where_tahun AND kecamatan IS NOT NULL AND kecamatan!=''
    GROUP BY kecamatan ORDER BY n DESC LIMIT 15");
while ($r = mysqli_fetch_assoc($res_kec)) { $kec_data[] = $r; }

// ===== TOP KOTA/KAB =====
$kota_data = [];
$res_kota = mysqli_query($conn, "SELECT kota, COUNT(*) n
    FROM $tbl $where_tahun AND kota IS NOT NULL AND kota!=''
    GROUP BY kota ORDER BY n DESC LIMIT 10");
while ($r = mysqli_fetch_assoc($res_kota)) { $kota_data[] = $r; }

// ===== TREN BULANAN =====
$bulan_data = [];
$res_bln = mysqli_query($conn, "SELECT
    MONTH(tgl_daftar) as bln,
    DATE_FORMAT(tgl_daftar,'%b') as nama,
    COUNT(*) n
    FROM $tbl $where_tahun AND tgl_daftar IS NOT NULL
    GROUP BY MONTH(tgl_daftar) ORDER BY MONTH(tgl_daftar)");
while ($r = mysqli_fetch_assoc($res_bln)) { $bulan_data[] = $r; }

// ===== PERBANDINGAN ANTAR TAHUN =====
$compare_data = [];
// Semua tahun dari arsip
foreach ($tahun_arsip as $th) {
    $row = ['tahun' => $th, 'jurusans' => []];
    $row['total'] = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM spmb_arsip_tahunan WHERE tahun_spmb='$th'"))['n'];
    $row['du']    = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM spmb_arsip_tahunan WHERE tahun_spmb='$th' AND status_siswa='SUDAH DAFTAR ULANG'"))['n'] ?? 0;
    $res_jc = mysqli_query($conn, "SELECT jurusan, COUNT(*) n FROM spmb_arsip_tahunan
        WHERE tahun_spmb='$th' GROUP BY jurusan");
    while ($rj = mysqli_fetch_assoc($res_jc)) { $row['jurusans'][$rj['jurusan']] = $rj['n']; }
    $compare_data[] = $row;
}
// Tambah tahun aktif
$row_aktif = ['tahun' => $tahun_aktif . ' (Aktif)', 'total' => $total, 'du' => $total_du, 'jurusans' => []];
foreach ($jur_data as $jd) { $row_aktif['jurusans'][$jd['jurusan']] = $jd['n']; }
array_unshift($compare_data, $row_aktif);

// ===== DISTRIBUSI HARI PENDAFTARAN =====
$hari_data = [];
$res_hari = mysqli_query($conn, "SELECT DAYNAME(tgl_daftar) as hari, COUNT(*) n
    FROM $tbl $where_tahun AND tgl_daftar IS NOT NULL
    GROUP BY DAYNAME(tgl_daftar), DAYOFWEEK(tgl_daftar)
    ORDER BY DAYOFWEEK(tgl_daftar)");
while ($r = mysqli_fetch_assoc($res_hari)) { $hari_data[] = $r; }

// Log tutup SPMB
$log_tutup = [];
$res_log = mysqli_query($conn, "SELECT * FROM spmb_log_tutup ORDER BY tgl_tutup DESC LIMIT 5");
if ($res_log) while ($r = mysqli_fetch_assoc($res_log)) { $log_tutup[] = $r; }

// Jurusan lengkap list
$semua_jur = ['TPM','TKR','TSM','TKJ','TAV'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ANALISIS SPMB | SMK PASUNDAN 2</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
:root {
    --bg:#020617; --card:rgba(15,23,42,0.85); --card2:rgba(15,23,42,0.5);
    --accent:#38bdf8; --green:#10b981; --red:#ef4444; --yellow:#f59e0b;
    --purple:#a78bfa; --orange:#f97316; --pink:#ec4899;
    --border:rgba(255,255,255,0.07); --muted:#64748b; --text:#cbd5e1;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;}
body{background:var(--bg);color:var(--text);min-height:100vh;
    background-image:radial-gradient(at 0% 0%,rgba(30,58,138,0.2) 0,transparent 50%),
                     radial-gradient(at 100% 100%,rgba(56,189,248,0.05) 0,transparent 50%);}

/* TOPBAR */
.topbar{display:flex;align-items:center;justify-content:space-between;padding:16px 36px;
    background:rgba(2,6,23,0.95);border-bottom:1px solid var(--border);
    position:sticky;top:0;z-index:100;backdrop-filter:blur(20px);}
.topbar-logo{font-size:1rem;font-weight:800;color:#fff;display:flex;align-items:center;gap:12px;}
.topbar-logo span{color:var(--accent);}
.topbar-logo .back-link{color:var(--muted);transition:0.2s;}
.topbar-logo .back-link:hover{color:var(--accent);}
.topbar-right{display:flex;gap:10px;align-items:center;}
.btn-sm{padding:8px 16px;border-radius:10px;font-size:0.72rem;font-weight:800;
    text-decoration:none;cursor:pointer;border:none;transition:0.2s;display:inline-flex;align-items:center;gap:7px;}
.btn-back{background:rgba(255,255,255,0.05);color:var(--muted);border:1px solid var(--border);}
.btn-back:hover{color:#fff;}
.btn-danger{background:rgba(239,68,68,0.1);color:var(--red);border:1px solid rgba(239,68,68,0.2);}
.btn-danger:hover{background:rgba(239,68,68,0.2);}

/* BREADCRUMB */
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:0.85rem;}
.breadcrumb a{color:var(--muted);text-decoration:none;transition:0.2s;display:flex;align-items:center;gap:4px;}
.breadcrumb a:hover{color:var(--accent);}
.breadcrumb span{color:var(--muted);}
.breadcrumb .current{color:#fff;font-weight:600;}

/* MAIN */
.main{padding:28px 36px;max-width:1400px;margin:0 auto;}

/* FILTER BAR */
.filter-bar{display:flex;align-items:center;gap:12px;margin-bottom:28px;flex-wrap:wrap;}
.filter-label{font-size:0.72rem;font-weight:800;color:var(--muted);text-transform:uppercase;}
.year-tab{padding:9px 20px;border-radius:10px;font-size:0.75rem;font-weight:800;
    text-decoration:none;color:var(--muted);background:var(--card2);
    border:1px solid var(--border);transition:0.2s;}
.year-tab:hover{color:#fff;}
.year-tab.active{background:var(--accent);color:#020617;border-color:var(--accent);}
.year-tab.arsip{border-color:rgba(167,139,250,0.3);color:var(--purple);}
.year-tab.arsip.active{background:var(--purple);color:#020617;}
.data-source{font-size:0.68rem;font-weight:700;padding:5px 12px;border-radius:8px;}
.ds-aktif{background:rgba(16,185,129,0.1);color:var(--green);border:1px solid rgba(16,185,129,0.2);}
.ds-arsip{background:rgba(167,139,250,0.1);color:var(--purple);border:1px solid rgba(167,139,250,0.2);}

/* KPI CARDS */
.kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:24px;}
.kpi{background:var(--card);border-radius:18px;padding:20px;border:1px solid var(--border);}
.kpi-label{font-size:0.6rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;}
.kpi-val{font-size:1.9rem;font-weight:800;color:#fff;line-height:1;}
.kpi-sub{font-size:0.65rem;color:var(--muted);margin-top:4px;}

/* SECTION TITLE */
.sec-hd{font-size:0.78rem;font-weight:800;color:var(--accent);text-transform:uppercase;
    letter-spacing:2px;margin-bottom:16px;display:flex;align-items:center;gap:8px;}

/* GRID LAYOUTS */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px;}
.grid-1{margin-bottom:20px;}

/* GLASS CARD */
.gc{background:var(--card);border-radius:20px;border:1px solid var(--border);padding:24px 28px;}

/* CHART CONTAINER */
.chart-wrap{position:relative;}
.chart-wrap canvas{max-height:260px;}
.chart-tall canvas{max-height:340px;}
.chart-short canvas{max-height:180px;}

/* HORIZONTAL BAR */
.hbar-list{display:flex;flex-direction:column;gap:10px;margin-top:4px;}
.hbar-item{}
.hbar-info{display:flex;justify-content:space-between;font-size:0.75rem;font-weight:700;margin-bottom:5px;}
.hbar-info .name{color:var(--text);max-width:70%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.hbar-info .count{color:#fff;font-weight:800;flex-shrink:0;}
.hbar-bg{background:rgba(255,255,255,0.05);height:7px;border-radius:6px;}
.hbar-fill{height:100%;border-radius:6px;transition:0.5s;}

/* JURUSAN CARDS */
.jur-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;}
.jur-card{background:rgba(0,0,0,0.2);border-radius:16px;padding:16px 18px;border:1px solid var(--border);}
.jur-name{font-size:0.8rem;font-weight:800;color:#fff;margin-bottom:8px;}
.jur-num{font-size:1.6rem;font-weight:800;line-height:1;margin-bottom:6px;}
.jur-chips{display:flex;gap:6px;font-size:0.62rem;font-weight:800;flex-wrap:wrap;}
.jc{padding:3px 8px;border-radius:6px;}
.jc-l{background:rgba(56,189,248,0.12);color:var(--accent);}
.jc-p{background:rgba(167,139,250,0.12);color:var(--purple);}
.jc-btq{background:rgba(245,158,11,0.12);color:var(--yellow);}

/* COMPARE TABLE */
.cmp-table{width:100%;border-collapse:collapse;}
.cmp-table th{text-align:center;font-size:0.65rem;color:var(--accent);text-transform:uppercase;
    padding:12px 10px;border-bottom:2px solid var(--border);font-weight:800;}
.cmp-table td{text-align:center;font-size:0.82rem;font-weight:700;padding:12px 10px;
    border-bottom:1px solid var(--border);}
.cmp-table tr:last-child td{border-bottom:none;}
.cmp-table tr:hover td{background:rgba(255,255,255,0.02);}
.cmp-table td:first-child{text-align:left;color:#fff;font-weight:800;}
.td-up{color:var(--green);}
.td-dn{color:var(--red);}

/* INSIGHT BOX */
.insight-list{display:flex;flex-direction:column;gap:10px;}
.insight-item{padding:14px 18px;border-radius:14px;display:flex;align-items:flex-start;gap:12px;
    font-size:0.8rem;font-weight:600;}
.ins-promo{background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.15);color:#a7f3d0;}
.ins-warn {background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.15);color:#fde68a;}
.ins-info {background:rgba(56,189,248,0.08);border:1px solid rgba(56,189,248,0.15);color:#bae6fd;}
.ins-icon{font-size:1.1rem;margin-top:1px;flex-shrink:0;}

/* LOG TUTUP */
.log-item{padding:12px 16px;background:rgba(0,0,0,0.2);border-radius:12px;
    border-left:3px solid var(--purple);margin-bottom:8px;font-size:0.78rem;}
.log-tahun{font-weight:800;color:#fff;margin-bottom:3px;}
.log-meta{color:var(--muted);font-size:0.68rem;}

/* TUTUP SPMB SECTION */
.tutup-card{background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.15);
    border-radius:20px;padding:24px 28px;margin-bottom:20px;}
.tutup-title{font-size:0.85rem;font-weight:800;color:var(--red);margin-bottom:6px;}
.tutup-desc{font-size:0.78rem;color:var(--muted);margin-bottom:16px;line-height:1.6;}

/* SCROLLABLE */
.scroll-y{max-height:320px;overflow-y:auto;padding-right:4px;}
.scroll-y::-webkit-scrollbar{width:3px;}
.scroll-y::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px;}

/* ==================== MOBILE RESPONSIVE ==================== */
@media (max-width: 1024px) {
    .kpi-grid {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}

@media (max-width: 768px) {
    /* Topbar */
    .topbar {
        padding: 12px 16px !important;
        flex-wrap: wrap;
        gap: 12px;
    }
    .topbar-logo {
        font-size: 0.85rem !important;
    }
    .topbar-right {
        flex-wrap: wrap;
        width: 100%;
    }
    .btn-sm {
        padding: 8px 12px !important;
        font-size: 0.65rem !important;
    }

    /* Main */
    .main {
        padding: 16px !important;
    }

    /* KPI Grid */
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    .kpi {
        padding: 14px !important;
    }
    .kpi-val {
        font-size: 1.4rem !important;
    }
    .kpi-label {
        font-size: 0.55rem !important;
    }

    /* Grid 2 columns */
    .grid-2 {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }

    /* Grid 3 columns */
    .grid-3 {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }

    /* Glass Card */
    .gc {
        padding: 16px !important;
    }

    /* Charts */
    .chart-wrap canvas {
        max-height: 200px !important;
    }
    .chart-tall canvas {
        max-height: 250px !important;
    }

    /* Compare Table */
    .cmp-table {
        font-size: 0.7rem !important;
    }
    .cmp-table th,
    .cmp-table td {
        padding: 8px 4px !important;
    }

    /* Scrollable */
    .scroll-y {
        max-height: 250px;
    }

    /* Filter bar */
    .filter-bar {
        gap: 8px !important;
    }
    .year-tab {
        padding: 7px 12px !important;
        font-size: 0.65rem !important;
    }
}

@media (max-width: 480px) {
    /* KPI Grid - 2 columns */
    .kpi-grid {
        grid-template-columns: 1fr 1fr !important;
    }

    /* Topbar buttons */
    .btn-sm {
        padding: 6px 10px !important;
        font-size: 0.6rem !important;
    }
    .btn-sm span {
        display: none;
    }

    /* Cards */
    .kpi {
        padding: 12px !important;
    }
    .kpi-val {
        font-size: 1.2rem !important;
    }

    /* Horizontal bar names */
    .hbar-info .name {
        max-width: 60% !important;
        font-size: 0.7rem !important;
    }
    .hbar-info .count {
        font-size: 0.65rem !important;
    }

    /* Jur Grid */
    .jur-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-logo">
        <a href="../database/index.php" class="back-link"><i class="fas fa-arrow-left"></i></a>
        SMK <span>PASUNDAN 2</span> — ANALISIS SPMB
    </div>
    <div class="topbar-right">
        <a href="export_analisis.php?tahun=<?= $tahun_dipilih ?>" class="btn-sm"
           style="background:rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2);">
            <i class="fas fa-file-excel"></i> EXPORT EXCEL
        </a>
        <?php if (in_array(strtolower($_SESSION['role']), ['superuser','database'])): ?>
        <button class="btn-sm btn-danger" onclick="modalTutupSPMB()">
            <i class="fas fa-archive"></i> TUTUP & ARSIPKAN SPMB
        </button>
        <?php endif; ?>
        <a href="../database/index.php" class="btn-sm btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<!-- BREADCRUMB -->
<div class="container mx-auto px-6 py-4">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../../"><i class="fas fa-home"></i></a>
        <span>/</span>
        <a href="../database/index.php">Database</a>
        <span>/</span>
        <span class="current">Analisis</span>
    </nav>
</div>

<div class="main">

<!-- FILTER TAHUN -->
<div class="filter-bar">
    <span class="filter-label"><i class="fas fa-calendar"></i> Tahun:</span>
    <a href="?tahun=<?= $tahun_aktif ?>" class="year-tab <?= $tahun_dipilih==$tahun_aktif?'active':'' ?>">
        <?= $tahun_aktif ?> (Aktif)
    </a>
    <?php foreach (array_reverse($tahun_arsip) as $th): ?>
    <a href="?tahun=<?= $th ?>" class="year-tab arsip <?= $tahun_dipilih==$th?'active':'' ?>"><?= $th ?></a>
    <?php endforeach; ?>
    <span class="data-source <?= $dari_arsip?'ds-arsip':'ds-aktif' ?>">
        <i class="fas fa-<?= $dari_arsip?'archive':'database' ?>"></i>
        <?= $dari_arsip?'Data Arsip':'Data Aktif' ?>
    </span>
</div>

<!-- KPI -->
<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-label">Total Pendaftar</div>
        <div class="kpi-val"><?= $total ?></div>
        <div class="kpi-sub">SPMB <?= $tahun_dipilih ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Daftar Ulang</div>
        <div class="kpi-val" style="color:var(--green)"><?= $total_du ?></div>
        <div class="kpi-sub"><?= $pct_du ?>% dari total</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Belum DU</div>
        <div class="kpi-val" style="color:var(--yellow)"><?= $total - $total_du ?></div>
        <div class="kpi-sub"><?= 100-$pct_du ?>% dari total</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Laki-Laki</div>
        <div class="kpi-val" style="color:var(--accent)"><?= $total_laki ?></div>
        <div class="kpi-sub"><?= $total>0?round($total_laki/$total*100):0 ?>%</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Perempuan</div>
        <div class="kpi-val" style="color:var(--purple)"><?= $total_pr ?></div>
        <div class="kpi-sub"><?= $total>0?round($total_pr/$total*100):0 ?>%</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Jumlah Kelas</div>
        <div class="kpi-val" style="color:var(--orange)">
            <?php
            if ($dari_arsip) {
                $kls = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT COUNT(DISTINCT kelas) n FROM spmb_arsip_tahunan WHERE tahun_spmb='$tahun_dipilih' AND kelas IS NOT NULL AND kelas!=''"));
            } else {
                $kls = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT COUNT(DISTINCT kelas) n FROM siswa WHERE kelas IS NOT NULL AND kelas!=''"));
            }
            echo $kls['n'] ?? 0;
            ?>
        </div>
        <div class="kpi-sub">kelas terbentuk</div>
    </div>
</div>

<!-- ROW 1: JURUSAN + TREN BULANAN -->
<div class="grid-2">

    <!-- JURUSAN -->
    <div class="gc">
        <div class="sec-hd"><i class="fas fa-layer-group"></i> Sebaran per Jurusan</div>
        <div class="chart-wrap chart-short" style="margin-bottom:18px;">
            <canvas id="chartJurusan"></canvas>
        </div>
        <div class="jur-grid">
            <?php
            $jur_colors = ['TPM'=>'#10b981','TKR'=>'#f97316','TSM'=>'#ef4444','TKJ'=>'#4f46e5','TAV'=>'#8b5cf6'];
            foreach ($jur_data as $j):
                $col = $jur_colors[$j['jurusan']] ?? '#64748b';
                $pct_jur = $total > 0 ? round($j['n']/$total*100) : 0;
            ?>
            <div class="jur-card" style="border-color:<?= $col ?>22;">
                <div class="jur-name" style="color:<?= $col ?>"><?= $j['jurusan'] ?></div>
                <div class="jur-num" style="color:<?= $col ?>"><?= $j['n'] ?> <small style="font-size:0.9rem;color:var(--muted)">(<?= $pct_jur ?>%)</small></div>
                <div class="jur-chips">
                    <span class="jc jc-l">L <?= $j['L'] ?></span>
                    <span class="jc jc-p">P <?= $j['P'] ?></span>
                    <span class="jc jc-btq">BTQ ∅<?= $j['avg_btq'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TREN BULANAN -->
    <div class="gc">
        <div class="sec-hd"><i class="fas fa-chart-line"></i> Tren Pendaftaran Bulanan</div>
        <div class="chart-wrap chart-tall">
            <canvas id="chartBulan"></canvas>
        </div>
        <?php if (count($bulan_data) > 0): ?>
        <div style="margin-top:14px;font-size:0.72rem;color:var(--muted);">
            <?php
            $bln_peak = array_reduce($bulan_data, fn($carry,$item) => (!$carry||$item['n']>$carry['n']?$item:$carry), null);
            ?>
            <i class="fas fa-info-circle" style="color:var(--accent)"></i>
            Puncak pendaftaran: <b style="color:#fff"><?= $bln_peak['nama'] ?? '-' ?></b>
            (<?= $bln_peak['n'] ?? 0 ?> pendaftar)
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ROW 2: SEKOLAH ASAL + KECAMATAN -->
<div class="grid-2">

    <!-- TOP SEKOLAH -->
    <div class="gc">
        <div class="sec-hd"><i class="fas fa-school"></i> Top Sekolah Asal (Potensi Target Promosi)</div>
        <?php if (count($sekolah_data) > 0): $max_sk = $sekolah_data[0]['n']; ?>
        <div class="hbar-list scroll-y">
            <?php foreach ($sekolah_data as $i => $sk):
                $pct_sk = $max_sk > 0 ? round($sk['n']/$max_sk*100) : 0;
                $col_sk = $i < 3 ? '#38bdf8' : ($i < 7 ? '#a78bfa' : '#475569');
            ?>
            <div class="hbar-item">
                <div class="hbar-info">
                    <span class="name"><?= ($i+1) ?>. <?= $sk['asal_sekolah'] ?></span>
                    <span class="count"><?= $sk['n'] ?> siswa</span>
                </div>
                <div class="hbar-bg">
                    <div class="hbar-fill" style="width:<?= $pct_sk ?>%;background:<?= $col_sk ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:var(--muted);text-align:center;padding:20px;">Tidak ada data</p>
        <?php endif; ?>
    </div>

    <!-- TOP KECAMATAN -->
    <div class="gc">
        <div class="sec-hd"><i class="fas fa-map-marker-alt"></i> Sebaran Kecamatan (Pemetaan Wilayah)</div>
        <?php if (count($kec_data) > 0): $max_kec = $kec_data[0]['n']; ?>
        <div class="hbar-list scroll-y">
            <?php foreach ($kec_data as $i => $kc):
                $pct_kc = $max_kec > 0 ? round($kc['n']/$max_kec*100) : 0;
                $col_kc = $i < 3 ? '#10b981' : ($i < 7 ? '#f59e0b' : '#475569');
            ?>
            <div class="hbar-item">
                <div class="hbar-info">
                    <span class="name"><?= ($i+1) ?>. <?= $kc['kecamatan'] ?></span>
                    <span class="count"><?= $kc['n'] ?> siswa</span>
                </div>
                <div class="hbar-bg">
                    <div class="hbar-fill" style="width:<?= $pct_kc ?>%;background:<?= $col_kc ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:var(--muted);text-align:center;padding:20px;">Tidak ada data kecamatan</p>
        <?php endif; ?>
    </div>

</div>

<!-- ROW 3: KOTA + HARI DAFTAR -->
<div class="grid-2">
    <!-- TOP KOTA -->
    <div class="gc">
        <div class="sec-hd"><i class="fas fa-city"></i> Sebaran Kota / Kabupaten</div>
        <div class="chart-wrap">
            <canvas id="chartKota"></canvas>
        </div>
    </div>

    <!-- HARI PENDAFTARAN -->
    <div class="gc">
        <div class="sec-hd"><i class="fas fa-calendar-week"></i> Distribusi Hari Pendaftaran</div>
        <div class="chart-wrap">
            <canvas id="chartHari"></canvas>
        </div>
        <p style="font-size:0.7rem;color:var(--muted);margin-top:10px;">
            <i class="fas fa-lightbulb" style="color:var(--yellow)"></i>
            Gunakan data ini untuk jadwal piket & buka stand promosi.
        </p>
    </div>
</div>

<!-- ROW 4: PERBANDINGAN ANTAR TAHUN -->
<?php if (count($compare_data) > 1): ?>
<div class="grid-1">
    <div class="gc">
        <div class="sec-hd"><i class="fas fa-chart-bar"></i> Perbandingan SPMB Antar Tahun</div>
        <div class="grid-2" style="margin-bottom:16px;">
            <div class="chart-wrap">
                <canvas id="chartCompare"></canvas>
            </div>
            <div>
                <table class="cmp-table">
                    <thead>
                        <tr>
                            <th style="text-align:left">Tahun</th>
                            <th>Total</th>
                            <?php foreach ($semua_jur as $j): ?><th><?= $j ?></th><?php endforeach; ?>
                            <th>DU</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compare_data as $ci => $cRow): ?>
                        <tr>
                            <td><?= $cRow['tahun'] ?></td>
                            <td>
                                <?= $cRow['total'] ?>
                                <?php if ($ci > 0 && $compare_data[$ci-1]['total'] > 0):
                                    $diff = $cRow['total'] - $compare_data[$ci-1]['total'];
                                    $cls = $diff >= 0 ? 'td-up' : 'td-dn';
                                    $sign = $diff >= 0 ? '▲' : '▼';
                                    echo "<small class='$cls'> $sign " . abs($diff) . "</small>";
                                endif; ?>
                            </td>
                            <?php foreach ($semua_jur as $j): ?>
                            <td><?= $cRow['jurusans'][$j] ?? '-' ?></td>
                            <?php endforeach; ?>
                            <td><?= $cRow['du'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ROW 5: INSIGHT OTOMATIS + LOG ARSIP -->
<div class="grid-2">

    <!-- INSIGHT -->
    <div class="gc">
        <div class="sec-hd"><i class="fas fa-lightbulb"></i> Insight Otomatis & Rekomendasi</div>
        <div class="insight-list">
            <?php
            // Insight 1: Sekolah asal teratas
            if (!empty($sekolah_data)) {
                $top3_sk = array_slice($sekolah_data, 0, 3);
                $nama_top = implode(', ', array_column($top3_sk, 'asal_sekolah'));
                echo "<div class='insight-item ins-promo'><span class='ins-icon'>🎯</span>
                <span><b>Target Promosi:</b> 3 sekolah pengirim terbanyak adalah <b style='color:#fff'>{$nama_top}</b>.
                Prioritaskan kunjungan/brosur ke sekolah ini untuk SPMB berikutnya.</span></div>";
            }
            // Insight 2: Kecamatan
            if (!empty($kec_data)) {
                $top_kec = $kec_data[0];
                echo "<div class='insight-item ins-info'><span class='ins-icon'>📍</span>
                <span><b>Wilayah Dominan:</b> Kecamatan <b style='color:#fff'>{$top_kec['kecamatan']}</b>
                menyumbang <b style='color:#fff'>{$top_kec['n']} siswa</b>.
                Pertimbangkan pasang spanduk/iklan di area ini.</span></div>";
            }
            // Insight 3: Jurusan diminati vs kurang
            if (count($jur_data) >= 2) {
                $top_jur  = $jur_data[0];
                $bot_jur  = $jur_data[count($jur_data)-1];
                echo "<div class='insight-item ins-info'><span class='ins-icon'>📊</span>
                <span><b>Jurusan:</b> <b style='color:#fff'>{$top_jur['jurusan']}</b> paling diminati
                ({$top_jur['n']} siswa). <b style='color:#fff'>{$bot_jur['jurusan']}</b> perlu promosi lebih
                khusus ({$bot_jur['n']} siswa).</span></div>";
            }
            // Insight 4: Konversi DU
            if ($total > 0) {
                if ($pct_du < 70) {
                    echo "<div class='insight-item ins-warn'><span class='ins-icon'>⚠️</span>
                    <span><b>Konversi DU rendah ({$pct_du}%):</b>
                    " . ($total-$total_du) . " siswa belum daftar ulang. Pertimbangkan WA blast reminder
                    atau cek status pembayaran.</span></div>";
                } else {
                    echo "<div class='insight-item ins-promo'><span class='ins-icon'>✅</span>
                    <span><b>Konversi DU baik ({$pct_du}%):</b>
                    {$total_du} dari {$total} siswa sudah daftar ulang.</span></div>";
                }
            }
            // Insight 5: Hari paling ramai
            if (!empty($hari_data)) {
                $peak_hari = array_reduce($hari_data, fn($c,$i) => (!$c||$i['n']>$c['n']?$i:$c), null);
                echo "<div class='insight-item ins-info'><span class='ins-icon'>📅</span>
                <span><b>Hari Tersibuk:</b> <b style='color:#fff'>{$peak_hari['hari']}</b>
                dengan {$peak_hari['n']} pendaftaran. Pastikan petugas lengkap di hari ini
                untuk SPMB berikutnya.</span></div>";
            }
            // Insight 6: Gender
            if ($total > 0 && $total_pr > 0) {
                $pct_p = round($total_pr/$total*100);
                if ($pct_p > 30) {
                    echo "<div class='insight-item ins-promo'><span class='ins-icon'>👩</span>
                    <span><b>Pendaftar Perempuan:</b> {$pct_p}% dari total ({$total_pr} siswa).
                    SMK teknik dengan minat perempuan yang cukup baik.</span></div>";
                }
            }
            ?>
        </div>
    </div>

    <!-- LOG ARSIP + TUTUP -->
    <div>
        <?php if (in_array(strtolower($_SESSION['role']), ['superuser','database'])): ?>
        <div class="tutup-card" style="margin-bottom:20px;">
            <div class="tutup-title"><i class="fas fa-archive"></i> Tutup & Arsipkan SPMB</div>
            <div class="tutup-desc">
                Setelah SPMB selesai, arsipkan semua data siswa aktif ke penyimpanan tahunan.
                Tabel siswa akan dikosongkan sehingga siap untuk SPMB tahun berikutnya.
                <br><b style="color:var(--red)">Data tidak dihapus — tersimpan permanen di arsip.</b>
            </div>
            <button class="btn-sm btn-danger" style="font-size:0.8rem;padding:12px 22px;" onclick="modalTutupSPMB()">
                <i class="fas fa-archive"></i> TUTUP SPMB <?= $tahun_aktif ?> & ARSIPKAN
            </button>
        </div>
        <?php endif; ?>

        <div class="gc">
            <div class="sec-hd"><i class="fas fa-history"></i> Riwayat Arsip SPMB</div>
            <?php if (!empty($log_tutup)): ?>
                <?php foreach ($log_tutup as $lg): ?>
                <div class="log-item">
                    <div class="log-tahun">SPMB <?= $lg['tahun_spmb'] ?> — <?= $lg['total_diarsipkan'] ?> siswa diarsipkan</div>
                    <div class="log-meta">
                        DU: <?= $lg['total_du'] ?> &bull; Belum DU: <?= $lg['total_tidak_du'] ?> &bull;
                        <?= date('d M Y H:i', strtotime($lg['tgl_tutup'])) ?> &bull; <?= $lg['petugas'] ?>
                    </div>
                    <?php if (!empty($lg['catatan'])): ?>
                    <div style="margin-top:4px;font-size:0.7rem;color:#94a3b8;font-style:italic;"><?= $lg['catatan'] ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:0.8rem;text-align:center;padding:20px;">
                    Belum ada SPMB yang diarsipkan.
                </p>
            <?php endif; ?>
        </div>
    </div>

</div>

</div><!-- end main -->

<script>
// ============== DATA PHP → JS ==============
const dataJurusan = <?= json_encode(array_column($jur_data, 'n')) ?>;
const labelJurusan = <?= json_encode(array_column($jur_data, 'jurusan')) ?>;
const dataBulan    = <?= json_encode(array_column($bulan_data, 'n')) ?>;
const labelBulan   = <?= json_encode(array_column($bulan_data, 'nama')) ?>;
const dataKota     = <?= json_encode(array_column($kota_data, 'n')) ?>;
const labelKota    = <?= json_encode(array_column($kota_data, 'kota')) ?>;
const dataHari     = <?= json_encode(array_column($hari_data, 'n')) ?>;
const labelHari    = <?= json_encode(array_column($hari_data, 'hari')) ?>;
const compareData  = <?= json_encode($compare_data) ?>;
const semua_jur    = <?= json_encode($semua_jur) ?>;

// Warna
const JUR_COLORS = {'TPM':'#10b981','TKR':'#f97316','TSM':'#ef4444','TKJ':'#4f46e5','TAV':'#8b5cf6'};
const CHART_DEFAULTS = {
    plugins: { legend: { labels: { color:'#94a3b8', font:{weight:'700',size:11} } } },
    scales: {
        x: { grid:{color:'rgba(255,255,255,0.04)'}, ticks:{color:'#64748b',font:{weight:'700'}} },
        y: { grid:{color:'rgba(255,255,255,0.04)'}, ticks:{color:'#64748b',font:{weight:'700'}} }
    }
};

// CHART 1: JURUSAN (Donut)
new Chart(document.getElementById('chartJurusan'), {
    type:'doughnut',
    data:{
        labels: labelJurusan,
        datasets:[{
            data: dataJurusan,
            backgroundColor: labelJurusan.map(j => JUR_COLORS[j]||'#64748b'),
            borderColor:'rgba(2,6,23,0.8)', borderWidth:3
        }]
    },
    options:{
        responsive:true, maintainAspectRatio:true,
        plugins:{ legend:{ position:'right', labels:{color:'#94a3b8',font:{weight:'700',size:11},padding:12} } }
    }
});

// CHART 2: TREN BULANAN (Line)
if (labelBulan.length > 0) {
    const ctx2 = document.getElementById('chartBulan').getContext('2d');
    const grad2 = ctx2.createLinearGradient(0,0,0,300);
    grad2.addColorStop(0,'rgba(56,189,248,0.3)');
    grad2.addColorStop(1,'rgba(2,6,23,0)');
    new Chart(ctx2, {
        type:'line',
        data:{labels:labelBulan, datasets:[{
            label:'Pendaftar',
            data:dataBulan,
            borderColor:'#38bdf8', borderWidth:3, fill:true, backgroundColor:grad2,
            tension:0.4, pointBackgroundColor:'#38bdf8', pointRadius:5
        }]},
        options:{responsive:true,maintainAspectRatio:false,...CHART_DEFAULTS,
            plugins:{legend:{display:false}}}
    });
}

// CHART 3: KOTA (Bar Horizontal)
if (labelKota.length > 0) {
    new Chart(document.getElementById('chartKota'), {
        type:'bar',
        data:{labels:labelKota, datasets:[{
            label:'Siswa', data:dataKota,
            backgroundColor:'rgba(16,185,129,0.7)', borderRadius:8
        }]},
        options:{
            responsive:true, maintainAspectRatio:false,
            indexAxis:'y',
            plugins:{legend:{display:false}},
            scales:{
                x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#64748b',font:{weight:'700'}}},
                y:{grid:{display:false},ticks:{color:'#94a3b8',font:{weight:'700',size:10}}}
            }
        }
    });
}

// CHART 4: HARI (Bar)
if (labelHari.length > 0) {
    new Chart(document.getElementById('chartHari'), {
        type:'bar',
        data:{labels:labelHari, datasets:[{
            label:'Pendaftar', data:dataHari,
            backgroundColor:['rgba(56,189,248,0.7)','rgba(167,139,250,0.7)','rgba(245,158,11,0.7)',
                             'rgba(16,185,129,0.7)','rgba(249,115,22,0.7)','rgba(236,72,153,0.7)','rgba(100,116,139,0.5)'],
            borderRadius:8
        }]},
        options:{
            responsive:true, maintainAspectRatio:false,
            plugins:{legend:{display:false}},
            scales:{
                x:{grid:{display:false},ticks:{color:'#64748b',font:{weight:'700'}}},
                y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#64748b',font:{weight:'700'}}}
            }
        }
    });
}

// CHART 5: PERBANDINGAN TAHUN
const cmpEl = document.getElementById('chartCompare');
if (cmpEl && compareData.length > 1) {
    const cmpLabels = compareData.map(r => r.tahun);
    const cmpDatasets = semua_jur.map((j,i) => ({
        label: j,
        data: compareData.map(r => r.jurusans[j] || 0),
        backgroundColor: Object.values(JUR_COLORS)[i] || '#64748b',
        borderRadius: 6
    }));
    new Chart(cmpEl, {
        type:'bar',
        data:{ labels:cmpLabels, datasets:cmpDatasets },
        options:{
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ labels:{color:'#94a3b8',font:{weight:'700',size:10}} } },
            scales:{
                x:{stacked:true,grid:{display:false},ticks:{color:'#64748b',font:{weight:'700'}}},
                y:{stacked:true,grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#64748b',font:{weight:'700'}}}
            }
        }
    });
}

// ============== TUTUP SPMB MODAL ==============
function modalTutupSPMB() {
    const tahunAktif = <?= $tahun_aktif ?>;
    Swal.fire({
        title: 'Tutup & Arsipkan SPMB ' + tahunAktif,
        html: `
            <p style="color:#94a3b8;margin-bottom:16px;font-size:0.85rem;">
                Semua data siswa aktif akan disimpan ke arsip tahunan.<br>
                Tabel siswa aktif akan dikosongkan untuk SPMB ${tahunAktif+1}.
            </p>
            <textarea id="swal-catatan" placeholder="Catatan penutupan (opsional)..."
                style="width:100%;padding:12px;background:#1e293b;border:1px solid #334155;
                border-radius:10px;color:#fff;font-size:0.85rem;height:80px;resize:none;"></textarea>
        `,
        icon: 'warning',
        showCancelButton: true,
        cancelButtonText: 'Batal',
        confirmButtonText: 'Ya, Arsipkan Sekarang',
        confirmButtonColor: '#ef4444',
        background: '#020617', color: '#fff',
        preConfirm: () => {
            return {
                tahun: tahunAktif,
                catatan: document.getElementById('swal-catatan').value
            };
        }
    }).then(result => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Mengarsipkan data...',
            html: 'Mohon tunggu, jangan tutup halaman ini.',
            allowOutsideClick: false, showConfirmButton: false,
            background: '#020617', color: '#fff',
            didOpen: () => {
                Swal.showLoading();
                const fd = new FormData();
                fd.append('aksi', 'tutup');
                fd.append('tahun', result.value.tahun);
                fd.append('catatan', result.value.catatan);

                fetch('../database/proses_tutup_spmb.php', { method:'POST', body:fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) {
                            Swal.fire({
                                icon: 'success',
                                title: 'SPMB Berhasil Diarsipkan!',
                                html: `<b style="color:#38bdf8">${data.total}</b> siswa telah disimpan ke arsip ${result.value.tahun}.<br>
                                       DU: <b style="color:#10b981">${data.du}</b>`,
                                confirmButtonColor: '#38bdf8',
                                background: '#020617', color: '#fff'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon:'error', title:'Gagal!', text: data.msg,
                                background:'#020617', color:'#fff' });
                        }
                    }).catch(e => {
                        Swal.fire({ icon:'error', title:'Error', text: e.message,
                            background:'#020617', color:'#fff' });
                    });
            }
        });
    });
}
</script>
</body>
</html>
