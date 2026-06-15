<?php
session_start();
include '../../config.php';

if(!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database','superuser'])) {
    show_error_page("Akses Ditolak", "Halaman ini hanya untuk Tim Database.");
}

// Sesuaikan kriteria 'Gelombang 1' Bapak di bawah ini
// Contoh: pendaftar sebelum tanggal 1 April 2026
$res = mysqli_query($conn, "SELECT id_pendaftaran, nama_lengkap, asal_sekolah, no_hp, jurusan 
    FROM siswa 
    WHERE tgl_daftar < '2026-04-01 00:00:00' 
    ORDER BY id_pendaftaran ASC");

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Data_Gelombang_1.xls"');

echo "<table border='1'>";
echo "<tr>
        <th>ID DAFTAR</th>
        <th>NAMA LENGKAP</th>
        <th>ASAL SEKOLAH</th>
        <th>NO TELEPON</th>
        <th>JURUSAN</th>
      </tr>";

while($row = mysqli_fetch_assoc($res)) {
    echo "<tr>
            <td>".$row['id_pendaftaran']."</td>
            <td>".$row['nama_lengkap']."</td>
            <td>".$row['asal_sekolah']."</td>
            <td>'".$row['no_hp']."</td>
            <td>".$row['jurusan']."</td>
          </tr>";
}
echo "</table>";
?>
