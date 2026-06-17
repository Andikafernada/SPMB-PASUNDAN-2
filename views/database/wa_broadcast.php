<?php
/**
 * WHATSAPP BROADCAST - Mass Message Sender
 * IP Restricted: Only accessible from internal network
 */
include '../../config.php';

// Check admin IP restriction
require_admin_ip();

// Check session
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database', 'superuser', 'superuser1'])) {
    header("Location: ../../panitia/index.php");
    exit();
}

// Load templates for dropdown
$templates = mysqli_query($conn, "SELECT * FROM wa_templates WHERE is_active = 1 ORDER BY jenis, nama_template");

// Filter states
$filter_nama = isset($_GET['filter_nama']) ? mysqli_real_escape_string($conn, $_GET['filter_nama']) : '';
$filter_jur = isset($_GET['filter_jur']) ? mysqli_real_escape_string($conn, $_GET['filter_jur']) : '';
$filter_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($conn, $_GET['filter_status']) : '';

// Build query for student list
$where = "WHERE no_hp IS NOT NULL AND no_hp != '' AND no_hp != '-'";
if ($filter_nama) {
    $where .= " AND (nama_lengkap LIKE '%$filter_nama%' OR id_pendaftaran LIKE '%$filter_nama%')";
}
if ($filter_jur) {
    $where .= " AND jurusan = '$filter_jur'";
}
if ($filter_status == 'DU') {
    $where .= " AND status_siswa = 'SUDAH DAFTAR ULANG'";
} elseif ($filter_status == 'BELUM_DU') {
    $where .= " AND status_siswa != 'SUDAH DAFTAR ULANG'";
}

$query_siswa = "SELECT id_siswa, id_pendaftaran, nama_lengkap, no_hp, jurusan, status_siswa FROM siswa $where ORDER BY id_siswa DESC";
$result_siswa = mysqli_query($conn, $query_siswa);
$total_siswa = mysqli_num_rows($result_siswa);
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <title>WhatsApp Broadcast | SPMB SMK Pasundan 2</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

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
        body { background-color: #f8fafc; }
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
        @keyframes pulse-ring { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.3); opacity: 0; } }
        .pulse-ring::before { content: ''; position: absolute; inset: -4px; border-radius: 50%; background: #10b981; animation: pulse-ring 1.5s infinite; }
    </style>
</head>
<body class="text-slate-700 custom-scroll">

    <!-- Header -->
    <div class="sticky top-0 z-50 flex items-center justify-between px-4 sm:px-8 py-4 bg-white/90 backdrop-blur-md border-b border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:text-indigo-600 transition-all">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <div>
                <h1 class="font-outfit text-xl font-black text-slate-900">WhatsApp Broadcast</h1>
                <p class="text-[10px] text-slate-500">Kirim pesan masal ke pendaftar</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="hidden sm:inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse mr-2"></span>
            <span class="text-[10px] font-bold text-slate-400"><?= htmlspecialchars($_SESSION['nama'] ?? 'ADMIN') ?></span>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT: Filters & Student List -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Filters -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-2 text-indigo-600 text-sm font-black uppercase tracking-wider mb-4">
                        <i class="fas fa-filter"></i> Filter Siswa
                    </div>
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="relative">
                            <input type="text" name="filter_nama" value="<?= htmlspecialchars($filter_nama) ?>" placeholder="Nama / ID Pendaftaran"
                                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                            <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                        </div>
                        <select name="filter_jur" class="bg-white border border-slate-200 rounded-xl text-sm font-medium px-4 py-2.5 outline-none focus:border-indigo-500">
                            <option value="">Semua Jurusan</option>
                            <?php foreach (['TPM', 'TKR', 'TSM', 'TKJ', 'TAV'] as $j): ?>
                                <option value="<?= $j ?>" <?= $filter_jur == $j ? 'selected' : '' ?>><?= $j ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="filter_status" class="bg-white border border-slate-200 rounded-xl text-sm font-medium px-4 py-2.5 outline-none focus:border-indigo-500">
                            <option value="">Semua Status</option>
                            <option value="DU" <?= $filter_status == 'DU' ? 'selected' : '' ?>>Sudah Daftar Ulang</option>
                            <option value="BELUM_DU" <?= $filter_status == 'BELUM_DU' ? 'selected' : '' ?>>Belum Daftar Ulang</option>
                        </select>
                        <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all">
                            <i class="fas fa-search mr-1"></i> Terapkan
                        </button>
                    </form>
                    <div class="mt-3 text-xs text-slate-500">
                        Ditemukan <strong class="text-indigo-600"><?= $total_siswa ?></strong> siswa dengan nomor WA
                    </div>
                </div>

                <!-- Student List -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="select-all" class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <label for="select-all" class="text-sm font-bold text-slate-600 cursor-pointer">Pilih Semua</label>
                        </div>
                        <div class="text-sm">
                            <span class="text-slate-500">Dipilih:</span>
                            <span id="selected-count" class="font-bold text-indigo-600">0</span>
                        </div>
                    </div>

                    <div class="max-h-[400px] overflow-y-auto custom-scroll">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-wider sticky top-0">
                                <tr>
                                    <th class="w-12 px-4 py-3 text-center">#</th>
                                    <th class="px-4 py-3 text-left">ID Pendaftaran</th>
                                    <th class="px-4 py-3 text-left">Nama</th>
                                    <th class="px-4 py-3 text-left">Jurusan</th>
                                    <th class="px-4 py-3 text-left">No. WA</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody id="student-list">
                                <?php $no = 1; while ($s = mysqli_fetch_assoc($result_siswa)): ?>
                                <tr class="border-b border-slate-50 hover:bg-indigo-50/30 transition-colors student-row">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="siswa_ids[]" value="<?= $s['id_siswa'] ?>"
                                               data-nama="<?= htmlspecialchars($s['nama_lengkap']) ?>"
                                               data-id="<?= htmlspecialchars($s['id_pendaftaran']) ?>"
                                               data-hp="<?= htmlspecialchars($s['no_hp']) ?>"
                                               data-jur="<?= htmlspecialchars($s['jurusan']) ?>"
                                               class="student-checkbox w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-indigo-600"><?= htmlspecialchars($s['id_pendaftaran'] ?: '-') ?></td>
                                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                                    <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($s['jurusan']) ?></td>
                                    <td class="px-4 py-3 font-mono text-xs text-emerald-600"><?= htmlspecialchars($s['no_hp']) ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($s['status_siswa'] == 'SUDAH DAFTAR ULANG'): ?>
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-full">DU</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded-full">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php $no++; endwhile; ?>
                            </tbody>
                        </table>
                        <?php if ($total_siswa == 0): ?>
                            <div class="py-12 text-center text-slate-400">
                                <i class="fas fa-users-slash text-4xl mb-3"></i>
                                <p class="font-medium">Tidak ada siswa yang ditemukan</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Message Setup -->
            <div class="space-y-4">

                <!-- Message Setup -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-2 text-indigo-600 text-sm font-black uppercase tracking-wider mb-4">
                        <i class="fas fa-envelope"></i> Setup Pesan
                    </div>

                    <!-- Template Selection -->
                    <div class="mb-4">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Template Pesan</label>
                        <select id="template-select" class="w-full bg-white border border-slate-200 rounded-xl text-sm font-medium px-4 py-3 outline-none focus:border-indigo-500 cursor-pointer">
                            <option value="">-- Pilih Template --</option>
                            <?php mysqli_data_seek($templates, 0); while ($t = mysqli_fetch_assoc($templates)): ?>
                                <option value="<?= $t['kode_template'] ?>" data-template="<?= htmlspecialchars($t['template_text']) ?>">
                                    [<?= strtoupper($t['jenis']) ?>] <?= htmlspecialchars($t['nama_template']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Delay Setting -->
                    <div class="mb-4">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Jeda Antar Pesan</label>
                        <div class="flex items-center gap-3">
                            <input type="range" id="delay-range" min="1" max="10" value="3" class="flex-1 accent-indigo-600">
                            <div class="flex items-center gap-1 bg-slate-100 px-3 py-1.5 rounded-lg">
                                <span id="delay-value" class="font-bold text-indigo-600 text-sm">3</span>
                                <span class="text-xs text-slate-500">detik</span>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Jeda untuk menghindari pemblokiran oleh WhatsApp</p>
                    </div>

                    <!-- Message Preview -->
                    <div class="mb-4">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Preview Pesan</label>
                        <div id="message-preview" class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-600 min-h-[120px]">
                            <p class="text-slate-400 italic">Pilih template untuk melihat preview...</p>
                        </div>
                    </div>

                    <!-- Send Button -->
                    <button type="button" id="btn-send" disabled
                            class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg shadow-indigo-200 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i> Kirim Broadcast
                    </button>
                    <p id="send-info" class="text-center text-[10px] text-slate-400 mt-2"></p>
                </div>

                <!-- Progress -->
                <div id="progress-panel" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hidden">
                    <div class="flex items-center gap-2 text-emerald-600 text-sm font-black uppercase tracking-wider mb-4">
                        <i class="fas fa-spinner fa-spin"></i> Progress Kirim
                    </div>

                    <div class="mb-4">
                        <div class="flex justify-between text-xs font-bold mb-2">
                            <span id="progress-text" class="text-slate-600">Mengirim...</span>
                            <span id="progress-percent" class="text-indigo-600">0%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3">
                            <div id="progress-bar" class="bg-gradient-to-r from-emerald-500 to-teal-400 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>

                    <div id="status-log" class="bg-slate-50 rounded-xl p-3 text-xs font-mono text-slate-600 max-h-32 overflow-y-auto custom-scroll">
                        <div class="text-slate-400 italic">Menunggu...</div>
                    </div>

                    <div id="result-summary" class="mt-4 hidden">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-center">
                                <div class="text-2xl font-black text-emerald-600" id="result-success">0</div>
                                <div class="text-[10px] font-bold text-emerald-600 uppercase">Berhasil</div>
                            </div>
                            <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-center">
                                <div class="text-2xl font-black text-red-600" id="result-failed">0</div>
                                <div class="text-[10px] font-bold text-red-600 uppercase">Gagal</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="broadcast-form" method="POST" action="proses_broadcast.php" class="hidden">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
        <input type="hidden" name="template_kode" id="form-template">
        <input type="hidden" name="delay" id="form-delay">
        <div id="form-siswa-container"></div>
    </form>

    <script>
        // CSRF Token
        const csrfToken = '<?= htmlspecialchars(generate_csrf_token()) ?>';

        // DOM Elements
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.student-checkbox');
        const selectedCount = document.getElementById('selected-count');
        const templateSelect = document.getElementById('template-select');
        const messagePreview = document.getElementById('message-preview');
        const delayRange = document.getElementById('delay-range');
        const delayValue = document.getElementById('delay-value');
        const btnSend = document.getElementById('btn-send');
        const sendInfo = document.getElementById('send-info');
        const progressPanel = document.getElementById('progress-panel');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const progressPercent = document.getElementById('progress-percent');
        const statusLog = document.getElementById('status-log');
        const resultSummary = document.getElementById('result-summary');
        const resultSuccess = document.getElementById('result-success');
        const resultFailed = document.getElementById('result-failed');

        // Select All Toggle
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedCount();
        });

        // Individual Checkbox
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSelectedCount();
                // Uncheck select all if some are unchecked
                const allChecked = [...checkboxes].every(c => c.checked);
                const someChecked = [...checkboxes].some(c => c.checked);
                selectAll.checked = allChecked;
                selectAll.indeterminate = someChecked && !allChecked;
            });
        });

        function updateSelectedCount() {
            const count = [...checkboxes].filter(cb => cb.checked).length;
            selectedCount.textContent = count;
            updateSendButton();
        }

        function updateSendButton() {
            const selected = [...checkboxes].filter(cb => cb.checked).length;
            const template = templateSelect.value;
            btnSend.disabled = selected === 0 || !template;
            sendInfo.textContent = selected > 0 ? `Akan dikirim ke ${selected} siswa` : '';
        }

        // Delay Slider
        delayRange.addEventListener('input', function() {
            delayValue.textContent = this.value;
        });

        // Template Selection - Update Preview
        templateSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const template = option.dataset.template;

            if (template) {
                // Get first selected student for preview
                const firstChecked = [...checkboxes].find(cb => cb.checked);
                let preview = template;

                if (firstChecked) {
                    // Replace placeholders with sample data
                    preview = preview.replace(/\{NAMA\}/gi, firstChecked.dataset.nama);
                    preview = preview.replace(/\{ID_DAFTAR\}/gi, firstChecked.dataset.id || 'SPMB26-XXX');
                    preview = preview.replace(/\{JURUSAN\}/gi, firstChecked.dataset.jur);
                    preview = preview.replace(/\{SEKOLAH\}/gi, '-');
                    preview = preview.replace(/\{NO_HP\}/gi, firstChecked.dataset.hp);
                    preview = preview.replace(/\{ADMIN\}/gi, '<?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>');
                    preview = preview.replace(/\{TANGGAL\}/gi, new Date().toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }));
                    preview = preview.replace(/\{GELOMBANG\}/gi, 'Gelombang 1');
                    preview = preview.replace(/\{BIAYA\}/gi, '150.000');
                    preview = preview.replace(/\{JURUSAN_LAMA\}/gi, '-');
                    preview = preview.replace(/\{JURUSAN_BARU\}/gi, '-');
                    preview = preview.replace(/\{ALASAN\}/gi, '-');

                    // Additional placeholders for REMINDER_DAFTAR_ULANG
                    preview = preview.replace(/\{NO_HP\}/gi, firstChecked.dataset.hp || '083817203455');
                    preview = preview.replace(/\{TANGGAL\}/gi, new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }));
                }

                // Convert \n to actual newlines and escape HTML
                preview = preview.replace(/\\n/g, '\n').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                messagePreview.innerHTML = `<pre class="whitespace-pre-wrap text-xs leading-relaxed">${preview}</pre>`;
            } else {
                messagePreview.innerHTML = '<p class="text-slate-400 italic">Pilih template untuk melihat preview...</p>';
            }

            updateSendButton();
        });

        // Send Broadcast
        btnSend.addEventListener('click', async function() {
            const selected = [...checkboxes].filter(cb => cb.checked);
            if (selected.length === 0) return;

            const template = templateSelect.value;
            const delay = parseInt(delayRange.value);

            // Confirm
            const confirm = await Swal.fire({
                title: 'Konfirmasi Broadcast',
                text: `Kirim pesan ke ${selected.length} siswa? Jeda ${delay} detik antar pesan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                background: '#ffffff',
            });

            if (!confirm.isConfirmed) return;

            // Show progress panel
            progressPanel.classList.remove('hidden');
            btnSend.disabled = true;
            statusLog.innerHTML = '<div class="text-slate-500">Memulai broadcast...</div>';
            resultSummary.classList.add('hidden');

            let success = 0;
            let failed = 0;
            const total = selected.length;

            // Collect selected students data
            const students = selected.map(cb => ({
                id: cb.value,
                nama: cb.dataset.nama,
                id_daftar: cb.dataset.id,
                hp: cb.dataset.hp,
                jur: cb.dataset.jur
            }));

            // Process each student
            for (let i = 0; i < students.length; i++) {
                const student = students[i];

                // Update progress
                const percent = Math.round(((i + 1) / total) * 100);
                progressBar.style.width = percent + '%';
                progressPercent.textContent = percent + '%';
                progressText.textContent = `Mengirim ke ${student.nama}...`;

                // Add to log
                const logEntry = document.createElement('div');
                logEntry.className = 'py-1 border-b border-slate-100';
                statusLog.appendChild(logEntry);
                statusLog.scrollTop = statusLog.scrollHeight;

                try {
                    // Send request to process single message
                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('template_kode', template);
                    formData.append('siswa_id', student.id);
                    formData.append('siswa_nama', student.nama);
                    formData.append('siswa_id_daftar', student.id_daftar);
                    formData.append('siswa_hp', student.hp);
                    formData.append('siswa_jur', student.jur);

                    const response = await fetch('proses_broadcast.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        success++;
                        logEntry.innerHTML = `<span class="text-emerald-600"><i class="fas fa-check-circle mr-1"></i></span> ${student.nama} - Terkirim`;
                    } else {
                        failed++;
                        logEntry.innerHTML = `<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i></span> ${student.nama} - ${result.error || 'Gagal'}`;
                    }
                } catch (err) {
                    failed++;
                    logEntry.innerHTML = `<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i></span> ${student.nama} - Error: ${err.message}`;
                }

                // Delay between messages (except last)
                if (i < students.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, delay * 1000));
                }
            }

            // Show final results
            progressText.textContent = 'Selesai!';
            resultSuccess.textContent = success;
            resultFailed.textContent = failed;
            resultSummary.classList.remove('hidden');

            // Uncheck all checkboxes
            checkboxes.forEach(cb => cb.checked = false);
            selectAll.checked = false;
            updateSelectedCount();

            // Success notification
            await Swal.fire({
                title: 'Broadcast Selesai!',
                text: `Berhasil: ${success}, Gagal: ${failed}`,
                icon: failed > 0 ? 'warning' : 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#4f46e5',
            });
        });
    </script>
</body>
</html>