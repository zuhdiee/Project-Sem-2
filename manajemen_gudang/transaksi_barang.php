<?php
// Pastikan session_start hanya dipanggil SEKALI
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
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

// ── Riwayat ───────────────────────────────────────────────────
$filter_jenis = in_array($_GET['jenis'] ?? '', ['masuk','keluar']) ? $_GET['jenis'] : 'all';
$where = $filter_jenis !== 'all' ? "WHERE ts.jenis='" . $conn->real_escape_string($filter_jenis) . "'" : '';

$riwayat = $conn->query("
    SELECT ts.id_transaksi, ts.created_at, ts.jenis, ts.jumlah,
           ts.supplier, ts.no_struk, ts.keterangan,
           b.nama_barang, b.merek, b.satuan,
           u.nama_lengkap
    FROM transaksi_stok ts
    JOIN barang b ON ts.id_barang = b.id_barang
    JOIN users  u ON ts.id_user   = u.id
    $where
    ORDER BY ts.created_at DESC LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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

.filter-tab{padding:6px 14px;font-size:11px;font-weight:600;border-radius:8px;border:1.5px solid transparent;cursor:pointer;transition:all 0.2s;background:#f8fafc;color:#94a3b8;text-decoration:none}
.filter-tab.f-all   {background:#1e293b;color:white}
.filter-tab.f-masuk {background:#dbeafe;color:#1d4ed8;border-color:#bfdbfe}
.filter-tab.f-keluar{background:#ffe4e6;color:#be123c;border-color:#fecdd3}

.stat-card{border-radius:16px;padding:20px 22px;background:white;border:1px solid #e2e8f0}

.info-panel{margin-top:8px;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;font-size:11px}
.stok-ok  {color:#059669;font-weight:700}
.stok-low {color:#f59e0b;font-weight:700}
.stok-danger{color:#e11d48;font-weight:700}

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
<body class="flex h-screen overflow-hidden">

<?php include 'include/side_panel.php'; ?>

<main class="flex-1 flex flex-col overflow-y-auto">
<?php include 'include/header.php'; ?>

<div class="p-8 pt-20">

    <!-- Page Header -->
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-[20px] font-bold text-slate-800 tracking-tight">Transaksi Barang</h1>
            <p class="text-slate-400 text-[11px] mt-0.5">Kelola barang masuk dan keluar gudang dalam satu tempat.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openModal('masuk')"
                class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-[11px] font-bold shadow-lg shadow-blue-100 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round"/></svg>
                Barang Masuk
            </button>
            <button onclick="openModal('keluar')"
                class="flex items-center gap-2 bg-rose-500 hover:bg-rose-600 text-white px-4 py-2.5 rounded-xl text-[11px] font-bold shadow-lg shadow-rose-100 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 12H4" stroke-width="2.5" stroke-linecap="round"/></svg>
                Barang Keluar
            </button>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="content-card">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-[14px] font-bold text-slate-800 flex items-center gap-2">
                <span class="w-1.5 h-5 bg-blue-600 rounded-full"></span>
                Riwayat Transaksi
            </h2>
            <div class="flex items-center gap-2">
                <a href="?jenis=all"    class="filter-tab <?= $filter_jenis==='all'    ? 'f-all'    : '' ?>">Semua</a>
                <a href="?jenis=masuk"  class="filter-tab <?= $filter_jenis==='masuk'  ? 'f-masuk'  : '' ?>">Masuk</a>
                <a href="?jenis=keluar" class="filter-tab <?= $filter_jenis==='keluar' ? 'f-keluar' : '' ?>">Keluar</a>
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
                    while ($row = $riwayat->fetch_assoc()): ?>
                    <tr>
                        <td class="text-slate-400"><?= $no++ ?></td>
                        <td class="text-slate-400 whitespace-nowrap">
                            <?= date('d/m/Y', strtotime($row['created_at'])) ?>
                            <br><span class="text-[10px]"><?= date('H:i', strtotime($row['created_at'])) ?></span>
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
                        <td class="font-bold <?= $row['jenis']==='masuk' ? 'text-blue-600' : 'text-rose-500' ?>">
                            <?= $row['jenis']==='masuk' ? '+' : '-' ?><?= number_format($row['jumlah'], 0) ?>
                            <span class="text-[10px] font-normal text-slate-400"><?= $row['satuan'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($row['supplier'] ?? '-') ?></td>
                        <td class="text-slate-400 max-w-[120px] truncate"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                        <td class="text-slate-500"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="9" class="py-14 text-center text-slate-400 italic text-[12px]">
                            Belum ada transaksi<?= $filter_jenis !== 'all' ? ' '.$filter_jenis : '' ?> yang tercatat.
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

        <!-- PANEL MASUK -->
        <div id="panel-masuk">

            <!-- Toggle: Barang Lama / Barang Baru -->
            <div class="px-6 pt-5 pb-2">
                <div class="flex gap-1 p-1 bg-slate-100 rounded-xl">
                    <button id="toggle-lama"
                        onclick="switchMasukMode('lama')"
                        class="flex-1 py-2 text-[11px] font-bold rounded-lg transition-all bg-white text-blue-700 shadow-sm">
                        📦 Stok Barang Ada
                    </button>
                    <button id="toggle-baru"
                        onclick="switchMasukMode('baru')"
                        class="flex-1 py-2 text-[11px] font-bold rounded-lg transition-all text-slate-500">
                        ✨ Tambah Barang Baru
                    </button>
                </div>
            </div>

            <!-- FORM: Barang Lama (restock) -->
            <form id="form-lama" method="POST" action="proses_transaksi.php" class="p-6 pt-3 space-y-4">
                <input type="hidden" name="aksi" value="transaksi">
                <input type="hidden" name="jenis" value="masuk">
                <input type="hidden" name="no_struk" value="0"><!-- FIX: cegah error integer kosong -->

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Tanggal Transaksi</label>
                        <input type="date" name="tanggal" id="input-date-lama" class="input-field input-field-masuk" required>
                    </div>
                    <div>
                        <label>Nama Supplier</label>
                        <input type="text" name="pihak_terkait" class="input-field input-field-masuk" placeholder="Nama supplier..." required>
                    </div>
                </div>

                <div>
                    <label>Pilih Barang</label>
                    <select name="id_barang" id="input-barang" class="input-field input-field-masuk" required
                            onchange="updateBarangInfo(this)">
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($barang_list as $b): ?>
                        <option value="<?= $b['id_barang'] ?>"
                                data-stok="<?= $b['stok'] ?>"
                                data-stok-min="<?= $b['stok_min'] ?>"
                                data-satuan="<?= $b['satuan'] ?>"
                                data-merek="<?= htmlspecialchars($b['merek'] ?? '') ?>"
                                data-kategori="<?= htmlspecialchars($b['nama_kategori'] ?? '') ?>">
                            <?= htmlspecialchars($b['nama_barang']) ?>
                            <?php if ($b['merek']): ?>(<?= htmlspecialchars($b['merek']) ?>)<?php endif; ?>
                            — Stok: <?= number_format($b['stok'], 0) ?> <?= $b['satuan'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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
                        <input type="number" name="jumlah" step="0.01" min="0.01"
                               class="input-field input-field-masuk" placeholder="0" required>
                    </div>
                    <div>
                        <label>Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" class="input-field input-field-masuk" placeholder="Catatan...">
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3 rounded-xl text-[12px] font-bold text-white transition-all"
                        style="background:#2563eb;box-shadow:0 4px 14px rgba(37,99,235,0.3)">
                    Simpan Transaksi Masuk
                </button>
            </form>

            <!-- FORM: Barang Baru -->
            <form id="form-baru" method="POST" action="proses_transaksi.php" class="p-6 pt-3 space-y-4 hidden">
                <input type="hidden" name="aksi" value="barang_baru">
                <input type="hidden" name="no_struk" value="0"><!-- FIX: cegah error integer kosong -->

                <div>
                    <label>Nama Barang *</label>
                    <input type="text" name="nama_barang" id="nb_nama"
                           class="input-field input-field-masuk" placeholder="Nama barang..." required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Merek</label>
                        <input type="text" name="merek" class="input-field input-field-masuk" placeholder="Merek...">
                    </div>
                    <div>
                        <label>Kategori *</label>
                        <select name="id_kategori" id="nb_kategori" class="input-field input-field-masuk" required>
                            <option value="" disabled selected>Pilih kategori…</option>
                            <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label>Stok Awal</label>
                        <input type="number" name="stok_awal" value="0" min="0"
                               class="input-field input-field-masuk" placeholder="0">
                    </div>
                    <div>
                        <label>Stok Min</label>
                        <input type="number" name="stok_min" value="10" min="0"
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Harga Beli</label>
                        <input type="number" name="harga_beli" min="0"
                               class="input-field input-field-masuk" placeholder="0">
                    </div>
                    <div>
                        <label>Harga Jual</label>
                        <input type="number" name="harga_jual" min="0"
                               class="input-field input-field-masuk" placeholder="0">
                    </div>
                </div>

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

                <button type="submit"
                        class="w-full py-3 rounded-xl text-[12px] font-bold text-white transition-all"
                        style="background:#2563eb;box-shadow:0 4px 14px rgba(37,99,235,0.3)">
                    ✨ Simpan Barang Baru
                </button>
            </form>

        </div><!-- /panel-masuk -->

        <!-- PANEL KELUAR -->
        <div id="panel-keluar" class="hidden">
            <form method="POST" action="proses_transaksi.php" class="p-6 space-y-4">
                <input type="hidden" name="aksi" value="transaksi">
                <input type="hidden" name="jenis" value="keluar">
                <input type="hidden" name="no_struk" value="0"><!-- FIX: cegah error integer kosong -->

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Tanggal Transaksi</label>
                        <input type="date" name="tanggal" id="input-date-keluar" class="input-field input-field-keluar" required>
                    </div>
                    <div>
                        <label>Penerima / Customer</label>
                        <input type="text" name="pihak_terkait" class="input-field input-field-keluar" placeholder="Nama penerima..." required>
                    </div>
                </div>

                <div>
                    <label>Pilih Barang</label>
                    <select name="id_barang" id="input-barang-keluar" class="input-field input-field-keluar" required
                            onchange="updateBarangInfoKeluar(this)">
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($barang_list as $b): ?>
                        <option value="<?= $b['id_barang'] ?>"
                                data-stok="<?= $b['stok'] ?>"
                                data-stok-min="<?= $b['stok_min'] ?>"
                                data-satuan="<?= $b['satuan'] ?>"
                                data-kategori="<?= htmlspecialchars($b['nama_kategori'] ?? '') ?>">
                            <?= htmlspecialchars($b['nama_barang']) ?>
                            <?php if ($b['merek']): ?>(<?= htmlspecialchars($b['merek']) ?>)<?php endif; ?>
                            — Stok: <?= number_format($b['stok'], 0) ?> <?= $b['satuan'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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
                        <input type="number" name="jumlah" step="0.01" min="0.01"
                               class="input-field input-field-keluar" placeholder="0" required>
                    </div>
                    <div>
                        <label>Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" class="input-field input-field-keluar" placeholder="Catatan...">
                    </div>
                </div>

                <button type="submit"
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

const barangData = <?= json_encode(array_column($barang_list, null, 'id_barang')) ?>;

function openModal(jenis = 'masuk') {
    document.getElementById('modal-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    switchTab(jenis);
    const today = new Date().toISOString().split('T')[0];
    const dl = document.getElementById('input-date-lama');
    const dk = document.getElementById('input-date-keluar');
    if (dl) dl.value = today;
    if (dk) dk.value = today;
}
function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
    document.body.style.overflow = '';
}
function closeModalOnBg(e) {
    if (e.target === document.getElementById('modal-overlay')) closeModal();
}

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

function updateBarangInfo(sel) {
    const id   = sel.value;
    const info = document.getElementById('barang-info');
    if (!id || !barangData[id]) { info.classList.add('hidden'); return; }
    const b    = barangData[id];
    const stok = parseFloat(b.stok);
    const min  = parseFloat(b.stok_min);
    document.getElementById('satuan-hint').textContent = '(satuan: ' + b.satuan + ')';
    const elStok = document.getElementById('info-stok');
    let cls  = 'stok-ok';
    let text = stok.toLocaleString('id-ID') + ' ' + b.satuan;
    if (stok <= 0)        { cls = 'stok-danger'; text += ' ✕ Habis!'; }
    else if (stok <= min) { cls = 'stok-low';    text += ' ⚠ Tipis!'; }
    elStok.className   = cls;
    elStok.textContent = text;
    document.getElementById('info-stok-min').textContent = min.toLocaleString('id-ID') + ' ' + b.satuan;
    document.getElementById('info-kategori').textContent = b.nama_kategori || '-';
    info.classList.remove('hidden');
}

function updateBarangInfoKeluar(sel) {
    const id   = sel.value;
    const info = document.getElementById('barang-info-keluar');
    if (!id || !barangData[id]) { info.classList.add('hidden'); return; }
    const b    = barangData[id];
    const stok = parseFloat(b.stok);
    const min  = parseFloat(b.stok_min);
    document.getElementById('satuan-hint-keluar').textContent = '(satuan: ' + b.satuan + ')';
    const elStok = document.getElementById('info-stok-keluar');
    let cls  = 'stok-ok';
    let text = stok.toLocaleString('id-ID') + ' ' + b.satuan;
    if (stok <= 0)        { cls = 'stok-danger'; text += ' ✕ Habis!'; }
    else if (stok <= min) { cls = 'stok-low';    text += ' ⚠ Tipis! — hati-hati!'; }
    elStok.className   = cls;
    elStok.textContent = text;
    document.getElementById('info-kategori-keluar').textContent = b.nama_kategori || '-';
    info.classList.remove('hidden');
}
</script>

</body>
</html>
