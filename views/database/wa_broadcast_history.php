<?php
/**
 * WA BROADCAST HISTORY - View sent messages
 */
session_start();
include '../../config.php';

// Check session and role
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['database', 'superuser', 'superuser1'])) {
    header("Location: ../../index.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_template = isset($_GET['template']) ? $_GET['template'] : '';
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($filter_status) {
    $where .= " AND h.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if ($filter_template) {
    $where .= " AND h.template_kode = ?";
    $params[] = $filter_template;
    $types .= "s";
}
if ($filter_search) {
    $where .= " AND (h.nama_siswa LIKE ? OR h.id_pendaftaran LIKE ? OR h.no_hp LIKE ?)";
    $search = "%$filter_search%";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

// Count total
$count_sql = "SELECT COUNT(*) as total FROM wa_broadcast_history h $where";
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $count_sql);
}
$total = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total / $per_page);

// Get history
$sql = "SELECT h.* FROM wa_broadcast_history h $where ORDER BY h.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$history = mysqli_stmt_get_result($stmt);

// Get templates for filter
$templates = mysqli_query($conn, "SELECT DISTINCT template_kode, template_nama FROM wa_broadcast_history ORDER BY template_nama");
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <title>History Broadcast WA | SPMB SMK Pasundan 2</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
    </style>
</head>
<body class="text-slate-700 custom-scroll">

    <!-- Header -->
    <div class="sticky top-0 z-50 flex items-center justify-between px-4 sm:px-8 py-4 bg-white/90 backdrop-blur-md border-b border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="wa_broadcast.php" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 hover:text-indigo-600 transition-all">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <div>
                <h1 class="font-outfit text-xl font-black text-slate-900">History Broadcast WA</h1>
                <p class="text-[10px] text-slate-500">Log semua pesan yang pernah dikirim</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-[10px] font-bold text-slate-400"><?= htmlspecialchars($_SESSION['nama'] ?? 'ADMIN') ?></span>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">

        <!-- Statistics Cards -->
        <?php
        $stats = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT
                COUNT(*) as total,
                SUM(status = 'success') as sukses,
                SUM(status = 'failed') as gagal
            FROM wa_broadcast_history
        "));
        ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-paper-plane text-indigo-600"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900"><?= number_format($stats['total']) ?></div>
                        <div class="text-xs text-slate-500">Total Pesan</div>
                    </div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check text-emerald-600"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-emerald-600"><?= number_format($stats['sukses'] ?? 0) ?></div>
                        <div class="text-xs text-slate-500">Berhasil</div>
                    </div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-times text-red-600"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-red-600"><?= number_format($stats['gagal'] ?? 0) ?></div>
                        <div class="text-xs text-slate-500">Gagal</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm mb-6">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="relative">
                    <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Cari nama, ID, No. HP..."
                           class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                    <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                </div>
                <select name="status" class="bg-white border border-slate-200 rounded-xl text-sm font-medium px-4 py-2.5 outline-none focus:border-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="success" <?= $filter_status == 'success' ? 'selected' : '' ?>>Berhasil</option>
                    <option value="failed" <?= $filter_status == 'failed' ? 'selected' : '' ?>>Gagal</option>
                    <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                </select>
                <select name="template" class="bg-white border border-slate-200 rounded-xl text-sm font-medium px-4 py-2.5 outline-none focus:border-indigo-500">
                    <option value="">Semua Template</option>
                    <?php mysqli_data_seek($templates, 0); while ($t = mysqli_fetch_assoc($templates)): ?>
                        <option value="<?= $t['template_kode'] ?>" <?= $filter_template == $t['template_kode'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['template_nama']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
            </form>
        </div>

        <!-- History Table -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                <span class="text-sm font-bold text-slate-600">
                    Menampilkan <?= number_format($offset + 1) ?> - <?= number_format(min($offset + $per_page, $total)) ?> dari <?= number_format($total) ?> data
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Siswa</th>
                            <th class="px-4 py-3 text-left">No. HP</th>
                            <th class="px-4 py-3 text-left">Template</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-left">Pesan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (mysqli_num_rows($history) == 0): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                    <p class="font-medium">Belum ada history broadcast</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = mysqli_fetch_assoc($history)): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="text-xs font-medium"><?= date('d/m/Y', strtotime($row['created_at'])) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium"><?= htmlspecialchars($row['nama_siswa'] ?: '-') ?></div>
                                    <div class="text-xs text-indigo-600 font-mono"><?= htmlspecialchars($row['id_pendaftaran'] ?: '-') ?></div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($row['no_hp']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-lg">
                                        <?= htmlspecialchars($row['template_kode']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($row['status'] == 'success'): ?>
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-full">
                                            <i class="fas fa-check mr-1"></i> Berhasil
                                        </span>
                                    <?php elseif ($row['status'] == 'failed'): ?>
                                        <span class="px-2 py-1 bg-red-100 text-red-700 text-[10px] font-bold rounded-full">
                                            <i class="fas fa-times mr-1"></i> Gagal
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-amber-100 text-amber-700 text-[10px] font-bold rounded-full">
                                            <i class="fas fa-clock mr-1"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <button onclick="showMessage('<?= htmlspecialchars(addslashes($row['pesan_text'] ?? '')) ?>')"
                                            class="text-indigo-600 hover:text-indigo-800 text-xs">
                                        <i class="fas fa-eye mr-1"></i> Lihat
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
                <?php
                $query_params = [];
                if ($filter_status) $query_params['status'] = $filter_status;
                if ($filter_template) $query_params['template'] = $filter_template;
                if ($filter_search) $query_params['search'] = $filter_search;
                $query_string = http_build_query($query_params);
                ?>
                <a href="?page=<?= $page - 1 ?><?= $query_string ? '&' . $query_string : '' ?>"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-bold rounded-xl <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                    <i class="fas fa-chevron-left mr-1"></i> Prev
                </a>
                <span class="text-sm text-slate-500">
                    Halaman <?= $page ?> dari <?= $total_pages ?>
                </span>
                <a href="?page=<?= $page + 1 ?><?= $query_string ? '&' . $query_string : '' ?>"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-bold rounded-xl <?= $page >= $total_pages ? 'opacity-50 pointer-events-none' : '' ?>">
                    Next <i class="fas fa-chevron-right ml-1"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full max-h-[80vh] overflow-hidden shadow-2xl">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 class="font-bold text-slate-900">Isi Pesan</h3>
                <button onclick="closeModal()" class="w-8 h-8 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-times text-slate-500"></i>
                </button>
            </div>
            <div class="p-5 overflow-y-auto max-h-[60vh]">
                <pre id="messageContent" class="whitespace-pre-wrap text-sm text-slate-600 font-sans"></pre>
            </div>
        </div>
    </div>

    <script>
        function showMessage(text) {
            document.getElementById('messageContent').textContent = text;
            document.getElementById('messageModal').classList.remove('hidden');
            document.getElementById('messageModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('messageModal').classList.add('hidden');
            document.getElementById('messageModal').classList.remove('flex');
        }

        document.getElementById('messageModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
