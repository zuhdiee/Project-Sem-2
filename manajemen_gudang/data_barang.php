<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// if (!isset($_SESSION['id'])) {
//     header("Location: login.php");
//     exit;
// }

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
$is_admin      = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang | Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f1f5f9; 
            color: #334155;
            font-size: 13px;
        }
        .sidebar { background: white; border-right: 1px solid #e2e8f0; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 12px;
            color: #64748b; font-weight: 500; font-size: 13px; transition: all 0.2s ease;
            margin-bottom: 8px;
        }
        .nav-link:hover { color: #2563eb; background: #eff6ff; }
        .nav-active { background: #2563eb; color: white !important; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
        .nav-label {
            font-size: 10px; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.05em;
            margin: 24px 0 10px 16px;
        }
        .modern-card {
            background: #ffffff; border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.07);
            border: 1px solid #cbd5e1;
        }
        thead th {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em;
            color: #94a3b8; padding: 12px 14px;
            border-bottom: 2px solid #f1f5f9; white-space: nowrap;
        }
        tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; }
        .status-badge { padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; }
        /* ── Toast Notification ── */
        #toast-container{position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:9999;display:flex;flex-direction:column;align-items:center;gap:10px;pointer-events:none}
        .toast{display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:16px;font-size:12.5px;font-weight:600;min-width:320px;max-width:520px;box-shadow:0 12px 40px rgba(0,0,0,0.15),0 2px 8px rgba(0,0,0,0.08);pointer-events:all;opacity:0;transform:translateY(-20px) scale(0.96);transition:opacity 0.35s cubic-bezier(0.34,1.56,0.64,1),transform 0.35s cubic-bezier(0.34,1.56,0.64,1);position:relative;overflow:hidden}
        .toast.show{opacity:1;transform:translateY(0) scale(1)}
        .toast.hide{opacity:0;transform:translateY(-16px) scale(0.96);transition:opacity 0.25s ease,transform 0.25s ease}
        .toast-ok {background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1.5px solid #6ee7b7;color:#065f46}
        .toast-err{background:linear-gradient(135deg,#fff1f2,#ffe4e6);border:1.5px solid #fca5a5;color:#9f1239}
        .toast-icon{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px}
        .toast-icon-ok {background:#bbf7d0}
        .toast-icon-err{background:#fecdd3}
        .toast-body{flex:1}
        .toast-title{font-size:12px;font-weight:700;margin-bottom:2px}
        .toast-msg{font-size:11px;font-weight:500;opacity:0.85;line-height:1.4}
        .toast-close{width:22px;height:22px;border-radius:6px;border:none;cursor:pointer;background:rgba(0,0,0,0.07);color:inherit;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;transition:background 0.15s}
        .toast-close:hover{background:rgba(0,0,0,0.14)}
        .toast-progress{position:absolute;bottom:0;left:0;height:3px;border-radius:0 0 16px 16px;animation:toast-bar 4.5s linear forwards}
        .toast-progress-ok {background:linear-gradient(90deg,#34d399,#059669)}
        .toast-progress-err{background:linear-gradient(90deg,#fb7185,#e11d48)}
        @keyframes toast-bar{from{width:100%}to{width:0%}}
    </style>
</head>
<body class="flex h-screen overflow-hidden">

<!-- ── Toast Container ── -->
<div id="toast-container"></div>

<?php
// ─── Koneksi Database ─────────────────────────────────────────
include 'koneksi.php';

// ─── Pagination ───────────────────────────────────────────────
$limit  = 10;
$page   = (isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0)
          ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ─── Filter & Search ──────────────────────────────────────────
$filter_kat = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$search     = isset($_GET['q'])        ? trim($_GET['q'])        : '';

// ─── Bangun WHERE ─────────────────────────────────────────────
$where_parts = [];
$where_sql   = '';

if ($filter_kat !== '') {
    $where_parts[] = "b.id_kategori = '" . mysqli_real_escape_string($conn, $filter_kat) . "'";
}
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where_parts[] = "(b.nama_barang LIKE '%$s%' OR b.merek LIKE '%$s%')";
}
$filter_stok = isset($_GET['stok']) ? trim($_GET['stok']) : '';
if ($filter_stok === 'tipis') {
    $where_parts[] = "b.stok < b.stok_min AND b.stok_min > 0";
}
if ($where_parts) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_parts);
}

// ─── Hitung total data ────────────────────────────────────────
$res_count  = mysqli_query($conn, "SELECT COUNT(*) AS total FROM barang b $where_sql");
$total_data = mysqli_fetch_assoc($res_count)['total'];
$total_pages = max(1, (int)ceil($total_data / $limit));

// ─── Statistik kartu ──────────────────────────────────────────
$res_stat = mysqli_query($conn, "
    SELECT
        COUNT(*)                                             AS total_variasi,
        SUM(CASE WHEN stok >= stok_min THEN 1 ELSE 0 END)   AS stok_aman,
        SUM(CASE WHEN stok <  stok_min THEN 1 ELSE 0 END)   AS stok_menipis
    FROM barang
");
$stat = mysqli_fetch_assoc($res_stat);

// ─── Ambil data barang ────────────────────────────────────────
$res_barang = mysqli_query($conn, "
    SELECT
        b.id_barang, b.nama_barang, b.merek,
        b.id_kategori,
        k.nama_kategori,
        b.satuan, b.harga_beli, b.harga_jual,
        b.stok, b.stok_min
    FROM barang b
    LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
    $where_sql
    ORDER BY b.id_barang ASC
    LIMIT $limit OFFSET $offset
");

// ─── Daftar kategori untuk dropdown filter ────────────────────
$kat_list = mysqli_query($conn, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");

// ─── Helper format Rupiah ─────────────────────────────────────
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
?>

    <?php include 'include/side_panel.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto">
        <?php include 'include/header.php'; ?>

        <div class="p-8 pt-20">

            <?php /* Flash messages handled by toast – see #toast-container below */ ?>

            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-[20px] font-bold text-slate-800 tracking-tight">Data Barang</h1>
                    <p class="text-slate-500 text-[11px]">Kelola dan pantau seluruh daftar inventaris gudang.</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-5 mb-8">

                <!-- Card: Total Variasi -->
                <div class="modern-card p-5 flex items-center gap-4 border-l-4 border-l-blue-500">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Total Variasi</p>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-extrabold text-slate-800"><?= number_format($stat['total_variasi']) ?></span>
                            <span class="text-[11px] font-semibold text-slate-400">Jenis Barang</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5">Terdaftar di inventaris</p>
                    </div>
                </div>

                <!-- Card: Stok Aman -->
                <div class="modern-card p-5 flex items-center gap-4 border-l-4 border-l-emerald-500">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Stok Aman</p>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-extrabold text-emerald-700"><?= number_format($stat['stok_aman']) ?></span>
                            <span class="text-[11px] font-semibold text-slate-400">Barang</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5">Di atas batas minimum</p>
                    </div>
                </div>

                <!-- Card: Stok Menipis (clickable → filter tipis) -->
                <a href="?stok=tipis" class="modern-card p-5 flex items-center gap-4 border-l-4 border-l-rose-500 group transition hover:shadow-lg hover:border-rose-200 cursor-pointer">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center flex-shrink-0 group-hover:bg-rose-100 transition">
                        <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-[10px] font-bold text-rose-400 uppercase tracking-wider mb-0.5">Stok Menipis</p>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-extrabold text-rose-700"><?= number_format($stat['stok_menipis']) ?></span>
                            <span class="text-[11px] font-semibold text-slate-400">Barang</span>
                        </div>
                        <p class="text-[10px] text-rose-400 mt-0.5 font-medium group-hover:text-rose-600 transition">Klik untuk lihat daftar →</p>
                    </div>
                </a>

            </div>

            <div class="modern-card overflow-hidden">

                <div class="p-4 border-b border-slate-100 flex flex-wrap justify-between items-center gap-3 bg-slate-50/50">
                    <?php if ($filter_stok === 'tipis'): ?>
                    <div class="w-full flex items-center justify-between bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5 mb-1">
                        <span class="text-[11px] font-bold text-rose-700">⚠ Menampilkan barang dengan stok di bawah minimum</span>
                        <a href="data_barang.php" class="text-[10px] font-bold text-rose-500 hover:text-rose-700 underline">Tampilkan semua</a>
                    </div>
                    <?php endif; ?>
                    <form method="GET" class="flex flex-wrap gap-2">
                        <select name="kategori" onchange="this.form.submit()"
                                class="text-[11px] font-bold bg-white border border-slate-200 rounded-lg px-3 py-1.5 outline-none text-slate-600">
                            <option value="">Semua Kategori</option>
                            <?php while ($k = mysqli_fetch_assoc($kat_list)): ?>
                                <option value="<?= $k['id_kategori'] ?>"
                                    <?= $filter_kat === $k['id_kategori'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-lg px-3 py-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8" stroke-width="2"/><path d="m21 21-4.35-4.35" stroke-width="2"/>
                            </svg>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                   placeholder="Cari nama / merek…"
                                   class="text-[11px] outline-none bg-transparent text-slate-700 w-44 placeholder:text-slate-300">
                        </div>

                        <input type="hidden" name="page" value="1">
                    </form>

                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Menampilkan <?= $total_data > 0 ? ($offset + 1) : 0 ?>–<?= min($offset + $limit, $total_data) ?>
                        dari <?= number_format($total_data) ?> barang
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="w-10 text-center">No</th>
                                <th>Nama Barang</th>
                                <th>Merek</th>
                                <th>Kategori</th>
                                <th class="text-right">Harga Beli</th>
                                <th class="text-right">Harga Jual</th>
                                <th class="text-center">Satuan</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Stok Min</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-[12px]">
                        <?php if (mysqli_num_rows($res_barang) === 0): ?>
                            <tr>
                                <td colspan="11" class="text-center py-12 text-slate-400 text-[12px]">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0H4" stroke-width="1.5"/>
                                    </svg>
                                    Tidak ada data barang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($res_barang)): ?>
                            <?php
                                $stok_habis   = (int)$row['stok'] <= 0;
                                $stok_menipis = !$stok_habis && (int)$row['stok'] < (int)$row['stok_min'];
                            ?>
                            <tr class="hover:bg-slate-50/80 transition">

                                <td class="text-center text-slate-400 font-bold"><?= $no++ ?></td>

                                <td>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($row['nama_barang']) ?></span>
                                    <span class="block text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($row['id_barang']) ?></span>
                                </td>

                                <td class="text-slate-600"><?= htmlspecialchars($row['merek'] ?? '-') ?></td>

                                <td>
                                    <span class="inline-block bg-blue-50 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-md">
                                        <?= htmlspecialchars($row['nama_kategori'] ?? '-') ?>
                                    </span>
                                </td>

                                <td class="text-right font-medium text-slate-700 whitespace-nowrap">
                                    <?= rupiah($row['harga_beli']) ?>
                                </td>

                                <td class="text-right font-semibold text-emerald-700 whitespace-nowrap">
                                    <?= rupiah($row['harga_jual']) ?>
                                </td>

                                <td class="text-center">
                                    <span class="inline-block bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded-md uppercase">
                                        <?= htmlspecialchars($row['satuan']) ?>
                                    </span>
                                </td>

                                <td class="text-center font-bold <?= $stok_habis ? 'text-rose-600' : ($stok_menipis ? 'text-amber-600' : 'text-slate-800') ?>">
                                    <?= number_format((float)$row['stok'], 0, ',', '.') ?>
                                </td>

                                <td class="text-center text-slate-500 text-[11px]">
                                    <?= number_format((float)$row['stok_min'], 0, ',', '.') ?>
                                </td>

                                <td class="text-center">
                                    <?php if ($stok_habis): ?>
                                        <span class="status-badge bg-rose-100 text-rose-700">Habis</span>
                                    <?php elseif ($stok_menipis): ?>
                                        <span class="status-badge bg-amber-100 text-amber-700">Hampir Habis</span>
                                    <?php else: ?>
                                        <span class="status-badge bg-emerald-100 text-emerald-700">Tersedia</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <?php if ($is_admin): ?>
                                    <div class="flex justify-center gap-1">
                                        <button type="button"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="Edit"
                                            data-id="<?= htmlspecialchars($row['id_barang'], ENT_QUOTES) ?>"
                                            data-nama="<?= htmlspecialchars($row['nama_barang'], ENT_QUOTES) ?>"
                                            data-merek="<?= htmlspecialchars($row['merek'] ?? '', ENT_QUOTES) ?>"
                                            data-id_kategori="<?= htmlspecialchars($row['id_kategori'] ?? '', ENT_QUOTES) ?>"
                                            data-satuan="<?= htmlspecialchars($row['satuan'], ENT_QUOTES) ?>"
                                            data-harga_beli="<?= htmlspecialchars($row['harga_beli'], ENT_QUOTES) ?>"
                                            data-harga_jual="<?= htmlspecialchars($row['harga_jual'], ENT_QUOTES) ?>"
                                            data-stok="<?= htmlspecialchars($row['stok'], ENT_QUOTES) ?>"
                                            data-stok_min="<?= htmlspecialchars($row['stok_min'], ENT_QUOTES) ?>"
                                            onclick="openEditModal(this)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"/>
                                            </svg>
                                        </button>
                                        <button onclick="konfirmasiHapus('<?= $row['id_barang'] ?>', '<?= addslashes(htmlspecialchars($row['nama_barang'])) ?>')"
                                                class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-[11px] text-slate-400">Hanya admin dapat mengubah</span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 flex justify-between items-center bg-white">
                    <span class="text-[10px] text-slate-400">
                        Halaman <strong><?= $page ?></strong> dari <strong><?= $total_pages ?></strong>
                    </span>
                    <div class="flex gap-1">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&kategori=<?= urlencode($filter_kat) ?>&q=<?= urlencode($search) ?>"
                           class="w-7 h-7 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-slate-50 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="3"/></svg>
                        </a>
                        <?php else: ?>
                        <span class="w-7 h-7 rounded-lg border border-slate-100 flex items-center justify-center text-slate-300">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="3"/></svg>
                        </span>
                        <?php endif; ?>

                        <?php
                        $start_p = max(1, $page - 2);
                        $end_p   = min($total_pages, $page + 2);
                        for ($p = $start_p; $p <= $end_p; $p++):
                        ?>
                        <a href="?page=<?= $p ?>&kategori=<?= urlencode($filter_kat) ?>&q=<?= urlencode($search) ?>"
                           class="w-7 h-7 rounded-lg text-[11px] font-bold flex items-center justify-center transition
                                  <?= $p === $page ? 'bg-blue-600 text-white shadow-md shadow-blue-100' : 'border border-slate-200 text-slate-500 hover:bg-slate-50' ?>">
                            <?= $p ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?>&kategori=<?= urlencode($filter_kat) ?>&q=<?= urlencode($search) ?>"
                           class="w-7 h-7 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-slate-50 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="3"/></svg>
                        </a>
                        <?php else: ?>
                        <span class="w-7 h-7 rounded-lg border border-slate-100 flex items-center justify-center text-slate-300">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="3"/></svg>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

            </div></div><?php include 'include/footer.php'; ?>
    </main>

    <?php include 'include/modal_tambah_barang.php'; ?>

<?php $kat_for_edit = mysqli_query($conn, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC"); ?>
    <div id="modalEdit" class="fixed inset-0 z-50 hidden items-center justify-center p-4"
         style="background: rgba(15,30,60,0.55); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
            <h3 class="text-[16px] font-bold text-slate-800 text-center mb-2">Edit Barang</h3>
            <form id="editForm" action="proses/edit_barang.php" method="POST" class="space-y-3">
                <input type="hidden" name="id_barang">
                <div>
                    <label class="block text-sm font-semibold mb-1">Nama Barang</label>
                    <input name="nama_barang" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Merek</label>
                    <input name="merek" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Kategori</label>
                    <select name="id_kategori" required class="w-full px-3 py-2 border rounded-lg">
                        <?php while ($k = mysqli_fetch_assoc($kat_for_edit)): ?>
                            <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Satuan</label>
                        <input name="satuan" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Stok Minimum</label>
                        <input name="stok_min" type="number" step="1" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Harga Beli</label>
                        <input name="harga_beli" type="number" step="0.01" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Harga Jual</label>
                        <input name="harga_jual" type="number" step="0.01" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Stok</label>
                    <input name="stok" type="number" step="1" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openEditModal(btn) {
        const d = btn.dataset;
        const f = document.getElementById('editForm');
        f.id_barang.value = d.id || '';
        f.nama_barang.value = d.nama || '';
        f.merek.value = d.merek || '';
        f.id_kategori.value = d.id_kategori || '';
        f.satuan.value = d.satuan || '';
        f.harga_beli.value = d.harga_beli || '';
        f.harga_jual.value = d.harga_jual || '';
        f.stok.value = d.stok || '';
        f.stok_min.value = d.stok_min || '';
        const m = document.getElementById('modalEdit');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeEditModal() { const m = document.getElementById('modalEdit'); m.classList.add('hidden'); m.classList.remove('flex'); }
    document.addEventListener('DOMContentLoaded', function() {
        const me = document.getElementById('modalEdit'); if (me) me.addEventListener('click', function(e){ if (e.target === this) closeEditModal(); });
    });
    </script>

    <div id="modalHapus" class="fixed inset-0 z-50 hidden items-center justify-center p-4"
         style="background: rgba(15,30,60,0.55); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/>
                </svg>
            </div>
            <h3 class="text-[14px] font-bold text-slate-800 text-center mb-1">Hapus Barang?</h3>
            <p class="text-[12px] text-slate-500 text-center mb-1">Anda akan menghapus:</p>
            <p id="hapusNamaBarang" class="text-[13px] font-bold text-slate-800 text-center mb-5"></p>
            <p class="text-[11px] text-slate-400 text-center mb-5">Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex gap-2">
                <button onclick="tutupModalHapus()"
                        class="flex-1 py-2 text-[12px] font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                    Batal
                </button>
                <a id="hapusLink" href="#"
                   class="flex-1 py-2 text-[12px] font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition text-center">
                    Ya, Hapus
                </a>
            </div>
        </div>
    </div>

    <script>
    function konfirmasiHapus(id, nama) {
        document.getElementById('hapusNamaBarang').textContent = nama;
        document.getElementById('hapusLink').href = 'proses/hapus_barang.php?id=' + encodeURIComponent(id)
            + '&redirect=' + encodeURIComponent(window.location.search);
        const m = document.getElementById('modalHapus');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function tutupModalHapus() {
        const m = document.getElementById('modalHapus');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    document.getElementById('modalHapus').addEventListener('click', function(e) {
        if (e.target === this) tutupModalHapus();
    });

    // ── Toast System ──────────────────────────────────────────────
    function showToast(type, title, message) {
        const container = document.getElementById('toast-container');
        const isOk = type === 'ok';

        const toast = document.createElement('div');
        toast.className = 'toast ' + (isOk ? 'toast-ok' : 'toast-err');
        toast.style.position = 'relative';
        toast.style.overflow = 'hidden';
        toast.innerHTML = `
            <div class="toast-icon ${isOk ? 'toast-icon-ok' : 'toast-icon-err'}">
                ${isOk
                    ? `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#059669"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`
                    : `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#e11d48"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round"/></svg>`
                }
            </div>
            <div class="toast-body">
                <div class="toast-title">${title}</div>
                <div class="toast-msg">${message}</div>
            </div>
            <button class="toast-close" onclick="dismissToast(this.closest('.toast'))">✕</button>
            <div class="toast-progress ${isOk ? 'toast-progress-ok' : 'toast-progress-err'}"></div>
        `;
        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));

        // Auto-dismiss setelah 4.5 detik
        const timer = setTimeout(() => dismissToast(toast), 4500);
        toast._timer = timer;
    }
    function dismissToast(toast) {
        if (!toast) return;
        clearTimeout(toast._timer);
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }

    // ── Trigger dari PHP flash session ────────────────────────────
    <?php if ($flash_success): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showToast('ok', 'Berhasil! 🎉', <?= json_encode(htmlspecialchars($flash_success)) ?>);
    });
    <?php endif; ?>
    <?php if ($flash_error): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showToast('err', 'Terjadi Kesalahan', <?= json_encode(htmlspecialchars($flash_error)) ?>);
    });
    <?php endif; ?>
    </script>

</body>
</html>