<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php"); exit;
}
if (!isset($_SESSION['role']) || in_array($_SESSION['role'], ['Karyawan'])) {
    header("Location: dashboard.php?pesan=akses_ditolak"); exit;
}

// ── Filter periode ─────────────────────────────────────────────
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

// ── Stat Cards ─────────────────────────────────────────────────
$r_keluar = $conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok ts WHERE jenis='keluar' AND $where_date")->fetch_assoc()['t'];
$r_masuk  = $conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok ts WHERE jenis='masuk'  AND $where_date")->fetch_assoc()['t'];
$r_trx    = $conn->query("SELECT COUNT(*) AS t FROM transaksi_stok ts WHERE $where_date")->fetch_assoc()['t'];
$r_tipis  = (int) $conn->query("SELECT COUNT(*) AS t FROM barang WHERE stok <= stok_min AND stok_min > 0")->fetch_assoc()['t'];

$prev       = $conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='keluar' AND MONTH(created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))")->fetch_assoc()['t'];
$pct_change = $prev > 0 ? round((($r_keluar - $prev) / $prev) * 100, 1) : 0;

// ── Grafik 1 hari (per jam hari ini) ──────────────────────────
$grafik1h_label = $grafik1h_masuk = $grafik1h_keluar = [];
for ($h = 0; $h <= 23; $h++) {
    $jam = str_pad($h, 2, '0', STR_PAD_LEFT);
    $grafik1h_label[]  = $jam.':00';
    $grafik1h_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='masuk'  AND DATE(created_at)=CURDATE() AND HOUR(created_at)=$h")->fetch_assoc()['t'];
    $grafik1h_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='keluar' AND DATE(created_at)=CURDATE() AND HOUR(created_at)=$h")->fetch_assoc()['t'];
}

// ── Grafik 7 hari terakhir ─────────────────────────────────────
$grafik7h_label = $grafik7h_masuk = $grafik7h_keluar = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $grafik7h_label[]  = date('d M', strtotime("-$i days"));
    $grafik7h_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='masuk'  AND DATE(created_at)='$tgl'")->fetch_assoc()['t'];
    $grafik7h_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='keluar' AND DATE(created_at)='$tgl'")->fetch_assoc()['t'];
}

// ── Grafik 1 bulan terakhir (30 hari) ─────────────────────────
$grafik_label = $grafik_masuk_arr = $grafik_keluar_arr = [];
for ($i = 29; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $grafik_label[]      = date('d M', strtotime("-$i days"));
    $grafik_masuk_arr[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='masuk'  AND DATE(created_at)='$tgl'")->fetch_assoc()['t'];
    $grafik_keluar_arr[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='keluar' AND DATE(created_at)='$tgl'")->fetch_assoc()['t'];
}

// ── Barang Terlaris ────────────────────────────────────────────
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

// ── Rekap hari ini (1 hari) ────────────────────────────────────
$rekap_harini_rows = [];
$rhi_res = $conn->query("
    SELECT DATE(created_at) AS tgl,
           SUM(CASE WHEN jenis='masuk'  THEN jumlah ELSE 0 END) AS masuk,
           SUM(CASE WHEN jenis='keluar' THEN jumlah ELSE 0 END) AS keluar
    FROM transaksi_stok
    WHERE DATE(created_at) = CURDATE()
    GROUP BY DATE(created_at)
");
if ($rhi_res) while ($r = $rhi_res->fetch_assoc()) $rekap_harini_rows[] = $r;

// ── Rekap harian 7 hari ────────────────────────────────────────
$rekap_harian_rows = [];
$rh_res = $conn->query("
    SELECT DATE(created_at) AS tgl,
           SUM(CASE WHEN jenis='masuk'  THEN jumlah ELSE 0 END) AS masuk,
           SUM(CASE WHEN jenis='keluar' THEN jumlah ELSE 0 END) AS keluar
    FROM transaksi_stok
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at) ORDER BY tgl DESC
");
if ($rh_res) while ($r = $rh_res->fetch_assoc()) $rekap_harian_rows[] = $r;

// ── Rekap bulanan 1 bulan ──────────────────────────────────────
$rekap_bulanan_rows = [];
$rb_res = $conn->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS bulan,
           DATE_FORMAT(created_at,'%M %Y') AS bulan_label,
           SUM(CASE WHEN jenis='masuk'  THEN jumlah ELSE 0 END) AS masuk,
           SUM(CASE WHEN jenis='keluar' THEN jumlah ELSE 0 END) AS keluar
    FROM transaksi_stok
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m'), DATE_FORMAT(created_at,'%M %Y')
    ORDER BY bulan DESC
");
if ($rb_res) while ($r = $rb_res->fetch_assoc()) $rekap_bulanan_rows[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Penjualan | Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Plus Jakarta Sans',sans-serif; background:#f1f5f9; color:#334155; font-size:13px; }
        .nav-link { display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:12px; color:#64748b; font-weight:500; font-size:13px; transition:all .2s; margin-bottom:8px; }
        .nav-link:hover { color:#2563eb; background:#eff6ff; }
        .nav-active { background:#2563eb; color:white!important; box-shadow:0 4px 12px rgba(37,99,235,.2); }
        .nav-label { font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; margin:24px 0 10px 16px; }
        .modern-card { background:#fff; border-radius:20px; box-shadow:0 10px 15px -3px rgba(0,0,0,.07); border:1px solid #cbd5e1; }

        /* ── Dropdown ── */
        .dd-wrap { position:relative; display:inline-block; }
        .dd-btn {
            display:flex; align-items:center; gap:6px;
            padding:7px 14px; border-radius:12px; font-size:11px; font-weight:700;
            border:1.5px solid #2563eb; color:#2563eb; background:#eff6ff;
            cursor:pointer; transition:all .15s; white-space:nowrap; user-select:none;
        }
        .dd-btn:hover { background:#dbeafe; }
        .dd-menu {
            position:absolute; top:calc(100% + 6px); right:0;
            background:white; border:1.5px solid #e2e8f0; border-radius:14px;
            box-shadow:0 10px 30px rgba(0,0,0,.12); min-width:170px;
            z-index:200; overflow:hidden; display:none;
        }
        .dd-menu.open { display:block; animation:fadeIn .12s ease; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:translateY(0)} }
        .dd-item {
            display:block; width:100%; padding:9px 16px;
            font-size:12px; font-weight:600; color:#64748b;
            background:none; border:none; text-align:left; cursor:pointer; transition:background .1s;
        }
        .dd-item:hover { background:#f8fafc; color:#2563eb; }
        .dd-item.active { color:#2563eb; background:#eff6ff; }

        thead th { font-size:10px; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; padding:10px 14px; border-bottom:2px solid #f1f5f9; white-space:nowrap; }
        tbody td { padding:11px 14px; border-bottom:1px solid #f8fafc; font-size:12px; vertical-align:middle; }
        tr:hover td { background:#f8fafc; }
        ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:99px}
    </style>
</head>
<body class="flex h-screen overflow-hidden">

<?php include 'include/side_panel.php'; ?>

<main class="flex-1 flex flex-col overflow-y-auto">
    <?php include 'include/header.php'; ?>

    <div class="p-8 pt-20">

        <!-- ── Header Row ──────────────────────────────────────── -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
            <div>
                <h1 class="text-[20px] font-bold text-slate-800 tracking-tight">Analisis Penjualan</h1>
                <p class="text-slate-500 text-[11px]">Pantau tren keluar masuk barang secara real-time.</p>
            </div>
            <div class="flex gap-2 flex-wrap items-center">

                <!-- Dropdown Filter Periode -->
                <div class="dd-wrap" id="wrapPeriode">
                    <button class="dd-btn" onclick="toggleDD('menuPeriode', event)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg>
                        <span id="lblPeriode"><?= $label_periode ?></span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2.5"/></svg>
                    </button>
                    <div class="dd-menu" id="menuPeriode">
                        <?php foreach(['hari_ini'=>'Hari Ini','bulan_ini'=>'Bulan Ini','3_bulan'=>'3 Bulan Terakhir','tahun_ini'=>'Tahun Ini'] as $v=>$l): ?>
                        <a href="?periode=<?= $v ?>" class="dd-item <?= $periode===$v?'active':'' ?>"><?= $l ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tombol Cetak PDF -->
                <button onclick="cetakPDF()" class="bg-blue-600 hover:bg-blue-700 active:scale-95 text-white px-4 py-2 rounded-xl text-[11px] font-bold shadow-lg shadow-blue-100 flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2.5"/></svg>
                    Cetak Laporan PDF
                </button>
            </div>
        </div>

        <!-- ── Stat Cards ──────────────────────────────────────── -->
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

        <!-- ── Grafik (full width) ─────────────────────────────── -->
        <div class="modern-card p-6 mb-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-[14px] font-bold text-slate-800">Tren Pergerakan Barang</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5" id="subGrafik">Masuk vs Keluar · 30 hari terakhir</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Dropdown Tampilan Grafik -->
                    <div class="dd-wrap" id="wrapGrafik">
                        <button class="dd-btn" onclick="toggleDD('menuGrafik', event)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2"/></svg>
                            <span id="lblGrafik">1 Hari</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2.5"/></svg>
                        </button>
                        <div class="dd-menu" id="menuGrafik">
                            <button class="dd-item active" onclick="switchGrafik('1hari', this)">1 Hari &nbsp;<span class="text-slate-400 font-normal">(per jam)</span></button>
                            <button class="dd-item" onclick="switchGrafik('7hari', this)">7 Hari &nbsp;<span class="text-slate-400 font-normal">(terakhir)</span></button>
                            <button class="dd-item" onclick="switchGrafik('1bulan', this)">1 Bulan &nbsp;<span class="text-slate-400 font-normal">(30 hari)</span></button>
                        </div>
                    </div>
                    <!-- Legend -->
                    <div class="flex gap-3 text-[11px]">
                        <span class="flex items-center gap-1.5 font-semibold text-blue-600"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>Masuk</span>
                        <span class="flex items-center gap-1.5 font-semibold text-rose-500"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>Keluar</span>
                    </div>
                </div>
            </div>
            <div style="height:280px;position:relative;">
                <canvas id="grafikTren"></canvas>
            </div>
        </div>

        <!-- ── Barang Terlaris + Rekap ─────────────────────────── -->
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
                    <table class="w-full" id="tabelTerlaris">
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

            <!-- Rekap dengan Dropdown -->
            <div class="modern-card overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-[13px] font-bold text-slate-800">Rekap</h2>
                    <!-- Dropdown Rekap -->
                    <div class="dd-wrap" id="wrapRekap">
                        <button class="dd-btn" style="padding:4px 10px;" onclick="toggleDD('menuRekap', event)">
                            <span id="lblRekap">1 Hari</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2.5"/></svg>
                        </button>
                        <div class="dd-menu" id="menuRekap">
                            <button class="dd-item active" onclick="switchRekap('harini', this)">1 Hari Terakhir</button>
                            <button class="dd-item" onclick="switchRekap('harian', this)">7 Hari Terakhir</button>
                            <button class="dd-item" onclick="switchRekap('bulanan', this)">1 Bulan Terakhir</button>
                        </div>
                    </div>
                </div>

                <!-- Rekap Hari Ini (1 hari) -->
                <div id="rekapHarini">
                    <table class="w-full text-[11px]">
                        <thead><tr class="bg-slate-50/60">
                            <th class="text-left px-4 py-2.5 text-[10px]">Tanggal</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-emerald-500">Masuk</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-blue-500">Keluar</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rekap_harini_rows)): ?>
                        <tr><td colspan="3" class="text-center py-6 text-slate-300 text-[11px]">Belum ada transaksi hari ini</td></tr>
                        <?php else: foreach ($rekap_harini_rows as $rhi): ?>
                        <tr class="hover:bg-slate-50 transition border-b border-slate-50">
                            <td class="px-4 py-2.5 font-semibold text-slate-700"><?= date('d M Y', strtotime($rhi['tgl'])) ?> <span class="text-slate-400 font-normal">(Hari Ini)</span></td>
                            <td class="px-4 py-2.5 text-right font-bold text-emerald-600">+<?= number_format($rhi['masuk']) ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-blue-600">-<?= number_format($rhi['keluar']) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Rekap Harian -->
                <div id="rekapHarian" style="display:none;">
                    <table class="w-full text-[11px]">
                        <thead><tr class="bg-slate-50/60">
                            <th class="text-left px-4 py-2.5 text-[10px]">Tanggal</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-emerald-500">Masuk</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-blue-500">Keluar</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rekap_harian_rows)): ?>
                        <tr><td colspan="3" class="text-center py-6 text-slate-300 text-[11px]">Belum ada data</td></tr>
                        <?php else: foreach ($rekap_harian_rows as $rh): ?>
                        <tr class="hover:bg-slate-50 transition border-b border-slate-50">
                            <td class="px-4 py-2.5 font-semibold text-slate-700"><?= date('d M', strtotime($rh['tgl'])) ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-emerald-600">+<?= number_format($rh['masuk']) ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-blue-600">-<?= number_format($rh['keluar']) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Rekap Bulanan (hidden) -->
                <div id="rekapBulanan" style="display:none;">
                    <table class="w-full text-[11px]">
                        <thead><tr class="bg-slate-50/60">
                            <th class="text-left px-4 py-2.5 text-[10px]">Bulan</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-emerald-500">Masuk</th>
                            <th class="text-right px-4 py-2.5 text-[10px] text-blue-500">Keluar</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rekap_bulanan_rows)): ?>
                        <tr><td colspan="3" class="text-center py-6 text-slate-300 text-[11px]">Belum ada data</td></tr>
                        <?php else: foreach ($rekap_bulanan_rows as $rb): ?>
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

        </div><!-- /grid -->
    </div><!-- /p-8 -->
    <?php include 'include/footer.php'; ?>
</main>

<script>
// ── Data grafik ────────────────────────────────────────────────
const dataGrafik = {
    '1hari':  { label:<?= json_encode($grafik1h_label) ?>,     masuk:<?= json_encode($grafik1h_masuk) ?>,    keluar:<?= json_encode($grafik1h_keluar) ?>,   sub:'Masuk vs Keluar · Hari Ini (per jam)' },
    '7hari':  { label:<?= json_encode($grafik7h_label) ?>,     masuk:<?= json_encode($grafik7h_masuk) ?>,    keluar:<?= json_encode($grafik7h_keluar) ?>,   sub:'Masuk vs Keluar · 7 Hari Terakhir' },
    '1bulan': { label:<?= json_encode($grafik_label) ?>,       masuk:<?= json_encode($grafik_masuk_arr) ?>,  keluar:<?= json_encode($grafik_keluar_arr) ?>,  sub:'Masuk vs Keluar · 30 Hari Terakhir' }
};

// ── Init chart ─────────────────────────────────────────────────
const ctxTren = document.getElementById('grafikTren').getContext('2d');
let grafikTren = new Chart(ctxTren, {
    type: 'line',
    data: {
        labels: dataGrafik['1hari'].label,
        datasets: [
            { label:'Masuk',  data:dataGrafik['1hari'].masuk,  borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.08)',  borderWidth:2.5, pointRadius:2, pointHoverRadius:5, tension:.4, fill:true },
            { label:'Keluar', data:dataGrafik['1hari'].keluar, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.06)',   borderWidth:2.5, pointRadius:2, pointHoverRadius:5, tension:.4, fill:true }
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
            x:{ grid:{display:false}, ticks:{font:{size:10,family:'Plus Jakarta Sans'}, color:'#94a3b8', maxTicksLimit:12} },
            y:{ beginAtZero:true, grid:{color:'#f1f5f9',drawBorder:false},
                ticks:{font:{size:10}, color:'#94a3b8', callback:v=>v>=1000?(v/1000).toFixed(1)+'k':v} }
        }
    }
});

// ── Switch grafik ──────────────────────────────────────────────
function switchGrafik(mode, btn) {
    const d = dataGrafik[mode];
    grafikTren.data.labels           = d.label;
    grafikTren.data.datasets[0].data = d.masuk;
    grafikTren.data.datasets[1].data = d.keluar;
    grafikTren.update();
    const lblMap = { '1hari':'1 Hari', '7hari':'7 Hari', '1bulan':'1 Bulan' };
    document.getElementById('lblGrafik').textContent  = lblMap[mode];
    document.getElementById('subGrafik').textContent  = d.sub;
    document.querySelectorAll('#menuGrafik .dd-item').forEach(i => i.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('menuGrafik').classList.remove('open');
}

// ── Switch rekap ───────────────────────────────────────────────
function switchRekap(mode, btn) {
    document.getElementById('rekapHarini').style.display  = mode==='harini'  ? '' : 'none';
    document.getElementById('rekapHarian').style.display  = mode==='harian'  ? '' : 'none';
    document.getElementById('rekapBulanan').style.display = mode==='bulanan' ? '' : 'none';
    const lblMap = { harini:'1 Hari', harian:'7 Hari', bulanan:'1 Bulan' };
    document.getElementById('lblRekap').textContent = lblMap[mode];
    document.querySelectorAll('#menuRekap .dd-item').forEach(i => i.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('menuRekap').classList.remove('open');
}

// ── Dropdown toggle ────────────────────────────────────────────
function toggleDD(id, e) {
    e.stopPropagation();
    const el = document.getElementById(id);
    const isOpen = el.classList.contains('open');
    document.querySelectorAll('.dd-menu').forEach(m => m.classList.remove('open'));
    if (!isOpen) el.classList.add('open');
}
document.addEventListener('click', () => {
    document.querySelectorAll('.dd-menu').forEach(m => m.classList.remove('open'));
});

// ── Cetak PDF ──────────────────────────────────────────────────
function cetakPDF() {
    const { jsPDF } = window.jspdf;
    const doc  = new jsPDF({ orientation:'portrait', unit:'mm', format:'a4' });
    const now  = new Date();
    const tgl  = now.toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' });
    const jam  = now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
    const periode = document.getElementById('lblPeriode').textContent;

    // Header biru
    doc.setFillColor(37, 99, 235);
    doc.rect(0, 0, 210, 30, 'F');
    doc.setTextColor(255,255,255);
    doc.setFontSize(15); doc.setFont('helvetica','bold');
    doc.text('Laporan Analisis Penjualan', 14, 12);
    doc.setFontSize(9); doc.setFont('helvetica','normal');
    doc.text('Putra Surya Agung – Logistic System', 14, 19);
    doc.text('Periode: ' + periode + '   |   Dicetak: ' + tgl + ' ' + jam, 14, 25);

    // Stat boxes
    doc.setTextColor(51,65,85);
    doc.setFontSize(10); doc.setFont('helvetica','bold');
    doc.text('Ringkasan Periode', 14, 38);

    const stats = [
        ['Total Keluar', '<?= number_format($r_keluar) ?>', [37,99,235]],
        ['Total Masuk',  '<?= number_format($r_masuk) ?>',  [16,185,129]],
        ['Transaksi',    '<?= number_format($r_trx) ?>',    [139,92,246]],
        ['Stok Tipis',   '<?= number_format($r_tipis) ?>',  [239,68,68]],
    ];
    stats.forEach(([lbl, val, col], i) => {
        const x = 14 + i * 46;
        doc.setFillColor(...col);
        doc.roundedRect(x, 42, 43, 22, 3, 3, 'F');
        doc.setTextColor(255,255,255);
        doc.setFontSize(7.5); doc.setFont('helvetica','normal');
        doc.text(lbl, x+4, 50);
        doc.setFontSize(14); doc.setFont('helvetica','bold');
        doc.text(val, x+4, 59);
    });

    // Barang Terlaris
    doc.setTextColor(51,65,85);
    doc.setFontSize(10); doc.setFont('helvetica','bold');
    doc.text('Barang Terlaris – ' + periode, 14, 74);

    <?php
    $pdf_terlaris = [];
    foreach ($terlaris_rows as $i => $t) {
        $pdf_terlaris[] = [
            $i+1,
            $t['nama_barang'],
            $t['merek'] ?? '-',
            $t['nama_kategori'] ?? '-',
            number_format($t['total_keluar']).' '.$t['satuan'],
            number_format($t['jumlah_trx']).'x'
        ];
    }
    ?>
    doc.autoTable({
        startY: 77,
        head: [['#','Nama Barang','Merek','Kategori','Total Keluar','Transaksi']],
        body: <?= json_encode($pdf_terlaris ?: [['','Belum ada data','','','','']]) ?>,
        styles: { fontSize:8, cellPadding:2.5 },
        headStyles: { fillColor:[37,99,235], textColor:255, fontStyle:'bold' },
        alternateRowStyles: { fillColor:[248,250,252] },
        columnStyles: { 0:{cellWidth:8,halign:'center'}, 4:{halign:'right'}, 5:{halign:'right'} },
        margin: { left:14, right:14 }
    });

    // Rekap Harian
    let y1 = doc.lastAutoTable.finalY + 8;
    doc.setTextColor(51,65,85); doc.setFontSize(10); doc.setFont('helvetica','bold');
    doc.text('Rekap 7 Hari Terakhir', 14, y1);
    <?php
    $pdf_harian = [];
    foreach ($rekap_harian_rows as $rh) {
        $pdf_harian[] = [date('d M Y', strtotime($rh['tgl'])), '+'.$rh['masuk'], '-'.$rh['keluar']];
    }
    ?>
    doc.autoTable({
        startY: y1 + 3,
        head: [['Tanggal','Masuk','Keluar']],
        body: <?= json_encode($pdf_harian ?: [['Belum ada data','','']]) ?>,
        styles: { fontSize:8, cellPadding:2.5 },
        headStyles: { fillColor:[16,185,129], textColor:255, fontStyle:'bold' },
        alternateRowStyles: { fillColor:[248,250,252] },
        columnStyles: { 1:{halign:'right',textColor:[16,185,129]}, 2:{halign:'right',textColor:[37,99,235]} },
        margin: { left:14, right:14 }
    });

    // Rekap Bulanan
    let y2 = doc.lastAutoTable.finalY + 8;
    doc.setTextColor(51,65,85); doc.setFontSize(10); doc.setFont('helvetica','bold');
    doc.text('Rekap 6 Bulan Terakhir', 14, y2);
    <?php
    $pdf_bulanan = [];
    foreach ($rekap_bulanan_rows as $rb) {
        $pdf_bulanan[] = [$rb['bulan_label'], '+'.$rb['masuk'], '-'.$rb['keluar']];
    }
    ?>
    doc.autoTable({
        startY: y2 + 3,
        head: [['Bulan','Masuk','Keluar']],
        body: <?= json_encode($pdf_bulanan ?: [['Belum ada data','','']]) ?>,
        styles: { fontSize:8, cellPadding:2.5 },
        headStyles: { fillColor:[139,92,246], textColor:255, fontStyle:'bold' },
        alternateRowStyles: { fillColor:[248,250,252] },
        columnStyles: { 1:{halign:'right',textColor:[16,185,129]}, 2:{halign:'right',textColor:[37,99,235]} },
        margin: { left:14, right:14 }
    });

    // Footer setiap halaman
    const total = doc.internal.getNumberOfPages();
    for (let i = 1; i <= total; i++) {
        doc.setPage(i);
        doc.setFontSize(7); doc.setTextColor(148,163,184);
        doc.text('Warehouse Management System v1.0 © 2026 Putra Surya Agung', 14, 290);
        doc.text('Hal. ' + i + ' / ' + total, 196, 290, { align:'right' });
    }

    doc.save('Laporan_Analisis_<?= $periode ?>_<?= date("Ymd") ?>.pdf');
}
</script>
</body>
</html>