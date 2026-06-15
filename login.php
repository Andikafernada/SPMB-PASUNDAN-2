<?php
// Redirect if already logged in
if(isset($_SESSION['role'])) {
    $redirects = [
        'pendaftaran' => 'views/pendaftaran/index.php',
        'tu' => 'views/tu/index.php',
        'database' => 'views/database/index.php',
        'superuser' => 'views/database/index.php',
        'user' => 'views/database/index.php'
    ];
    if(isset($redirects[$_SESSION['role']])) {
        header("Location: " . $redirects[$_SESSION['role']]);
        exit();
    }
}

include 'config.php';

$csrf_token = generate_csrf_token();
$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';

$res_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa");
$total_siswa = mysqli_fetch_assoc($res_total)['total'] ?? 0;

$jurusan_query = "SELECT jurusan, COUNT(*) as jumlah FROM siswa WHERE jurusan IS NOT NULL AND jurusan != '' GROUP BY jurusan ORDER BY jumlah DESC";
$jurusan_data = mysqli_query($conn, $jurusan_query);

$bulan_query = "SELECT DATE_FORMAT(tgl_daftar, '%b') as nama_bulan, COUNT(*) as jumlah FROM siswa WHERE tgl_daftar IS NOT NULL GROUP BY MONTH(tgl_daftar) ORDER BY MONTH(tgl_daftar) ASC LIMIT 6";
$bulan_res = mysqli_query($conn, $bulan_query);

$labels_bulan = [];
$data_pendaftar = [];
while($b = mysqli_fetch_assoc($bulan_res)) {
    $labels_bulan[] = $b['nama_bulan'];
    $data_pendaftar[] = (int)$b['jumlah'];
}

if(count($labels_bulan) == 1) {
    array_unshift($labels_bulan, "Mar");
    array_unshift($data_pendaftar, 0);
}
if(empty($labels_bulan)) { $labels_bulan = ['Mar', 'Apr']; $data_pendaftar = [0, 0]; }

function getPersen($jumlah, $total) {
    return ($total > 0) ? ($jumlah / $total) * 100 : 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Committee - SMK Pasundan 2 Bandung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container { flex-direction: column !important; }
            .panel-stats { padding: 40px 24px !important; min-height: auto !important; }
            .panel-login { padding: 40px 24px !important; }
            .main-title { font-size: 2rem !important; letter-spacing: -1px !important; }
            .counter-number { font-size: 3rem !important; }
        }

        .login-container { display: flex; min-height: 100vh; }

        .panel-stats {
            flex: 1.2;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 50%, #e0e7ff 100%);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .panel-stats::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            background: #3b82f6;
            filter: blur(150px);
            opacity: 0.2;
            top: 0;
            left: 0;
        }

        .main-title {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -2px;
            margin-bottom: 30px;
            line-height: 0.9;
            text-transform: uppercase;
            color: #1e3a8a;
        }

        .counter-number {
            font-size: 5rem;
            font-weight: 800;
            margin-bottom: 30px;
            letter-spacing: -3px;
            color: #1e40af;
        }

        .counter-number span {
            font-size: 1rem;
            color: #3b82f6;
            letter-spacing: 3px;
            display: block;
            margin-top: -5px;
            font-weight: 600;
        }

        .jurusan-stat { margin-bottom: 40px; }
        .jurusan-row { margin-bottom: 14px; max-width: 400px; }
        .jurusan-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #64748b;
        }

        .progress-bg { background: rgba(59, 130, 246, 0.1); height: 6px; border-radius: 10px; }
        .progress-fill { background: linear-gradient(90deg, #3b82f6, #60a5fa); height: 100%; border-radius: 10px; box-shadow: 0 0 10px rgba(59, 130, 246, 0.3); }

        .chart-box { width: 100%; max-width: 400px; height: 160px; }

        .panel-login {
            flex: 0.8;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
        }

        .login-card {
            width: 100%;
            max-width: 380px;
        }

        .input-group { margin-bottom: 20px; }
        .input-group label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #3b82f6;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .input-group input {
            width: 100%;
            padding: 14px 18px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-weight: 500;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .footer-text {
            text-align: center;
            font-size: 0.7rem;
            margin-top: 30px;
            color: #94a3b8;
            letter-spacing: 0.5px;
            line-height: 1.5;
        }

        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Stats Panel -->
        <div class="panel-stats">
            <div>
                <h1 class="main-title">SMK<br>PASUNDAN 2</h1>
                <div class="counter-number">
                    <?= $total_siswa ?>
                    <span>PENDAFTAR BARU 2026</span>
                </div>

                <div class="jurusan-stat">
                    <?php mysqli_data_seek($jurusan_data, 0); ?>
                    <?php while($j = mysqli_fetch_assoc($jurusan_data)): ?>
                    <div class="jurusan-row">
                        <div class="jurusan-info">
                            <span><?= $j['jurusan'] ?></span>
                            <span style="color:#1e40af"><?= $j['jumlah'] ?> SISWA</span>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-fill" style="width:<?= getPersen($j['jumlah'], $total_siswa) ?>%"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="chart-box">
                    <p style="font-size: 0.65rem; font-weight: 700; color: #3b82f6; margin-bottom: 12px; letter-spacing: 2px; text-transform: uppercase;">Tren Pendaftaran Bulanan</p>
                    <canvas id="luxuryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Login Panel -->
        <div class="panel-login">
            <div class="login-card">
                <h2 style="font-weight:700; font-size:1.75rem; margin-bottom:8px; color:#1e293b;">Masuk Sistem</h2>
                <p style="color:#64748b; font-size:0.9rem; margin-bottom:30px;">Silakan masukkan kredensial Anda.</p>

                <?php if($pesan=='salah'): ?>
                    <div class="alert-box">
                        <i class="fas fa-exclamation-circle"></i>
                        Kredensial tidak sesuai.
                    </div>
                <?php elseif($pesan=='terblokir'): ?>
                    <div class="alert-box">
                        <i class="fas fa-lock"></i>
                        Terlalu banyak percobaan. Coba lagi 15 menit.
                    </div>
                <?php elseif($pesan=='invalid'): ?>
                    <div class="alert-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        Permintaan tidak valid.
                    </div>
                <?php endif; ?>

                <form action="login_proses.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="input-group">
                        <label><i class="fas fa-user mr-1"></i> Username</label>
                        <input type="text" name="username" placeholder="Masukkan username" required autocomplete="username">
                    </div>
                    <div class="input-group">
                        <label><i class="fas fa-lock mr-1"></i> Password</label>
                        <input type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                    </button>
                </form>

                <div class="footer-text">
                    SMK PASUNDAN 2 BANDUNG &bull; SISTEM OLEH ANDIKA FERNANDA
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('luxuryChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 160);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels_bulan) ?>,
                datasets: [{
                    data: <?= json_encode($data_pendaftar) ?>,
                    borderColor: '#3b82f6',
                    borderWidth: 3,
                    fill: true,
                    backgroundColor: gradient,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#3b82f6',
                    pointHitRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(59, 130, 246, 0.05)', drawBorder: false },
                        ticks: { color: '#94a3b8', stepSize: 1, font: { size: 10, weight: '600' } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#3b82f6', font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    </script>
</body>
</html>
