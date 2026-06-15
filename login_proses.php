<?php
/**
 * LOGIN PROCESSOR - SPMB SMK Pasundan 2 Bandung
 * Updated: 2026-06-09 - Security Hardening
 */

include 'config.php';

// ==========================================
// RATE LIMITING CHECK
// ==========================================
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$now = time();
$window = 900; // 15 minutes

// Clean old attempts
mysqli_query($conn, "DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");

// Count recent failed attempts from this IP
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM login_attempts
                                WHERE ip_address='$ip'
                                AND status='failed'
                                AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$row = mysqli_fetch_assoc($result);

if ($row['cnt'] >= 5) {
    // Too many attempts - block
    header("Location: panitia/index.php?pesan=terblokir");
    exit;
}

// ==========================================
// CSRF VALIDATION
// ==========================================
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    // Log potential CSRF attack
    mysqli_query($conn, "INSERT INTO audit_log (action, details, ip_address)
                        VALUES ('csrf_failed', 'Invalid CSRF token on login', '$ip')");
    header("Location: panitia/index.php?pesan=invalid");
    exit;
}

// ==========================================
// LOGIN ATTEMPT
// ==========================================
$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = $_POST['password'];

// Query cari user dengan prepared statement
$query = "SELECT * FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$login = mysqli_stmt_get_result($stmt);
$ketemu = mysqli_num_rows($login);

// Record this attempt (will update to success/failed below)
$attempt_recorded = false;

if ($ketemu > 0) {
    $r = mysqli_fetch_assoc($login);

    // ==========================================
    // PASSWORD VERIFICATION — bcrypt only
    // PLAIN TEXT FALLBACK SUDAH DIHAPUS (2026-06-10)
    // ==========================================
    $password_valid = password_verify($password, $r['password']);

    if ($password_valid) {
        // ==========================================
        // SUCCESSFUL LOGIN
        // ==========================================

        // Record successful login
        mysqli_query($conn, "INSERT INTO login_attempts (ip_address, username, status, attempt_time)
                            VALUES ('$ip', '$username', 'success', NOW())");

        // Audit log
        mysqli_query($conn, "INSERT INTO audit_log (user_id, username, action, details, ip_address)
                            VALUES (" . $r['id'] . ", '$username', 'login', 'User logged in', '$ip')");

        // REGENERATE SESSION ID (prevents session fixation)
        session_regenerate_id(true);

        // Set Session
        $_SESSION['id_user']  = $r['id'];
        $_SESSION['username'] = $r['username'];
        $_SESSION['nama']     = $r['nama_lengkap'];
        $_SESSION['role']     = $r['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $ip;

        // LOGIKA MULTI-LOGIN (Arahkan sesuai Role)
        if ($r['role'] == "pendaftaran") {
            header("Location: views/pendaftaran/index.php");
        }
        elseif ($r['role'] == "tu") {
            header("Location: views/tu/index.php");
        }
        elseif ($r['role'] == "database") {
            header("Location: views/database/index.php");
        }
        else {
            header("Location: panitia/index.php?pesan=gagal");
        }
        exit;
    } else {
        // ==========================================
        // WRONG PASSWORD
        // ==========================================
        mysqli_query($conn, "INSERT INTO login_attempts (ip_address, username, status, attempt_time)
                            VALUES ('$ip', '$username', 'failed', NOW())");
        header("Location: panitia/index.php?pesan=salah");
        exit;
    }
} else {
    // ==========================================
    // USER NOT FOUND
    // ==========================================
    mysqli_query($conn, "INSERT INTO login_attempts (ip_address, username, status, attempt_time)
                        VALUES ('$ip', '$username', 'failed', NOW())");
    header("Location: panitia/index.php?pesan=salah");
    exit;
}

// Tutup statement
mysqli_stmt_close($stmt);
?>