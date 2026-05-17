<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php"); exit;
}

// Hanya izinkan akses untuk role 'admin' atau 'owner'
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Owner'])) {
    header("Location: dashboard.php?pesan=akses_ditolak"); exit;
}

// ── Filter periode ────────────────────────────────────────────
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulan_ini';
switch ($periode) {
    case 'hari_ini':
        $where_date    = "DATE(ts.created_at) = CURDATE()";
        $label_periode = 'Hari Ini'; break;
    case '7_hari':
        $where_date    = "ts.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $label_periode = '7 Hari Terakhir'; break;
    case '3_bulan':
        $where_date    = "ts.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        $label_periode = '3 Bulan Terakhir'; break;
    case 'tahun_ini':
        $where_date    = "YEAR(ts.created_at) = YEAR(CURDATE())";
        $label_periode = 'Tahun Ini'; break;
    default:
        $where_date    = "MONTH(ts.created_at) = MONTH(CURDATE()) AND YEAR(ts.created_at) = YEAR(CURDATE())";
        $label_periode = 'Bulan Ini';
}

// ── Stat Cards ────────────────────────────────────────────────
$r_keluar = $conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok ts WHERE jenis='keluar' AND $where_date")->fetch_assoc()['t'];
$r_masuk  = $conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok ts WHERE jenis='masuk'  AND $where_date")->fetch_assoc()['t'];
$r_trx    = $conn->query("SELECT COUNT(*) AS t FROM transaksi_stok ts WHERE $where_date")->fetch_assoc()['t'];
$r_tipis  = (int) $conn->query("SELECT COUNT(*) AS t FROM barang WHERE stok <= stok_min AND stok_min > 0")->fetch_assoc()['t'];

// % perubahan vs bulan lalu
$prev = $conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='keluar' AND MONTH(created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))")->fetch_assoc()['t'];
$pct_change = $prev > 0 ? round((($r_keluar - $prev) / $prev) * 100, 1) : 0;

// ── Grafik 30 hari ────────────────────────────────────────────
$grafik_label = $grafik_masuk_arr = $grafik_keluar_arr = [];
for ($i = 29; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $grafik_label[]      = date('d M', strtotime("-$i days"));
    $grafik_masuk_arr[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='masuk'  AND DATE(created_at)='$tgl'")->fetch_assoc()['t'];
    $grafik_keluar_arr[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='keluar' AND DATE(created_at)='$tgl'")->fetch_assoc()['t'];
}

// ── Grafik 6 bulan ────────────────────────────────────────────
$grafik6_label = $grafik6_masuk = $grafik6_keluar = [];
for ($i = 5; $i >= 0; $i--) {
    $y = date('Y', strtotime("-$i months"));
    $m = date('m', strtotime("-$i months"));
    $grafik6_label[]  = date('M Y', strtotime("-$i months"));
    $grafik6_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='masuk'  AND YEAR(created_at)=$y AND MONTH(created_at)=$m")->fetch_assoc()['t'];
    $grafik6_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='keluar' AND YEAR(created_at)=$y AND MONTH(created_at)=$m")->fetch_assoc()['t'];
}

// ── Barang Terlaris ───────────────────────────────────────────
$terlaris = $conn->query("
    SELECT b.nama_barang, b.merek, b.satuan,
           k.nama_kategori,
           SUM(ts.jumlah) AS total_keluar,
           COUNT(ts.id_transaksi) AS jumlah_trx
    FROM transaksi_stok ts
    JOIN barang b ON ts.id_barang = b.id_barang
    LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
    WHERE ts.jenis='keluar' AND $where_date
    GROUP BY ts.id_barang, b.nama_barang, b.merek, b.satuan, k.nama_kategori
    ORDER BY total_keluar DESC LIMIT 10
");
$terlaris_rows = [];
if ($terlaris) while ($r = $terlaris->fetch_assoc()) $terlaris_rows[] = $r;
$max_keluar = !empty($terlaris_rows) ? $terlaris_rows[0]['total_keluar'] : 1;

// ── Rekap harian 7 hari ───────────────────────────────────────
$rekap_harian = $conn->query("
    SELECT DATE(created_at) AS tgl,
           SUM(CASE WHEN jenis='masuk'  THEN jumlah ELSE 0 END) AS masuk,
           SUM(CASE WHEN jenis='keluar' THEN jumlah ELSE 0 END) AS keluar,
           COUNT(*) AS trx
    FROM transaksi_stok
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at) ORDER BY tgl DESC
");

// ── Rekap bulanan 6 bulan ─────────────────────────────────────
$rekap_bulanan = $conn->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS bulan,
           DATE_FORMAT(created_at,'%M %Y') AS bulan_label,
           SUM(CASE WHEN jenis='masuk'  THEN jumlah ELSE 0 END) AS masuk,
           SUM(CASE WHEN jenis='keluar' THEN jumlah ELSE 0 END) AS keluar
    FROM transaksi_stok
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m'), DATE_FORMAT(created_at,'%M %Y')
    ORDER BY bulan DESC
");

// ── Kategori aktif (donut) ────────────────────────────────────
$kat_res = $conn->query("
    SELECT k.id_kategori, k.nama_kategori, SUM(ts.jumlah) AS total
    FROM transaksi_stok ts
    JOIN barang b ON ts.id_barang = b.id_barang
    JOIN kategori k ON b.id_kategori = k.id_kategori
    WHERE ts.jenis='keluar' AND $where_date
    GROUP BY k.id_kategori, k.nama_kategori ORDER BY total DESC LIMIT 5
");
$kat_data = []; $kat_total = 0;
if ($kat_res) while ($r = $kat_res->fetch_assoc()) { $kat_data[] = $r; $kat_total += $r['total']; }
$donut_colors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Penjualan | Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Plus Jakarta Sans',sans-serif; background:#f1f5f9; color:#334155; font-size:13px; }
        .sidebar { background:white; border-right:1px solid #e2e8f0; }
        .nav-link { display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:12px; color:#64748b; font-weight:500; font-size:13px; transition:all .2s; margin-bottom:8px; }
        .nav-link:hover { color:#2563eb; background:#eff6ff; }
        .nav-active { background:#2563eb; color:white!important; box-shadow:0 4px 12px rgba(37,99,235,.2); }
        .nav-label { font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; margin:24px 0 10px 16px; }
        .modern-card { background:#fff; border-radius:20px; box-shadow:0 10px 15px -3px rgba(0,0,0,.07); border:1px solid #cbd5e1; }
        .pill { padding:5px 14px; border-radius:10px; font-size:11px; font-weight:700; border:1.5px solid #e2e8f0; color:#64748b; background:white; cursor:pointer; transition:all .15s; text-decoration:none; display:inline-block; }
        .pill:hover,.pill.active { background:#2563eb; color:white; border-color:#2563eb; }
        .tab-btn { padding:7px 14px; font-size:11px; font-weight:700; border-radius:9px; cursor:pointer; transition:all .15s; color:#94a3b8; border:none; background:transparent; }
        .tab-btn.active { background:#eff6ff; color:#2563eb; }
        thead th { font-size:10px; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; padding:10px 14px; border-bottom:2px solid #f1f5f9; white-space:nowrap; }
        tbody td { padding:11px 14px; border-bottom:1px solid #f8fafc; font-size:12px; vertical-align:middle; }
        tr:hover td { background:#f8fafc; }
        ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:99px}
        @media print { .no-print{display:none!important} body{background:white} }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

<?php include 'include/side_panel.php'; ?>

<main class="flex-1 flex flex-col overflow-y-auto">
    <?php include 'include/header.php'; ?>

    <div class="p-8 pt-20">

        <!-- Header -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
            <div>
                <h1 class="text-[20px] font-bold text-slate-800 tracking-tight">Analisis Penjualan</h1>
                <p class="text-slate-500 text-[11px]">Pantau tren keluar masuk barang secara real-time.</p>
            </div>
            <div class="flex gap-2 flex-wrap no-print">
                <?php foreach(['hari_ini'=>'Hari Ini','bulan_ini'=>'Bulan Ini','3_bulan'=>'3 Bulan','tahun_ini'=>'Tahun Ini'] as $v=>$l): ?>
                <a href="?periode=<?= $v ?>" class="pill <?= $periode===$v?'active':'' ?>"><?= $l ?></a>
                <?php endforeach; ?>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-[11px] font-bold shadow-lg shadow-blue-100 flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2.5"/></svg>
                    Cetak Laporan
                </button>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="modern-card p-5 border-l-4 border-l-blue-600">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Total Keluar</p>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 10l7-7m0 0l7 7m-7-7v18" stroke-width="2" stroke-linecap="round"/></svg>
                    </div>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-800"><?= number_format($r_keluar) ?></h3>
                <p class="text-[10px] mt-1 font-semibold <?= $pct_change >= 0 ? 'text-emerald-600' : 'text-rose-500' ?>">
                    <?= $pct_change >= 0 ? '▲' : '▼' ?> <?= abs($pct_change) ?>% vs bulan lalu
                </p>
            </div>
            <div class="modern-card p-5 border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Total Masuk</p>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 14l-7 7m0 0l-7-7m7 7V3" stroke-width="2" stroke-linecap="round"/></svg>
                    </div>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-800"><?= number_format($r_masuk) ?></h3>
                <p class="text-[10px] text-slate-400 mt-1"><?= $label_periode ?></p>
            </div>
            <div class="modern-card p-5 border-l-4 border-l-violet-500">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Transaksi</p>
                    <div class="w-8 h-8 rounded-xl bg-violet-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="1.8"/></svg>
                    </div>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-800"><?= number_format($r_trx) ?></h3>
                <p class="text-[10px] text-slate-400 mt-1"><?= $label_periode ?></p>
            </div>
            <div class="modern-card p-5 border-l-4 border-l-rose-500">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-rose-500 uppercase">Stok Tipis</p>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke-width="2"/></svg>
                    </div>
                </div>
                <h3 class="text-2xl font-extrabold text-rose-700"><?= number_format($r_tipis) ?></h3>
                <p class="text-[10px] mt-1"><a href="data_barang.php" class="text-rose-500 hover:underline font-semibold">Lihat detail →</a></p>
            </div>
        </div>

        <!-- Grafik + Donut -->
        <div class="grid grid-cols-3 gap-5 mb-5">
            <!-- Grafik Tren -->
            <div class="col-span-2 modern-card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-800">Tren Pergerakan Barang</h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">Masuk vs Keluar per hari / bulan</p>
                    </div>
                    <div class="flex items-center gap-3 no-print">
                        <div class="flex gap-1 bg-slate-100 rounded-xl p-1">
                            <button class="tab-btn active" id="tab30" onclick="switchGrafik('harian')">30 Hari</button>
                            <button class="tab-btn" id="tab6"  onclick="switchGrafik('bulanan')">6 Bulan</button>
                        </div>
                        <div class="flex gap-3 text-[11px]">
                            <span class="flex items-center gap-1.5 font-semibold text-blue-600"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>Masuk</span>
                            <span class="flex items-center gap-1.5 font-semibold text-rose-500"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>Keluar</span>
                        </div>
                    </div>
                </div>
                <div style="height:260px;position:relative;">
                    <canvas id="grafikTren"></canvas>
                </div>
            </div>

            <!-- Donut -->
            <div class="modern-card p-6 flex flex-col">
                <h2 class="text-[14px] font-bold text-slate-800 mb-1">Distribusi Keluar</h2>
                <p class="text-[11px] text-slate-400 mb-4">Per kategori · <?= $label_periode ?></p>
                <?php if (empty($kat_data)): ?>
                <div class="flex-1 flex items-center justify-center text-slate-300 text-[12px]">Belum ada data</div>
                <?php else: ?>
                <div style="height:155px;position:relative;margin:0 auto;width:155px;">
                    <canvas id="grafikDonut"></canvas>
                </div>
                <div class="mt-4 space-y-2">
                    <?php foreach ($kat_data as $ci => $k): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?= $donut_colors[$ci % 5] ?>"></div>
                            <span class="text-[11px] text-slate-600 truncate max-w-[110px]"><?= htmlspecialchars($k['nama_kategori']) ?></span>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700"><?= $kat_total > 0 ? round($k['total']/$kat_total*100) : 0 ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Barang Terlaris + Rekap -->
        <div class="grid grid-cols-3 gap-5">

            <!-- Terlaris -->
            <div class="col-span-2 modern-card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-800">Barang Terlaris</h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">Top 10 keluar terbanyak · <?= $label_periode ?></p>
                    </div>
                    <span class="text-[10px] font-bold bg-blue-50 text-blue-600 px-3 py-1 rounded-xl">Keluar Terbanyak</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50/60">
                                <th class="w-10 text-center">#</th>
                                <th>Nama Barang</th>
                                <th>Merek</th>
                                <th>Kategori</th>
                                <th class="text-right">Total Keluar</th>
                                <th class="text-right">Transaksi</th>
                                <th class="w-24">Proporsi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($terlaris_rows)): ?>
                        <tr><td colspan="7" class="text-center py-10 text-slate-300 text-[12px]">Belum ada data transaksi keluar.</td></tr>
                        <?php else: foreach ($terlaris_rows as $rank => $t):
                            $pct = $max_keluar > 0 ? round($t['total_keluar'] / $max_keluar * 100) : 0;
                            $r = $rank + 1;
                        ?>
                        <tr>
                            <td class="text-center">
                                <span class="w-6 h-6 rounded-lg inline-flex items-center justify-center text-[11px] font-extrabold
                                    <?= $r===1 ? 'bg-amber-100 text-amber-600' : ($r===2 ? 'bg-slate-100 text-slate-500' : ($r===3 ? 'bg-orange-50 text-orange-500' : 'text-slate-400')) ?>">
                                    <?= $r ?>
                                </span>
                            </td>
                            <td><p class="font-bold text-slate-800"><?= htmlspecialchars($t['nama_barang']) ?></p></td>
                            <td class="text-slate-500"><?= htmlspecialchars($t['merek'] ?? '-') ?></td>
                            <td><span class="inline-block bg-blue-50 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-md"><?= htmlspecialchars($t['nama_kategori'] ?? '-') ?></span></td>
                            <td class="text-right font-extrabold text-slate-800">
                                <?= number_format($t['total_keluar']) ?>
                                <span class="text-[10px] font-normal text-slate-400"><?= $t['satuan'] ?></span>
                            </td>
                            <td class="text-right text-slate-500"><?= number_format($t['jumlah_trx']) ?>×</td>
                            <td>
                                <div class="w-full bg-slate-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-blue-500 transition-all" style="width:<?= $pct ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rekap Harian + Bulanan -->
            <div class="flex flex-col gap-5">
                <!-- Harian -->
                <div class="modern-card overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-[13px] font-bold text-slate-800">Rekap 7 Hari</h2>
                        <span class="text-[10px] text-slate-400">Masuk / Keluar</span>
                    </div>
                    <table class="w-full text-[11px]">
                        <thead><tr class="bg-slate-50/60">
                            <th class="text-left px-4 py-2.5 text-[10px]">Tanggal</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-emerald-500">Masuk</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-blue-500">Keluar</th>
                        </tr></thead>
                        <tbody>
                        <?php if (!$rekap_harian || $rekap_harian->num_rows === 0): ?>
                        <tr><td colspan="3" class="text-center py-6 text-slate-300 text-[11px]">Belum ada data</td></tr>
                        <?php else: while ($rh = $rekap_harian->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition border-b border-slate-50">
                            <td class="px-4 py-2.5 font-semibold text-slate-700"><?= date('d M', strtotime($rh['tgl'])) ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-emerald-600">+<?= number_format($rh['masuk']) ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-blue-600">-<?= number_format($rh['keluar']) ?></td>
                        </tr>
                        <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Bulanan -->
                <div class="modern-card overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-[13px] font-bold text-slate-800">Rekap 6 Bulan</h2>
                        <span class="text-[10px] text-slate-400">Masuk / Keluar</span>
                    </div>
                    <table class="w-full text-[11px]">
                        <thead><tr class="bg-slate-50/60">
                            <th class="text-left px-4 py-2.5 text-[10px]">Bulan</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-emerald-500">Masuk</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-blue-500">Keluar</th>
                        </tr></thead>
                        <tbody>
                        <?php if (!$rekap_bulanan || $rekap_bulanan->num_rows === 0): ?>
                        <tr><td colspan="3" class="text-center py-6 text-slate-300 text-[11px]">Belum ada data</td></tr>
                        <?php else: while ($rb = $rekap_bulanan->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition border-b border-slate-50">
                            <td class="px-4 py-2.5 font-semibold text-slate-700"><?= $rb['bulan_label'] ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-emerald-600">+<?= number_format($rb['masuk']) ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-blue-600">-<?= number_format($rb['keluar']) ?></td>
                        </tr>
                        <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /p-8 -->
    <?php include 'include/footer.php'; ?>
</main>

<script>
const d30Label  = <?= json_encode($grafik_label) ?>;
const d30Masuk  = <?= json_encode($grafik_masuk_arr) ?>;
const d30Keluar = <?= json_encode($grafik_keluar_arr) ?>;
const d6Label   = <?= json_encode($grafik6_label) ?>;
const d6Masuk   = <?= json_encode($grafik6_masuk) ?>;
const d6Keluar  = <?= json_encode($grafik6_keluar) ?>;

const ctxTren = document.getElementById('grafikTren').getContext('2d');
let grafikTren = new Chart(ctxTren, {
    type: 'line',
    data: {
        labels: d30Label,
        datasets: [
            { label:'Masuk',  data:d30Masuk,  borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.08)',  borderWidth:2.5, pointRadius:2, pointHoverRadius:5, tension:.4, fill:true },
            { label:'Keluar', data:d30Keluar, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.06)',   borderWidth:2.5, pointRadius:2, pointHoverRadius:5, tension:.4, fill:true }
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins: {
            legend:{ display:false },
            tooltip:{ backgroundColor:'#1e293b', titleColor:'#94a3b8', bodyColor:'#f8fafc', padding:10, cornerRadius:10,
                callbacks:{ label: c => ' '+c.dataset.label+': '+c.parsed.y.toLocaleString('id-ID') } }
        },
        scales:{
            x:{ grid:{display:false}, ticks:{font:{size:10,family:'Plus Jakarta Sans'}, color:'#94a3b8', maxTicksLimit:10} },
            y:{ beginAtZero:true, grid:{color:'#f1f5f9',drawBorder:false},
                ticks:{font:{size:10}, color:'#94a3b8', callback:v=>v>=1000?(v/1000).toFixed(1)+'k':v} }
        }
    }
});

function switchGrafik(mode) {
    document.getElementById('tab30').classList.toggle('active', mode==='harian');
    document.getElementById('tab6').classList.toggle('active',  mode==='bulanan');
    grafikTren.data.labels              = mode==='harian' ? d30Label  : d6Label;
    grafikTren.data.datasets[0].data   = mode==='harian' ? d30Masuk  : d6Masuk;
    grafikTren.data.datasets[1].data   = mode==='harian' ? d30Keluar : d6Keluar;
    grafikTren.update();
}

<?php if (!empty($kat_data)): ?>
new Chart(document.getElementById('grafikDonut').getContext('2d'), {
    type:'doughnut',
    data:{
        labels: <?= json_encode(array_column($kat_data,'nama_kategori')) ?>,
        datasets:[{ data:<?= json_encode(array_column($kat_data,'total')) ?>, backgroundColor:['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444'], borderWidth:2, borderColor:'#fff', hoverOffset:6 }]
    },
    options:{ responsive:true, maintainAspectRatio:false, cutout:'70%',
        plugins:{ legend:{display:false}, tooltip:{backgroundColor:'#1e293b', bodyColor:'#f8fafc', padding:8, cornerRadius:8,
            callbacks:{label:c=>' '+c.label+': '+c.raw.toLocaleString('id-ID')} } } }
});
<?php endif; ?>
</script>
</body>
</html>