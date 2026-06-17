<?php
/**
 * API UNDO - Handle undo requests
 */
session_start();
include '../../config.php';

// Check session
if (!isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'undo_create':
        // Delete the newly created student
        $id = intval($data['id'] ?? 0);
        if ($id > 0) {
            // Only allow if status is still BELUM (not ACC'd yet)
            $check = mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE id_siswa = $id AND status_bayar = 'BELUM'");
            if (mysqli_num_rows($check) > 0) {
                mysqli_query($conn, "DELETE FROM siswa WHERE id_siswa = $id");
                echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus']);
            } else {
                echo json_encode(['error' => 'Data tidak dapat dihapus (sudah di-ACC)']);
            }
        } else {
            echo json_encode(['error' => 'ID tidak valid']);
        }
        break;

    case 'undo_delete':
        // Restore deleted data (basic implementation)
        $studentData = $data['data'] ?? [];
        if (!empty($studentData)) {
            // Would need to implement full restore logic
            echo json_encode(['success' => true, 'message' => 'Data restore belum diimplementasi']);
        } else {
            echo json_encode(['error' => 'Data tidak ditemukan']);
        }
        break;

    case 'get_stack':
        // Return undo stack for display
        $stack = $_SESSION['undo_stack'] ?? [];
        echo json_encode(['stack' => $stack]);
        break;

    case 'clear_stack':
        // Clear undo stack
        $_SESSION['undo_stack'] = [];
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
