<?php
/**
 * INFO PENDAFTARAN - SPMB SMK Pasundan 2 Bandung
 * Halaman informasi pendaftaran offline
 * Updated: 2026-06-10
 */

include '../config.php';
$wa = $_ENV['WA_CONTACT'] ?? '6283817203455';
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

    <title>Informasi Pendaftaran - SPMB SMK Pasundan 2 Bandung <?= date('Y'); ?></title>
    <meta name="description" content="Informasi Pendaftaran SPMB SMK Pasundan 2 Bandung - Persyaratan dan jadwal pendaftaran">
    <meta name="robots" content="index, follow">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        h1, h2, .font-outfit { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="text-slate-700 min-h-screen">

    <!-- Navigation -->
    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-lg border-b border-slate-200 shadow-sm">
        <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="../index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center">
                    <span class="text-white font-outfit font-black text-sm">P2</span>
                </div>
                <span class="font-outfit font-bold text-slate-900">SPMB SMK Pasundan 2</span>
            </a>
            <a href="https://wa.me/<?= $wa ?>" target="_blank" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">💬 Hubungi Kami</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-12 pb-16 px-6">
        <div class="max-w-2xl mx-auto">

            <!-- Header -->
            <div class="text-center mb-10">
                <div class="text-6xl mb-4">🏫</div>
                <h1 class="text-4xl font-outfit font-black text-slate-900 mb-3">Pendaftaran Offline</h1>
                <p class="text-slate-500 text-lg">Pendaftaran dilaksanakan langsung di sekolah dengan membawa dokumen yang diperlukan.</p>
            </div>

            <!-- Info Card -->
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 text-white">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📍</span>
                        <div>
                            <div class="font-bold text-sm">Lokasi Pendaftaran</div>
                            <div class="text-blue-100 text-sm">Kantor Sekolah SMK Pasundan 2 Bandung</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Persyaratan -->
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden mb-8">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📋</span>
                        <div class="font-outfit font-bold text-slate-900 text-lg">Dokumen yang Wajib Dibawa</div>
                    </div>
                </div>
                <div class="p-6">
                    <ul class="space-y-4">
                        <li class="flex items-start gap-4 p-4 bg-slate-50 rounded-xl">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-xl">1️⃣</span>
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">Fotocopy Kartu Keluarga (FC KK)</div>
                                <div class="text-slate-500 text-sm mt-1">Silakan fotocopy Kartu Keluarga sebanyak 2 lembar</div>
                            </div>
                        </li>
                        <li class="flex items-start gap-4 p-4 bg-slate-50 rounded-xl">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-xl">2️⃣</span>
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">Fotocopy Ijazah SD</div>
                                <div class="text-slate-500 text-sm mt-1">Fotocopy ijazah SD/Sederajat sebanyak 2 lembar</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Info Tambahan -->
            <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-6 mb-8">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">💡</span>
                    <div>
                        <div class="font-bold text-amber-700 mb-2">Informasi Penting</div>
                        <ul class="text-amber-700 text-sm space-y-2">
                            <li>• Pendaftaran dibuka setiap hari kerja (Senin - Jumat)</li>
                            <li>• Jam operasional: 08.00 - 15.00 WIB</li>
                            <li>• Datanglah tepat waktu dengan membawa dokumen lengkap</li>
                            <li>• Orang tua/wali diharapkan hadir bersama</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Biaya -->
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden mb-8">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">💰</span>
                        <div class="font-outfit font-bold text-slate-900 text-lg">Biaya Pendaftaran</div>
                    </div>
                </div>
                <div class="p-6">
                    <?php
                    $gel_list = [];
                    $rg = mysqli_query($conn, "SELECT * FROM gelombang ORDER BY biaya ASC");
                    while ($g = mysqli_fetch_assoc($rg)) $gel_list[] = $g;
                    ?>
<div class="space-y-3">
                        <?php foreach ($gel_list as $g): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                            <div class="font-semibold text-slate-700"><?= htmlspecialchars($g['nama']) ?></div>
                            <div class="font-outfit font-bold text-blue-600">Rp <?= number_format($g['biaya'], 0, ',', '.') ?></div>
                        </div>
                        <?php endforeach; ?>
</div>
                </div>
            </div>

            <!-- Jurusan -->
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden mb-8">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🎯</span>
                        <div class="font-outfit font-bold text-slate-900 text-lg">Jurusan yang Tersedia</div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-3">
                        <?php
                        $jurusan_options = [
                            ['kode' => 'TPM', 'nama' => 'Teknik Pemesinan', 'icon' => '⚙️'],
                            ['kode' => 'TKR', 'nama' => 'Teknik Kendaraan Ringan', 'icon' => '🚗'],
                            ['kode' => 'TSM', 'nama' => 'Teknik Sepeda Motor', 'icon' => '🏍️'],
                            ['kode' => 'TKJ', 'nama' => 'Teknik Komputer & Jaringan', 'icon' => '💻'],
                            ['kode' => 'TAV', 'nama' => 'Teknik Audio Video', 'icon' => '📡'],
                        ];
                        foreach ($jurusan_options as $jur):
                        ?>
                        <div class="p-4 bg-slate-50 rounded-xl text-center">
                            <div class="text-2xl mb-2"><?= $jur['icon'] ?></div>
                            <div class="font-bold text-slate-700 text-sm"><?= $jur['kode'] ?></div>
                            <div class="text-slate-500 text-xs mt-1"><?= $jur['nama'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <a href="https://wa.me/<?= $wa ?>?text=Halo%20saya%20tertarik%20pendaftaran%20di%20SMK%20Pasundan%202%20Bandung.%20Bisa%20beritahu%20syarat%20dan%20jadwal%20pendaftarannya%3F" target="_blank"
                   class="inline-flex items-center gap-3 px-8 py-4 bg-green-500 hover:bg-green-600 text-white font-bold text-lg rounded-2xl shadow-lg transition">
 💬 Hubungi Sekolah via WhatsApp
                </a>
                <p class="text-slate-400 text-sm mt-4">Klik tombol di atas untuk menanyakan jadwal dan informasi lebih lanjut</p>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-8 px-6">
        <div class="max-w-4xl mx-auto text-center text-sm">
            <p>© <?= date('Y') ?> SMK Pasundan 2 Bandung. Hak cipta dilindungi.</p>
        </div>
    </footer>

</body>
</html>
