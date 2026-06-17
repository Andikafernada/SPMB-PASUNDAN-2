<?php
/**
 * TPA LEADERBOARD - SMK Pasundan 2 Bandung
 * Public ranking page for TPA participants
 */
ob_start();
include '../../config.php';

// Get ranking data
$query = "
    SELECT
        s.id_siswa,
        s.id_pendaftaran,
        s.nama_lengkap,
        s.jurusan,
        s.tpa_nilai_total,
        s.tpa_benar_verbal,
        s.tpa_benar_numerik,
        s.tpa_benar_logika,
        s.tpa_tanggal,
        (
            SELECT COUNT(*) + 1
            FROM siswa s2
            WHERE s2.tpa_nilai_total > s.tpa_nilai_total
            AND s2.tpa_selesai = 1
            AND s2.tpa_nilai_total IS NOT NULL
        ) as rank
    FROM siswa s
    WHERE s.tpa_selesai = 1
    AND s.tpa_nilai_total IS NOT NULL
    ORDER BY s.tpa_nilai_total DESC, s.tpa_tanggal ASC
    LIMIT 100
";

$result = mysqli_query($conn, $query);

// Get stats
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*) as total_participants,
        AVG(tpa_nilai_total) as avg_score,
        MAX(tpa_nilai_total) as highest_score,
        COUNT(CASE WHEN tpa_nilai_total >= 90 THEN 1 END) as genius_count,
        COUNT(CASE WHEN tpa_nilai_total >= 75 THEN 1 END) as star_count
    FROM siswa
    WHERE tpa_selesai = 1 AND tpa_nilai_total IS NOT NULL
"));

// Get top 3 for podium
$top3 = [];
mysqli_data_seek($result, 0);
for ($i = 0; $i < 3 && $row = mysqli_fetch_assoc($result); $i++) {
    $top3[] = $row;
}
// Reset pointer
mysqli_data_seek($result, 0);

// Badge definitions
$badges = [
    'genius' => ['name' => 'Genius Akademik', 'icon' => 'fa-trophy', 'color' => '#FFD700', 'min' => 90],
    'star' => ['name' => 'Bintang Cemerlang', 'icon' => 'fa-star', 'color' => '#3B82F6', 'min' => 75],
    'rising' => ['name' => 'Pejuang Berpotensi', 'icon' => 'fa-rocket', 'color' => '#10B981', 'min' => 60],
];

function getBadge($score) {
    global $badges;
    if ($score >= 90) return $badges['genius'];
    if ($score >= 75) return $badges['star'];
    if ($score >= 60) return $badges['rising'];
    return ['name' => 'Penantang Terampil', 'icon' => 'fa-dumbbell', 'color' => '#8B5CF6', 'min' => 0];
}

function getRankIcon($rank) {
    if ($rank == 1) return ['icon' => 'fa-crown', 'color' => '#FFD700'];
    if ($rank == 2) return ['icon' => 'fa-medal', 'color' => '#C0C0C0'];
    if ($rank == 3) return ['icon' => 'fa-medal', 'color' => '#CD7F32'];
    return null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard TPA - SMK Pasundan 2 Bandung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'outfit': ['Outfit', 'sans-serif'],
                        'sans': ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glow-gold {
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.4);
        }

        .glow-silver {
            box-shadow: 0 0 25px rgba(192, 192, 192, 0.3);
        }

        .glow-bronze {
            box-shadow: 0 0 25px rgba(205, 127, 50, 0.3);
        }

        .podium-1 { height: 180px; }
        .podium-2 { height: 140px; }
        .podium-3 { height: 100px; }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 0 40px rgba(255, 215, 0, 0.6); }
        }

        .pulse-gold {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        @keyframes slide-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-up {
            animation: slide-up 0.5s ease-out forwards;
        }

        .leaderboard-row:hover {
            transform: scale(1.02);
            background: rgba(255, 255, 255, 0.1);
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .shimmer {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
    </style>
</head>
<body class="text-white">

    <!-- Particle Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 py-8">

        <!-- Header -->
        <header class="text-center mb-10">
            <div class="inline-flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-brain text-white text-xl"></i>
                </div>
                <h1 class="font-outfit text-4xl md:text-5xl font-black">
                    <span class="bg-gradient-to-r from-yellow-400 via-orange-400 to-yellow-400 bg-clip-text text-transparent">
                        Leaderboard TPA
                    </span>
                </h1>
            </div>
            <p class="text-slate-400 text-lg">SMK Pasundan 2 Bandung - Tes Potensi Akademik</p>
        </header>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <div class="glass rounded-2xl p-4 text-center">
                <div class="text-3xl font-black text-white mb-1"><?= number_format($stats['total_participants']) ?></div>
                <div class="text-xs text-slate-400 uppercase tracking-wider">Total Peserta</div>
            </div>
            <div class="glass rounded-2xl p-4 text-center">
                <div class="text-3xl font-black text-yellow-400 mb-1"><?= number_format($stats['avg_score'], 1) ?></div>
                <div class="text-xs text-slate-400 uppercase tracking-wider">Rata-rata</div>
            </div>
            <div class="glass rounded-2xl p-4 text-center">
                <div class="text-3xl font-black text-cyan-400 mb-1"><?= number_format($stats['highest_score']) ?></div>
                <div class="text-xs text-slate-400 uppercase tracking-wider">Nilai Tertinggi</div>
            </div>
            <div class="glass rounded-2xl p-4 text-center">
                <div class="text-3xl font-black text-purple-400 mb-1"><?= number_format($stats['genius_count']) ?></div>
                <div class="text-xs text-slate-400 uppercase tracking-wider">Genius (90+)</div>
            </div>
        </div>

        <!-- Podium Top 3 -->
        <?php if (count($top3) >= 3): ?>
        <div class="mb-12">
            <h2 class="text-center text-xl font-bold text-slate-300 mb-6 flex items-center justify-center gap-3">
                <span class="h-px w-16 bg-gradient-to-r from-transparent to-slate-600"></span>
                <i class="fas fa-trophy text-yellow-400"></i> TOP 3 ACHIEVERS
                <span class="h-px w-16 bg-gradient-to-l from-transparent to-slate-600"></span>
            </h2>

            <div class="flex items-end justify-center gap-4 md:gap-8">

                <!-- 2nd Place -->
                <?php if (isset($top3[1])): ?>
                <div class="text-center slide-up" style="animation-delay: 0.2s">
                    <div class="relative">
                        <div class="w-20 h-20 md:w-24 md:h-24 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 mx-auto flex items-center justify-center glow-silver overflow-hidden border-4 border-gray-400/50">
                            <i class="fas fa-user text-gray-600 text-3xl md:text-4xl"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-8 h-8 bg-gradient-to-br from-gray-300 to-gray-400 rounded-full flex items-center justify-center text-gray-700 font-black text-sm border-2 border-gray-300">
                            <i class="fas fa-medal"></i>
                        </div>
                    </div>
                    <div class="mt-2 font-bold text-slate-300 text-sm md:text-base truncate max-w-24"><?= htmlspecialchars(substr($top3[1]['nama_lengkap'], 0, 12)) ?></div>
                    <div class="text-2xl md:text-3xl font-black text-gray-300"><?= $top3[1]['tpa_nilai_total'] ?></div>
                    <div class="text-xs text-slate-500"><?= $top3[1]['jurusan'] ?></div>

                    <div class="mt-4 w-16 md:w-24 mx-auto podium-2 bg-gradient-to-t from-gray-600/50 to-gray-500/30 rounded-t-xl flex items-end justify-center pb-2 border-t-2 border-l-2 border-r-2 border-gray-500/30">
                        <span class="text-xl md:text-2xl font-black text-gray-300">2</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 1st Place -->
                <?php if (isset($top3[0])): ?>
                <div class="text-center slide-up float-animation" style="animation-delay: 0.1s">
                    <div class="relative">
                        <div class="w-24 h-24 md:w-32 md:h-32 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 mx-auto flex items-center justify-center glow-gold pulse-gold overflow-hidden border-4 border-yellow-300/50">
                            <i class="fas fa-crown text-white text-4xl md:text-5xl"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-black border-2 border-yellow-300">
                            <i class="fas fa-crown"></i>
                        </div>
                    </div>
                    <div class="mt-3 font-bold text-yellow-300 text-base md:text-lg truncate max-w-32"><?= htmlspecialchars(substr($top3[0]['nama_lengkap'], 0, 15)) ?></div>
                    <div class="text-4xl md:text-5xl font-black text-yellow-400"><?= $top3[0]['tpa_nilai_total'] ?></div>
                    <div class="text-xs text-slate-400"><?= $top3[0]['jurusan'] ?></div>

                    <div class="mt-4 w-20 md:w-32 mx-auto podium-1 bg-gradient-to-t from-yellow-600/50 to-yellow-500/30 rounded-t-xl flex items-end justify-center pb-2 border-t-2 border-l-2 border-r-2 border-yellow-500/30">
                        <span class="text-2xl md:text-3xl font-black text-yellow-400">1</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 3rd Place -->
                <?php if (isset($top3[2])): ?>
                <div class="text-center slide-up" style="animation-delay: 0.3s">
                    <div class="relative">
                        <div class="w-20 h-20 md:w-24 md:h-24 rounded-full bg-gradient-to-br from-amber-600 to-amber-700 mx-auto flex items-center justify-center glow-bronze overflow-hidden border-4 border-amber-500/50">
                            <i class="fas fa-user text-amber-200 text-3xl md:text-4xl"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-8 h-8 bg-gradient-to-br from-amber-600 to-amber-700 rounded-full flex items-center justify-center text-amber-200 font-black text-sm border-2 border-amber-500">
                            <i class="fas fa-medal"></i>
                        </div>
                    </div>
                    <div class="mt-2 font-bold text-amber-300 text-sm md:text-base truncate max-w-24"><?= htmlspecialchars(substr($top3[2]['nama_lengkap'], 0, 12)) ?></div>
                    <div class="text-2xl md:text-3xl font-black text-amber-400"><?= $top3[2]['tpa_nilai_total'] ?></div>
                    <div class="text-xs text-slate-500"><?= $top3[2]['jurusan'] ?></div>

                    <div class="mt-4 w-16 md:w-24 mx-auto podium-3 bg-gradient-to-t from-amber-700/50 to-amber-600/30 rounded-t-xl flex items-end justify-center pb-2 border-t-2 border-l-2 border-r-2 border-amber-600/30">
                        <span class="text-xl md:text-2xl font-black text-amber-400">3</span>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endif; ?>

        <!-- Leaderboard Table -->
        <div class="glass rounded-3xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <i class="fas fa-list-ol text-cyan-400"></i>
                    Ranking Lengkap
                </h3>
                <span class="text-sm text-slate-400">Top 100 Peserta</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr class="text-xs text-slate-400 uppercase tracking-wider">
                            <th class="px-4 py-3 text-center w-16">Rank</th>
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-center hidden md:table-cell">Jurusan</th>
                            <th class="px-4 py-3 text-center">Total</th>
                            <th class="px-4 py-3 text-center hidden sm:table-cell">Verbal</th>
                            <th class="px-4 py-3 text-center hidden sm:table-cell">Numerik</th>
                            <th class="px-4 py-3 text-center hidden sm:table-cell">Logika</th>
                            <th class="px-4 py-3 text-center">Badge</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php
                        $rank = 1;
                        while ($row = mysqli_fetch_assoc($result)):
                            $badge = getBadge($row['tpa_nilai_total']);
                            $rankIcon = getRankIcon($rank);
                        ?>
                        <tr class="leaderboard-row transition-all duration-200 cursor-default slide-up"
                            style="animation-delay: <?= min($rank * 0.05, 0.5) ?>s"
                            onclick="showDetail(<?= htmlspecialchars(json_encode($row)) ?>)">
                            <td class="px-4 py-4 text-center">
                                <?php if ($rankIcon): ?>
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full" style="background: <?= $rankIcon['color'] ?>20; color: <?= $rankIcon['color'] ?>">
                                        <i class="fas <?= $rankIcon['icon'] ?>"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 font-bold"><?= $rank ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-bold text-white"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                <div class="text-xs text-slate-500 md:hidden"><?= $row['jurusan'] ?></div>
                            </td>
                            <td class="px-4 py-4 text-center hidden md:table-cell">
                                <span class="px-2 py-1 bg-cyan-500/20 text-cyan-400 rounded-lg text-xs font-bold"><?= $row['jurusan'] ?></span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="text-xl font-black <?= $row['tpa_nilai_total'] >= 90 ? 'text-yellow-400' : ($row['tpa_nilai_total'] >= 75 ? 'text-cyan-400' : 'text-white') ?>">
                                    <?= $row['tpa_nilai_total'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center hidden sm:table-cell">
                                <span class="text-slate-400"><?= $row['tpa_benar_verbal'] ?? '-' ?></span>
                            </td>
                            <td class="px-4 py-4 text-center hidden sm:table-cell">
                                <span class="text-slate-400"><?= $row['tpa_benar_numerik'] ?? '-' ?></span>
                            </td>
                            <td class="px-4 py-4 text-center hidden sm:table-cell">
                                <span class="text-slate-400"><?= $row['tpa_benar_logika'] ?? '-' ?></span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold"
                                      style="background: <?= $badge['color'] ?>20; color: <?= $badge['color'] ?>">
                                    <i class="fas <?= $badge['icon'] ?>"></i>
                                </span>
                            </td>
                        </tr>
                        <?php
                        $rank++;
                        if ($rank > 100) break;
                        endwhile;
                        ?>
                    </tbody>
                </table>

                <?php if (mysqli_num_rows($result) == 0): ?>
                    <div class="py-16 text-center text-slate-500">
                        <i class="fas fa-users text-5xl mb-4 opacity-30"></i>
                        <p class="font-medium">Belum ada data peserta TPA</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-10 text-center text-slate-500 text-sm">
            <p>SPMB SMK Pasundan 2 Bandung &copy; <?= date('Y') ?></p>
            <p class="mt-1 text-xs">
                <a href="login.php" class="hover:text-cyan-400 transition-colors">Ikuti TPA</a>
                &bull;
                <a href="../tpa/sertifikat.php" class="hover:text-cyan-400 transition-colors">Lihat Sertifikat</a>
            </p>
        </footer>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="glass rounded-3xl max-w-md w-full overflow-hidden">
            <div class="p-6 text-center border-b border-white/10">
                <div id="modalBadge" class="w-20 h-20 rounded-full mx-auto flex items-center justify-center mb-4">
                    <i class="fas text-4xl"></i>
                </div>
                <h3 id="modalName" class="text-xl font-bold text-white mb-1"></h3>
                <p id="modalJurusan" class="text-slate-400 text-sm"></p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4 text-center mb-6">
                    <div>
                        <div class="text-4xl font-black text-yellow-400" id="modalScore">0</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider">Nilai Total</div>
                    </div>
                    <div>
                        <div id="modalRank" class="text-4xl font-black text-cyan-400">0</div>
                        <div class="text-xs text-slate-400 uppercase tracking-wider">Peringkat</div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="bg-white/5 rounded-xl p-3">
                        <div class="text-xl font-bold text-white" id="modalVerbal">-</div>
                        <div class="text-[10px] text-slate-400 uppercase">Verbal</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3">
                        <div class="text-xl font-bold text-white" id="modalNumerik">-</div>
                        <div class="text-[10px] text-slate-400 uppercase">Numerik</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3">
                        <div class="text-xl font-bold text-white" id="modalLogika">-</div>
                        <div class="text-[10px] text-slate-400 uppercase">Logika</div>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-6">
                <button onclick="closeModal()" class="w-full py-3 bg-white/10 hover:bg-white/20 rounded-xl font-bold text-sm transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        function showDetail(data) {
            document.getElementById('modalName').textContent = data.nama_lengkap;
            document.getElementById('modalJurusan').textContent = data.jurusan;
            document.getElementById('modalScore').textContent = data.tpa_nilai_total;
            document.getElementById('modalRank').textContent = data.rank;

            const badge = <?= json_encode(array_values($badges)[0]) ?>;
            if (data.tpa_nilai_total >= 90) {
                document.getElementById('modalBadge').style.background = 'linear-gradient(135deg, #FFD700, #FFA500)';
                document.getElementById('modalBadge').querySelector('i').className = 'fas fa-trophy';
            } else if (data.tpa_nilai_total >= 75) {
                document.getElementById('modalBadge').style.background = 'linear-gradient(135deg, #3B82F6, #1D4ED8)';
                document.getElementById('modalBadge').querySelector('i').className = 'fas fa-star';
            } else {
                document.getElementById('modalBadge').style.background = 'linear-gradient(135deg, #10B981, #059669)';
                document.getElementById('modalBadge').querySelector('i').className = 'fas fa-rocket';
            }

            document.getElementById('modalVerbal').textContent = data.tpa_benar_verbal ?? '-';
            document.getElementById('modalNumerik').textContent = data.tpa_benar_numerik ?? '-';
            document.getElementById('modalLogika').textContent = data.tpa_benar_logika ?? '-';

            document.getElementById('detailModal').classList.remove('hidden');
            document.getElementById('detailModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
            document.getElementById('detailModal').classList.remove('flex');
        }

        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Confetti on load
        window.addEventListener('load', function() {
            const duration = 3 * 1000;
            const animationEnd = Date.now() + duration;

            confetti({
                particleCount: 50,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#FFD700', '#3B82F6', '#10B981', '#8B5CF6']
            });
        });
    </script>
</body>
</html>
