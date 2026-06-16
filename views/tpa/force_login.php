<?php
/**
 * TPA FORCE LOGIN - Untuk Committee
 * SMK Pasundan 2 Bandung
 * Committee bisa login sebagai siswa untuk membuka/mengerjakan TPA
 */
include '../../config.php';

// Proteksi: harus login sebagai admin/committee
if (!isset($_SESSION['role'])) {
    header("Location: ../../login.php");
    exit();
}

$allowed_roles = ['pendaftaran', 'tu', 'database', 'superuser', 'user'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../login.php");
    exit();
}

// Handle force login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_login'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $id_siswa = (int)($_POST['id_siswa'] ?? 0);
        if ($id_siswa > 0) {
            $siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = $id_siswa"));
            if ($siswa) {
                // Set session TPA (seolah-olah siswa login)
                $_SESSION['tpa_login'] = true;
                $_SESSION['tpa_id_siswa'] = $siswa['id_siswa'];
                $_SESSION['tpa_nama'] = $siswa['nama_lengkap'];
                $_SESSION['tpa_jurusan'] = $siswa['jurusan'];
                $_SESSION['tpa_id_reg'] = $siswa['id_pendaftaran'];
                $_SESSION['tpa_admin_access'] = true; // Flag bahwa ini akses admin

                header("Location: index.php");
                exit();
            }
        }
    }
}

// Ambil semua siswa (tanpa filter pembayaran)
$siswa_query = mysqli_query($conn, "SELECT id_siswa, id_pendaftaran, nama_lengkap, jurusan, status_bayar, tpa_selesai, tpa_tanggal
                                     FROM siswa ORDER BY nama_lengkap");
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPA Force Login | SMK Pasundan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Space Grotesk', sans-serif; }
        .font-sora { font-family: 'Sora', sans-serif; }
        body { background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%); min-height: 100vh; }
    </style>
</head>
<body class="text-white">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-slate-900/90 backdrop-blur-xl border-b border-white/10">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="admin_hasil.php" class="text-slate-400 hover:text-white transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-secret text-white"></i>
                    </div>
                    <div>
                        <div class="font-sora font-bold">Force Login TPA</div>
                        <div class="text-xs text-slate-400">
                            Akses sebagai: <span class="text-red-400 font-bold uppercase"><?= $_SESSION['nama'] ?? $_SESSION['role'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <span class="px-3 py-1 bg-red-500/20 border border-red-500/30 rounded-lg text-xs font-bold text-red-400">
                    <i class="fas fa-shield-alt mr-1"></i> Committee Only
                </span>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <!-- Warning -->
        <div class="bg-red-500/10 border border-red-500/30 rounded-2xl p-6 mb-8">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-red-400 mb-2">⚠️ Peringatan Akses Committee</h3>
                    <ul class="text-sm text-slate-300 space-y-1">
                        <li>• Fitur ini hanya untuk membantu siswa yang mengalami kesulitan saat TPA</li>
                        <li>• Akses Anda akan dicatat dalam log sistem</li>
                        <li>• Jangan gunakan fitur ini untuk melihat hasil sebelum siswa mengerjakannya</li>
                        <li>• Reset TPA bisa dilakukan di halaman <a href="admin_hasil.php" class="text-indigo-400 underline">Hasil TPA</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="mb-6">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="search-input" placeholder="Ketik nama atau ID pendaftaran..."
                       class="w-full bg-slate-800/50 border border-white/10 rounded-xl pl-12 pr-4 py-4 text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none">
            </div>
        </div>

        <!-- Student List -->
        <div class="bg-slate-800/50 border border-white/10 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-white/10">
                <h3 class="font-bold flex items-center gap-2">
                    <i class="fas fa-list text-indigo-400"></i>
                    Daftar Siswa (<?= mysqli_num_rows($siswa_query) ?>)
                </h3>
            </div>
            <div class="divide-y divide-white/5 max-h-[500px] overflow-y-auto" id="student-list">
                <?php while ($siswa = mysqli_fetch_assoc($siswa_query)): ?>
                <div class="student-item p-4 hover:bg-white/5 transition flex items-center justify-between"
                     data-name="<?= strtolower($siswa['nama_lengkap']) ?>"
                     data-id="<?= strtolower($siswa['id_pendaftaran']) ?>">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/30 to-purple-500/30 flex items-center justify-center">
                            <i class="fas fa-user text-indigo-400"></i>
                        </div>
                        <div>
                            <div class="font-bold"><?= htmlspecialchars($siswa['nama_lengkap']) ?></div>
                            <div class="text-xs text-slate-400">
                                <?= htmlspecialchars($siswa['id_pendaftaran']) ?> •
                                <?= htmlspecialchars($siswa['jurusan']) ?>
                                <span class="ml-2 <?= $siswa['status_bayar'] === 'LUNAS' ? 'text-emerald-400' : 'text-amber-400' ?>">
                                    (<?= $siswa['status_bayar'] ?>)
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if ($siswa['tpa_selesai'] == 1): ?>
                        <span class="px-3 py-1 bg-emerald-500/20 rounded text-xs font-bold text-emerald-400">
                            <i class="fas fa-check mr-1"></i>Selesai
                        </span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-amber-500/20 rounded text-xs font-bold text-amber-400">
                            <i class="fas fa-clock mr-1"></i>Belum
                        </span>
                        <?php endif; ?>

                        <form method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="id_siswa" value="<?= $siswa['id_siswa'] ?>">
                            <button type="submit" name="force_login"
                                    class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 rounded-lg text-xs font-bold transition">
                                <i class="fas fa-external-link-alt mr-1"></i>Buka TPA
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Back -->
        <div class="mt-6 text-center">
            <a href="admin_hasil.php" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-800 hover:bg-slate-700 rounded-xl text-sm font-bold transition">
                <i class="fas fa-arrow-left"></i>Kembali ke Hasil TPA
            </a>
        </div>
    </main>

    <script>
        // Search functionality
        document.getElementById('search-input').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const items = document.querySelectorAll('.student-item');

            items.forEach(item => {
                const name = item.dataset.name;
                const id = item.dataset.id;

                if (name.includes(searchTerm) || id.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
