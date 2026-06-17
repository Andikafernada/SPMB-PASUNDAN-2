<?php
/**
 * CEK STATUS PENDAFTARAN - Halaman Publik
 * SMK Pasundan 2 Bandung
 * Allow students/parents to check registration status
 */
include '../../config.php';

// Handle status check
$status_data = null;
$error_message = null;
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pendaftaran'])) {
    $searched = true;
    $id_daftar = mysqli_real_escape_string($conn, strtoupper(trim($_POST['id_pendaftaran'])));

    // Search by ID Pendaftaran or NIK
    $query = mysqli_query($conn, "
        SELECT
            s.*,
            g.nama_gelombang,
            g.tanggal_mulai,
            g.tanggal_selesai
        FROM siswa s
        LEFT JOIN gelombang g ON s.gelombang = g.id_gelombang
        WHERE s.id_pendaftaran = '$id_daftar'
        OR s.nik = '$id_daftar'
        LIMIT 1
    ");

    if (mysqli_num_rows($query) > 0) {
        $status_data = mysqli_fetch_assoc($query);

        // Calculate completion percentage
        $required_fields = ['nama_lengkap', 'nisn', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kota', 'provinsi'];
        $completed = 0;
        foreach ($required_fields as $field) {
            if (!empty($status_data[$field])) $completed++;
        }
        $status_data['completion'] = round(($completed / count($required_fields)) * 100);
    } else {
        $error_message = "Data tidak ditemukan. Pastikan ID Pendaftaran atau NIK yang Anda输入 benar.";
    }
}

// Status steps
function getStatusSteps($data) {
    if (!$data) return [];

    $steps = [];

    // Step 1: Pendaftaran
    $steps[] = [
        'label' => 'Pendaftaran',
        'desc' => 'Data berhasil disimpan',
        'status' => 'completed',
        'date' => $data['tgl_daftar'] ?? null
    ];

    // Step 2: ACC TU
    if ($data['status_bayar'] === 'LUNAS') {
        $steps[] = [
            'label' => 'Verifikasi TU',
            'desc' => 'ID Pendaftaran: ' . ($data['id_pendaftaran'] ?? '-'),
            'status' => 'completed',
            'date' => null
        ];
    } else {
        $steps[] = [
            'label' => 'Verifikasi TU',
            'desc' => 'Menunggu verifikasi dari TU',
            'status' => 'pending',
            'date' => null
        ];
    }

    // Step 3: TPA
    if ($data['tpa_selesai'] == 1) {
        $steps[] = [
            'label' => 'Tes TPA',
            'desc' => 'Nilai: ' . ($data['tpa_nilai_total'] ?? '-'),
            'status' => 'completed',
            'date' => $data['tpa_tanggal'] ?? null
        ];
    } else {
        $steps[] = [
            'label' => 'Tes TPA',
            'desc' => 'Belum mengerjakan TPA',
            'status' => $data['status_bayar'] === 'LUNAS' ? 'pending' : 'locked',
            'date' => null
        ];
    }

    // Step 4: Daftar Ulang
    if ($data['status_siswa'] === 'SUDAH DAFTAR ULANG') {
        $steps[] = [
            'label' => 'Daftar Ulang',
            'desc' => 'Berkas lengkap & lunas',
            'status' => 'completed',
            'date' => null
        ];
    } else {
        $steps[] = [
            'label' => 'Daftar Ulang',
            'desc' => 'Menunggu proses daftar ulang',
            'status' => 'locked',
            'date' => null
        ];
    }

    return $steps;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pendaftaran - SMK Pasundan 2 Bandung</title>
    <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f8fafc 100%);
            min-height: 100vh;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .float-animation {
            animation: float 4s ease-in-out infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        .step-line {
            position: relative;
        }

        .step-line::after {
            content: '';
            position: absolute;
            left: 24px;
            top: 48px;
            width: 2px;
            height: calc(100% - 48px);
            background: #e2e8f0;
        }

        .step-line:last-child::after {
            display: none;
        }

        .step-line.completed::after {
            background: #10b981;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in {
            animation: slideIn 0.5s ease-out forwards;
        }

        @keyframes fadeScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .fade-scale {
            animation: fadeScale 0.4s ease-out forwards;
        }

        .search-input:focus {
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
    </style>
</head>
<body class="text-slate-800">

    <!-- Header -->
    <header class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-white font-outfit font-black text-sm">P2</span>
                </div>
                <div class="text-center">
                    <h1 class="font-outfit font-bold text-slate-900 text-lg">SMK Pasundan 2 Bandung</h1>
                    <p class="text-[10px] text-indigo-600 font-bold uppercase tracking-widest">Cek Status Pendaftaran</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-8">

        <?php if (!$searched || $error_message): ?>
        <!-- Search Form -->
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 overflow-hidden fade-scale">
            <!-- Decorative Top -->
            <div class="h-2 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600"></div>

            <div class="p-8 md:p-10">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-indigo-100 rounded-full mx-auto flex items-center justify-center mb-4 float-animation">
                        <i class="fas fa-search text-indigo-600 text-3xl"></i>
                    </div>
                    <h2 class="font-outfit text-2xl md:text-3xl font-black text-slate-900 mb-2">Cek Status Pendaftaran</h2>
                    <p class="text-slate-500">Masukkan ID Pendaftaran atau NIK Anda untuk melihat status pendaftaran</p>
                </div>

                <?php if ($error_message): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    <p class="text-red-700 text-sm"><?= htmlspecialchars($error_message) ?></p>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="relative">
                        <input
                            type="text"
                            name="id_pendaftaran"
                            required
                            placeholder="Contoh: SPMB26-001 atau 1234567890123456"
                            class="search-input w-full px-6 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-slate-900 font-bold text-lg focus:border-indigo-500 focus:bg-white outline-none transition-all"
                            autofocus
                        >
                        <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-colors shadow-lg shadow-indigo-200">
                            <i class="fas fa-search mr-2"></i> Cek
                        </button>
                    </div>
                </form>

                <div class="mt-8 p-4 bg-slate-50 rounded-xl">
                    <p class="text-xs text-slate-500 text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        ID Pendaftaran tertera di bukti pendaftaran atau SMS/WhatsApp notifikasi dari sekolah.
                        Contoh: <span class="font-mono font-bold text-indigo-600">SPMB26-001</span>
                    </p>
                </div>
            </div>
        </div>

        <?php elseif ($status_data): ?>
        <!-- Status Result -->
        <div class="space-y-6">

            <!-- Student Info Card -->
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 overflow-hidden fade-scale">
                <div class="h-2 bg-gradient-to-r from-emerald-500 to-teal-400"></div>

                <div class="p-6 md:p-8">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h2 class="font-outfit text-2xl font-black text-slate-900"><?= htmlspecialchars($status_data['nama_lengkap'] ?? '-') ?></h2>
                            <p class="text-indigo-600 font-bold"><?= htmlspecialchars($status_data['id_pendaftaran'] ?? 'Belum dapat ID') ?></p>
                        </div>
                        <?php if ($status_data['status_siswa'] === 'SUDAH DAFTAR ULANG'): ?>
                        <span class="px-4 py-2 bg-emerald-100 text-emerald-700 font-bold text-sm rounded-full">
                            <i class="fas fa-check-circle mr-1"></i> LULUS
                        </span>
                        <?php else: ?>
                        <span class="px-4 py-2 bg-amber-100 text-amber-700 font-bold text-sm rounded-full">
                            <i class="fas fa-clock mr-1"></i> PROSES
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <div class="text-xs text-slate-500 mb-1">Jurusan</div>
                            <div class="font-black text-slate-900"><?= htmlspecialchars($status_data['jurusan'] ?? '-') ?></div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <div class="text-xs text-slate-500 mb-1">Pembayaran</div>
                            <div class="font-black <?= $status_data['status_bayar'] === 'LUNAS' ? 'text-emerald-600' : 'text-amber-600' ?>">
                                <?= $status_data['status_bayar'] === 'LUNAS' ? 'Lunas' : 'Belum' ?>
                            </div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <div class="text-xs text-slate-500 mb-1">TPA</div>
                            <div class="font-black <?= $status_data['tpa_selesai'] == 1 ? 'text-emerald-600' : 'text-slate-400' ?>">
                                <?= $status_data['tpa_selesai'] == 1 ? ($status_data['tpa_nilai_total'] ?? '-') : 'Belum' ?>
                            </div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <div class="text-xs text-slate-500 mb-1">Kelengkapan</div>
                            <div class="font-black <?= $status_data['completion'] >= 100 ? 'text-emerald-600' : 'text-amber-600' ?>">
                                <?= $status_data['completion'] ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 overflow-hidden fade-scale">
                <div class="h-2 bg-gradient-to-r from-blue-600 to-indigo-600"></div>

                <div class="p-6 md:p-8">
                    <h3 class="font-outfit text-lg font-black text-slate-900 mb-6 flex items-center gap-2">
                        <i class="fas fa-tasks text-indigo-600"></i>
                        Timeline Pendaftaran
                    </h3>

                    <div class="space-y-6">
                        <?php
                        $steps = getStatusSteps($status_data);
                        foreach ($steps as $i => $step):
                            $step_class = $step['status'];
                            if ($step_class === 'completed') {
                                $icon_bg = 'bg-emerald-500';
                                $icon = 'fa-check';
                                $text_color = 'text-emerald-600';
                                $desc_color = 'text-slate-600';
                            } elseif ($step_class === 'pending') {
                                $icon_bg = 'bg-amber-500';
                                $icon = 'fa-clock';
                                $text_color = 'text-amber-600';
                                $desc_color = 'text-slate-500';
                            } else {
                                $icon_bg = 'bg-slate-300';
                                $icon = 'fa-lock';
                                $text_color = 'text-slate-400';
                                $desc_color = 'text-slate-400';
                            }
                        ?>
                        <div class="step-line <?= $step_class ?> flex items-start gap-4 slide-in" style="animation-delay: <?= $i * 0.1 ?>s">
                            <div class="w-12 h-12 rounded-full <?= $icon_bg ?> flex items-center justify-center flex-shrink-0 relative z-10">
                                <i class="fas <?= $icon ?> text-white"></i>
                            </div>
                            <div class="flex-1 pt-1">
                                <div class="font-bold <?= $text_color ?>"><?= htmlspecialchars($step['label']) ?></div>
                                <div class="<?= $desc_color ?> text-sm"><?= htmlspecialchars($step['desc']) ?></div>
                                <?php if (!empty($step['date'])): ?>
                                <div class="text-xs text-slate-400 mt-1">
                                    <i class="fas fa-calendar mr-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($step['date'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center no-print">
                <button onclick="window.print()" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-colors shadow-lg shadow-indigo-200 flex items-center justify-center gap-2">
                    <i class="fas fa-print"></i> Cetak Status
                </button>
                <a href="?" class="px-8 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i> Cek Lagi
                </a>
            </div>

            <!-- Help Section -->
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 no-print">
                <h4 class="font-bold text-amber-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-question-circle"></i> Butuh Bantuan?
                </h4>
                <ul class="text-sm text-amber-700 space-y-2">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-phone mt-1 text-amber-500"></i>
                        <span>Hubungi TU: (022) 7310119</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fab fa-whatsapp mt-1 text-amber-500"></i>
                        <span>WhatsApp: 0838-1720-3455</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-clock mt-1 text-amber-500"></i>
                        <span>Jam Layanan: Senin - Jumat, 08.00 - 15.00 WIB</span>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="mt-12 py-6 text-center text-slate-500 text-sm">
        <p>&copy; <?= date('Y') ?> SMK Pasundan 2 Bandung. Hak Cipta Dilindungi.</p>
        <p class="mt-1 text-xs">
            <a href="../tpa/login.php" class="hover:text-indigo-600">Ikuti TPA</a>
            &bull;
            <a href="../tpa/sertifikat.php" class="hover:text-indigo-600">Lihat Sertifikat</a>
            &bull;
            <a href="../../" class="hover:text-indigo-600">Halaman Utama</a>
        </p>
    </footer>

    <!-- Print Only Header (hidden on screen) -->
    <div class="hidden print-only p-8 text-center border-b-2 border-slate-300">
        <h1 class="font-outfit text-2xl font-black">SMK Pasundan 2 Bandung</h1>
        <p class="text-slate-500">Bukti Status Pendaftaran</p>
        <p class="text-xs text-slate-400 mt-2">Dicetak pada: <?= date('d/m/Y H:i') ?></p>
    </div>

    <script>
        // Auto-focus input on page load
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.querySelector('input[name="id_pendaftaran"]');
            if (input) {
                setTimeout(() => input.focus(), 300);
            }
        });

        // Print handling
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });

        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
    </script>
</body>
</html>
