<?php
/**
 * WA TEMPLATE MANAGER - CRUD Page
 * Manage WhatsApp message templates
 * FIX: Implementasi Prepared Statements untuk AJAX endpoints
 */
session_start();
include '../../config.php';

// Check session and role
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database', 'superuser', 'superuser1'])) {
    header("Location: ../../index.php");
    exit();
}

// ============================================================
// HANDLE AJAX ACTIONS (PREPARED STATEMENTS)
// ============================================================
if (isset($_GET['action']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    
    switch ($action) {
        case 'get':
            $id = intval($_POST['id'] ?? 0);
            $stmt = mysqli_prepare($conn, "SELECT * FROM wa_templates WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            echo json_encode(mysqli_fetch_assoc($result) ?: ['error' => 'Template not found']);
            mysqli_stmt_close($stmt);
            exit();
            
        case 'save':
            $id     = intval($_POST['id'] ?? 0);
            $kode   = trim($_POST['kode_template'] ?? '');
            $nama   = trim($_POST['nama_template'] ?? '');
            $jenis  = trim($_POST['jenis'] ?? 'acc');
            $text   = trim($_POST['template_text'] ?? '');
            $active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($id > 0) {
                // UPDATE
                $stmt = mysqli_prepare($conn, "UPDATE wa_templates SET kode_template=?, nama_template=?, jenis=?, template_text=?, is_active=?, updated_at=NOW() WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssssii", $kode, $nama, $jenis, $text, $active, $id);
            } else {
                // INSERT
                $stmt = mysqli_prepare($conn, "INSERT INTO wa_templates (kode_template, nama_template, jenis, template_text, is_active) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssi", $kode, $nama, $jenis, $text, $active);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Gagal menyimpan: ' . mysqli_error($conn)]);
            }
            mysqli_stmt_close($stmt);
            exit();
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $stmt = mysqli_prepare($conn, "DELETE FROM wa_templates WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Gagal menghapus template.']);
            }
            mysqli_stmt_close($stmt);
            exit();
            
        case 'preview':
            $text = $_POST['template_text'] ?? '';
            $jenis = $_POST['jenis'] ?? 'acc';
            $preview = render_wa_template($text, [
                'nama' => 'ANDIKA FERNANDA', 
                'id_daftar' => 'SPMB26-001',
                'sekolah' => 'SMPN 1 BANDUNG',
                'jurusan' => 'Teknik Komputer & Jaringan',
                'jurusan_baru' => 'Teknik Pemesinan',
                'jurusan_lama' => 'Teknik Sepeda Motor',
                'alasan' => 'Permintaan orang tua',
                'admin' => 'Admin Pendaftaran',
                'tanggal' => date('d/m/Y H:i'),
                'bulan' => date('F Y'),
                'gelombang' => 'Gelombang 1',
                'biaya' => 'Rp 150.000'
            ]);
            echo json_encode(['preview' => $preview]);
            exit();
    }
}

// (BAGIAN TAMPILAN HTML KE BAWAH TETAP SAMA)
$templates_query = "SELECT * FROM wa_templates ORDER BY jenis ASC, nama_template ASC";
$templates_result = mysqli_query($conn, $templates_query);
$placeholders = get_wa_placeholder_list(); // Dari config.php
?>
