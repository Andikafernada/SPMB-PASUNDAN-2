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
// COMPOSER AUTOLOADER (mPDF & libraries)
// ==========================================
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
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
 * Updated 2026-06-16: IP restriction ENABLED for internal network only
 * @return bool
 */
function is_admin_ip_allowed() {
    // Allow only internal network and localhost
    $allowed_ips = [
        '127.0.0.1',
        '::1',
        '192.168.',
        '10.',
        '172.16.',
        '172.17.',
        '172.18.',
        '172.19.',
    ];
    $client_ip = get_client_ip();
    foreach ($allowed_ips as $ip) {
        if (strpos($client_ip, $ip) === 0) {
            return true;
        }
    }
    return false; // Default: deny external access
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

// ============================================================
// WHATSAPP TEMPLATE SYSTEM
// Functions for rendering and sending templated WA messages
// ============================================================

/**
 * Render template dengan placeholder
 * @param string $template Template dengan placeholder {PLACEHOLDER}
 * @param array $data Associative array placeholder => nilai
 * @return string Template yang sudah dirender
 */
function render_wa_template($template, $data) {
    $result = $template;
    foreach ($data as $key => $value) {
        // Handle case-insensitive placeholders
        $result = str_replace('{' . strtoupper($key) . '}', $value ?? '', $result);
        $result = str_replace('{' . $key . '}', $value ?? '', $result);
    }
    // Convert literal \n escape sequences to actual newlines
    $result = str_replace('\\n', "\n", $result);
    return $result;
}

/**
 * Load template dari database
 * @param mysqli $conn Koneksi database
 * @param string $kode_template Kode template
 * @param bool $active_only Hanya yang aktif
 * @return array|null Data template atau null jika tidak ditemukan
 */
function load_wa_template($conn, $kode_template, $active_only = true) {
    $where = $active_only ? "AND is_active = TRUE" : "";
    $sql = "SELECT * FROM wa_templates WHERE kode_template = ? $where LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, "s", $kode_template);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}

/**
 * Get all templates (optionally filtered by jenis)
 * @param mysqli $conn Koneksi database
 * @param string|null $jenis Filter by jenis, null for all
 * @param bool $active_only Hanya yang aktif
 * @return array List of templates
 */
function get_all_wa_templates($conn, $jenis = null, $active_only = false) {
    $where = "";
    $params = [];
    $types = "";
    
    if ($jenis) {
        $where .= " AND jenis = ?";
        $params[] = $jenis;
        $types .= "s";
    }
    if ($active_only) {
        $where .= " AND is_active = TRUE";
    }
    
    if (empty($where)) {
        $sql = "SELECT * FROM wa_templates ORDER BY jenis, nama_template";
    } else {
        $sql = "SELECT * FROM wa_templates WHERE 1=1 $where ORDER BY jenis, nama_template";
    }
    
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }
    
    $templates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $templates[] = $row;
    }
    return $templates;
}

/**
 * Format nomor HP ke format WhatsApp internasional
 * @param string $no_hp Nomor HP
 * @return string Nomor HP format 62xxx
 */
function format_wa_number($no_hp) {
    $no_hp = preg_replace('/[^0-9]/', '', $no_hp);
    if (substr($no_hp, 0, 1) == '0') {
        $no_hp = '62' . substr($no_hp, 1);
    }
    // Ensure starts with 62
    if (substr($no_hp, 0, 2) != '62') {
        $no_hp = '62' . $no_hp;
    }
    return $no_hp;
}

/**
 * Kirim pesan WA via EVO API langsung (bypass N8N)
 * @param mysqli $conn Koneksi database
 * @param string $kode_template Kode template
 * @param array $payload Data untuk placeholder
 * @param string $no_hp Nomor HP tujuan
 * @return bool True jika sukses
 */
function kirim_wa_template($conn, $kode_template, $payload, $no_hp) {
    $template = load_wa_template($conn, $kode_template);
    if (!$template) {
        error_log("WA Template not found: $kode_template");
        return false;
    }

    // Render template dengan data
    $rendered_message = render_wa_template($template['template_text'], $payload);

    // Pastikan UTF-8 encoding
    $rendered_message = mb_convert_encoding($rendered_message, 'UTF-8', 'UTF-8');

    // =============================================
    // KIRIM VIA EVO API LANGSUNG (tanpa N8N)
    // =============================================
    // WAJIB: Set environment variables untuk production
    $evo_instance = $_ENV['EVO_INSTANCE'] ?? '';
    $evo_apikey = $_ENV['EVO_API_KEY'] ?? '';
    $evo_base_url = $_ENV['EVO_BASE_URL'] ?? '';

    // Jika tidak ada konfigurasi, skip pengiriman
    if (empty($evo_instance) || empty($evo_apikey) || empty($evo_base_url)) {
        error_log("EVO API: Configuration missing - WA not sent to $no_hp");
        return false;
    }

    $evo_url = rtrim($evo_base_url, '/') . "/message/sendText/$evo_instance";

    // Format Evolution API v2
    $evo_data = [
        'number' => $no_hp,
        'textMessage' => [
            'text' => $rendered_message
        ]
    ];

    $ch = curl_init($evo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($evo_data, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8',
        'apikey: ' . $evo_apikey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Log untuk debugging
    if ($http_code == 200 || $http_code == 201) {
        error_log("WA Sent via EVO: $no_hp - Template: $kode_template");
        return true;
    } else {
        error_log("WA FAILED via EVO: $no_hp - HTTP: $http_code - Error: $curl_error - Response: $response");
        return false;
    }
}

/**
 * Preview template dengan sample data
 * @param string $template_text Template text
 * @param string $jenis Jenis template untuk sample data
 * @return string Rendered preview
 */
function preview_wa_template($template_text, $jenis = 'acc') {
    $sample_data = [
        'acc' => [
            'nama' => 'Budi Santoso',
            'id_daftar' => 'SPMB26-001',
            'sekolah' => 'SMP Negeri 1 Bandung',
            'jurusan' => 'Teknik Kendaraan Ringan',
            'admin' => 'Admin Sistem',
            'tanggal' => '16/06/2026 14:30',
            'gelombang' => 'Gelombang 1',
            'biaya' => '150.000'
        ],
        'daftar_ulang' => [
            'nama' => 'Ani Wijaya',
            'id_daftar' => 'SPMB26-002',
            'admin' => 'Petugas TU',
            'tanggal' => '16/06/2026 15:00'
        ],
        'pindah_jurusan' => [
            'nama' => 'Dewi Lestari',
            'jurusan_lama' => 'TKR',
            'jurusan_baru' => 'TPM',
            'alasan' => 'Minat dan bakat di bidang permesinan',
            'admin' => 'Tim Database',
            'tanggal' => '16/06/2026 10:00'
        ],
        'cabut' => [
            'nama' => 'Eko Prasetyo',
            'alasan' => 'Memilih sekolah lain',
            'admin' => 'Tim Database',
            'tanggal' => '16/06/2026 09:00'
        ],
        'reminder' => [
            'nama' => 'Fajar Nugroho',
            'gelombang' => 'Gelombang 2',
            'biaya' => '175.000',
            'admin' => 'Admin'
        ]
    ];
    
    $data = $sample_data[$jenis] ?? $sample_data['acc'];
    return render_wa_template($template_text, $data);
}

/**
 * Get placeholder list untuk UI helper
 * @return array List of available placeholders with descriptions
 */
function get_wa_placeholder_list() {
    return [
        ['key' => 'NAMA', 'label' => 'Nama Lengkap', 'desc' => 'Nama siswa'],
        ['key' => 'ID_DAFTAR', 'label' => 'ID Pendaftaran', 'desc' => 'ID registrasi (SPMB26-xxx)'],
        ['key' => 'JURUSAN', 'label' => 'Jurusan', 'desc' => 'Jurusan yang dipilih'],
        ['key' => 'JURUSAN_BARU', 'label' => 'Jurusan Baru', 'desc' => 'Jurusan tujuan pindah'],
        ['key' => 'JURUSAN_LAMA', 'label' => 'Jurusan Lama', 'desc' => 'Jurusan asal pindah'],
        ['key' => 'ALASAN', 'label' => 'Alasan', 'desc' => 'Alasan perubahan'],
        ['key' => 'ADMIN', 'label' => 'Admin/Petugas', 'desc' => 'Nama admin yang memproses'],
        ['key' => 'TANGGAL', 'label' => 'Tanggal', 'desc' => 'Tanggal kejadian'],
        ['key' => 'SEKOLAH', 'label' => 'Asal Sekolah', 'desc' => 'Sekolah asal siswa'],
        ['key' => 'NO_HP', 'label' => 'No HP', 'desc' => 'Nomor HP siswa'],
        ['key' => 'GELOMBANG', 'label' => 'Gelombang', 'desc' => 'Gelombang pendaftaran'],
        ['key' => 'BIAYA', 'label' => 'Biaya', 'desc' => 'Jumlah biaya pendaftaran']
    ];
}
