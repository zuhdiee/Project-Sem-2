<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php"); exit;
}

// Hanya izinkan akses untuk role 'admin' atau 'owner'
if (!isset($_SESSION['role']) || in_array($_SESSION['role'], ['Karyawan'])) {
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

// Simpan rekap sebagai array agar bisa dipakai ulang di template PDF
$rekap_harian_arr = [];
if ($rekap_harian) while ($r = $rekap_harian->fetch_assoc()) $rekap_harian_arr[] = $r;
$rekap_bulanan_arr = [];
if ($rekap_bulanan) while ($r = $rekap_bulanan->fetch_assoc()) $rekap_bulanan_arr[] = $r;

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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

        /* Loading overlay saat generate PDF */
        #pdf-loading { display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:9999; align-items:center; justify-content:center; flex-direction:column; gap:14px; }
        #pdf-loading.aktif { display:flex; }
        #pdf-loading .spinner { width:44px; height:44px; border:4px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .8s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

<!-- Loading overlay saat generate PDF -->
<div id="pdf-loading">
    <div class="spinner"></div>
    <p style="color:white;font-size:13px;font-weight:600;">Membuat PDF, mohon tunggu…</p>
</div>

<?php include 'include/side_panel.php'; ?>

<main class="flex-1 flex flex-col overflow-y-auto">
    <?php include 'include/header.php'; ?>

    <div id="konten-laporan" class="p-8 pt-20">

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
                <button onclick="bukaModalCetak()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-[11px] font-bold shadow-lg shadow-blue-100 flex items-center gap-2 transition">
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
                    <img id="printImgTren" class="print-chart-img" style="display:none;position:absolute;top:0;left:0;" alt="Grafik Tren">
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
                    <img id="printImgDonut" class="print-chart-img" style="display:none;position:absolute;top:0;left:0;" alt="Grafik Donut">
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
                        <?php if (empty($rekap_harian_arr)): ?>
                        <tr><td colspan="3" class="text-center py-6 text-slate-300 text-[11px]">Belum ada data</td></tr>
                        <?php else: foreach ($rekap_harian_arr as $rh): ?>
                        <tr class="hover:bg-slate-50 transition border-b border-slate-50">
                            <td class="px-4 py-2.5 font-semibold text-slate-700"><?= date('d M', strtotime($rh['tgl'])) ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-emerald-600">+<?= number_format($rh['masuk']) ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-blue-600">-<?= number_format($rh['keluar']) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
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
                        <?php if (empty($rekap_bulanan_arr)): ?>
                        <tr><td colspan="3" class="text-center py-6 text-slate-300 text-[11px]">Belum ada data</td></tr>
                        <?php else: foreach ($rekap_bulanan_arr as $rb): ?>
                        <tr class="hover:bg-slate-50 transition border-b border-slate-50">
                            <td class="px-4 py-2.5 font-semibold text-slate-700"><?= $rb['bulan_label'] ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-emerald-600">+<?= number_format($rb['masuk']) ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-blue-600">-<?= number_format($rb['keluar']) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
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
// ── Cetak Laporan ─────────────────────────────────────────────
let orientasiCetak = 'landscape';

function bukaModalCetak() {
    document.getElementById('modal-cetak').style.display = 'flex';
}
function tutupModalCetak() {
    document.getElementById('modal-cetak').style.display = 'none';
}
function pilihOrientasi(val) {
    orientasiCetak = val;
    // Fix: gunakan style langsung, bukan classList agar tidak ada konflik CSS
    document.querySelectorAll('.ori-btn').forEach(function(b) {
        if (b.dataset.val === val) {
            b.style.borderColor = '#2563eb';
            b.style.background  = '#eff6ff';
            b.querySelectorAll('div').forEach(function(d){ d.style.color='#1d4ed8'; });
        } else {
            b.style.borderColor = '#e2e8f0';
            b.style.background  = 'white';
            b.querySelectorAll('div').forEach(function(d){ d.style.color = d.dataset.sub ? '#64748b' : '#1e293b'; });
        }
    });
}

function siapkanGambarChart() {
    const cTren  = document.getElementById('grafikTren');
    const cDonut = document.getElementById('grafikDonut');
    const iTren  = document.getElementById('cetak-img-tren');
    const iDonut = document.getElementById('cetak-img-donut');
    if (cTren  && iTren)  iTren.src = cTren.toDataURL('image/png', 1.0);
    if (cDonut && iDonut) iDonut.src = cDonut.toDataURL('image/png', 1.0);
}

function cetakSatuOrientasi(ori, callback) {
    let ps = document.getElementById('cetak-page-style');
    if (!ps) { ps = document.createElement('style'); ps.id='cetak-page-style'; document.head.appendChild(ps); }
    ps.textContent = '@page { size: A4 ' + ori + '; margin: 0; }';
    document.getElementById('cetak-template').classList.add('sedang-cetak');
    setTimeout(function() {
        window.print();
        setTimeout(function() {
            document.getElementById('cetak-template').classList.remove('sedang-cetak');
            if (callback) callback();
        }, 500);
    }, 350);
}

function jalankanCetak() {
    tutupModalCetak();
    siapkanGambarChart();

    if (orientasiCetak === 'keduanya') {
        // Print landscape dulu, setelah selesai print portrait
        cetakSatuOrientasi('landscape', function() {
            setTimeout(function() {
                cetakSatuOrientasi('portrait', null);
            }, 800);
        });
    } else {
        cetakSatuOrientasi(orientasiCetak, null);
    }
}

window.addEventListener('afterprint', function() {
    document.getElementById('cetak-img-tren').src  = '';
    document.getElementById('cetak-img-donut').src = '';
});
</script>

<!-- ══ MODAL PILIH ORIENTASI ══════════════════════════════════ -->
<div id="modal-cetak" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:16px; padding:28px 32px; width:400px; box-shadow:0 20px 60px rgba(0,0,0,.2); font-family:'Plus Jakarta Sans',sans-serif;">
        <h2 style="font-size:15px; font-weight:800; color:#1e293b; margin-bottom:4px;">Cetak Laporan</h2>
        <p style="font-size:11px; color:#94a3b8; margin-bottom:20px;">Pilih orientasi halaman sebelum mencetak.</p>

        <div style="display:flex; gap:10px; margin-bottom:16px;">
            <!-- Landscape (default aktif via inline style) -->
            <button class="ori-btn" data-val="landscape" onclick="pilihOrientasi('landscape')"
                style="flex:1; padding:14px 8px; border-radius:10px; border:2px solid #2563eb; background:#eff6ff; cursor:pointer; font-family:inherit; transition:all .15s;">
                <div style="font-size:22px; margin-bottom:6px; color:#1d4ed8;">&#9645;</div>
                <div style="font-size:12px; font-weight:700; color:#1d4ed8;">Landscape</div>
                <div data-sub="1" style="font-size:10px; color:#1d4ed8; margin-top:2px;">Lebih lebar (A4)</div>
            </button>
            <!-- Portrait -->
            <button class="ori-btn" data-val="portrait" onclick="pilihOrientasi('portrait')"
                style="flex:1; padding:14px 8px; border-radius:10px; border:2px solid #e2e8f0; background:white; cursor:pointer; font-family:inherit; transition:all .15s;">
                <div style="font-size:22px; margin-bottom:6px; color:#1e293b;">&#9644;</div>
                <div style="font-size:12px; font-weight:700; color:#1e293b;">Portrait</div>
                <div data-sub="1" style="font-size:10px; color:#64748b; margin-top:2px;">Lebih tinggi (A4)</div>
            </button>
            <!-- Keduanya -->
            <button class="ori-btn" data-val="keduanya" onclick="pilihOrientasi('keduanya')"
                style="flex:1; padding:14px 8px; border-radius:10px; border:2px solid #e2e8f0; background:white; cursor:pointer; font-family:inherit; transition:all .15s;">
                <div style="font-size:22px; margin-bottom:6px; color:#1e293b;">&#9645;&#9644;</div>
                <div style="font-size:12px; font-weight:700; color:#1e293b;">Keduanya</div>
                <div data-sub="1" style="font-size:10px; color:#64748b; margin-top:2px;">L + P (A4)</div>
            </button>
        </div>

        <div style="display:flex; gap:8px;">
            <button onclick="jalankanCetak()" style="flex:1; padding:10px; background:#2563eb; color:white; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit;">
                &#8595; Download PDF
            </button>
            <button onclick="tutupModalCetak()" style="padding:10px 16px; background:#f1f5f9; color:#64748b; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit;">
                Batal
            </button>
        </div>
    </div>
</div>

<style>
/* ── TEMPLATE CETAK (tersembunyi di layar, muncul saat print) ── */
#cetak-template { display:none; }
#cetak-template.sedang-cetak { display:block !important; }

@media print {
    * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
    body > *:not(#cetak-template) { display:none !important; }
    #cetak-template {
        display:block !important;
        width:100% !important;
        font-family:Arial,Helvetica,sans-serif;
        font-size:12px;
        color:#334155;
        padding:8mm;
        box-sizing:border-box;
    }
    canvas { display:none !important; }
    .cetak-chart-img { display:block !important; }
}
</style>

<!-- ══ TEMPLATE CETAK ═════════════════════════════════════════ -->
<div id="cetak-template" style="background:white; font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#334155; padding:10mm; box-sizing:border-box; width:1050px;">

    <!-- KOP -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:3px solid #2563eb; padding-bottom:10px; margin-bottom:16px;">
        <tr>
            <td style="vertical-align:middle;">
                <div style="font-size:18px; font-weight:900; color:#1e293b;">Putra Surya Agung</div>
                <div style="font-size:10px; color:#64748b; margin-top:3px;">LOGISTIC SYSTEM &#183; Laporan Analisis Penjualan</div>
            </td>
            <td style="vertical-align:middle; text-align:right;">
                <div style="display:inline-block; background:#eff6ff; color:#2563eb; font-size:10px; font-weight:700; padding:4px 12px; border-radius:6px;">Periode: <?= htmlspecialchars($label_periode) ?></div>
                <div style="font-size:10px; color:#94a3b8; margin-top:5px;">Dicetak: <?= date('d M Y') ?> &#183; <?= date('H:i') ?></div>
            </td>
        </tr>
    </table>

    <!-- STAT CARDS -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px; table-layout:fixed;">
        <tr>
            <?php
            $cards = [
                ['label'=>'Total Keluar','value'=>number_format($r_keluar),'sub'=>($pct_change>=0?'&#9650;':'&#9660;').' '.abs($pct_change).'% vs bulan lalu','sub_color'=>$pct_change>=0?'#059669':'#e11d48','border'=>'#2563eb'],
                ['label'=>'Total Masuk','value'=>number_format($r_masuk),'sub'=>htmlspecialchars($label_periode),'sub_color'=>'#94a3b8','border'=>'#10b981'],
                ['label'=>'Transaksi','value'=>number_format($r_trx),'sub'=>htmlspecialchars($label_periode),'sub_color'=>'#94a3b8','border'=>'#8b5cf6'],
                ['label'=>'Stok Tipis','value'=>number_format($r_tipis),'sub'=>'Item perlu restock','sub_color'=>'#94a3b8','border'=>'#ef4444','label_color'=>'#e11d48','value_color'=>'#be123c'],
            ];
            foreach ($cards as $ci => $c):
            ?>
            <td style="padding:0 <?= $ci===0?'4px 0 0':($ci===3?'0 0 4px':'4px') ?>;">
                <div style="border:1px solid #e2e8f0; border-left:4px solid <?= $c['border'] ?>; border-radius:8px; padding:10px 13px;">
                    <div style="font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:<?= $c['label_color']??'#64748b' ?>;"><?= $c['label'] ?></div>
                    <div style="font-size:22px; font-weight:900; color:<?= $c['value_color']??'#1e293b' ?>; margin:4px 0 3px; line-height:1;"><?= $c['value'] ?></div>
                    <div style="font-size:9px; color:<?= $c['sub_color'] ?>; font-weight:600;"><?= $c['sub'] ?></div>
                </div>
            </td>
            <?php endforeach; ?>
        </tr>
    </table>

    <!-- GRAFIK -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px; table-layout:fixed;">
        <tr valign="top">
            <td width="63%" style="padding:0;">
                <div style="border:1px solid #e2e8f0; border-radius:10px; padding:13px 15px; margin-right:4px;">
                    <div style="font-size:13px; font-weight:700; color:#1e293b;">Tren Pergerakan Barang</div>
                    <div style="font-size:9px; color:#94a3b8; margin-top:2px; margin-bottom:10px;">Masuk vs Keluar per hari (30 hari terakhir)</div>
                    <img id="cetak-img-tren" class="cetak-chart-img" style="display:none; width:100%; height:170px; object-fit:fill;" alt="">
                    <div style="margin-top:8px; font-size:9px; color:#64748b;">
                        <span style="display:inline-block; width:8px; height:8px; background:#3b82f6; border-radius:50%; margin-right:4px;"></span>Masuk &nbsp;
                        <span style="display:inline-block; width:8px; height:8px; background:#ef4444; border-radius:50%; margin-right:4px;"></span>Keluar
                    </div>
                </div>
            </td>
            <td width="37%" style="padding:0;">
                <div style="border:1px solid #e2e8f0; border-radius:10px; padding:13px 15px; height:100%; margin-left:4px; box-sizing:border-box;">
                    <div style="font-size:13px; font-weight:700; color:#1e293b;">Distribusi Keluar</div>
                    <div style="font-size:9px; color:#94a3b8; margin-top:2px; margin-bottom:10px;">Per kategori &#183; <?= htmlspecialchars($label_periode) ?></div>
                    <?php if (empty($kat_data)): ?>
                    <div style="text-align:center; color:#94a3b8; font-size:10px; padding:20px 0;">Belum ada data</div>
                    <?php else: ?>
                    <div style="text-align:center; margin-bottom:8px;">
                        <img id="cetak-img-donut" class="cetak-chart-img" style="display:none; width:130px; height:130px; object-fit:contain;" alt="">
                    </div>
                    <?php foreach ($kat_data as $ci2 => $k): $clrs=['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444']; ?>
                    <div style="display:table; width:100%; margin-top:5px;">
                        <span style="display:table-cell;">
                            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:<?= $clrs[$ci2%5] ?>; margin-right:4px; vertical-align:middle;"></span>
                            <span style="font-size:10px; color:#475569;"><?= htmlspecialchars($k['nama_kategori']) ?></span>
                        </span>
                        <span style="display:table-cell; text-align:right; font-size:10px; font-weight:700; color:#1e293b;">
                            <?= $kat_total > 0 ? round($k['total']/$kat_total*100) : 0 ?>%
                        </span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- TERLARIS + REKAP -->
    <table width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
        <tr valign="top">
            <!-- Barang Terlaris: 55% -->
            <td width="55%" style="padding:0;">
                <div style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; margin-right:4px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th colspan="5" style="padding:8px 10px; text-align:left; border-bottom:1px solid #f1f5f9;">
                                    <span style="font-size:12px; font-weight:700; color:#1e293b;">Barang Terlaris</span>
                                    <span style="float:right; font-size:9px; background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:4px; font-weight:700;">Keluar Terbanyak</span>
                                    <div style="font-size:9px; color:#94a3b8; font-weight:400; margin-top:2px;">Top 10 &#183; <?= htmlspecialchars($label_periode) ?></div>
                                </th>
                            </tr>
                            <tr style="background:#f8fafc;">
                                <th style="font-size:8px; text-transform:uppercase; color:#94a3b8; padding:5px 8px; text-align:center; border-bottom:1px solid #f1f5f9; width:20px;">#</th>
                                <th style="font-size:8px; text-transform:uppercase; color:#94a3b8; padding:5px 8px; text-align:left; border-bottom:1px solid #f1f5f9; width:38%;">Nama Barang</th>
                                <th style="font-size:8px; text-transform:uppercase; color:#94a3b8; padding:5px 8px; text-align:left; border-bottom:1px solid #f1f5f9;">Kategori</th>
                                <th style="font-size:8px; text-transform:uppercase; color:#94a3b8; padding:5px 8px; text-align:right; border-bottom:1px solid #f1f5f9; width:70px;">Keluar</th>
                                <th style="font-size:8px; text-transform:uppercase; color:#94a3b8; padding:5px 8px; text-align:right; border-bottom:1px solid #f1f5f9; width:30px;">Trx</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($terlaris_rows)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:14px; color:#94a3b8; font-size:10px;">Belum ada data</td></tr>
                        <?php else: foreach ($terlaris_rows as $ri => $t): ?>
                        <tr style="border-bottom:1px solid #f8fafc;">
                            <td style="padding:5px 8px; font-size:10px; color:#94a3b8; text-align:center;"><?= $ri+1 ?></td>
                            <td style="padding:5px 8px;">
                                <div style="font-size:10px; font-weight:700; color:#1e293b;"><?= htmlspecialchars($t['nama_barang']) ?></div>
                                <div style="font-size:8px; color:#94a3b8;"><?= htmlspecialchars($t['merek']??'') ?></div>
                            </td>
                            <td style="padding:5px 8px; font-size:9px; color:#2563eb;"><?= htmlspecialchars($t['nama_kategori']??'-') ?></td>
                            <td style="padding:5px 8px; font-size:10px; font-weight:700; color:#1e293b; text-align:right; white-space:nowrap;"><?= number_format($t['total_keluar']) ?> <span style="font-size:8px; color:#94a3b8; font-weight:400;"><?= htmlspecialchars($t['satuan']) ?></span></td>
                            <td style="padding:5px 8px; font-size:10px; color:#64748b; text-align:right;"><?= number_format($t['jumlah_trx']) ?>x</td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </td>

            <!-- Rekap 7 Hari + Rekap 6 Bulan: 45% -->
            <td width="45%" style="padding:0;">

                <!-- Rekap 7 Hari -->
                <div style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; margin-bottom:10px; margin-left:4px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th colspan="3" style="padding:8px 12px; border-bottom:1px solid #f1f5f9; text-align:left;">
                                    <span style="font-size:12px; font-weight:700; color:#1e293b;">Rekap 7 Hari</span>
                                    <span style="float:right; font-size:9px; color:#94a3b8; font-weight:400;">Masuk / Keluar</span>
                                </th>
                            </tr>
                            <tr style="background:#f8fafc;">
                                <th style="font-size:8px; text-transform:uppercase; color:#94a3b8; padding:5px 12px; text-align:left; border-bottom:1px solid #f1f5f9;">Tanggal</th>
                                <th style="font-size:8px; text-transform:uppercase; color:#10b981; padding:5px 12px; text-align:right; border-bottom:1px solid #f1f5f9;">Masuk</th>
                                <th style="font-size:8px; text-transform:uppercase; color:#3b82f6; padding:5px 12px; text-align:right; border-bottom:1px solid #f1f5f9;">Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rekap_harian_arr)): ?>
                        <tr><td colspan="3" style="text-align:center; padding:10px; color:#94a3b8; font-size:10px;">Belum ada data</td></tr>
                        <?php else: foreach ($rekap_harian_arr as $rh): ?>
                        <tr style="border-bottom:1px solid #f8fafc;">
                            <td style="padding:5px 12px; font-size:10px; font-weight:600; color:#475569;"><?= date('d M', strtotime($rh['tgl'])) ?></td>
                            <td style="padding:5px 12px; font-size:10px; font-weight:700; color:#059669; text-align:right;">+<?= number_format($rh['masuk']) ?></td>
                            <td style="padding:5px 12px; font-size:10px; font-weight:700; color:#2563eb; text-align:right;">-<?= number_format($rh['keluar']) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Rekap 6 Bulan -->
                <div style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; margin-left:4px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th colspan="3" style="padding:8px 12px; border-bottom:1px solid #f1f5f9; text-align:left;">
                                    <span style="font-size:12px; font-weight:700; color:#1e293b;">Rekap 6 Bulan</span>
                                    <span style="float:right; font-size:9px; color:#94a3b8; font-weight:400;">Masuk / Keluar</span>
                                </th>
                            </tr>
                            <tr style="background:#f8fafc;">
                                <th style="font-size:8px; text-transform:uppercase; color:#94a3b8; padding:5px 12px; text-align:left; border-bottom:1px solid #f1f5f9;">Bulan</th>
                                <th style="font-size:8px; text-transform:uppercase; color:#10b981; padding:5px 12px; text-align:right; border-bottom:1px solid #f1f5f9;">Masuk</th>
                                <th style="font-size:8px; text-transform:uppercase; color:#3b82f6; padding:5px 12px; text-align:right; border-bottom:1px solid #f1f5f9;">Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rekap_bulanan_arr)): ?>
                        <tr><td colspan="3" style="text-align:center; padding:10px; color:#94a3b8; font-size:10px;">Belum ada data</td></tr>
                        <?php else: foreach ($rekap_bulanan_arr as $rb): ?>
                        <tr style="border-bottom:1px solid #f8fafc;">
                            <td style="padding:5px 12px; font-size:10px; font-weight:600; color:#475569;"><?= $rb['bulan_label'] ?></td>
                            <td style="padding:5px 12px; font-size:10px; font-weight:700; color:#059669; text-align:right;">+<?= number_format($rb['masuk']) ?></td>
                            <td style="padding:5px 12px; font-size:10px; font-weight:700; color:#2563eb; text-align:right;">-<?= number_format($rb['keluar']) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div style="margin-top:14px; padding-top:8px; border-top:1px solid #e2e8f0; text-align:center; font-size:9px; color:#94a3b8;">
        Warehouse Management System v1.0 &#183; &copy; <?= date('Y') ?> Putra Surya Agung
    </div>
</div>

</body>
</html>