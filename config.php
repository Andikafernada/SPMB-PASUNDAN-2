<?php
/**
 * KONFIGURASI SISTEM SPMB
 * SMK Pasundan 2 Bandung
 * Updated: 2026-06-10 - Credentials moved to .env file
 */

// ==========================================
// LOAD ENVIRONMENT VARIABLES
// ==========================================
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (trim($line) === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $val) = array_map('trim', explode('=', $line, 2));
        // Remove surrounding quotes if present
        $val = trim($val, '"\'');
        $_ENV[$key] = $val;
        putenv("$key=$val");
    }
}

// ==========================================
// SESSION SECURITY HARDENING (ADAPTIF)
// ==========================================
// Deteksi apakah koneksi aman (HTTPS) dari web server asli, proxy, atau Cloudflare
$is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $is_https ? 1 : 0); // OTO-DETEKSI HTTPS (DOMAIN) vs HTTP (IP LOKAL)
ini_set('session.cookie_samesite', 'Lax'); // Lax agar tidak memblokir perpindahan IP Lokal/Domain
ini_set('session.gc_maxlifetime', 1800); // 30 minutes

// Start session with strict settings (Adaptif ke current host)
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'use_strict_mode' => 1,
        'use_trans_sid' => 0,
    ]);
}

// ==========================================
// CSRF TOKEN GENERATION
// ==========================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ==========================================
// RATE LIMITING HELPERS
// ==========================================
function check_login_attempts($conn, $username) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();
    $window = $now - 900; // 15 minutes window

    // Clean old attempts
    mysqli_query($conn, "DELETE FROM login_attempts WHERE attempt_time < FROM_UNIXTIME($window)");

    // Count recent attempts
    $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM login_attempts
                                    WHERE ip_address='$ip' AND attempt_time > FROM_UNIXTIME($window)");
    $row = mysqli_fetch_assoc($result);

    return $row['cnt'] < 5; // Allow max 5 attempts in 15 minutes
}

function record_login_attempt($conn, $username, $success = false) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $status = $success ? 'success' : 'failed';
    mysqli_query($conn, "INSERT INTO login_attempts (ip_address, username, status, attempt_time)
                        VALUES ('$ip', '$username', '$status', NOW())");
}

// ==========================================
// IP RESTRICTION FOR ADMIN PAGES
// ==========================================
/**
 * Check if current IP is allowed for admin access
 * Updated 2026-06-10: IP restriction DISABLED — allow all IPs for flexibility
 * @return bool
 */
function is_admin_ip_allowed() {
    return true; // Semua IP diizinkan
}

/**
 * Get client IP address
 * @return string
 */
function get_client_ip() {
    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (strpos($client_ip, ',') !== false) {
        $client_ip = trim(explode(',', $client_ip)[0]);
    }
    return $client_ip;
}

/**
 * Require admin IP restriction - call this at top of admin pages
 * Will show error page and exit if IP is not allowed
 */
function require_admin_ip() {
    if (!is_admin_ip_allowed()) {
        http_response_code(403);
        echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak | SPMB SMK Pasundan 2</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #020617; color: white; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .container { text-align: center; padding: 40px; }
        .icon { font-size: 80px; margin-bottom: 20px; }
        h1 { color: #ef4444; font-size: 2rem; margin-bottom: 10px; }
        p { color: #94a3b8; margin-bottom: 20px; }
        .info { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); padding: 20px; border-radius: 12px; margin-top: 20px; }
        code { background: #1e293b; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚫</div>
        <h1>Akses Ditolak</h1>
        <p>Halaman ini hanya bisa diakses dari jaringan internal sekolah.</p>
        <div class="info">
            <p>IP Anda: <code>' . htmlspecialchars(get_client_ip()) . '</code></p>
            <p>Jika Anda administrator, silakan hubungi IT support.</p>
        </div>
    </div>
</body>
</html>';
        exit;
    }
}

// ==========================================
// DATABASE CONNECTION (from .env)
// ==========================================
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? 'db_sekolah';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Koneksi gagal: " . mysqli_connect_error()); }

// ==========================================
// DEEPSEEK AI CONFIGURATION (from .env, optional)
// ==========================================
if (!empty($_ENV['DEEPSEEK_API_KEY'])) {
    define('DEEPSEEK_API_KEY', $_ENV['DEEPSEEK_API_KEY']);
    define('DEEPSEEK_API_URL', $_ENV['DEEPSEEK_API_URL'] ?? 'https://api.deepseek.com/chat/completions');
}

// ==========================================
// N8N WEBHOOK CONFIG (from .env)
// ==========================================
define('N8N_WEBHOOK_URL', $_ENV['N8N_WEBHOOK_URL'] ?? 'http://172.16.0.180:5678/webhook/b3849cc7-fa0e-4e4a-aca4-54cc49f5325f');
define('N8N_API_KEY', $_ENV['N8N_API_KEY'] ?? '');

// ==========================================
// JURUSAN LIST (STANDAR RESMI SMK PASUNDAN 2)
// ==========================================
$jurusan_list = [
    'TPM' => 'Teknik Pemesinan',
    'TKR' => 'Teknik Kendaraan Ringan',
    'TSM' => 'Teknik Sepeda Motor',
    'TKJ' => 'Teknik Komputer & Jaringan',
    'TAV' => 'Teknik Audio Video',
];

// Mapping nama lengkap ke kode jurusan
function get_kode_jurusan($nama) {
    $mapping = [
        'Teknik Pemesinan' => 'TPM',
        'Teknik Kendaraan Ringan' => 'TKR',
        'Teknik Sepeda Motor' => 'TSM',
        'Teknik Komputer & Jaringan' => 'TKJ',
        'Teknik Audio Video' => 'TAV',
    ];
    return $mapping[$nama] ?? $nama;
}

// ==========================================
// GELOMBANG / BIAYA PENDAFTARAN
// ==========================================
$gelombang_list = [
    1 => ['nama' => 'Gelombang 1', 'biaya' => 150000, 'tgl_mulai' => '2026-03-01', 'tgl_selesai' => '2026-03-31'],
    2 => ['nama' => 'Gelombang 2', 'biaya' => 175000, 'tgl_mulai' => '2026-04-01', 'tgl_selesai' => '2026-04-30'],
    3 => ['nama' => 'Gelombang 3', 'biaya' => 200000, 'tgl_mulai' => '2026-05-01', 'tgl_selesai' => '2026-05-31'],
];

function get_gelombang_aktif($conn) {
    $today = date('Y-m-d');
    $result = mysqli_query($conn, "SELECT * FROM gelombang
                                   WHERE '$today' BETWEEN tgl_mulai AND tgl_selesai AND aktif = TRUE
                                   LIMIT 1");
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// ==========================================
// HELPERS: ERROR HANDLING & REDIRECT
// Updated: 2026-06-10 - Standardized error pages
// ==========================================

/**
 * Tampilkan error page styled dan exit
 */
function show_error_page($title, $message, $code = 500) {
    http_response_code($code);
    echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error | SPMB SMK Pasundan 2</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #020617; color: white; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .box { max-width: 420px; text-align: center; }
        .icon { font-size: 64px; margin-bottom: 16px; }
        h1 { color: #ef4444; font-size: 1.5rem; margin-bottom: 12px; }
        p { color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px; line-height: 1.6; }
        .code { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); padding: 10px 20px; border-radius: 12px; font-family: monospace; font-size: 0.8rem; color: #f87171; margin-top: 10px; }
        .btn { display: inline-block; padding: 10px 24px; background: #1e293b; color: #94a3b8; text-decoration: none; border-radius: 10px; font-size: 0.85rem; margin-top: 16px; border: 1px solid #334155; }
        .btn:hover { background: #334155; color: white; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">⚠️</div>
        <h1>' . htmlspecialchars($title) . '</h1>
        <p>' . htmlspecialchars($message) . '</p>
        <div class="code">Error Code: ' . $code . '</div>
        <a href="index.php" class="btn">← Kembali ke Dashboard</a>
    </div>
</body>
</html>';
    exit;
}

/**
 * Redirect dengan flash message
 */
function redirect_with_status($url, $status) {
    header("Location: $url?status=$status");
    exit;
}
?>
