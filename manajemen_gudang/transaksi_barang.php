<?php
// Pastikan session_start hanya dipanggil SEKALI
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

include 'koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ── Fetch kategori (untuk form tambah barang baru) ───────────
$kategori_list = [];
$res_kat = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
if ($res_kat) while ($row = $res_kat->fetch_assoc()) $kategori_list[] = $row;

// ── Fetch barang (join kategori) ──────────────────────────────
$barang_list = [];
$res = $conn->query("
    SELECT b.id_barang, b.nama_barang, b.merek, b.satuan,
           b.stok, b.stok_min, b.harga_beli, b.harga_jual,
           k.nama_kategori
    FROM barang b
    LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
    ORDER BY b.nama_barang ASC
");
while ($row = $res->fetch_assoc()) $barang_list[] = $row;

// ── Stat cards ────────────────────────────────────────────────
$stat_masuk  = $conn->query("SELECT COALESCE(SUM(jumlah),0) as total, COUNT(*) as count FROM transaksi_stok WHERE jenis='masuk'  AND DATE(created_at)=CURDATE()")->fetch_assoc();
$stat_keluar = $conn->query("SELECT COALESCE(SUM(jumlah),0) as total, COUNT(*) as count FROM transaksi_stok WHERE jenis='keluar' AND DATE(created_at)=CURDATE()")->fetch_assoc();
$stat_total  = $conn->query("SELECT COUNT(*) as count FROM transaksi_stok WHERE DATE(created_at)=CURDATE()")->fetch_assoc();

// ── Filter Periode & Jenis ────────────────────────────────────
$filter_periode = in_array($_GET['periode'] ?? '', ['1', '7', '30']) ? $_GET['periode'] : '7';
$filter_jenis   = in_array($_GET['jenis']   ?? '', ['masuk', 'keluar']) ? $_GET['jenis'] : 'all';

$where_parts = ["ts.created_at >= DATE_SUB(NOW(), INTERVAL {$filter_periode} DAY)"];
if ($filter_jenis !== 'all') {
    $where_parts[] = "ts.jenis='" . $conn->real_escape_string($filter_jenis) . "'";
}
$where = 'WHERE ' . implode(' AND ', $where_parts);

// Label periode untuk tampilan
$periode_label = ['1' => 'Hari Ini', '7' => '7 Hari Terakhir', '30' => '1 Bulan Terakhir'];

// ── Riwayat ───────────────────────────────────────────────────
$riwayat = $conn->query("
    SELECT ts.id_transaksi, ts.created_at, ts.jenis, ts.jumlah,
           ts.supplier, ts.no_struk, ts.keterangan,
           b.nama_barang, b.merek, b.satuan,
           u.nama_lengkap
    FROM transaksi_stok ts
    JOIN barang b ON ts.id_barang = b.id_barang
    JOIN users  u ON ts.id_user   = u.id
    $where
    ORDER BY ts.created_at DESC
    LIMIT 200
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="include/favicon.png" type="image/png">
<title>Transaksi Barang | Putra Surya Agung</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f1f5f9;color:#334155;font-size:13px}
.nav-link{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;color:#64748b;font-weight:500;font-size:13px;transition:all 0.2s;margin-bottom:8px;text-decoration:none}
.nav-link:hover{color:#2563eb;background:#eff6ff}
.nav-active{background:#2563eb;color:white!important;box-shadow:0 4px 12px rgba(37,99,235,0.2)}
.nav-label{font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px 16px}
.content-card{background:white;border-radius:20px;border:1px solid #e2e8f0;padding:24px}

.tab-btn{flex:1;padding:10px 16px;font-size:12px;font-weight:700;border-radius:10px;border:none;cursor:pointer;transition:all 0.25s}
.tab-masuk.active {background:#2563eb;color:white;box-shadow:0 4px 14px rgba(37,99,235,0.3)}
.tab-keluar.active{background:#f43f5e;color:white;box-shadow:0 4px 14px rgba(244,63,94,0.3)}
.tab-btn:not(.active){background:rgba(255,255,255,0.18);color:rgba(255,255,255,0.75)}

.modal-overlay{position:fixed;inset:0;z-index:50;background:rgba(15,23,42,0.5);backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity 0.3s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal-box{background:white;border-radius:24px;width:100%;max-width:580px;margin:16px;box-shadow:0 30px 70px rgba(0,0,0,0.2);transform:translateY(28px) scale(0.97);transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1);overflow:hidden}
.modal-overlay.open .modal-box{transform:translateY(0) scale(1)}
.modal-header-masuk {background:linear-gradient(135deg,#2563eb,#1d4ed8)}
.modal-header-keluar{background:linear-gradient(135deg,#f43f5e,#e11d48)}

.input-field{width:100%;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:12px;outline:none;font-family:'Plus Jakarta Sans',sans-serif;color:#334155;transition:all 0.2s}
.input-field-masuk:focus {border-color:#2563eb;background:white;box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
.input-field-keluar:focus{border-color:#f43f5e;background:white;box-shadow:0 0 0 3px rgba(244,63,94,0.1)}
label{display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:5px}

.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700}
.badge-masuk {background:#dbeafe;color:#1d4ed8}
.badge-keluar{background:#ffe4e6;color:#be123c}

thead tr th{padding-bottom:14px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.07em;border-bottom:1px solid #f1f5f9}
tbody tr{border-bottom:1px solid #f8fafc;transition:background 0.15s}
tbody tr:hover{background:#f8fafc}
tbody tr td{padding:11px 8px 11px 0;font-size:11px;color:#475569;vertical-align:middle}

/* ── Filter Tabs ── */
.filter-tab{padding:6px 14px;font-size:11px;font-weight:600;border-radius:8px;border:1.5px solid transparent;cursor:pointer;transition:all 0.2s;background:#f8fafc;color:#94a3b8;text-decoration:none;white-space:nowrap}
.filter-tab:hover{color:#475569;background:#f1f5f9}
.filter-tab.f-all   {background:#1e293b;color:white}
.filter-tab.f-masuk {background:#dbeafe;color:#1d4ed8;border-color:#bfdbfe}
.filter-tab.f-keluar{background:#ffe4e6;color:#be123c;border-color:#fecdd3}

/* ── Periode Dropdown ── */
.periode-select{padding:6px 28px 6px 12px;font-size:11px;font-weight:600;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 10px center;-webkit-appearance:none;appearance:none;color:#334155;cursor:pointer;outline:none;transition:all 0.2s}
.periode-select:focus{border-color:#2563eb;background-color:white;box-shadow:0 0 0 3px rgba(37,99,235,0.1)}

.stat-card{border-radius:16px;padding:20px 22px;background:white;border:1px solid #e2e8f0}

.info-panel{margin-top:8px;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;font-size:11px}
.stok-ok  {color:#059669;font-weight:700}
.stok-low {color:#f59e0b;font-weight:700}
.stok-danger{color:#e11d48;font-weight:700}

/* ── Empty State ── */
.empty-state{padding:56px 0;display:flex;flex-direction:column;align-items:center;gap:12px}
.empty-icon{width:56px;height:56px;border-radius:16px;background:#f1f5f9;display:flex;align-items:center;justify-content:center}

/* ── Toast Notification ── */
#toast-container{position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:9999;display:flex;flex-direction:column;align-items:center;gap:10px;pointer-events:none}
.toast{display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:16px;font-size:12.5px;font-weight:600;min-width:320px;max-width:520px;box-shadow:0 12px 40px rgba(0,0,0,0.15),0 2px 8px rgba(0,0,0,0.08);pointer-events:all;opacity:0;transform:translateY(-20px) scale(0.96);transition:opacity 0.35s cubic-bezier(0.34,1.56,0.64,1),transform 0.35s cubic-bezier(0.34,1.56,0.64,1)}
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
<body class="flex h-screen overflow-hidden md:flex-row flex-col">

<?php include 'include/side_panel.php'; ?>

<main class="flex-1 flex flex-col overflow-y-auto pt-14 md:pt-0">
<?php include 'include/header.php'; ?>

<div class="p-4 md:p-8 md:pt-20 pt-4">

    <!-- Page Header -->
    <div class="mb-5 md:mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-[18px] md:text-[20px] font-bold text-slate-800 tracking-tight">Transaksi Barang</h1>
            <p class="text-slate-400 text-[11px] mt-0.5">Kelola barang masuk dan keluar gudang dalam satu tempat.</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                <button onclick="openModal('masuk')" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-3 md:px-4 py-2.5 rounded-xl text-[11px] font-bold shadow-lg shadow-blue-100 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round"/></svg>
                    Barang Masuk
                </button>
                <button onclick="openModal('keluar')" class="flex items-center gap-2 bg-rose-500 hover:bg-rose-600 text-white px-3 md:px-4 py-2.5 rounded-xl text-[11px] font-bold shadow-lg shadow-rose-100 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 12H4" stroke-width="2.5" stroke-linecap="round"/></svg>
                    Barang Keluar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="content-card">

        <!-- Header + Filter -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4 md:mb-5">
            <h2 class="text-[14px] font-bold text-slate-800 flex items-center gap-2">
                <span class="w-1.5 h-5 bg-blue-600 rounded-full"></span>
                Riwayat Transaksi
            </h2>
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Dropdown Periode -->
                <select class="periode-select"
                        onchange="window.location.href='?periode='+this.value+'&jenis=<?= $filter_jenis ?>'">
                    <option value="1"  <?= $filter_periode==='1'  ? 'selected' : '' ?>>Hari Ini</option>
                    <option value="7"  <?= $filter_periode==='7'  ? 'selected' : '' ?>>7 Hari Terakhir</option>
                    <option value="30" <?= $filter_periode==='30' ? 'selected' : '' ?>>1 Bulan Terakhir</option>
                </select>
                <!-- Filter Jenis -->
                <a href="?periode=<?= $filter_periode ?>&jenis=all"    class="filter-tab <?= $filter_jenis==='all'    ? 'f-all'    : '' ?>">Semua</a>
                <a href="?periode=<?= $filter_periode ?>&jenis=masuk"  class="filter-tab <?= $filter_jenis==='masuk'  ? 'f-masuk'  : '' ?>">Masuk</a>
                <a href="?periode=<?= $filter_periode ?>&jenis=keluar" class="filter-tab <?= $filter_jenis==='keluar' ? 'f-keluar' : '' ?>">Keluar</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Waktu</th>
                        <th>Nama Barang</th>
                        <th>Merek</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Supplier / Penerima</th>
                        <th>Keterangan</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($riwayat && $riwayat->num_rows > 0):
                    $no = 1;
                    while ($row = $riwayat->fetch_assoc()):
                        $dt = new DateTime($row['created_at']);
                    ?>
                    <tr>
                        <td class="text-slate-400"><?= $no++ ?></td>
                        <td class="text-slate-400 whitespace-nowrap">
                            <?= $dt->format('d/m/Y') ?>
                            <br><span class="text-[10px]"><?= $dt->format('H:i') ?></span>
                        </td>
                        <td class="font-semibold text-slate-700"><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td class="text-slate-400"><?= htmlspecialchars($row['merek'] ?? '-') ?></td>
                        <td>
                            <?php if ($row['jenis'] === 'masuk'): ?>
                            <span class="badge badge-masuk">↑ Masuk</span>
                            <?php else: ?>
                            <span class="badge badge-keluar">↓ Keluar</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold <?= $row['jenis'] === 'masuk' ? 'text-blue-600' : 'text-rose-500' ?>">
                            <?= $row['jenis'] === 'masuk' ? '+' : '-' ?><?= number_format($row['jumlah'], 0) ?>
                            <span class="text-[10px] font-normal text-slate-400"><?= $row['satuan'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($row['supplier'] ?? '-') ?></td>
                        <td class="text-slate-400 max-w-[120px] truncate"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                        <td class="text-slate-500"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <div class="text-center">
                                    <p class="text-slate-500 font-semibold text-[12px]">Belum ada transaksi</p>
                                    <p class="text-slate-400 text-[11px] mt-0.5">
                                        Tidak ada data transaksi
                                        <?= $filter_jenis !== 'all' ? "<strong>{$filter_jenis}</strong> " : '' ?>
                                        dalam <strong><?= strtolower($periode_label[$filter_periode]) ?></strong>.
                                    </p>
                                </div>
                                <?php if ($filter_jenis !== 'all' || $filter_periode !== '30'): ?>
                                <a href="?periode=30&jenis=all"
                                   class="mt-1 text-[11px] text-blue-600 font-semibold hover:underline">
                                    Lihat semua transaksi →
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>
</main>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
<!-- ===== MODAL ===== -->
<div class="modal-overlay" id="modal-overlay" onclick="closeModalOnBg(event)">
<div class="modal-box" style="max-height:92vh;display:flex;flex-direction:column;overflow:hidden;">

    <!-- Header Modal -->
    <div class="modal-header-masuk p-6 pb-4 flex-shrink-0" id="modal-header">
        <div class="flex items-center justify-between mb-4">
            <div>
                <div class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-0.5">Input Transaksi</div>
                <h3 class="text-white text-[17px] font-bold" id="modal-title">Barang Masuk</h3>
            </div>
            <button onclick="closeModal()"
                class="w-8 h-8 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-all">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <!-- Tab Masuk / Keluar -->
        <div class="flex gap-2 p-1 bg-white/15 rounded-xl">
            <button class="tab-btn tab-masuk active"  id="tab-masuk"  onclick="switchTab('masuk')">↑ Barang Masuk</button>
            <button class="tab-btn tab-keluar"        id="tab-keluar" onclick="switchTab('keluar')">↓ Barang Keluar</button>
        </div>
    </div>

    <!-- Scrollable body -->
    <div class="flex-1 overflow-y-auto" style="-webkit-overflow-scrolling:touch;">

        <!-- ══════════════════════════════════════════
             PANEL MASUK
        ═══════════════════════════════════════════ -->
        <div id="panel-masuk">

            <!-- Toggle: Barang Lama / Barang Baru -->
            <div class="px-6 pt-5 pb-2">
                <div class="flex gap-1 p-1 bg-slate-100 rounded-xl">
                    <button id="toggle-lama"
                        onclick="switchMasukMode('lama')"
                        class="flex-1 py-2 text-[11px] font-bold rounded-lg transition-all bg-white text-blue-700 shadow-sm">
                        Stok Barang Ada
                    </button>
                    <button id="toggle-baru"
                        onclick="switchMasukMode('baru')"
                        class="flex-1 py-2 text-[11px] font-bold rounded-lg transition-all text-slate-500">
                        Tambah Barang Baru
                    </button>
                </div>
            </div>

            <!-- FORM: Barang Lama (restock) -->
            <form id="form-lama" method="POST" action="proses_transaksi.php" class="p-6 pt-3 space-y-4">
                <input type="hidden" name="aksi" value="transaksi">
                <input type="hidden" name="jenis" value="masuk">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Tanggal Transaksi</label>
                        <input type="date" name="tanggal" id="input-date-lama" class="input-field input-field-masuk" required readonly style="pointer-events:none;background:#f1f5f9;color:#94a3b8;cursor:not-allowed;">
                    </div>
                    <div>
                        <label>Nama Supplier</label>
                        <input type="text" name="pihak_terkait" class="input-field input-field-masuk" placeholder="Nama supplier..." required>
                    </div>
                </div>

                <div>
                    <label>Pilih Barang</label>
                    <input list="list-barang" name="barang_label_masuk" id="input-barang" class="input-field input-field-masuk"
                           placeholder="Ketik nama barang..." required
                           oninput="handleBarangInput(this, 'masuk')">
                    <input type="hidden" name="id_barang" id="input-barang-id-masuk">
                    <datalist id="list-barang">
                        <?php foreach ($barang_list as $b): ?>
                        <option value="<?= htmlspecialchars($b['nama_barang'] . ($b['merek'] ? ' (' . $b['merek'] . ')' : '')) ?>"
                                data-id="<?= $b['id_barang'] ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <div id="barang-info" class="info-panel hidden">
                        <div class="flex justify-between mb-1">
                            <span class="text-slate-500">Stok saat ini</span>
                            <span id="info-stok" class="stok-ok">—</span>
                        </div>
                        <div class="flex justify-between mb-1">
                            <span class="text-slate-500">Stok minimum</span>
                            <span id="info-stok-min" class="text-slate-600">—</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Kategori</span>
                            <span id="info-kategori" class="text-slate-600">—</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Jumlah <span id="satuan-hint" class="text-slate-400 font-normal text-[10px]"></span></label>
                        <input type="number" name="jumlah" id="masuk-jumlah" step="1" min="1"
                               oninput="validasiJumlahMasuk()"
                               class="input-field input-field-masuk" placeholder="0" required>
                        <!-- Warning jumlah tidak bulat -->
                        <p id="warnJumlahMasuk" class="hidden items-center gap-1 mt-1 whitespace-nowrap" style="color:#ea580c;font-size:11px;font-weight:600;">
                            ⚠ Jumlah tidak boleh mengandung koma
                        </p>
                    </div>
                    <div>
                        <label>Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" class="input-field input-field-masuk" placeholder="Catatan...">
                    </div>
                </div>

                <button type="submit" id="btnSimpanMasuk"
                        class="w-full py-3 rounded-xl text-[12px] font-bold text-white transition-all"
                        style="background:#2563eb;box-shadow:0 4px 14px rgba(37,99,235,0.3)">
                    Simpan Transaksi Masuk
                </button>
            </form>

            <!-- FORM: Barang Baru -->
            <form id="form-baru" method="POST" action="proses_transaksi.php" class="p-6 pt-3 space-y-4 hidden">
                <input type="hidden" name="aksi" value="barang_baru">

                <!-- Nama Barang -->
                <div>
                    <label>Nama Barang *</label>
                    <input type="text" name="nama_barang" id="nb_nama"
                           class="input-field input-field-masuk" placeholder="Nama barang..." required>
                </div>

                <!-- Merek + Kategori -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Merek</label>
                        <input type="text" name="merek" class="input-field input-field-masuk" placeholder="Merek...">
                    </div>
                    <div>
                        <label>Kategori *</label>
                        <input list="list-kategori" name="kategori_label" id="nb_kategori" class="input-field input-field-masuk"
                               placeholder="Ketik nama kategori..." required
                               oninput="handleKategoriInput(this)">
                        <input type="hidden" name="id_kategori" id="nb_kategori_id">
                        <datalist id="list-kategori">
                            <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?= htmlspecialchars($kat['nama_kategori']) ?>"
                                    data-id="<?= $kat['id_kategori'] ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <!-- Stok + Stok Min + Satuan -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label>Stok Awal</label>
                        <input type="number" name="stok_awal" value="0" min="0" step="1"
                               class="input-field input-field-masuk" placeholder="0">
                    </div>
                    <div>
                        <label>Stok Min</label>
                        <input type="number" name="stok_min" value="10" min="0" step="1"
                               class="input-field input-field-masuk" placeholder="10">
                    </div>
                    <div>
                        <label>Satuan *</label>
                        <select name="satuan" id="nb_satuan" class="input-field input-field-masuk" required>
                            <option value="pcs">Pcs</option>
                            <option value="kg">Kg</option>
                            <option value="dus">Dus</option>
                            <option value="liter">Liter</option>
                            <option value="sak">Sak</option>
                        </select>
                    </div>
                </div>

                <!-- Harga Beli + Harga Jual -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Harga Beli</label>
                        <input type="number" name="harga_beli" id="nb_harga_beli" min="0"
                               oninput="validasiHargaBaru()"
                               class="input-field input-field-masuk" placeholder="0">
                    </div>
                    <div>
                        <label>Harga Jual</label>
                        <input type="number" name="harga_jual" id="nb_harga_jual" min="0"
                               oninput="validasiHargaBaru()"
                               class="input-field input-field-masuk" placeholder="0">
                    </div>
                </div>
                <!-- Warning harga jual < harga beli -->
                <div id="warnHargaBaru" class="hidden items-center gap-2.5 rounded-xl px-4 py-3"
                     style="background:#fffbeb;border:1.5px solid #fcd34d;">
                    <div style="width:28px;height:28px;border-radius:8px;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="15" height="15" fill="none" stroke="#d97706" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p style="color:#92400e;font-size:12px;font-weight:700;line-height:1;margin-bottom:2px;">Harga tidak valid!</p>
                        <p style="color:#b45309;font-size:11px;">Harga jual tidak boleh lebih rendah dari harga beli.</p>
                    </div>
                </div>

                <!-- Supplier + Keterangan -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Supplier / Sumber</label>
                        <input type="text" name="supplier" value=""
                               class="input-field input-field-masuk" placeholder="Nama Supplier">
                    </div>
                    <div>
                        <label>Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" class="input-field input-field-masuk" placeholder="Catatan...">
                    </div>
                </div>

                <button type="submit" id="btnSimpanBaruBaru"
                        class="w-full py-3 rounded-xl text-[12px] font-bold text-white transition-all"
                        style="background:#2563eb;box-shadow:0 4px 14px rgba(37,99,235,0.3)">
                    Simpan Barang Baru
                </button>
            </form>

        </div><!-- /panel-masuk -->

        <!-- ══════════════════════════════════════════
             PANEL KELUAR
        ═══════════════════════════════════════════ -->
        <div id="panel-keluar" class="hidden">
            <form method="POST" action="proses_transaksi.php" class="p-6 space-y-4">
                <input type="hidden" name="aksi" value="transaksi">
                <input type="hidden" name="jenis" value="keluar">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Tanggal Transaksi</label>
                        <input type="date" name="tanggal" id="input-date-keluar" class="input-field input-field-keluar" required readonly style="pointer-events:none;background:#f1f5f9;color:#94a3b8;cursor:not-allowed;">
                    </div>
                    <div>
                        <label>Penerima / Customer</label>
                        <input type="text" name="pihak_terkait" class="input-field input-field-keluar" placeholder="Nama penerima..." required>
                    </div>
                </div>

                <div>
                    <label>Pilih Barang</label>
                    <input list="list-barang" name="barang_label_keluar" id="input-barang-keluar" class="input-field input-field-keluar"
                           placeholder="Ketik nama barang..." required
                           oninput="handleBarangInput(this, 'keluar')">
                    <input type="hidden" name="id_barang" id="input-barang-id-keluar">
                    <div id="barang-info-keluar" class="info-panel hidden">
                        <div class="flex justify-between mb-1">
                            <span class="text-slate-500">Stok saat ini</span>
                            <span id="info-stok-keluar" class="stok-ok">—</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Kategori</span>
                            <span id="info-kategori-keluar" class="text-slate-600">—</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Jumlah <span id="satuan-hint-keluar" class="text-slate-400 font-normal text-[10px]"></span></label>
                        <input type="number" name="jumlah" id="keluar-jumlah" step="1" min="1"
                               oninput="validasiStokKeluar()"
                               class="input-field input-field-keluar" placeholder="0" required>
                        <!-- Warning jumlah tidak bulat -->
                        <p id="warnJumlahKeluar" class="hidden items-center gap-1 mt-1 whitespace-nowrap" style="color:#ea580c;font-size:11px;font-weight:600;">
                            ⚠ Jumlah tidak boleh mengandung koma
                        </p>
                    </div>
                    <div>
                        <label>Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" class="input-field input-field-keluar" placeholder="Catatan...">
                    </div>
                </div>

                <!-- Warning stok tidak cukup -->
                <div id="warnStokKeluar" class="hidden items-center gap-3 rounded-xl px-4 py-3"
                     style="background:#fff1f2;border:1.5px solid #fca5a5;">
                    <div style="width:34px;height:34px;min-width:34px;border-radius:10px;background:#fecdd3;display:flex;align-items:center;justify-content:center;">
                        <svg width="17" height="17" fill="none" stroke="#e11d48" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p style="color:#9f1239;font-size:12px;font-weight:700;line-height:1;margin-bottom:3px;">Stok tidak mencukupi!</p>
                        <p id="warnStokKeluarMsg" style="color:#be123c;font-size:11px;line-height:1.4;">Jumlah keluar melebihi stok yang tersedia. Tambah stok terlebih dahulu.</p>
                    </div>
                    <button type="button" onclick="pindahKeTambahStok()"
                       style="flex-shrink:0;padding:6px 12px;background:#e11d48;color:white;border-radius:8px;font-size:11px;font-weight:700;border:none;cursor:pointer;white-space:nowrap;"
                       onmouseover="this.style.background='#be123c'" onmouseout="this.style.background='#e11d48'">
                        + Tambah Stok
                    </button>
                </div>

                <button type="submit" id="btnSimpanKeluar"
                        class="w-full py-3 rounded-xl text-[12px] font-bold text-white transition-all"
                        style="background:#f43f5e;box-shadow:0 4px 14px rgba(244,63,94,0.3)">
                    Simpan Transaksi Keluar
                </button>
            </form>
        </div><!-- /panel-keluar -->

    </div><!-- /scrollable body -->
</div>
</div>
<?php endif; ?>

<!-- ===== TOAST CONTAINER ===== -->
<div id="toast-container"></div>

<script>
// ── Toast System ──────────────────────────────────────────────────
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

    requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));

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

// ── Trigger dari PHP flash session ──────────────────────────────
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

// ── Pindah ke form Tambah Stok (dari warning keluar) ─────────────
function pindahKeTambahStok() {
    // Ambil barang yang sedang dipilih di form keluar
    const barangLabel = document.getElementById('input-barang-keluar').value;
    const barangId    = document.getElementById('input-barang-id-keluar').value;

    // Switch ke tab masuk
    switchTab('masuk');
    switchMasukMode('lama');

    // Isi otomatis field barang di form masuk jika ada
    if (barangLabel) {
        const inputMasuk = document.getElementById('input-barang');
        const hiddenMasuk = document.getElementById('input-barang-id-masuk');
        if (inputMasuk)  inputMasuk.value  = barangLabel;
        if (hiddenMasuk) hiddenMasuk.value = barangId;
        if (barangId) updateBarangInfoById(barangId);
    }

    // Scroll ke atas modal body
    const body = document.querySelector('.modal-box .flex-1.overflow-y-auto');
    if (body) body.scrollTop = 0;
}

// ── Validasi: Harga Jual < Harga Beli (Form Barang Baru) ─────────
function validasiHargaBaru() {
    const beli  = parseFloat(document.getElementById('nb_harga_beli').value) || 0;
    const jual  = parseFloat(document.getElementById('nb_harga_jual').value) || 0;
    const warn  = document.getElementById('warnHargaBaru');
    const btn   = document.getElementById('btnSimpanBaruBaru');
    const inputJual = document.getElementById('nb_harga_jual');
    const invalid = beli > 0 && jual > 0 && jual < beli;

    if (invalid) {
        warn.classList.remove('hidden'); warn.classList.add('flex');
        inputJual.style.borderColor = '#fbbf24';
        inputJual.style.background  = '#fffbeb';
        inputJual.style.boxShadow   = '0 0 0 3px rgba(251,191,36,0.15)';
        btn.disabled = true;
        btn.style.background  = '#94a3b8';
        btn.style.boxShadow   = 'none';
        btn.style.cursor      = 'not-allowed';
    } else {
        warn.classList.add('hidden'); warn.classList.remove('flex');
        inputJual.style.borderColor = '';
        inputJual.style.background  = '';
        inputJual.style.boxShadow   = '';
        btn.disabled = false;
        btn.style.background  = '#2563eb';
        btn.style.boxShadow   = '0 4px 14px rgba(37,99,235,0.3)';
        btn.style.cursor      = '';
    }
}

// ── Validasi: Jumlah Masuk harus bilangan bulat ──────────────────
function validasiJumlahMasuk() {
    const input = document.getElementById('masuk-jumlah');
    const warn  = document.getElementById('warnJumlahMasuk');
    const btn   = document.getElementById('btnSimpanMasuk');
    const val   = input.value;
    const isDesimal = val !== '' && (val.includes('.') || val.includes(',') || !Number.isInteger(parseFloat(val)));

    if (isDesimal) {
        warn.classList.remove('hidden'); warn.style.display = 'block';
        input.style.borderColor = '#fb923c';
        input.style.background  = '#fff7ed';
        input.style.boxShadow   = '0 0 0 3px rgba(251,146,60,0.15)';
        btn.disabled = true;
        btn.style.background = '#94a3b8';
        btn.style.boxShadow  = 'none';
        btn.style.cursor     = 'not-allowed';
    } else {
        warn.classList.add('hidden'); warn.style.display = '';
        input.style.borderColor = '';
        input.style.background  = '';
        input.style.boxShadow   = '';
        btn.disabled = false;
        btn.style.background = '#2563eb';
        btn.style.boxShadow  = '0 4px 14px rgba(37,99,235,0.3)';
        btn.style.cursor     = '';
    }
}

// ── Validasi: Stok Tidak Cukup (Form Barang Keluar) ──────────────
// selectedStokKeluar diisi saat barang dipilih
let selectedStokKeluar = null;
let selectedSatuanKeluar = '';

function validasiStokKeluar() {
    const jumlahRaw   = document.getElementById('keluar-jumlah').value;
    const jumlah      = parseFloat(jumlahRaw) || 0;
    const warn        = document.getElementById('warnStokKeluar');
    const warnDesimal = document.getElementById('warnJumlahKeluar');
    const btn         = document.getElementById('btnSimpanKeluar');
    const inputJumlah = document.getElementById('keluar-jumlah');

    // Cek dulu apakah desimal
    const isDesimal = jumlahRaw !== '' && (jumlahRaw.includes('.') || jumlahRaw.includes(',') || !Number.isInteger(parseFloat(jumlahRaw)));

    if (isDesimal) {
        warnDesimal.classList.remove('hidden'); warnDesimal.style.display = 'block';
        warn.classList.add('hidden'); warn.classList.remove('flex');
        inputJumlah.style.borderColor = '#fb923c';
        inputJumlah.style.background  = '#fff7ed';
        inputJumlah.style.boxShadow   = '0 0 0 3px rgba(251,146,60,0.15)';
        btn.disabled = true;
        btn.style.background = '#94a3b8';
        btn.style.boxShadow  = 'none';
        btn.style.cursor     = 'not-allowed';
        return;
    } else {
        warnDesimal.classList.add('hidden'); warnDesimal.style.display = '';
    }

    if (selectedStokKeluar === null || jumlah <= 0) {
        warn.classList.add('hidden'); warn.classList.remove('flex');
        btn.disabled = false;
        btn.style.background = '#f43f5e';
        btn.style.boxShadow  = '0 4px 14px rgba(244,63,94,0.3)';
        btn.style.cursor     = '';
        inputJumlah.style.borderColor = '';
        inputJumlah.style.background  = '';
        inputJumlah.style.boxShadow   = '';
        return;
    }

    const stok = parseFloat(selectedStokKeluar);
    const kurang = jumlah > stok;

    if (kurang) {
        const selisih = (jumlah - stok).toLocaleString('id-ID');
        const stokStr = stok.toLocaleString('id-ID');
        document.getElementById('warnStokKeluarMsg').textContent =
            'Stok tersedia hanya ' + stokStr + ' ' + selectedSatuanKeluar +
            ', kurang ' + selisih + ' ' + selectedSatuanKeluar + '. Tambah stok terlebih dahulu.';
        warn.classList.remove('hidden'); warn.classList.add('flex');
        inputJumlah.style.borderColor = '#f43f5e';
        inputJumlah.style.background  = '#fff1f2';
        inputJumlah.style.boxShadow   = '0 0 0 3px rgba(244,63,94,0.15)';
        btn.disabled = true;
        btn.style.background = '#94a3b8';
        btn.style.boxShadow  = 'none';
        btn.style.cursor     = 'not-allowed';
    } else {
        warn.classList.add('hidden'); warn.classList.remove('flex');
        inputJumlah.style.borderColor = '';
        inputJumlah.style.background  = '';
        inputJumlah.style.boxShadow   = '';
        btn.disabled = false;
        btn.style.background = '#f43f5e';
        btn.style.boxShadow  = '0 4px 14px rgba(244,63,94,0.3)';
        btn.style.cursor     = '';
    }
}

const barangData  = <?= json_encode(array_column($barang_list, null, 'id_barang')) ?>;
const kategoriData = <?= json_encode(array_column($kategori_list, null, 'id_kategori')) ?>;

function findBarangByLabel(label) {
    const query = label.trim().toLowerCase();
    if (!query) return null;
    return Object.values(barangData).find(b => {
        const labelText = (b.nama_barang + (b.merek ? ' (' + b.merek + ')' : '')).toLowerCase();
        return labelText === query;
    }) || null;
}

function findKategoriByLabel(label) {
    const query = label.trim().toLowerCase();
    if (!query) return null;
    return Object.values(kategoriData).find(k => k.nama_kategori.toLowerCase() === query) || null;
}

function handleKategoriInput(input) {
    const kategori = findKategoriByLabel(input.value);
    document.getElementById('nb_kategori_id').value = kategori ? kategori.id_kategori : '';
}

function handleBarangInput(input, mode) {
    const barang = findBarangByLabel(input.value);
    const hiddenId = document.getElementById(mode === 'masuk' ? 'input-barang-id-masuk' : 'input-barang-id-keluar');
    hiddenId.value = barang ? barang.id_barang : '';

    if (mode === 'masuk') {
        barang ? updateBarangInfoById(barang.id_barang) : document.getElementById('barang-info').classList.add('hidden');
    } else {
        barang ? updateBarangInfoKeluarById(barang.id_barang) : document.getElementById('barang-info-keluar').classList.add('hidden');
    }
}

// ── Modal open/close ──────────────────────────────────────────────
function openModal(jenis = 'masuk') {
    document.getElementById('modal-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    switchTab(jenis);
    const today = new Date().toISOString().split('T')[0];
    const dl = document.getElementById('input-date-lama');
    const dk = document.getElementById('input-date-keluar');
    if (dl) { dl.value = today; dl.readOnly = true; }
    if (dk) { dk.value = today; dk.readOnly = true; }
}
function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
    document.body.style.overflow = '';
    // Reset state validasi
    selectedStokKeluar   = null;
    selectedSatuanKeluar = '';
    const wK = document.getElementById('warnStokKeluar');
    if (wK) { wK.classList.add('hidden'); wK.classList.remove('flex'); }
    const wB = document.getElementById('warnHargaBaru');
    if (wB) { wB.classList.add('hidden'); wB.classList.remove('flex'); }
}
function closeModalOnBg(e) {
    if (e.target === document.getElementById('modal-overlay')) closeModal();
}

// ── Switch tab Masuk / Keluar ────────────────────────────────────
function switchTab(jenis) {
    const isMasuk = jenis === 'masuk';
    const hdr = document.getElementById('modal-header');
    hdr.className = (isMasuk ? 'modal-header-masuk' : 'modal-header-keluar') + ' p-6 pb-4 flex-shrink-0';
    document.getElementById('modal-title').textContent = isMasuk ? 'Barang Masuk' : 'Barang Keluar';
    document.getElementById('tab-masuk').className  = 'tab-btn tab-masuk'  + (isMasuk  ? ' active' : '');
    document.getElementById('tab-keluar').className = 'tab-btn tab-keluar' + (!isMasuk ? ' active' : '');
    document.getElementById('panel-masuk').classList.toggle('hidden', !isMasuk);
    document.getElementById('panel-keluar').classList.toggle('hidden', isMasuk);
}

// ── Toggle Barang Lama / Barang Baru ─────────────────────────────
function switchMasukMode(mode) {
    const isLama = mode === 'lama';
    document.getElementById('form-lama').classList.toggle('hidden', !isLama);
    document.getElementById('form-baru').classList.toggle('hidden', isLama);
    const btnLama = document.getElementById('toggle-lama');
    const btnBaru = document.getElementById('toggle-baru');
    if (isLama) {
        btnLama.className = 'flex-1 py-2 text-[11px] font-bold rounded-lg transition-all bg-white text-blue-700 shadow-sm';
        btnBaru.className = 'flex-1 py-2 text-[11px] font-bold rounded-lg transition-all text-slate-500';
    } else {
        btnLama.className = 'flex-1 py-2 text-[11px] font-bold rounded-lg transition-all text-slate-500';
        btnBaru.className = 'flex-1 py-2 text-[11px] font-bold rounded-lg transition-all bg-white text-blue-700 shadow-sm';
    }
}

// ── Info barang panel Masuk ──────────────────────────────────────
function updateBarangInfoById(id) {
    const info = document.getElementById('barang-info');
    if (!id || !barangData[id]) { info.classList.add('hidden'); return; }
    const b    = barangData[id];
    const stok = parseFloat(b.stok);
    const min  = parseFloat(b.stok_min);
    document.getElementById('satuan-hint').textContent = '(satuan: ' + b.satuan + ')';
    const elStok = document.getElementById('info-stok');
    let cls = 'stok-ok', text = stok.toLocaleString('id-ID') + ' ' + b.satuan;
    if (stok <= 0)        { cls = 'stok-danger'; text += ' ✕ Habis!'; }
    else if (stok <= min) { cls = 'stok-low';    text += ' ⚠ Tipis!'; }
    elStok.className = cls;
    elStok.textContent = text;
    document.getElementById('info-stok-min').textContent  = min.toLocaleString('id-ID') + ' ' + b.satuan;
    document.getElementById('info-kategori').textContent  = b.nama_kategori || '-';
    info.classList.remove('hidden');
}

// ── Info barang panel Keluar ─────────────────────────────────────
function updateBarangInfoKeluarById(id) {
    const info = document.getElementById('barang-info-keluar');
    if (!id || !barangData[id]) {
        info.classList.add('hidden');
        selectedStokKeluar  = null;
        selectedSatuanKeluar = '';
        validasiStokKeluar();
        return;
    }
    const b    = barangData[id];
    const stok = parseFloat(b.stok);
    const min  = parseFloat(b.stok_min);

    // Simpan stok untuk validasi jumlah keluar
    selectedStokKeluar   = b.stok;
    selectedSatuanKeluar = b.satuan;

    document.getElementById('satuan-hint-keluar').textContent = '(satuan: ' + b.satuan + ')';
    const elStok = document.getElementById('info-stok-keluar');
    let cls = 'stok-ok', text = stok.toLocaleString('id-ID') + ' ' + b.satuan;
    if (stok <= 0)        { cls = 'stok-danger'; text += ' ✕ Habis!'; }
    else if (stok <= min) { cls = 'stok-low';    text += ' ⚠ Tipis! — hati-hati!'; }
    elStok.className = cls;
    elStok.textContent = text;
    document.getElementById('info-kategori-keluar').textContent = b.nama_kategori || '-';
    info.classList.remove('hidden');

    // Re-validasi jumlah yang sudah diisi
    validasiStokKeluar();
}
</script>

</body>
</html>