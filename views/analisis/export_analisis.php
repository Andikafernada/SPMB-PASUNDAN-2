<?php
/**
 * EXPORT ANALISIS KE EXCEL - v5.0 (SECURED & OPTIMIZED)
 * Export: rekap jurusan, top sekolah, sebaran kecamatan, tren bulanan, perbandingan tahun
 * FIX: Pencegahan Race Condition, Shell Injection, dan Storage Leak
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

$jur_data = [];
$res_jur = mysqli_query($conn, "SELECT jurusan, COUNT(*) n, 
    SUM(CASE WHEN jenis_kelamin='LAKI-LAKI' THEN 1 ELSE 0 END) as L,
    SUM(CASE WHEN jenis_kelamin='PEREMPUAN' THEN 1 ELSE 0 END) as P
    FROM $tbl $where_tahun AND jurusan IS NOT NULL AND jurusan!='' GROUP BY jurusan ORDER BY n DESC");
while($r = mysqli_fetch_assoc($res_jur)) $jur_data[] = $r;

$sek_data = [];
$res_sk = mysqli_query($conn, "SELECT asal_sekolah, COUNT(*) n FROM $tbl $where_tahun AND asal_sekolah IS NOT NULL AND asal_sekolah!='' GROUP BY asal_sekolah ORDER BY n DESC LIMIT 20");
while($r = mysqli_fetch_assoc($res_sk)) $sek_data[] = $r;

$kec_data = [];
$res_kec = mysqli_query($conn, "SELECT kecamatan, COUNT(*) n FROM $tbl $where_tahun AND kecamatan IS NOT NULL AND kecamatan!='' GROUP BY kecamatan ORDER BY n DESC LIMIT 20");
while($r = mysqli_fetch_assoc($res_kec)) $kec_data[] = $r;

$bln_data = [];
$res_bln = mysqli_query($conn, "SELECT DATE_FORMAT(tgl_daftar,'%b %Y') as bln, COUNT(*) n FROM $tbl $where_tahun AND tgl_daftar IS NOT NULL GROUP BY MONTH(tgl_daftar), YEAR(tgl_daftar) ORDER BY tgl_daftar ASC");
while($r = mysqli_fetch_assoc($res_bln)) $bln_data[] = $r;

$cmp_data = [];
$res_th = mysqli_query($conn, "SELECT DISTINCT tahun_spmb FROM spmb_arsip_tahunan ORDER BY tahun_spmb DESC");
$th_list = [];
while($r = mysqli_fetch_assoc($res_th)) $th_list[] = $r['tahun_spmb'];
$th_list[] = date('Y'); // tambah aktif
$th_list = array_unique($th_list);
rsort($th_list);

foreach($th_list as $th) {
    $t = ($th == date('Y')) ? "siswa" : "spmb_arsip_tahunan";
    $w = ($th == date('Y')) ? "WHERE 1=1" : "WHERE tahun_spmb='$th'";
    $c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM $t $w"))['n'] ?? 0;
    if($c > 0) $cmp_data[] = ['tahun' => $th, 'n' => $c];
}

$payload = [
    'tahun' => $tahun_dipilih,
    'total' => $total,
    'du'    => $total_du,
    'jur'   => $jur_data,
    'sek'   => $sek_data,
    'kec'   => $kec_data,
    'bln'   => $bln_data,
    'cmp'   => $cmp_data
];

// =========================================================================
// PERBAIKAN: Gunakan Uniqid untuk mencegah file tabrakan saat ditarik bersamaan
// =========================================================================
$uid = uniqid('analisis_', true);
$json_path = "/tmp/{$uid}.json";
$script_path = "/tmp/{$uid}.py";
$output_path = "/tmp/Laporan_Analisis_SPMB_{$tahun_dipilih}_{$uid}.xlsx";

file_put_contents($json_path, json_encode($payload));

$script = <<<PY
import sys, json
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

json_path = sys.argv[1]
output_path = sys.argv[2]

with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

wb = Workbook()
ws = wb.active
ws.title = "RINGKASAN"

# Styles
THIN = Border(left=Side(style='thin'), right=Side(style='thin'), top=Side(style='thin'), bottom=Side(style='thin'))
CENTER = Alignment(horizontal='center', vertical='center')
LEFT = Alignment(horizontal='left', vertical='center')
BOLD = Font(name='Arial', bold=True, size=11)
NORM = Font(name='Arial', size=10)
TITLE = Font(name='Arial', bold=True, size=14, color='FFFFFF')

# MAIN HEADER
ws.merge_cells('A1:D1')
ws['A1'] = f"LAPORAN ANALISIS SPMB SMK PASUNDAN 2 - TAHUN {data['tahun']}"
ws['A1'].font = TITLE
ws['A1'].fill = PatternFill('solid', start_color='1E3A8A', end_color='1E3A8A')
ws['A1'].alignment = CENTER
ws.row_dimensions[1].height = 25

ws['A3'] = "TOTAL PENDAFTAR"; ws['B3'] = data['total']
ws['A4'] = "SUDAH DAFTAR ULANG"; ws['B4'] = data['du']
ws['A5'] = "BELUM DAFTAR ULANG"; ws['B5'] = data['total'] - data['du']

for r in range(3,6):
    ws[f'A{r}'].font = BOLD
    ws[f'A{r}'].fill = PatternFill('solid', start_color='F1F5F9', end_color='F1F5F9')
    ws[f'B{r}'].font = BOLD
    ws[f'A{r}'].border = THIN; ws[f'B{r}'].border = THIN

# SHEET JURUSAN
ws2 = wb.create_sheet("JURUSAN")
ws2.append(["JURUSAN", "TOTAL", "LAKI-LAKI", "PEREMPUAN"])
for cell in ws2[1]:
    cell.font = BOLD; cell.fill = PatternFill('solid', start_color='38BDF8', end_color='38BDF8')
    cell.alignment = CENTER; cell.border = THIN
r = 2
for j in data['jur']:
    ws2.append([j['jurusan'], j['n'], j['L'], j['P']])
    for ci in range(1,5): ws2.cell(r, ci).border = THIN; ws2.cell(r, ci).alignment = CENTER
    r+=1
for i,w in enumerate([25,15,15,15],1): ws2.column_dimensions[get_column_letter(i)].width = w

# SHEET SEKOLAH
ws3 = wb.create_sheet("TOP SEKOLAH")
ws3.append(["NAMA SEKOLAH ASAL", "JUMLAH PENDAFTAR"])
for cell in ws3[1]:
    cell.font = BOLD; cell.fill = PatternFill('solid', start_color='A78BFA', end_color='A78BFA')
    cell.alignment = CENTER; cell.border = THIN
r = 2
for s in data['sek']:
    ws3.append([s['asal_sekolah'], s['n']])
    for ci in range(1,3): ws3.cell(r, ci).border = THIN
    r+=1
ws3.column_dimensions['A'].width = 45; ws3.column_dimensions['B'].width = 20

# SHEET KECAMATAN
ws4 = wb.create_sheet("SEBARAN KECAMATAN")
ws4.append(["KECAMATAN", "JUMLAH PENDAFTAR"])
for cell in ws4[1]:
    cell.font = BOLD; cell.fill = PatternFill('solid', start_color='10B981', end_color='10B981')
    cell.alignment = CENTER; cell.border = THIN
r = 2
for k in data['kec']:
    ws4.append([k['kecamatan'], k['n']])
    for ci in range(1,3): ws4.cell(r, ci).border = THIN
    r+=1
ws4.column_dimensions['A'].width = 30; ws4.column_dimensions['B'].width = 20

# TREN & TAHUN
ws5 = wb.create_sheet("TREN & HISTORI")
ws5.append(["BULAN", "PENDAFTAR"])
for cell in ws5[1]:
    cell.font = BOLD; cell.fill = PatternFill('solid', start_color='F59E0B', end_color='F59E0B')
    cell.alignment = CENTER; cell.border = THIN
r = 2
for b in data['bln']:
    ws5.append([b['bln'], b['n']])
    for ci in range(1,3): ws5.cell(r, ci).border = THIN; ws5.cell(r, ci).alignment = CENTER
    r+=1

ws5.append(["",""]) # spacer
ws5.append(["TAHUN SPMB", "TOTAL PENDAFTAR"])
rh = r+1
for cell in ws5[rh]:
    cell.font = BOLD; cell.fill = PatternFill('solid', start_color='EF4444', end_color='EF4444')
    cell.alignment = CENTER; cell.border = THIN
r = rh+1
for c in data['cmp']:
    ws5.append([c['tahun'], c['n']])
    for ci in range(1,3): ws5.cell(r, ci).border = THIN; ws5.cell(r, ci).alignment = CENTER
    r+=1
ws5.column_dimensions['A'].width = 20; ws5.column_dimensions['B'].width = 20

# Set sheet order
wb.move_sheet('RINGKASAN', offset=0)

wb.save(output_path)
print("OK")
PY;

file_put_contents($script_path, $script);

// =========================================================================
// PERBAIKAN: Gunakan escapeshellarg() untuk mengamankan path di level sistem operasi
// =========================================================================
$safe_script_path = escapeshellarg($script_path);
$safe_json_path = escapeshellarg($json_path);
$safe_output_path = escapeshellarg($output_path);

$result = shell_exec("python3 $safe_script_path $safe_json_path $safe_output_path 2>&1");

if (!file_exists($output_path)) {
    // Bersihkan file temporary jika gagal
    @unlink($script_path);
    @unlink($json_path);
    show_error_page("Gagal Generate Excel", "Python execution error: " . htmlspecialchars($result));
}

// Download file
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Laporan_Analisis_SPMB_' . $tahun_dipilih . '.xlsx"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($output_path));
readfile($output_path);

// =========================================================================
// PERBAIKAN: Bersihkan (Unlink) sampah file Python, JSON, & Excel dari memori /tmp
// =========================================================================
@unlink($script_path);
@unlink($json_path);
@unlink($output_path);
exit;
?>
