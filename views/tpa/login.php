<?php
/**
 * TPA LOGIN - SMK Pasundan 2 Bandung
 * Mobile-First Responsive Design - Education Theme
 * Akses: Semua siswa dengan ID Pendaftaran
 */
session_start();
include '../../config.php';

if (isset($_SESSION['tpa_login']) && $_SESSION['tpa_login'] === true) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reg = mysqli_real_escape_string($conn, $_POST['id_pendaftaran'] ?? '');

    if (empty($id_reg)) {
        $error = 'ID Pendaftaran harus diisi!';
    } else {
        // Cari siswa berdasarkan id_pendaftaran
        $q = mysqli_query($conn, "SELECT * FROM siswa WHERE id_pendaftaran = '$id_reg' LIMIT 1");
        if (mysqli_num_rows($q) > 0) {
            $siswa = mysqli_fetch_assoc($q);

            // Jika sudah selesai TPA, arahkan ke hasil
            if ($siswa['tpa_selesai'] == 1) {
                header("Location: hasil.php?id=" . $siswa['id_siswa']);
                exit();
            }

            // Set session untuk TPA
            $_SESSION['tpa_login'] = true;
            $_SESSION['tpa_id_siswa'] = $siswa['id_siswa'];
            $_SESSION['tpa_nama'] = $siswa['nama_lengkap'];
            $_SESSION['tpa_jurusan'] = $siswa['jurusan'];
            $_SESSION['tpa_id_reg'] = $id_reg;

            header("Location: index.php");
            exit();
        } else {
            $error = 'ID Pendaftaran tidak ditemukan! Pastikan ID yang Anda masukkan benar.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login TPA - SMK Pasundan 2 Bandung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-heading { font-family: 'Outfit', sans-serif; }

        body {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 50%, #e0e7ff 100%);
            min-height: 100vh;
            min-height: 100dvh;
        }

        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .shake { animation: shake 0.3s ease; }
    </style>
</head>
<body class="text-slate-800 antialiased">

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-blue-500/30">
                    <i class="fas fa-brain text-3xl text-white"></i>
                </div>
                <h1 class="font-heading text-2xl md:text-3xl font-black text-slate-900 mb-2">
                    Tes Potensi Akademik
                </h1>
                <p class="text-slate-500 text-sm">SMK Pasundan 2 Bandung</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-8">
                <div class="text-center mb-6">
                    <h2 class="font-bold text-lg text-slate-900 mb-2">Masuk dengan ID Pendaftaran</h2>
                    <p class="text-sm text-slate-500">Gunakan ID Pendaftaran yang sudah Anda dapatkan saat pendaftaran</p>
                </div>

                <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm flex items-center gap-3 shake">
                    <i class="fas fa-exclamation-circle text-lg"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            <i class="fas fa-id-card mr-2 text-blue-500"></i>ID Pendaftaran
                        </label>
                        <input
                            type="text"
                            name="id_pendaftaran"
                            id="id_reg"
                            placeholder="Contoh: SPMB26-001"
                            required
                            autocomplete="off"
                            autocapitalize="characters"
                            class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 font-bold text-center uppercase tracking-wider focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                        >
                    </div>

                    <button type="submit" class="w-full px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all hover:-translate-y-0.5">
                        <i class="fas fa-rocket mr-2"></i>Mulai TPA
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                    <p class="text-sm text-slate-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Butuh bantuan? Hubungi pihak sekolah
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-slate-500 text-xs">
                <p>&copy; <?= date('Y') ?> SMK Pasundan 2 Bandung</p>
            </div>
        </div>
    </div>

    <script>
        // Auto uppercase
        document.getElementById('id_reg').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Auto focus
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('id_reg').focus();
        });
    </script>
</body>
</html>
