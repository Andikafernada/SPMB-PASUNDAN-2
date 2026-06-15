<?php
/**
 * EXPORT ANALISIS KE EXCEL - v5.0
 * Export: rekap jurusan, top sekolah, sebaran kecamatan, tren bulanan, perbandingan tahun
 */
session_start();
include '../../config.php';

$allowed = ['database','superuser'];
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed)) {
    show_error_page("Akses Ditolak", "Halaman ini hanya untuk Tim Database.");
}

$tahun_dipilih = intval($_GET['tahun'] ?? date('Y'));
$mode          = $_GET['mode'] ?? 'analisis';

// Tentukan sumber data
$dari_arsip = false;
$cek_arsip  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) n FROM spmb_arsip_tahunan WHERE tahun_spmb='$tahun_dipilih'"));
if ($cek_arsip && $cek_arsip['n'] > 0) $dari_arsip = true;

$tbl         = $dari_arsip ? "spmb_arsip_tahunan" : "siswa";
$where_tahun = $dari_arsip ? "WHERE tahun_spmb='$tahun_dipilih'" : "WHERE 1=1";

// Kumpulkan semua data
$total     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $tbl $where_tahun"))['n'];
$total_du  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $tbl $where_tahun AND status_siswa='SUDAH DAFTAR ULANG'"))['n'] ?? 0;
$total_l   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $tbl $where_tahun AND jenis_kelamin='LAKI-LAKI'"))['n'] ?? 0;
$total_p   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $tbl $where_tahun AND jenis_kelamin='PEREMPUAN'"))['n'] ?? 0;

// Jurusan
$jur_rows = [];
$res = mysqli_query($conn, "SELECT jurusan, COUNT(*) n,
    SUM(CASE WHEN jenis_kelamin='LAKI-LAKI' THEN 1 ELSE 0 END) L,
    SUM(CASE WHEN jenis_kelamin='PEREMPUAN' THEN 1 ELSE 0 END) P,
    ROUND(AVG(nilai_btq),1) avg_btq
    FROM $tbl $where_tahun AND jurusan IS NOT NULL AND jurusan!=''
    GROUP BY jurusan ORDER BY n DESC");
while ($r = mysqli_fetch_assoc($res)) { $jur_rows[] = $r; }

// Top sekolah
$sekolah_rows = [];
$res = mysqli_query($conn, "SELECT asal_sekolah, COUNT(*) n
    FROM $tbl $where_tahun AND asal_sekolah IS NOT NULL AND asal_sekolah!=''
    GROUP BY asal_sekolah ORDER BY n DESC LIMIT 30");
while ($r = mysqli_fetch_assoc($res)) { $sekolah_rows[] = $r; }

// Kecamatan
$kec_rows = [];
$res = mysqli_query($conn, "SELECT kecamatan, COUNT(*) n
    FROM $tbl $where_tahun AND kecamatan IS NOT NULL AND kecamatan!=''
    GROUP BY kecamatan ORDER BY n DESC LIMIT 30");
while ($r = mysqli_fetch_assoc($res)) { $kec_rows[] = $r; }

// Kota
$kota_rows = [];
$res = mysqli_query($conn, "SELECT kota, COUNT(*) n
    FROM $tbl $where_tahun AND kota IS NOT NULL AND kota!=''
    GROUP BY kota ORDER BY n DESC");
while ($r = mysqli_fetch_assoc($res)) { $kota_rows[] = $r; }

// Tren bulanan
$bulan_rows = [];
$res = mysqli_query($conn, "SELECT DATE_FORMAT(tgl_daftar,'%b') nama, MONTH(tgl_daftar) bln, COUNT(*) n
    FROM $tbl $where_tahun AND tgl_daftar IS NOT NULL
    GROUP BY MONTH(tgl_daftar) ORDER BY MONTH(tgl_daftar)");
while ($r = mysqli_fetch_assoc($res)) { $bulan_rows[] = $r; }

// Perbandingan semua tahun arsip
$semua_tahun = [];
$res = mysqli_query($conn, "SELECT DISTINCT tahun_spmb FROM spmb_arsip_tahunan ORDER BY tahun_spmb");
while ($r = mysqli_fetch_assoc($res)) { $semua_tahun[] = $r['tahun_spmb']; }
$semua_tahun[] = date('Y') . ' (Aktif)';

$compare_rows = [];
foreach ($semua_tahun as $th) {
    $is_aktif = strpos($th, 'Aktif') !== false;
    $th_num   = intval($th);
    $t        = $is_aktif ? "siswa" : "spmb_arsip_tahunan";
    $w        = $is_aktif ? "WHERE 1=1" : "WHERE tahun_spmb='$th_num'";
    $row = ['tahun' => $th, 'total' => 0, 'du' => 0];
    $row['total'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $t $w"))['n'] ?? 0;
    $row['du']    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $t $w AND status_siswa='SUDAH DAFTAR ULANG'"))['n'] ?? 0;
    foreach (['TPM','TKR','TSM','TKJ','TAV'] as $j) {
        $row[$j] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $t $w AND jurusan='$j'"))['n'] ?? 0;
    }
    $compare_rows[] = $row;
}

// Siapkan data JSON untuk Python
$json_data = json_encode([
    'tahun'        => $tahun_dipilih,
    'dari_arsip'   => $dari_arsip,
    'generated'    => date('d M Y H:i'),
    'petugas'      => $_SESSION['nama'],
    'summary'      => ['total'=>$total,'du'=>$total_du,'l'=>$total_l,'p'=>$total_p,
                       'pct_du'=> $total>0?round($total_du/$total*100):0,
                       'pct_l' => $total>0?round($total_l/$total*100):0],
    'jurusan'      => $jur_rows,
    'sekolah'      => $sekolah_rows,
    'kecamatan'    => $kec_rows,
    'kota'         => $kota_rows,
    'bulan'        => $bulan_rows,
    'perbandingan' => $compare_rows,
]);

$json_path   = "/tmp/analisis_{$tahun_dipilih}.json";
$output_path = "/tmp/Analisis_SPMB_{$tahun_dipilih}_" . date('Ymd_His') . ".xlsx";
file_put_contents($json_path, $json_data);

$script = <<<'PY'
import sys, json
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side, GradientFill
from openpyxl.utils import get_column_letter
from openpyxl.chart import BarChart, Reference, LineChart, PieChart
from openpyxl.chart.series import DataPoint

json_path   = sys.argv[1]
output_path = sys.argv[2]

with open(json_path) as f:
    d = json.load(f)

wb = Workbook()
wb.remove(wb.active)

# --- STYLES ---
HDR  = PatternFill('solid', start_color='1E3A5F', end_color='1E3A5F')
HDR2 = PatternFill('solid', start_color='0F4C81', end_color='0F4C81')
ALT  = PatternFill('solid', start_color='EBF5FB', end_color='EBF5FB')
GRN  = PatternFill('solid', start_color='D1FAE5', end_color='D1FAE5')
YLW  = PatternFill('solid', start_color='FEF3C7', end_color='FEF3C7')
RED  = PatternFill('solid', start_color='FEE2E2', end_color='FEE2E2')
THIN = Border(
    left=Side(style='thin',color='CCCCCC'), right=Side(style='thin',color='CCCCCC'),
    top=Side(style='thin',color='CCCCCC'),  bottom=Side(style='thin',color='CCCCCC'))
BOLD_W = Font(name='Arial', bold=True, color='FFFFFF', size=10)
BOLD_D = Font(name='Arial', bold=True, color='1E3A5F', size=10)
NORM   = Font(name='Arial', size=10)
CENTER = Alignment(horizontal='center', vertical='center')
LEFT   = Alignment(horizontal='left',   vertical='center')

def hdr_row(ws, row, cols_vals, fill=HDR):
    for ci, val in enumerate(cols_vals, 1):
        c = ws.cell(row=row, column=ci, value=val)
        c.font = BOLD_W; c.fill = fill; c.alignment = CENTER; c.border = THIN

def data_row(ws, row, vals, fill=None):
    for ci, val in enumerate(vals, 1):
        c = ws.cell(row=row, column=ci, value=val)
        c.font = NORM; c.alignment = CENTER; c.border = THIN
        if fill: c.fill = fill

def title_merge(ws, row, cols, text, size=14):
    ws.merge_cells(f'A{row}:{get_column_letter(cols)}{row}')
    c = ws[f'A{row}']
    c.value = text
    c.font = Font(name='Arial', bold=True, size=size, color='1E3A5F')
    c.alignment = CENTER
    ws.row_dimensions[row].height = 28

tahun   = d['tahun']
gen     = d['generated']
petugas = d['petugas']
s       = d['summary']

# =============================================
# SHEET 1: RINGKASAN
# =============================================
ws1 = wb.create_sheet('RINGKASAN')
title_merge(ws1, 1, 6, f'LAPORAN ANALISIS SPMB {tahun} — SMK PASUNDAN 2 BANDUNG', 15)
title_merge(ws1, 2, 6, f'Dibuat: {gen}  |  Petugas: {petugas}', 10)
ws1.row_dimensions[3].height = 8

# KPI boxes
kpis = [
    ('TOTAL PENDAFTAR', s['total'], ''),
    ('SUDAH DAFTAR ULANG', s['du'], f"{s['pct_du']}%"),
    ('BELUM DAFTAR ULANG', s['total']-s['du'], f"{100-s['pct_du']}%"),
    ('LAKI-LAKI', s['l'], f"{s['pct_l']}%"),
    ('PEREMPUAN', s['p'], f"{100-s['pct_l']}%"),
    ('SUMBER DATA', 'ARSIP' if d['dari_arsip'] else 'AKTIF', f'Tahun {tahun}'),
]
for ci, (lbl, val, sub) in enumerate(kpis, 1):
    ws1.cell(4, ci).value = lbl
    ws1.cell(4, ci).font = Font(name='Arial', bold=True, size=9, color='64748B')
    ws1.cell(4, ci).alignment = CENTER
    ws1.cell(5, ci).value = val
    ws1.cell(5, ci).font = Font(name='Arial', bold=True, size=20, color='1E3A5F')
    ws1.cell(5, ci).alignment = CENTER
    ws1.cell(6, ci).value = sub
    ws1.cell(6, ci).font = Font(name='Arial', size=9, color='64748B')
    ws1.cell(6, ci).alignment = CENTER
    ws1.column_dimensions[get_column_letter(ci)].width = 22
ws1.row_dimensions[4].height = 20
ws1.row_dimensions[5].height = 36
ws1.row_dimensions[6].height = 18

# Jurusan summary
ws1.row_dimensions[8].height = 8
hdr_row(ws1, 9, ['JURUSAN','TOTAL','LAKI-LAKI','PEREMPUAN','AVG BTQ','%'])
for ri, j in enumerate(d['jurusan'], 10):
    pct = round(j['n']/s['total']*100,1) if s['total'] else 0
    fill = ALT if ri%2==0 else None
    data_row(ws1, ri, [j['jurusan'], j['n'], j['L'], j['P'], j['avg_btq'], f'{pct}%'], fill)

# Pie chart jurusan
if d['jurusan']:
    pie = PieChart()
    pie.title = f"Sebaran Jurusan SPMB {tahun}"
    pie.style = 10
    labels = Reference(ws1, min_col=1, min_row=10, max_row=9+len(d['jurusan']))
    data_ref = Reference(ws1, min_col=2, min_row=9, max_row=9+len(d['jurusan']))
    pie.add_data(data_ref, titles_from_data=True)
    pie.set_categories(labels)
    ws1.add_chart(pie, "A16")

ws1.freeze_panes = 'A10'

# =============================================
# SHEET 2: SEKOLAH ASAL
# =============================================
ws2 = wb.create_sheet('SEKOLAH ASAL')
title_merge(ws2, 1, 4, f'TOP SEKOLAH ASAL — SPMB {tahun}', 13)
ws2.merge_cells('A2:D2')
ws2['A2'].value = 'Data ini berguna untuk menentukan target promosi & kunjungan sekolah tahun depan.'
ws2['A2'].font = Font(name='Arial', size=10, italic=True, color='64748B')
ws2['A2'].alignment = CENTER
ws2.row_dimensions[3].height = 6
hdr_row(ws2, 4, ['PERINGKAT', 'NAMA SEKOLAH', 'JUMLAH SISWA', 'PERSENTASE'])
ws2.column_dimensions['A'].width = 12
ws2.column_dimensions['B'].width = 45
ws2.column_dimensions['C'].width = 18
ws2.column_dimensions['D'].width = 15
for ri, sk in enumerate(d['sekolah'], 5):
    pct = round(sk['n']/s['total']*100,1) if s['total'] else 0
    fill = GRN if ri<=7 else (ALT if ri%2==0 else None)
    data_row(ws2, ri, [ri-4, sk['asal_sekolah'], sk['n'], f'{pct}%'], fill)
    ws2.cell(ri, 2).alignment = LEFT
ws2.freeze_panes = 'A5'

# Bar chart sekolah (top 10)
if d['sekolah']:
    bc = BarChart()
    bc.type = "bar"; bc.style = 10
    bc.title = "Top 10 Sekolah Asal"
    bc.y_axis.title = "Jumlah Siswa"
    n_show = min(10, len(d['sekolah']))
    data_r  = Reference(ws2, min_col=3, min_row=4, max_row=4+n_show)
    cats    = Reference(ws2, min_col=2, min_row=5, max_row=4+n_show)
    bc.add_data(data_r, titles_from_data=True)
    bc.set_categories(cats)
    bc.shape = 4; bc.width = 20; bc.height = 14
    ws2.add_chart(bc, f"A{6+len(d['sekolah'])}")

# =============================================
# SHEET 3: SEBARAN WILAYAH
# =============================================
ws3 = wb.create_sheet('SEBARAN WILAYAH')
title_merge(ws3, 1, 7, f'SEBARAN WILAYAH — SPMB {tahun}', 13)
ws3.row_dimensions[2].height = 6

# Kecamatan
ws3.cell(3, 1).value = 'SEBARAN KECAMATAN'
ws3.cell(3, 1).font = Font(name='Arial', bold=True, size=11, color='1E3A5F')
hdr_row(ws3, 4, ['NO', 'KECAMATAN', 'SISWA', '%'])
for ri, kc in enumerate(d['kecamatan'], 5):
    pct = round(kc['n']/s['total']*100,1) if s['total'] else 0
    fill = ALT if ri%2==0 else None
    data_row(ws3, ri, [ri-4, kc['kecamatan'], kc['n'], f'{pct}%'], fill)
    ws3.cell(ri, 2).alignment = LEFT

# Kota (kolom F-I)
offset = 6
ws3.cell(3, offset).value = 'SEBARAN KOTA/KAB'
ws3.cell(3, offset).font = Font(name='Arial', bold=True, size=11, color='1E3A5F')
for ci, hdr in enumerate(['NO','KOTA/KAB','SISWA','%'], offset):
    c = ws3.cell(4, ci, value=hdr)
    c.font = BOLD_W; c.fill = HDR2; c.alignment = CENTER; c.border = THIN
for ri, kt in enumerate(d['kota'], 5):
    pct = round(kt['n']/s['total']*100,1) if s['total'] else 0
    fill = ALT if ri%2==0 else None
    for ci, val in enumerate([ri-4, kt['kota'], kt['n'], f'{pct}%'], offset):
        c = ws3.cell(ri, ci, value=val)
        c.font = NORM; c.alignment = CENTER; c.border = THIN
        if fill: c.fill = fill
    ws3.cell(ri, offset+1).alignment = LEFT

for col in ['A','B','C','D','F','G','H','I']:
    ws3.column_dimensions[col].width = 18
ws3.column_dimensions['B'].width = 28
ws3.column_dimensions['G'].width = 28

# =============================================
# SHEET 4: TREN BULANAN
# =============================================
ws4 = wb.create_sheet('TREN BULANAN')
title_merge(ws4, 1, 3, f'TREN PENDAFTARAN BULANAN — SPMB {tahun}', 13)
ws4.row_dimensions[2].height = 6
hdr_row(ws4, 3, ['BULAN', 'JUMLAH PENDAFTAR', 'KUMULATIF'])
ws4.column_dimensions['A'].width = 16
ws4.column_dimensions['B'].width = 22
ws4.column_dimensions['C'].width = 18
kum = 0
for ri, b in enumerate(d['bulan'], 4):
    kum += b['n']
    fill = ALT if ri%2==0 else None
    data_row(ws4, ri, [b['nama'], b['n'], kum], fill)
total_row = 4 + len(d['bulan'])
ws4.cell(total_row, 1).value = 'TOTAL'
ws4.cell(total_row, 2).value = s['total']
ws4.cell(total_row, 3).value = s['total']
for ci in range(1,4):
    ws4.cell(total_row,ci).font = Font(name='Arial',bold=True,size=10,color='FFFFFF')
    ws4.cell(total_row,ci).fill = HDR
    ws4.cell(total_row,ci).alignment = CENTER
    ws4.cell(total_row,ci).border = THIN

# Line chart tren
if d['bulan']:
    lc = LineChart()
    lc.title = "Tren Pendaftaran Bulanan"
    lc.style = 10; lc.y_axis.title = "Jumlah Pendaftar"
    data_r = Reference(ws4, min_col=2, min_row=3, max_row=3+len(d['bulan']))
    cats   = Reference(ws4, min_col=1, min_row=4, max_row=3+len(d['bulan']))
    lc.add_data(data_r, titles_from_data=True)
    lc.set_categories(cats)
    lc.width = 22; lc.height = 14
    ws4.add_chart(lc, f"A{total_row+2}")

# =============================================
# SHEET 5: PERBANDINGAN TAHUN
# =============================================
ws5 = wb.create_sheet('PERBANDINGAN TAHUN')
title_merge(ws5, 1, 9, 'PERBANDINGAN SPMB ANTAR TAHUN — SMK PASUNDAN 2', 13)
ws5.row_dimensions[2].height = 6
jurusans = ['TPM','TKR','TSM','TKJ','TAV']
hdr_row(ws5, 3, ['TAHUN','TOTAL','DU','%DU'] + jurusans)
ws5.column_dimensions['A'].width = 16
for i in range(2,10): ws5.column_dimensions[get_column_letter(i)].width = 14

prev_total = None
for ri, row in enumerate(d['perbandingan'], 4):
    pct_du = round(row['du']/row['total']*100,1) if row['total'] else 0
    vals = [row['tahun'], row['total'], row['du'], f'{pct_du}%'] + [row.get(j,0) for j in jurusans]
    fill = ALT if ri%2==0 else None
    # Highlight aktif
    if 'Aktif' in str(row['tahun']): fill = GRN
    data_row(ws5, ri, vals, fill)
    ws5.cell(ri, 1).alignment = LEFT
    # Tanda naik/turun
    if prev_total is not None and row['total'] > 0:
        diff = row['total'] - prev_total
        arrow = '▲' if diff > 0 else ('▼' if diff < 0 else '—')
        clr = '059669' if diff > 0 else ('DC2626' if diff < 0 else '64748B')
        ws5.cell(ri, 2).value = f"{row['total']} {arrow}{abs(diff) if diff!=0 else ''}"
        ws5.cell(ri, 2).font = Font(name='Arial', size=10, color=clr, bold=diff!=0)
    prev_total = row['total']

# Bar chart perbandingan
if len(d['perbandingan']) > 1:
    bc2 = BarChart()
    bc2.type = "col"; bc2.style = 10; bc2.grouping = "stacked"; bc2.overlap = 100
    bc2.title = "Pendaftar per Tahun per Jurusan"
    last_row = 3 + len(d['perbandingan'])
    for col_idx, jur in enumerate(jurusans, 5):
        dr = Reference(ws5, min_col=col_idx, min_row=3, max_row=last_row)
        bc2.add_data(dr, titles_from_data=True)
    cats = Reference(ws5, min_col=1, min_row=4, max_row=last_row)
    bc2.set_categories(cats)
    bc2.width = 24; bc2.height = 16
    ws5.add_chart(bc2, f"A{last_row+2}")

ws5.freeze_panes = 'B4'

# =============================================
# SHEET 6: REKOMENDASI
# =============================================
ws6 = wb.create_sheet('REKOMENDASI')
title_merge(ws6, 1, 5, f'REKOMENDASI STRATEGI SPMB {int(tahun)+1}', 13)
ws6.merge_cells('A2:E2')
ws6['A2'].value = f'Berdasarkan data SPMB {tahun} — SMK PASUNDAN 2 BANDUNG'
ws6['A2'].font = Font(name='Arial', size=10, italic=True, color='64748B')
ws6['A2'].alignment = CENTER
ws6.row_dimensions[3].height = 10

recs = [
    ['PROMOSI', 'TARGET SEKOLAH', '', '', ''],
    ['', 'Fokuskan kunjungan/penyebaran brosur ke 5 sekolah pengirim siswa terbanyak:', '', '', ''],
]
for i, sk in enumerate(d['sekolah'][:5], 1):
    recs.append(['', f'{i}. {sk["asal_sekolah"]}', f'{sk["n"]} siswa', '', ''])

recs += [
    ['', '', '', '', ''],
    ['WILAYAH', 'FOKUS IKLAN & SPANDUK', '', '', ''],
    ['', f'Pasang iklan/spanduk di 3 kecamatan teratas:', '', '', ''],
]
for i, kc in enumerate(d['kecamatan'][:3], 1):
    recs.append(['', f'{i}. Kec. {kc["kecamatan"]}', f'{kc["n"]} siswa', '', ''])

if d['jurusan']:
    bot_jur = d['jurusan'][-1]
    top_jur = d['jurusan'][0]
    recs += [
        ['', '', '', '', ''],
        ['JURUSAN', 'STRATEGI PER JURUSAN', '', '', ''],
        ['', f'Jurusan paling diminati: {top_jur["jurusan"]} ({top_jur["n"]} siswa) — pertahankan.', '', '', ''],
        ['', f'Jurusan perlu promosi khusus: {bot_jur["jurusan"]} ({bot_jur["n"]} siswa) — buat konten menarik.', '', '', ''],
    ]

cat_colors = {'PROMOSI': '1E40AF', 'WILAYAH': '065F46', 'JURUSAN': '7C3AED'}
for ci, w in zip([1,2,3,4,5],[12,55,16,10,10]):
    ws6.column_dimensions[get_column_letter(ci)].width = w

ri = 4
for row in recs:
    for ci, val in enumerate(row, 1):
        c = ws6.cell(ri, ci, value=val)
        c.font = NORM; c.alignment = LEFT; c.border = THIN
        if ci == 1 and val and val != '':
            c.fill = PatternFill('solid', start_color=cat_colors.get(val,'334155'), end_color=cat_colors.get(val,'334155'))
            c.font = Font(name='Arial', bold=True, size=10, color='FFFFFF')
            c.alignment = CENTER
        if ci == 2 and row[0] == '' and val.startswith(('1.','2.','3.','4.','5.')):
            c.font = Font(name='Arial', bold=True, size=10, color='1E3A5F')
    ri += 1

# Set sheet order
wb.move_sheet('RINGKASAN', offset=0)

wb.save(output_path)
print("OK")
PY;

$script_path = "/tmp/gen_analisis.py";
file_put_contents($script_path, $script);

$result = shell_exec("python3 $script_path '$json_path' '$output_path' 2>&1");

if (!file_exists($output_path)) {
    show_error_page("Gagal Generate Excel", "Python script gagal menghasilkan file. Detail: " . htmlspecialchars($result));
}

$filename = "Analisis_SPMB_{$tahun_dipilih}_" . date('d-m-Y') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Content-Length: ' . filesize($output_path));
header('Cache-Control: must-revalidate');
readfile($output_path);
unlink($output_path);
unlink($json_path);
exit();
