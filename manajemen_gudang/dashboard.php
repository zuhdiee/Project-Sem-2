<?php
// ── WAJIB DI PALING ATAS sebelum output HTML apapun ──────────────
session_start();
include_once 'koneksi.php'; // $conn MySQLi

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// ── Stat Cards dari DB ───────────────────────────────────────────
$total_stok = $conn->query("SELECT COALESCE(SUM(stok), 0) AS total FROM barang")->fetch_assoc()['total'];

$barang_masuk = $conn->query("
    SELECT COALESCE(SUM(jumlah), 0) AS total 
    FROM transaksi_stok 
    WHERE jenis = 'masuk' 
      AND MONTH(created_at) = MONTH(CURDATE()) 
      AND YEAR(created_at)  = YEAR(CURDATE())
")->fetch_assoc()['total'];

$barang_keluar = $conn->query("
    SELECT COALESCE(SUM(jumlah), 0) AS total 
    FROM transaksi_stok 
    WHERE jenis = 'keluar' 
      AND MONTH(created_at) = MONTH(CURDATE()) 
      AND YEAR(created_at)  = YEAR(CURDATE())
")->fetch_assoc()['total'];

$stok_tipis = $conn->query("
    SELECT COUNT(*) AS total 
    FROM barang 
    WHERE stok <= stok_min AND stok_min > 0
")->fetch_assoc()['total'];

// ── Aktivitas Terbaru (10 terakhir) ─────────────────────────────
$aktivitas = $conn->query("
    SELECT ts.jenis, ts.jumlah, ts.created_at,
           b.nama_barang, b.satuan
    FROM transaksi_stok ts
    JOIN barang b ON ts.id_barang = b.id_barang
    ORDER BY ts.created_at DESC
    LIMIT 10
");

// ── Data Grafik 6 Bulan ──────────────────────────────────────
$g_label = $g_masuk = $g_keluar = [];
for ($i = 5; $i >= 0; $i--) {
    $y = date('Y', strtotime("-$i months"));
    $m = date('m', strtotime("-$i months"));
    $g_label[]  = date('M Y', strtotime("-$i months"));
    $g_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='masuk'  AND YEAR(created_at)=$y AND MONTH(created_at)=$m")->fetch_assoc()['t'];
    $g_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) AS t FROM transaksi_stok WHERE jenis='keluar' AND YEAR(created_at)=$y AND MONTH(created_at)=$m")->fetch_assoc()['t'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Putra Surya Agung</title>
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
            color: #64748b; font-weight: 500; font-size: 13px; 
            transition: all 0.2s ease; margin-bottom: 8px;
        }
        .nav-link:hover { color: #2563eb; background: #eff6ff; }
        .nav-active { 
            background: #2563eb; color: white !important; 
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        .nav-label {
            font-size: 10px; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.05em;
            margin: 24px 0 10px 16px;
        }
        .modern-card {
            background: #ffffff; border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07);
            border: 1px solid #cbd5e1; 
        }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include_once 'include/side_panel.php'; ?>

    <main class="flex-1 flex flex-col overflow-y-auto">
        <?php include_once 'include/header.php'; ?>

        <div class="p-8 pt-20">
            <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'akses_ditolak'): ?>
            <div style="background:#fff4f4;border:1px solid #fecaca;color:#7f1d1d;border-radius:12px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:16px;">
                ✕ Akses ditolak — Anda tidak memiliki izin untuk membuka halaman ini.
            </div>
            <?php endif; ?>
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-[20px] font-bold text-slate-800 tracking-tight">Ringkasan Gudang</h1>
                    <p class="text-slate-500 text-[11px]">Memantau pergerakan stok secara real-time.</p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-4 gap-5 mb-8">
                <div class="modern-card p-5 border-l-4 border-l-blue-600">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Total Stok</p>
                    <h3 class="text-xl font-extrabold text-slate-800">
                        <?= number_format($total_stok, 0, ',', '.') ?>
                        <span class="text-[10px] font-normal text-slate-400 ml-1">Pcs</span>
                    </h3>
                    <p class="text-[9px] text-slate-400 mt-1">Semua barang di gudang</p>
                </div>
                <div class="modern-card p-5 border-l-4 border-l-emerald-600">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Barang Masuk</p>
                    <h3 class="text-xl font-extrabold text-slate-800">
                        <?= number_format($barang_masuk, 0, ',', '.') ?>
                    </h3>
                    <p class="text-[9px] text-slate-400 mt-1">Bulan ini</p>
                </div>
                <div class="modern-card p-5 border-l-4 border-l-orange-600">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Barang Keluar</p>
                    <h3 class="text-xl font-extrabold text-slate-800">
                        <?= number_format($barang_keluar, 0, ',', '.') ?>
                    </h3>
                    <p class="text-[9px] text-slate-400 mt-1">Bulan ini</p>
                </div>
                <div class="modern-card p-5 border-l-4 border-l-rose-600">
                    <p class="text-[10px] font-bold text-rose-600 uppercase mb-1">Stok Tipis</p>
                    <h3 class="text-xl font-extrabold text-rose-700">
                        <?= number_format($stok_tipis, 0) ?>
                        <span class="text-[10px] font-normal text-slate-400 ml-1">Item</span>
                    </h3>
                    <p class="text-[9px] text-slate-400 mt-1">Di bawah stok minimum</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <!-- Grafik -->
                <div class="col-span-2 modern-card p-6 h-[380px] flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h4 class="font-bold text-slate-800 text-[13px]">Tren Pergerakan Stok</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">Barang masuk vs keluar 6 bulan terakhir</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex gap-3 text-[11px]">
                                <span class="flex items-center gap-1.5 font-semibold text-blue-600"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>Masuk</span>
                                <span class="flex items-center gap-1.5 font-semibold text-rose-500"><span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>Keluar</span>
                            </div>
                            <a href="analisis_penjualan.php" class="text-[10px] font-bold text-slate-400 bg-slate-50 px-3 py-1 rounded-md border border-slate-100 hover:text-blue-600 hover:border-blue-200 transition">
                                Detail →
                            </a>
                        </div>
                    </div>
                    <div class="flex-1 w-full relative">
                        <canvas id="grafikDashboard"></canvas>
                    </div>
                </div>

                <!-- Aktivitas Terbaru dari DB -->
                <div class="modern-card p-6 h-[380px] flex flex-col">
                    <h4 class="font-bold text-slate-800 text-[13px] mb-5">Aktivitas Terbaru</h4>
                    <div class="flex-1 overflow-y-auto space-y-1 pr-1">

                        <?php if ($aktivitas && $aktivitas->num_rows > 0): ?>
                            <?php while ($row = $aktivitas->fetch_assoc()):
                                $isMasuk   = $row['jenis'] === 'masuk';
                                $jumlahFmt = number_format($row['jumlah'], 0, ',', '.') . ' ' . $row['satuan'];
                                $tanggal   = date('d-m-Y', strtotime($row['created_at']));
                                $jam       = date('H:i', strtotime($row['created_at']));
                            ?>
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 transition border-b border-slate-100 pb-3">
                                <div class="w-8 h-8 rounded-lg <?= $isMasuk ? 'bg-emerald-100 text-emerald-600' : 'bg-orange-100 text-orange-600' ?> flex items-center justify-center shrink-0">
                                    <?php if ($isMasuk): ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <?php else: ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-slate-800 truncate">
                                        <?= $isMasuk ? 'Restock' : 'Keluar' ?> <?= htmlspecialchars($row['nama_barang']) ?>
                                    </p>
                                    <p class="text-[9px] text-slate-400">
                                        <?= $tanggal ?> • <?= $jam ?> •
                                        <span class="<?= $isMasuk ? 'text-emerald-600' : 'text-orange-500' ?> font-semibold">
                                            <?= $isMasuk ? '+' : '-' ?><?= $jumlahFmt ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <?php endwhile; ?>

                        <?php else: ?>
                            <div class="flex items-center justify-center h-full text-slate-400 italic text-[11px]">
                                Belum ada aktivitas tercatat.
                            </div>
                        <?php endif; ?>

                    </div>
                    <a href="transaksi_barang.php"
                       class="mt-4 text-[10px] font-bold text-blue-600 hover:text-blue-800 w-full text-center transition block">
                        Lihat Semua Riwayat →
                    </a>
                </div>
            </div>
        </div>

        <?php include_once 'include/footer.php'; ?>
    </main>

    <?php include_once 'include/modal_tambah_barang.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('grafikDashboard').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode($g_label) ?>,
        datasets: [
            { label:'Masuk',  data:<?= json_encode($g_masuk) ?>,  borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.09)',  borderWidth:2.5, pointRadius:3, pointHoverRadius:5, tension:.4, fill:true },
            { label:'Keluar', data:<?= json_encode($g_keluar) ?>, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.06)',   borderWidth:2.5, pointRadius:3, pointHoverRadius:5, tension:.4, fill:true }
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
            x:{ grid:{display:false}, ticks:{font:{size:10,family:'Plus Jakarta Sans'}, color:'#94a3b8'} },
            y:{ beginAtZero:true, grid:{color:'#f1f5f9',drawBorder:false},
                ticks:{font:{size:10}, color:'#94a3b8', callback:v=>v>=1000?(v/1000).toFixed(1)+'k':v} }
        }
    }
});
</script>
</body>
</html>