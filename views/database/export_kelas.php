<?php
/**
 * EXPORT KELAS KE EXCEL
 * Generate file Excel per jurusan dengan semua kelas
 */
session_start();
include '../../config.php';

if(!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'database') {
    show_error_page("Akses Ditolak", "Halaman ini hanya untuk Tim Database.");
}

$jur     = mysqli_real_escape_string($conn, $_GET['jur'] ?? 'TPM');
$jur_raw = $_GET['jur'] ?? 'TPM';

// Pastikan kolom ada
mysqli_query($conn, "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS nilai_btq INT DEFAULT 0");
mysqli_query($conn, "ALTER TABLE siswa ADD COLUMN IF NOT EXISTS kelas VARCHAR(20) DEFAULT NULL");

// Ambil data per kelas
$res = mysqli_query($conn, "SELECT nama_lengkap, nisn, nik, jenis_kelamin, tempat_lahir, tanggal_lahir,
    no_hp, asal_sekolah, alamat, rt, rw, kelurahan, kecamatan,
    nilai_btq, kelas, id_pendaftaran, jurusan, status_siswa, nama_ayah, nama_ibu
    FROM siswa WHERE jurusan='$jur' ORDER BY kelas ASC, nilai_btq DESC, nama_lengkap ASC");

$siswa_all = [];
while ($s = mysqli_fetch_assoc($res)) { $siswa_all[] = $s; }

if (empty($siswa_all)) {
    show_error_page("Tidak Ada Data", "Tidak ada data siswa untuk jurusan " . htmlspecialchars($jur_raw) . ".");
}

// Grup per kelas
$per_kelas = [];
foreach ($siswa_all as $s) {
    $k = !empty($s['kelas']) ? $s['kelas'] : 'BELUM DIKELAS';
    $per_kelas[$k][] = $s;
}

// Tulis data ke JSON temp
$json_data = json_encode([
    'jurusan'   => $jur_raw,
    'per_kelas' => $per_kelas,
    'generated' => date('d M Y H:i')
]);

$json_path = "/tmp/export_kelas_{$jur_raw}.json";
file_put_contents($json_path, $json_data);

// Jalankan Python untuk generate Excel
$script = <<<'PYEOF'
import sys, json, os
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

json_path = sys.argv[1]
output_path = sys.argv[2]

with open(json_path, 'r') as f:
    data = json.load(f)

jur       = data['jurusan']
per_kelas = data['per_kelas']
generated = data['generated']

wb = Workbook()
wb.remove(wb.active)  # hapus sheet default

# Warna header
HDR_FILL   = PatternFill('solid', start_color='1E3A5F', end_color='1E3A5F')
HDR_FONT   = Font(name='Arial', bold=True, color='FFFFFF', size=10)
ALT_FILL   = PatternFill('solid', start_color='EBF5FB', end_color='EBF5FB')
L_FILL     = PatternFill('solid', start_color='DBEAFE', end_color='DBEAFE')
P_FILL     = PatternFill('solid', start_color='F3E8FF', end_color='F3E8FF')
THIN       = Border(
    left=Side(style='thin', color='CCCCCC'),
    right=Side(style='thin', color='CCCCCC'),
    top=Side(style='thin', color='CCCCCC'),
    bottom=Side(style='thin', color='CCCCCC')
)

COLS = ['No', 'ID Pendaftaran', 'Nama Lengkap', 'JK', 'Nilai BTQ', 'Grade',
        'NISN', 'NIK', 'Tempat Lahir', 'Tgl Lahir', 'No HP',
        'Asal Sekolah', 'Alamat', 'RT', 'RW', 'Kelurahan', 'Kecamatan',
        'Nama Ayah', 'Nama Ibu', 'Status']
COL_WIDTHS = [5, 16, 30, 6, 10, 8, 14, 20, 18, 14, 16, 30, 30, 6, 6, 18, 18, 20, 20, 18]

def btq_grade(val):
    v = int(val) if str(val).isdigit() else 0
    if v >= 80: return 'A'
    if v >= 65: return 'B'
    if v >= 50: return 'C'
    if v > 0:   return 'D'
    return '-'

for nama_kelas, siswa_list in per_kelas.items():
    safe_name = nama_kelas.replace('/', '-')[:31]
    ws = wb.create_sheet(title=safe_name)

    # JUDUL
    ws.merge_cells('A1:T1')
    ws['A1'] = f'DATA SISWA KELAS {nama_kelas} — SMK PASUNDAN 2 BANDUNG'
    ws['A1'].font = Font(name='Arial', bold=True, size=13, color='1E3A5F')
    ws['A1'].alignment = Alignment(horizontal='center')

    ws.merge_cells('A2:T2')
    ws['A2'] = f'Jurusan: {jur}  |  Total: {len(siswa_list)} Siswa  |  Dibuat: {generated}'
    ws['A2'].font = Font(name='Arial', size=10, color='555555')
    ws['A2'].alignment = Alignment(horizontal='center')
    ws.row_dimensions[3].height = 6  # spasi

    # HEADER
    for ci, (col_name, width) in enumerate(zip(COLS, COL_WIDTHS), start=1):
        cell = ws.cell(row=4, column=ci, value=col_name)
        cell.font = HDR_FONT
        cell.fill = HDR_FILL
        cell.alignment = Alignment(horizontal='center', vertical='center', wrap_text=True)
        cell.border = THIN
        ws.column_dimensions[get_column_letter(ci)].width = width
    ws.row_dimensions[4].height = 30

    # DATA ROWS
    for ri, s in enumerate(siswa_list, start=5):
        jk = (s.get('jenis_kelamin') or '').upper()
        nb = s.get('nilai_btq') or 0
        row_fill = L_FILL if jk == 'LAKI-LAKI' else (P_FILL if jk == 'PEREMPUAN' else (ALT_FILL if ri % 2 == 0 else None))

        vals = [
            ri - 4,
            s.get('id_pendaftaran', ''),
            (s.get('nama_lengkap', '') or '').upper(),
            'L' if jk == 'LAKI-LAKI' else ('P' if jk == 'PEREMPUAN' else '?'),
            nb,
            btq_grade(nb),
            s.get('nisn', '') or '',
            s.get('nik', '') or '',
            (s.get('tempat_lahir', '') or '').upper(),
            s.get('tanggal_lahir', '') or '',
            s.get('no_hp', '') or '',
            (s.get('asal_sekolah', '') or '').upper(),
            (s.get('alamat', '') or '').upper(),
            s.get('rt', '') or '',
            s.get('rw', '') or '',
            (s.get('kelurahan', '') or '').upper(),
            (s.get('kecamatan', '') or '').upper(),
            (s.get('nama_ayah', '') or '').upper(),
            (s.get('nama_ibu', '') or '').upper(),
            s.get('status_siswa', '') or 'BELUM DAFTAR ULANG'
        ]
        for ci, val in enumerate(vals, start=1):
            cell = ws.cell(row=ri, column=ci, value=val)
            cell.font = Font(name='Arial', size=9)
            cell.border = THIN
            cell.alignment = Alignment(horizontal='center' if ci in [1,4,5,6,13,14] else 'left', vertical='center')
            if row_fill:
                cell.fill = row_fill

        ws.row_dimensions[ri].height = 18

    # FREEZE
    ws.freeze_panes = 'A5'
    ws.auto_filter.ref = f'A4:{get_column_letter(len(COLS))}4'

# SHEET REKAP SEMUA KELAS
ws_rekap = wb.create_sheet(title='REKAP', index=0)
ws_rekap.merge_cells('A1:G1')
ws_rekap['A1'] = f'REKAP PENGKELASAN — {jur} — SMK PASUNDAN 2 BANDUNG'
ws_rekap['A1'].font = Font(name='Arial', bold=True, size=13, color='1E3A5F')
ws_rekap['A1'].alignment = Alignment(horizontal='center')

rekap_headers = ['Kelas', 'Total', 'Laki-Laki', 'Perempuan', '% L', '% P', 'Avg BTQ']
for ci, h in enumerate(rekap_headers, start=1):
    cell = ws_rekap.cell(row=3, column=ci, value=h)
    cell.font = HDR_FONT
    cell.fill = HDR_FILL
    cell.alignment = Alignment(horizontal='center')
    cell.border = THIN
    ws_rekap.column_dimensions[get_column_letter(ci)].width = 14

ri = 4
for nama_kelas, siswa_list in per_kelas.items():
    total = len(siswa_list)
    ll = sum(1 for s in siswa_list if (s.get('jenis_kelamin') or '').upper() == 'LAKI-LAKI')
    pp = sum(1 for s in siswa_list if (s.get('jenis_kelamin') or '').upper() == 'PEREMPUAN')
    avg_btq = round(sum(int(s.get('nilai_btq') or 0) for s in siswa_list) / total, 1) if total else 0
    pct_l = round(ll / total * 100, 1) if total else 0
    pct_p = round(pp / total * 100, 1) if total else 0

    vals = [nama_kelas, total, ll, pp, f'{pct_l}%', f'{pct_p}%', avg_btq]
    for ci, val in enumerate(vals, start=1):
        cell = ws_rekap.cell(row=ri, column=ci, value=val)
        cell.font = Font(name='Arial', size=10)
        cell.border = THIN
        cell.alignment = Alignment(horizontal='center')
        if ri % 2 == 0:
            cell.fill = ALT_FILL
    ri += 1

# TOTAL ROW
for ci in range(1, 8):
    ws_rekap.cell(row=ri, column=ci).border = THIN
ws_rekap.cell(row=ri, column=1).value = 'TOTAL'
ws_rekap.cell(row=ri, column=2).value = f'=SUM(B4:B{ri-1})'
ws_rekap.cell(row=ri, column=3).value = f'=SUM(C4:C{ri-1})'
ws_rekap.cell(row=ri, column=4).value = f'=SUM(D4:D{ri-1})'
for ci in range(1, 8):
    ws_rekap.cell(row=ri, column=ci).font = Font(name='Arial', bold=True, size=10)
    ws_rekap.cell(row=ri, column=ci).fill = PatternFill('solid', start_color='1E3A5F', end_color='1E3A5F')
    ws_rekap.cell(row=ri, column=ci).font = Font(name='Arial', bold=True, size=10, color='FFFFFF')

ws_rekap.freeze_panes = 'A4'

wb.save(output_path)
print("OK")
PYEOF;

$script_path = "/tmp/export_kelas_gen.py";
file_put_contents($script_path, $script);

$output_path = "/tmp/Pengkelasan_{$jur_raw}_" . date('Ymd_His') . ".xlsx";
$result = shell_exec("python3 $script_path '$json_path' '$output_path' 2>&1");

if (!file_exists($output_path)) {
    show_error_page("Gagal Generate Excel", "Python script gagal menghasilkan file. Detail: " . htmlspecialchars($result));
}

// Kirim sebagai download
$filename = "Pengkelasan_{$jur_raw}_" . date('d-m-Y') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Content-Length: ' . filesize($output_path));
header('Cache-Control: must-revalidate');
readfile($output_path);
unlink($output_path);
unlink($json_path);
exit();
