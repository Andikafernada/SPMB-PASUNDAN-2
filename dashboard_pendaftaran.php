<?php
/**
 * DASHBOARD PENDAFTARAN - Admin Page
 * Mobile Responsive Design
 */
include 'config.php';

// Check admin IP restriction
require_admin_ip();

// Check session
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'pendaftaran'){
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Pendaftaran | SMK Pasundan 2</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        :root {
            --glass: rgba(255, 255, 255, 0.1);
            --border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0f172a;
            color: white;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* Mobile Menu Toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1001;
            width: 44px;
            height: 44px;
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 12px;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        /* Overlay for mobile menu */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 999;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--border);
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar h3 {
            text-align: center;
            color: #38bdf8;
            margin-bottom: 30px;
            padding-top: 60px;
        }

        .menu-item {
            padding: 14px 16px;
            margin: 4px 0;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-size: 14px;
        }

        .menu-item:hover {
            background: rgba(56, 189, 248, 0.2);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
        }

        .btn-logout {
            background: #f43f5e;
            padding: 12px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            text-align: center;
            margin-top: auto;
            margin-bottom: 20px;
            display: block;
            font-size: 14px;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 24px;
            margin-left: 260px;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header h1 {
            font-size: 20px;
        }

        .header p {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        .clock-box {
            background: var(--glass);
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font-size: 14px;
        }

        /* STATS CARD GRID */
        .grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .card {
            background: var(--glass);
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--border);
            backdrop-filter: blur(5px);
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,0.15);
        }

        .card i {
            font-size: 24px;
            color: #38bdf8;
            margin-bottom: 12px;
        }

        .card h3 {
            font-size: 24px;
            margin-bottom: 4px;
        }

        .card p {
            font-size: 12px;
            color: #94a3b8;
        }

        .chart-card {
            background: var(--glass);
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--border);
            margin-top: 24px;
        }

        .chart-placeholder {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            border: 2px dashed #334155;
            border-radius: 12px;
            margin-top: 16px;
            font-size: 13px;
        }

        /* MOBILE RESPONSIVE */
        @media (max-width: 768px) {
            .mobile-toggle {
                display: flex;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
                padding-top: 70px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .grid-stats {
                grid-template-columns: 1fr 1fr;
            }

            .card {
                padding: 16px;
            }

            .card i {
                font-size: 20px;
            }

            .card h3 {
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {
            .grid-stats {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle -->
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h3><i class="fas fa-school"></i> PPDB SYSTEM</h3>
        <a href="#" class="menu-item"><i class="fas fa-home"></i> Beranda</a>
        <a href="#" class="menu-item"><i class="fas fa-user-edit"></i> Verifikasi Data</a>
        <a href="#" class="menu-item"><i class="fas fa-print"></i> Cetak Kartu</a>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>! 👋</h1>
                <p>Selamat datang di Dashboard Pendaftaran SMK Pasundan 2.</p>
            </div>
            <div class="clock-box">
                <i class="far fa-clock"></i> <span id="clock"></span>
            </div>
        </div>

        <div class="grid-stats">
            <div class="card animate__animated animate__fadeInUp">
                <i class="fas fa-users"></i>
                <h3>150</h3>
                <p>Total Pendaftar</p>
            </div>
            <div class="card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <i class="fas fa-user-check"></i>
                <h3>85</h3>
                <p>Sudah Verifikasi</p>
            </div>
            <div class="card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <i class="fas fa-user-clock"></i>
                <h3>65</h3>
                <p>Menunggu Review</p>
            </div>
        </div>

        <div class="chart-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
            <h3><i class="fas fa-chart-line" style="font-size: 16px; margin-right: 8px;"></i> Grafik Pendaftaran</h3>
            <div class="chart-placeholder">
                [ Area Grafik Highcharts / Chart.js ]
            </div>
        </div>
    </div>

    <script>
        // Update Jam Realtime
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit', second:'2-digit'});
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Toggle Sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.querySelector('.sidebar-overlay').classList.toggle('active');
        }

        // Close sidebar when clicking menu item (mobile)
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebar();
                }
            });
        });
    </script>
</body>
</html>
