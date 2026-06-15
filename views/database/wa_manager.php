<?php
/**
 * WA MANAGER - Admin Page
 * IP Restricted: Only accessible from internal network
 */
include '../../config.php';

// Check admin IP restriction
require_admin_ip();

// Check session
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'database') {
    header("Location: ../../panitia/index.php"); exit();
}

$instance = $_ENV['EVO_INSTANCE'] ?? 'Pasundan2';
$api_key  = $_ENV['EVO_API_KEY'] ?? '';
$base_url = $_ENV['EVO_BASE_URL'] ?? 'http://172.16.0.180:8080';

function callEvo($url, $method, $key, $data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $key", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return ['_curl_error' => $err];
    return json_decode($res, true) ?? [];
}

// Aksi
if (isset($_GET['force_reset'])) {
    callEvo("$base_url/instance/logout/$instance", "DELETE", $api_key);
    callEvo("$base_url/instance/delete/$instance", "DELETE", $api_key);
    callEvo("$base_url/instance/create", "POST", $api_key, ["instanceName" => $instance, "token" => $api_key, "qrcode" => true]);
    header("Location: wa_manager.php?msg=reset_ok"); exit();
}

$status_raw = callEvo("$base_url/instance/connectionState/$instance", "GET", $api_key);
$api_down   = isset($status_raw['_curl_error']);
$is_open    = !$api_down && (($status_raw['instance']['state'] ?? '') === 'open' || ($status_raw['state'] ?? '') === 'open');

$qr_src = "";
if (!$is_open && !$api_down) {
    $qr_res = callEvo("$base_url/instance/connect/$instance", "GET", $api_key);
    $qr_src = $qr_res['base64'] ?? $qr_res['code'] ?? '';
}

$total_siswa_wa = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE no_hp IS NOT NULL AND no_hp != ''"));
$total_du_wa    = mysqli_num_rows(mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE status_siswa='SUDAH DAFTAR ULANG' AND no_hp IS NOT NULL"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>WA MANAGER | SMK PASUNDAN 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #020617; font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="p-6 lg:p-12">

    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-0 right-0 w-[40%] h-[40%] rounded-full bg-emerald-900/10 blur-[150px]"></div>
    </div>

    <div class="max-w-4xl mx-auto relative z-10">
        <div class="flex items-center justify-between mb-8">
            <h1 class="font-outfit text-3xl font-black text-white">WA Control Center</h1>
            <a href="index.php" class="px-5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition-all"><i class="fas fa-arrow-left mr-2"></i> Kembali</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-slate-900/40 backdrop-blur-xl border border-slate-800/60 rounded-[2rem] p-8 shadow-2xl flex flex-col items-center text-center">
                <div class="w-20 h-20 bg-slate-950 rounded-3xl flex items-center justify-center text-4xl mb-4 border border-slate-800">
                    <i class="fab fa-whatsapp <?= $is_open ? 'text-emerald-400' : 'text-slate-600' ?>"></i>
                </div>
                
                <h2 class="text-xl font-outfit font-black text-white mb-2">Instance: <?= $instance ?></h2>
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-950 border border-slate-800 mb-6">
                    <span class="w-2 h-2 rounded-full <?= $is_open ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' ?>"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-300"><?= $is_open ? 'TERHUBUNG' : 'TERPUTUS' ?></span>
                </div>

                <?php if (!$is_open && !$api_down && $qr_src): ?>
                    <div class="bg-white p-4 rounded-2xl mb-6"><img src="<?= $qr_src ?>" class="w-48 h-48"></div>
                    <p class="text-xs font-bold text-slate-400 mb-6">Scan dengan WhatsApp Anda</p>
                <?php endif; ?>

                <div class="flex gap-3">
                    <a href="wa_manager.php" class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition-all"><i class="fas fa-sync mr-2"></i> Refresh</a>
                    <a href="?force_reset=true" class="px-6 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-xl text-xs font-bold border border-red-500/20 transition-all"><i class="fas fa-bomb mr-2"></i> Reset Instance</a>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-slate-900/40 backdrop-blur-xl border border-slate-800/60 rounded-[2rem] p-6 shadow-xl">
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total WA Terdaftar</p>
                    <p class="text-3xl font-outfit font-black text-white"><?= $total_siswa_wa ?></p>
                </div>
                <div class="bg-emerald-900/10 backdrop-blur-xl border border-emerald-500/20 rounded-[2rem] p-6 shadow-xl">
                    <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Daftar Ulang (WA Sent)</p>
                    <p class="text-3xl font-outfit font-black text-emerald-400"><?= $total_du_wa ?></p>
                </div>
            </div>
        </div>

        <?php if ($is_open): ?>
        <div class="mt-6 bg-slate-900/40 backdrop-blur-xl border border-slate-800/60 rounded-[2rem] p-8 shadow-2xl">
            <h3 class="font-outfit text-lg font-black text-white mb-6">Test Kirim Pesan</h3>
            <form method="GET" action="wa_manager.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="test_wa" value="1">
                <input type="text" name="no_hp" placeholder="Nomor Tujuan (628...)" required class="bg-slate-950 border border-slate-800 rounded-xl p-4 text-sm font-bold text-white focus:border-sky-500 outline-none">
                <input type="text" name="pesan" placeholder="Isi pesan..." value="Test sistem berhasil ✅" class="bg-slate-950 border border-slate-800 rounded-xl p-4 text-sm font-bold text-white focus:border-sky-500 outline-none">
                <button type="submit" class="md:col-span-2 bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-black text-xs uppercase tracking-widest rounded-xl p-4 hover:shadow-lg hover:shadow-emerald-500/20 transition-all">
                    Kirim Sekarang
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>