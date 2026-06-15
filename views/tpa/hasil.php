<?php
/**
 * TPA HASIL - SMK Pasundan 2 Bandung
 * Mobile-First Responsive Design - Education Theme
 */
include '../../config.php';

$id_siswa = (int)($_GET['id'] ?? 0);
if (!$id_siswa) {
    header("Location: login.php");
    exit();
}

$siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = $id_siswa"));
if (!$siswa) {
    header("Location: login.php");
    exit();
}

if ($siswa['tpa_selesai'] != 1) {
    header("Location: index.php");
    exit();
}

$total_soal = $siswa['tpa_jumlah_soal_verbal'] + $siswa['tpa_jumlah_soal_numerik'] + $siswa['tpa_jumlah_soal_logika'];
$total_benar = $siswa['tpa_benar_verbal'] + $siswa['tpa_benar_numerik'] + $siswa['tpa_benar_logika'];
$akurasi = $total_soal > 0 ? round(($total_benar / $total_soal) * 100) : 0;
$nilai = $siswa['tpa_nilai_total'];

$nilai_verbal = $siswa['tpa_jumlah_soal_verbal'] > 0 ? round(($siswa['tpa_benar_verbal'] / $siswa['tpa_jumlah_soal_verbal']) * 100) : 0;
$nilai_numerik = $siswa['tpa_jumlah_soal_numerik'] > 0 ? round(($siswa['tpa_benar_numerik'] / $siswa['tpa_jumlah_soal_numerik']) * 100) : 0;
$nilai_logika = $siswa['tpa_jumlah_soal_logika'] > 0 ? round(($siswa['tpa_benar_logika'] / $siswa['tpa_jumlah_soal_logika']) * 100) : 0;

if ($nilai >= 80) {
    $kategori_hasil = ['label' => 'DI ATAS RATA-RATA', 'color' => 'emerald', 'icon' => '🏆', 'desc' => 'Potensi akademik Anda di atas rata-rata. Kemampuan analitis dan problem solving sangat baik.'];
} elseif ($nilai >= 60) {
    $kategori_hasil = ['label' => 'RATA-RATA', 'color' => 'blue', 'icon' => '⭐', 'desc' => 'Potensi akademik Anda cukup baik. Terus belajar dan berlatih untuk meningkatkan.'];
} else {
    $kategori_hasil = ['label' => 'PERLU PENINGKATAN', 'color' => 'amber', 'icon' => '💪', 'desc' => 'Jangan menyerah! Potensi akademik bisa dikembangkan dengan latihan rutin.'];
}

$warna = [
    'emerald' => ['bg' => 'bg-emerald-500', 'text' => 'text-emerald-600', 'border' => 'border-emerald-200', 'bg_light' => 'bg-emerald-50'],
    'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-600', 'border' => 'border-blue-200', 'bg_light' => 'bg-blue-50'],
    'amber' => ['bg' => 'bg-amber-500', 'text' => 'text-amber-600', 'border' => 'border-amber-200', 'bg_light' => 'bg-amber-50'],
];
$w = $warna[$kategori_hasil['color']];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Hasil TPA - SMK Pasundan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-heading { font-family: 'Outfit', sans-serif; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-up { animation: fadeInUp 0.5s ease-out forwards; }

        @keyframes scaleIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
        .animate-scale { animation: scaleIn 0.5s ease-out forwards; }

        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="login.php" class="text-slate-500 hover:text-slate-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-brain text-white"></i>
                </div>
                <div>
                    <div class="font-heading font-bold text-sm">Hasil TPA</div>
                    <div class="text-xs text-slate-500">SMK Pasundan 2</div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-8">
        <!-- User Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 animate-fade-up">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-2xl flex items-center justify-center text-2xl">
                    👤
                </div>
                <div>
                    <div class="font-heading font-bold text-lg"><?= htmlspecialchars($siswa['nama_lengkap']) ?></div>
                    <div class="text-sm text-slate-500">
                        ID: <?= htmlspecialchars($siswa['id_pendaftaran']) ?> • <?= htmlspecialchars($siswa['jurusan']) ?>
                    </div>
                    <div class="text-xs text-slate-400 mt-1">
                        <?= date('d F Y, H:i', strtotime($siswa['tpa_tanggal'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Score -->
        <div class="text-center mb-8 animate-scale">
            <div class="text-6xl mb-2"><?= $kategori_hasil['icon'] ?></div>
            <div class="inline-flex items-center gap-2 px-5 py-2 <?= $w['bg_light'] ?> border <?= $w['border'] ?> rounded-full mb-4">
                <span class="<?= $w['text'] ?> font-bold"><?= $kategori_hasil['label'] ?></span>
            </div>
            <div class="text-7xl font-black <?= $w['text'] ?> mb-2"><?= $nilai ?></div>
            <div class="text-sm text-slate-500">Nilai TPA</div>
            <p class="mt-4 text-slate-600 text-sm max-w-xs mx-auto"><?= $kategori_hasil['desc'] ?></p>
        </div>

        <!-- Score Cards -->
        <div class="grid grid-cols-3 gap-3 mb-6 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-slate-100">
                <div class="text-2xl font-black text-indigo-600 mb-1"><?= $nilai_verbal ?></div>
                <div class="text-xs text-slate-500">Verbal</div>
                <div class="text-xs text-slate-400"><?= $siswa['tpa_benar_verbal'] ?>/<?= $siswa['tpa_jumlah_soal_verbal'] ?> benar</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-slate-100">
                <div class="text-2xl font-black text-emerald-600 mb-1"><?= $nilai_numerik ?></div>
                <div class="text-xs text-slate-500">Numerik</div>
                <div class="text-xs text-slate-400"><?= $siswa['tpa_benar_numerik'] ?>/<?= $siswa['tpa_jumlah_soal_numerik'] ?> benar</div>
            </div>
            <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-slate-100">
                <div class="text-2xl font-black text-amber-600 mb-1"><?= $nilai_logika ?></div>
                <div class="text-xs text-slate-500">Logika</div>
                <div class="text-xs text-slate-400"><?= $siswa['tpa_benar_logika'] ?>/<?= $siswa['tpa_jumlah_soal_logika'] ?> benar</div>
            </div>
        </div>

        <!-- Summary -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6 animate-fade-up" style="animation-delay: 0.2s;">
            <h3 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-pie text-blue-500"></i>
                Ringkasan
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-slate-900"><?= $total_soal ?></div>
                    <div class="text-xs text-slate-500">Total Soal</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-emerald-600"><?= $total_benar ?></div>
                    <div class="text-xs text-slate-500">Benar</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-red-500"><?= $total_soal - $total_benar ?></div>
                    <div class="text-xs text-slate-500">Salah</div>
                </div>
                <div>
                    <div class="text-2xl font-bold <?= $w['text'] ?>"><?= $akurasi ?>%</div>
                    <div class="text-xs text-slate-500">Akurasi</div>
                </div>
            </div>
        </div>

        <!-- Achievement Card CTA -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 mb-6 border border-blue-100 animate-fade-up" style="animation-delay: 0.3s;">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center text-2xl">
                        🏆
                    </div>
                    <div>
                        <div class="font-bold text-slate-900">Kartu Achievement</div>
                        <div class="text-sm text-slate-500">Download & bagikan ke social media!</div>
                    </div>
                </div>
                <a href="card.php?id=<?= $id_siswa ?>" class="px-5 py-3 bg-gradient-to-r from-amber-400 to-orange-500 text-white font-bold rounded-xl shadow-lg shadow-orange-500/30 hover:opacity-90 transition text-sm whitespace-nowrap">
                    <i class="fas fa-medal mr-2"></i>Download Kartu
                </a>
            </div>
        </div>

        <!-- Info -->
        <div class="bg-blue-50 rounded-xl p-4 text-sm text-blue-700 mb-6 animate-fade-up" style="animation-delay: 0.4s;">
            <i class="fas fa-info-circle mr-2"></i>
            Hasil TPA akan menjadi pertimbangan dalam proses seleksi. Nilai akan digabungkan dengan hasil wawancara.
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 animate-fade-up" style="animation-delay: 0.5s;">
            <a href="card.php?id=<?= $id_siswa ?>" class="flex-1 text-center px-6 py-4 bg-gradient-to-r from-amber-400 to-orange-500 text-white font-bold rounded-xl shadow-lg hover:opacity-90 transition">
                <i class="fas fa-medal mr-2"></i>Download Achievement Card
            </a>
            <a href="../../index.php" class="flex-1 text-center px-6 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition">
                <i class="fas fa-home mr-2"></i>Beranda
            </a>
        </div>
    </main>

    <footer class="py-6 text-center text-sm text-slate-500 border-t border-slate-200 mt-12">
        &copy; <?= date('Y') ?> SMK Pasundan 2 Bandung
    </footer>
</body>
</html>
