<?php
include '../../config.php';
header('Content-Type: application/json');

// Pastikan input uppercase agar pencarian lebih akurat
$q = isset($_GET['q']) ? mysqli_real_escape_string($conn, strtoupper($_GET['q'])) : '';

// Gunakan DISTINCT agar nama sekolah yang sama tidak muncul berkali-kali
$query = "SELECT DISTINCT nama_sekolah as id, 
                 CONCAT(nama_sekolah, ' [', IFNULL(kecamatan, kabupaten_kota), ']') as text 
          FROM ref_sekolah 
          WHERE nama_sekolah LIKE '%$q%' 
          OR npsn LIKE '%$q%' 
          GROUP BY nama_sekolah -- Memastikan hanya 1 nama per sekolah yang muncul
          LIMIT 20";

$sql = mysqli_query($conn, $query);
$results = [];
while($row = mysqli_fetch_assoc($sql)) {
    $results[] = $row;
}
echo json_encode($results);
