<?php
/**
 * PUBLIC STATUS CHECKER - SMK Pasundan 2 Bandung
 * Halaman untuk cek status pendaftaran
 */
include '../config.php';

$error = '';
$status_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = mysqli_real_escape_string($conn, $_POST['kode'] ?? '');
    if (empty($kode)) {
        $error = 'Masukkan kode billing atau nomor pendaftaran';
    } else {
        $sql = "SELECT * FROM siswa WHERE kode_billing = ? OR id_pendaftaran = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $kode, $kode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $status_data = mysqli_fetch_assoc($result);
        } else {
            $error = 'Data tidak ditemukan. Pastikan kode yang Anda masukkan benar.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon.svg">
    <link rel="apple-touch-icon" href="../favicon.svg">

    <title>Cek Status Pendaftaran - SMK Pasundan 2 Bandung</title>
    <meta name="description" content="Cek status pendaftaran siswa baru SMK Pasundan 2 Bandung">
    <meta name="robots" content="index, follow">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-heading { font-family: 'Outfit', sans-serif; }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="../index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                    <span class="text-white font-black text-xs">P2</span>
                </div>
                <div>
                    <div class="font-heading font-bold text-sm text-slate-900">SMK Pasundan 2</div>
                    <div class="text-[10px] text-slate-500">Bandung</div>
                </div>
            </a>
            <a href="../index.php" class="text-sm font-medium text-slate-600 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left mr-1"></i>Kembali
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-lg mx-auto px-4 py-12">
        <!-- Hero -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-search text-4xl text-blue-600"></i>
            </div>
            <h1 class="font-heading text-3xl font-black text-slate-900 mb-3">
                Cek Status Pendaftaran
            </h1>
            <p class="text-slate-600">
                Masukkan kode billing atau ID pendaftaran Anda untuk melihat status pendaftaran.
            </p>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-8">
            <form method="POST">
                <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        <i class="fas fa-key mr-1 text-slate-400"></i>
                        Kode Pendaftaran
                    </label>
                    <input type="text" name="kode" placeholder="Contoh: SPMB26-001"
                           class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                           required>
                </div>

                <button type="submit" class="w-full px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all hover:-translate-y-0.5">
                    <i class="fas fa-search mr-2"></i>Cari
                </button>
            </form>
        </div>

        <!-- Result -->
        <?php if ($status_data): ?>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user text-xl"></i>
                    </div>
                    <div>
                        <div class="font-bold"><?= htmlspecialchars($status_data['nama_lengkap']) ?></div>
                        <div class="text-sm text-blue-100"><?= htmlspecialchars($status_data['id_pendaftaran'] ?? '-') ?></div>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="p-4 bg-slate-50 rounded-xl text-center">
                        <div class="text-xs text-slate-500 mb-1">Jurusan</div>
                        <div class="font-bold text-slate-900"><?= htmlspecialchars($status_data['jurusan'] ?? '-') ?></div>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl text-center">
                        <div class="text-xs text-slate-500 mb-1">Status Bayar</div>
                        <div class="font-bold <?= ($status_data['status_bayar'] ?? '') === 'LUNAS' ? 'text-emerald-600' : 'text-amber-600' ?>">
                            <?= $status_data['status_bayar'] ?? 'BELUM' ?>
                        </div>
                    </div>
                </div>

                <?php if (($status_data['status_bayar'] ?? '') === 'LUNAS'): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
                    <i class="fas fa-check-circle mr-2"></i>
                    Pembayaran sudah lunas. Silakan tunggu informasi selanjutnya.
                </div>
                <?php else: ?>
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-700 text-sm">
                    <i class="fas fa-clock mr-2"></i>
                    Pembayaran belum lunas. Segera lakukan pembayaran sesuai kode billing.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Info -->
        <div class="mt-8 text-center text-sm text-slate-500">
            <p>Butuh bantuan? <a href="https://wa.me/" target="_blank" class="text-blue-600 font-medium hover:underline">Hubungi sekolah</a></p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-sm text-slate-500 border-t border-slate-200 mt-12">
        &copy; <?= date('Y') ?> SMK Pasundan 2 Bandung
    </footer>
</body>
</html>
