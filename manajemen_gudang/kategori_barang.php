<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

include 'koneksi.php';

// ======================================
// PASTIKAN KOLOM IKON & WARNA ADA
// ======================================
$cek_ikon = mysqli_query($conn, "SHOW COLUMNS FROM kategori LIKE 'ikon'");
if (mysqli_num_rows($cek_ikon) === 0) {
    mysqli_query($conn, "ALTER TABLE kategori ADD COLUMN ikon VARCHAR(20) DEFAULT '📦' AFTER deskripsi");
    mysqli_query($conn, "ALTER TABLE kategori ADD COLUMN warna VARCHAR(20) DEFAULT '#dbeafe' AFTER ikon");
}

// ======================================
// TAMBAH KATEGORI
// ======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_kategori') {
    if (!$is_admin) {
        header('Location: kategori_barang.php?status=gagal&msg=' . urlencode('Akses ditolak. Hanya admin yang dapat menambahkan kategori.'));
        exit;
    }

    $nama_kategori  = trim($_POST['nama_kategori']  ?? '');
    $desk_kategori  = trim($_POST['deskripsi_kategori'] ?? '');
    $ikon_kategori  = trim($_POST['ikon_kategori']  ?? '📦');
    $warna_kategori = trim($_POST['warna_kategori'] ?? '#dbeafe');

    if (!empty($nama_kategori)) {
        $getLast = mysqli_query($conn, "SELECT id_kategori FROM kategori ORDER BY id_kategori DESC LIMIT 1");
        $num = mysqli_num_rows($getLast) > 0
            ? (int) substr(mysqli_fetch_assoc($getLast)['id_kategori'], 3) + 1
            : 1;
        $newID     = 'KAT' . str_pad($num, 3, '0', STR_PAD_LEFT);
        $nama_esc  = mysqli_real_escape_string($conn, $nama_kategori);
        $desk_esc  = mysqli_real_escape_string($conn, $desk_kategori);
        $ikon_esc  = mysqli_real_escape_string($conn, $ikon_kategori);
        $warna_esc = mysqli_real_escape_string($conn, $warna_kategori);

        $result = mysqli_query($conn,
            "INSERT INTO kategori (id_kategori, nama_kategori, deskripsi, ikon, warna, created_at, updated_at)
             VALUES ('$newID', '$nama_esc', '$desk_esc', '$ikon_esc', '$warna_esc', NOW(), NOW())"
        );

        if ($result) {
            header("Location: kategori_barang.php?status=sukses&nama=" . urlencode($nama_kategori));
        } else {
            header("Location: kategori_barang.php?status=gagal&msg=" . urlencode(mysqli_error($conn)));
        }
        exit;
    }
}

// ======================================
// FUNGSI MAPPING IKON & WARNA OTOMATIS
// ======================================
function getIkonDanWarna($nama_kategori, $ikon_db, $warna_db) {
    $accent_map = [
        '#dbeafe'=>'#2563eb','#fce7f3'=>'#db2777','#fef3c7'=>'#d97706',
        '#ffedd5'=>'#ea580c','#fef9c3'=>'#ca8a04','#dcfce7'=>'#16a34a',
        '#ede9fe'=>'#7c3aed','#e0f2fe'=>'#0284c7','#fce7e7'=>'#dc2626',
        '#f0fdf4'=>'#15803d','#fdf4ff'=>'#a21caf','#fff7ed'=>'#c2410c',
    ];

    // keyword => [ikon, warna_bg, accent]
    $icon_map = [
        'beras'        => ['🍚', '#dbeafe', '#2563eb'],
        'serealia'     => ['🌾', '#fef9c3', '#ca8a04'],
        'jagung'       => ['🌽', '#fef9c3', '#ca8a04'],
        'gula'         => ['🍬', '#fce7f3', '#db2777'],
        'pemanis'      => ['🍯', '#fef3c7', '#d97706'],
        'madu'         => ['🍯', '#fef3c7', '#d97706'],
        'minyak'       => ['🛢️',  '#fef3c7', '#d97706'],
        'tepung'       => ['🥖', '#ffedd5', '#ea580c'],
        'bumbu'        => ['🧂', '#ffedd5', '#ea580c'],
        'rempah'       => ['🌶️',  '#fce7e7', '#dc2626'],
        'kecap'        => ['🍶', '#fef9c3', '#ca8a04'],
        'saus'         => ['🥫', '#fce7e7', '#dc2626'],
        'sambal'       => ['🌶️',  '#fce7e7', '#dc2626'],
        'mie'          => ['🍜', '#fef9c3', '#ca8a04'],
        'mi '          => ['🍜', '#fef9c3', '#ca8a04'],
        'bihun'        => ['🍜', '#fef9c3', '#ca8a04'],
        'makaroni'     => ['🍝', '#ffedd5', '#ea580c'],
        'minuman'      => ['🥤', '#dcfce7', '#16a34a'],
        'jus'          => ['🍹', '#dcfce7', '#16a34a'],
        'sirup'        => ['🍷', '#fce7f3', '#db2777'],
        'kaleng'       => ['🥫', '#e0f2fe', '#0284c7'],
        'botol'        => ['🍶', '#dcfce7', '#16a34a'],
        'kemasan'      => ['📦', '#dbeafe', '#2563eb'],
        'kopi'         => ['☕', '#ede9fe', '#7c3aed'],
        'teh'          => ['🍵', '#dcfce7', '#16a34a'],
        'susu'         => ['🥛', '#e0f2fe', '#0284c7'],
        'olahan susu'  => ['🧀', '#fef3c7', '#d97706'],
        'keju'         => ['🧀', '#fef3c7', '#d97706'],
        'yogurt'       => ['🥛', '#e0f2fe', '#0284c7'],
        'snack'        => ['🍿', '#fef9c3', '#ca8a04'],
        'camilan'      => ['🍿', '#fef9c3', '#ca8a04'],
        'biskuit'      => ['🍪', '#ffedd5', '#ea580c'],
        'coklat'       => ['🍫', '#fce7f3', '#db2777'],
        'cokelat'      => ['🍫', '#fce7f3', '#db2777'],
        'permen'       => ['🍭', '#fce7f3', '#db2777'],
        'kerupuk'      => ['🥨', '#fef9c3', '#ca8a04'],
        'makanan'      => ['🍱', '#fef3c7', '#d97706'],
        'daging'       => ['🥩', '#fce7e7', '#dc2626'],
        'ayam'         => ['🍗', '#ffedd5', '#ea580c'],
        'ikan'         => ['🐟', '#e0f2fe', '#0284c7'],
        'seafood'      => ['🦐', '#e0f2fe', '#0284c7'],
        'sayur'        => ['🥦', '#dcfce7', '#16a34a'],
        'buah'         => ['🍎', '#fce7e7', '#dc2626'],
        'telur'        => ['🥚', '#fef3c7', '#d97706'],
        'roti'         => ['🍞', '#ffedd5', '#ea580c'],
        'bakery'       => ['🥐', '#ffedd5', '#ea580c'],
        'sabun'        => ['🧼', '#e0f2fe', '#0284c7'],
        'detergen'     => ['🧺', '#dbeafe', '#2563eb'],
        'pembersih'    => ['🧹', '#dbeafe', '#2563eb'],
        'sampo'        => ['🧴', '#ede9fe', '#7c3aed'],
        'shampoo'      => ['🧴', '#ede9fe', '#7c3aed'],
        'rambut'       => ['💇', '#fce7f3', '#db2777'],
        'perawatan'    => ['🪥', '#fce7f3', '#db2777'],
        'pasta gigi'   => ['🪥', '#e0f2fe', '#0284c7'],
        'odol'         => ['🪥', '#e0f2fe', '#0284c7'],
        'wajah'        => ['✨', '#fdf4ff', '#a21caf'],
        'kulit'        => ['🧴', '#fdf4ff', '#a21caf'],
        'rokok'        => ['🚬', '#f0fdf4', '#15803d'],
        'tembakau'     => ['🚬', '#f0fdf4', '#15803d'],
        'gas'          => ['🔥', '#fce7e7', '#dc2626'],
        'bahan bakar'  => ['⛽', '#ffedd5', '#ea580c'],
        'lpg'          => ['🔥', '#fce7e7', '#dc2626'],
        'sembako'      => ['🛒', '#dbeafe', '#2563eb'],
        'energi'       => ['⚡', '#fef9c3', '#ca8a04'],
        'berenergi'    => ['⚡', '#fef9c3', '#ca8a04'],
        'vitamin'      => ['💊', '#fce7f3', '#db2777'],
        'obat'         => ['💊', '#fce7e7', '#dc2626'],
        'suplemen'     => ['💊', '#dcfce7', '#16a34a'],
        'alat tulis'   => ['✏️',  '#fdf4ff', '#a21caf'],
        'kertas'       => ['📄', '#dbeafe', '#2563eb'],
        'elektronik'   => ['🔌', '#ede9fe', '#7c3aed'],
        'baterai'      => ['🔋', '#fef9c3', '#ca8a04'],
        'plastik'      => ['🛍️',  '#e0f2fe', '#0284c7'],
        'kantong'      => ['🛍️',  '#e0f2fe', '#0284c7'],
        'tisu'         => ['🧻', '#f0fdf4', '#15803d'],
        'toilet'       => ['🚽', '#e0f2fe', '#0284c7'],
        'bayi'         => ['👶', '#fce7f3', '#db2777'],
        'anak'         => ['🧒', '#fef9c3', '#ca8a04'],
        'hewan'        => ['🐾', '#ffedd5', '#ea580c'],
        'pakan'        => ['🌿', '#dcfce7', '#16a34a'],
    ];

    $n = strtolower($nama_kategori);
    // Hanya pakai dari DB jika bukan nilai default kardus
    $ikon  = (!empty($ikon_db)  && $ikon_db  !== '📦')     ? $ikon_db  : null;
    $warna = (!empty($warna_db) && $warna_db !== '#dbeafe') ? $warna_db : null;
    $accent = '#2563eb';

    foreach ($icon_map as $keyword => [$em, $bg, $ac]) {
        if (str_contains($n, $keyword)) {
            if (!$ikon)  $ikon  = $em;
            if (!$warna) $warna = $bg;
            $accent = $ac;
            return [$ikon, $warna, $accent];
        }
    }

    // Fallback: tidak ada keyword cocok
    $warna  = $warna  ?: '#dbeafe';
    $ikon   = $ikon   ?: '📦';
    $accent = $accent_map[$warna] ?? '#2563eb';
    return [$ikon, $warna, $accent];
}

// ======================================
// AMBIL DATA KATEGORI + BARANG
// ======================================
$kategori_list = [];
$query = mysqli_query($conn, "SELECT * FROM kategori ORDER BY id_kategori ASC");

while ($row = mysqli_fetch_assoc($query)) {
    [$icon, $warna, $accent] = getIkonDanWarna(
        $row['nama_kategori'],
        $row['ikon']  ?? '',
        $row['warna'] ?? ''
    );

    $bq = mysqli_query($conn,
        "SELECT id_barang, nama_barang, merek, satuan, harga_beli, harga_jual, stok, stok_min
         FROM barang WHERE id_kategori='{$row['id_kategori']}' ORDER BY id_barang ASC"
    );
    $barang_list = []; $barang_preview = []; $total_stok = 0;
    while ($b = mysqli_fetch_assoc($bq)) {
        $barang_list[]    = $b;
        $barang_preview[] = $b['nama_barang'];
        $total_stok      += (int)($b['stok'] ?? 0);
    }

    $kategori_list[] = [
        'id_kategori'   => $row['id_kategori'],
        'nama_kategori' => $row['nama_kategori'],
        'ikon'          => $icon,
        'warna'         => $warna,
        'accent'        => $accent,
        'jumlah_barang' => count($barang_list),
        'total_stok'    => $total_stok,
        'preview'       => array_slice($barang_preview, 0, 3),
        'barang_json'   => htmlspecialchars(json_encode($barang_list, JSON_UNESCAPED_UNICODE), ENT_QUOTES),
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kategori Barang | Putra Surya Agung</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f1f5f9;color:#334155;font-size:13px;}

.cat-card{background:#fff;border:1px solid #e8f0fe;border-radius:20px;padding:20px;cursor:pointer;transition:transform .2s,box-shadow .2s,border-color .2s;display:flex;flex-direction:column;gap:14px;}
.cat-card:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(37,99,235,.10);border-color:#bfdbfe;}
.icon-box{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;}
.badge-count{font-size:11px;font-weight:600;padding:4px 10px;border-radius:999px;}
.card-divider{border-top:1px dashed #e2e8f0;padding-top:12px;}

.search-wrap{position:relative;}
.search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;}
.search-input{padding:8px 14px 8px 34px;border:1px solid #e2e8f0;border-radius:12px;font-size:12px;background:#fff;width:210px;outline:none;transition:border-color .2s;font-family:inherit;}
.search-input:focus{border-color:#93c5fd;}

.modal-bg{position:fixed;inset:0;background:rgba(15,23,42,.50);backdrop-filter:blur(5px);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px;}
.modal-bg.active{display:flex;}
@keyframes slideUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}

/* DETAIL MODAL */
.detail-modal{width:900px;max-width:100%;max-height:90vh;overflow:hidden;background:#fff;border-radius:24px;display:flex;flex-direction:column;animation:slideUp .25s ease;box-shadow:0 32px 80px rgba(0,0,0,.20);}
.modal-header{padding:24px 28px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:18px;position:relative;flex-shrink:0;}
.modal-close{position:absolute;top:16px;right:18px;width:34px;height:34px;border-radius:50%;border:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748b;font-size:15px;transition:all .15s;}
.modal-close:hover{background:#fee2e2;color:#dc2626;border-color:#fca5a5;}
.modal-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:16px 28px;border-bottom:1px solid #f1f5f9;flex-shrink:0;}
.stat-box{background:#f8fafc;border:1px solid #e8f0fe;border-radius:14px;padding:14px 16px;}
.stat-label{font-size:10px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
.stat-value{font-size:22px;font-weight:700;color:#1e293b;margin-top:4px;}
.modal-search-wrap{padding:14px 28px;border-bottom:1px solid #f1f5f9;flex-shrink:0;position:relative;}
.modal-search-wrap svg{position:absolute;left:42px;top:50%;transform:translateY(-50%);color:#94a3b8;}
.modal-search{width:100%;padding:10px 14px 10px 36px;border:1px solid #e2e8f0;border-radius:12px;font-size:12px;outline:none;font-family:inherit;transition:border-color .2s;background:#f8fafc;color:#334155;}
.modal-search:focus{border-color:#93c5fd;background:#fff;}
.table-wrap{overflow-y:auto;flex:1;}
table{width:100%;border-collapse:collapse;}
thead th{position:sticky;top:0;z-index:1;background:#f8fafc;padding:11px 14px;text-align:left;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
thead th:first-child{padding-left:28px;}thead th:last-child{padding-right:28px;}
tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:#f8fbff;}
tbody td{padding:13px 14px;font-size:12.5px;color:#334155;vertical-align:middle;}
tbody td:first-child{padding-left:28px;}tbody td:last-child{padding-right:28px;}
.td-id{font-weight:700;color:#94a3b8;font-size:11px;font-family:monospace;}
.td-nama{font-weight:600;color:#1e293b;}.td-merek{color:#64748b;}
.td-num{font-weight:600;text-align:right;}.td-center{text-align:center;}
.stok-pill{display:inline-block;font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;white-space:nowrap;}
.stok-ok{background:#dcfce7;color:#15803d;}.stok-warn{background:#fef3c7;color:#b45309;}.stok-low{background:#fee2e2;color:#b91c1c;}
.empty-state{text-align:center;padding:48px 0;color:#94a3b8;font-size:13px;}

/* TAMBAH MODAL */
.tambah-modal{width:510px;max-width:100%;max-height:92vh;overflow-y:auto;background:#fff;border-radius:24px;padding:28px;position:relative;animation:slideUp .25s ease;box-shadow:0 32px 80px rgba(0,0,0,.20);}
.preview-card{display:flex;align-items:center;gap:14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:16px;padding:14px 18px;margin-bottom:22px;}
.preview-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;transition:all .2s;}
.form-label{font-size:12px;font-weight:700;color:#475569;margin-bottom:7px;display:block;}
.form-label span{color:#ef4444;margin-left:2px;}
.nama-input{width:100%;border:1.5px solid #dbeafe;border-radius:12px;padding:11px 14px;outline:none;font-size:13px;font-family:inherit;transition:border-color .2s;color:#1e293b;background:#fafcff;}
.nama-input:focus{border-color:#93c5fd;background:#fff;}
.icon-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-top:8px;}
.icon-option{width:100%;aspect-ratio:1;border:2px solid #e2e8f0;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;cursor:pointer;background:#f8fafc;transition:all .15s;}
.icon-option:hover{border-color:#93c5fd;background:#eff6ff;transform:scale(1.08);}
.icon-option.selected{border-color:#2563eb;background:#eff6ff;box-shadow:0 0 0 3px rgba(37,99,235,.15);}
.color-grid{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;}
.color-dot{width:34px;height:34px;border-radius:50%;cursor:pointer;border:3px solid transparent;transition:all .15s;flex-shrink:0;}
.color-dot:hover{transform:scale(1.15);}
.color-dot.selected{border-color:#2563eb;box-shadow:0 0 0 2px white,0 0 0 4px #2563eb;}
.btn-row{display:flex;gap:10px;margin-top:22px;}
.btn-cancel{flex:1;padding:12px;border-radius:12px;border:1.5px solid #e2e8f0;background:#fff;font-size:13px;font-weight:600;color:#64748b;cursor:pointer;font-family:inherit;transition:all .15s;}
.btn-cancel:hover{background:#f8fafc;border-color:#cbd5e1;}
.btn-save{flex:2;padding:12px;border-radius:12px;border:none;background:#2563eb;color:#fff;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;display:flex;align-items:center;justify-content:center;gap:6px;}
.btn-save:hover{background:#1d4ed8;}
.section-gap{margin-top:18px;}

/* ══ TOAST ══ */
@keyframes toastIn {
    from { opacity:0; transform:translateX(30px) scale(0.95); }
    to   { opacity:1; transform:translateX(0) scale(1); }
}
@keyframes toastOut {
    from { opacity:1; transform:translateX(0) scale(1); }
    to   { opacity:0; transform:translateX(20px) scale(0.95); }
}
@keyframes toastProgress {
    from { width:100%; }
    to   { width:0%; }
}
.toast {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 99999;
    min-width: 300px;
    max-width: 360px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 16px 48px rgba(0,0,0,.16), 0 2px 8px rgba(0,0,0,.08);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    display: none;
    flex-direction: column;
}
.toast.show {
    display: flex;
    animation: toastIn .35s cubic-bezier(.34,1.56,.64,1) forwards;
}
.toast.hide {
    animation: toastOut .25s ease forwards;
}
.toast-body {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
}
.toast-icon-wrap {
    width: 40px; height: 40px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.toast-text-title {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
}
.toast-text-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 3px;
    line-height: 1.4;
}
.toast-close-btn {
    margin-left: auto;
    width: 26px; height: 26px;
    border-radius: 8px;
    border: none;
    background: #f1f5f9;
    color: #94a3b8;
    font-size: 13px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all .15s;
}
.toast-close-btn:hover { background:#fee2e2; color:#dc2626; }
.toast-bar {
    height: 3px;
    border-radius: 0 0 18px 18px;
}
.toast-bar.animating {
    animation: toastProgress 3.5s linear forwards;
}
.toast-sukses .toast-icon-wrap { background:#dcfce7; }
.toast-sukses .toast-bar       { background:linear-gradient(90deg,#16a34a,#4ade80); }
.toast-gagal  .toast-icon-wrap { background:#fee2e2; }
.toast-gagal  .toast-bar       { background:linear-gradient(90deg,#dc2626,#f87171); }
</style>
</head>

<body class="flex h-screen overflow-hidden">
<?php include 'include/side_panel.php'; ?>

<main class="flex-1 flex flex-col overflow-y-auto">
<?php include 'include/header.php'; ?>

<div class="p-8 pt-20">

    <div class="flex justify-between items-start mb-8">
        <div>
            <h1 class="text-[20px] font-bold text-slate-800 tracking-tight">Kategori Barang</h1>
            <p class="text-slate-500 text-[11px] mt-1">Kelola kategori barang gudang secara terorganisir.</p>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($is_admin): ?>
            <button onclick="openTambah()" class="bg-blue-600 hover:bg-blue-700 text-white text-[12px] font-semibold px-5 py-2.5 rounded-xl transition flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Kategori
            </button>
            <?php else: ?>
            <span class="bg-slate-100 text-slate-500 text-[12px] font-semibold px-4 py-2 rounded-xl">Hanya admin dapat menambah kategori</span>
            <?php endif; ?>
            <div class="search-wrap">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input class="search-input" type="text" id="searchInput" placeholder="Cari kategori..." onkeyup="filterKategori()">
            </div>
        </div>
    </div>

    <div class="mb-5 text-[12px] text-slate-500">
        Total <strong><?= count($kategori_list) ?></strong> kategori
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" id="kategoriGrid">
        <?php foreach ($kategori_list as $kat): ?>
        <div class="cat-card"
             data-nama="<?= strtolower(htmlspecialchars($kat['nama_kategori'])) ?>"
             onclick='showDetail(
                 <?= $kat['barang_json'] ?>,
                 "<?= htmlspecialchars($kat['nama_kategori'], ENT_QUOTES) ?>",
                 "<?= $kat['ikon'] ?>",
                 "<?= $kat['warna'] ?>",
                 "<?= $kat['accent'] ?>",
                 "<?= $kat['id_kategori'] ?>",
                 <?= $kat['jumlah_barang'] ?>,
                 <?= $kat['total_stok'] ?>
             )'>
            <div class="flex items-start justify-between">
                <div class="icon-box" style="background:<?= $kat['warna'] ?>"><?= $kat['ikon'] ?></div>
                <span class="badge-count" style="background:<?= $kat['warna'] ?>;color:<?= $kat['accent'] ?>"><?= $kat['jumlah_barang'] ?> item</span>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-slate-800"><?= htmlspecialchars($kat['nama_kategori']) ?></h3>
                <p class="text-[11px] text-slate-400 mt-0.5"><?= $kat['id_kategori'] ?></p>
            </div>
            <div class="card-divider">
                <?php if (!empty($kat['preview'])): ?>
                    <?php foreach ($kat['preview'] as $p): ?>
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:<?= $kat['accent'] ?>"></div>
                        <span class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($p) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($kat['jumlah_barang'] > 3): ?>
                    <p class="text-[10px] text-slate-400 mt-1 pl-3.5">+<?= $kat['jumlah_barang'] - 3 ?> barang lainnya...</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-[11px] text-slate-400 italic">Belum ada barang</p>
                <?php endif; ?>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                <span class="text-[11px] text-slate-400">Total stok</span>
                <span class="text-[12px] font-bold" style="color:<?= $kat['accent'] ?>"><?= number_format($kat['total_stok']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'include/footer.php'; ?>
</main>

<!-- ══════════════════════════════
     DETAIL MODAL
══════════════════════════════ -->
<div class="modal-bg" id="detailModal" onclick="closeDetailOutside(event)">
    <div class="detail-modal">
        <div class="modal-header">
            <div id="dIcon" class="icon-box" style="background:#dbeafe;font-size:28px;width:58px;height:58px;border-radius:16px;flex-shrink:0;">📦</div>
            <div style="padding-right:48px;">
                <h2 id="dNama" class="text-[19px] font-bold text-slate-800 leading-tight"></h2>
                <p id="dID" class="text-[11px] text-slate-400 mt-1 font-mono font-semibold"></p>
            </div>
            <div class="modal-close" onclick="closeDetail()">✕</div>
        </div>
        <div class="modal-stats">
            <div class="stat-box"><div class="stat-label">Jumlah Barang</div><div id="dJumlah" class="stat-value">0</div></div>
            <div class="stat-box"><div class="stat-label">Total Stok</div><div id="dTotalStok" class="stat-value">0</div></div>
            <div class="stat-box"><div class="stat-label">Stok Aman</div><div id="dStokOk" class="stat-value" style="color:#16a34a">0</div></div>
            <div class="stat-box"><div class="stat-label">Stok Menipis / Habis</div><div id="dStokWarn" class="stat-value" style="color:#dc2626">0</div></div>
        </div>
        <div class="modal-search-wrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input class="modal-search" type="text" id="modalSearch" placeholder="Cari nama barang..." oninput="filterModalBarang()">
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID Barang</th><th>Nama Barang</th><th>Merek</th><th>Satuan</th>
                        <th style="text-align:right">Harga Beli</th><th style="text-align:right">Harga Jual</th>
                        <th style="text-align:right">Stok</th><th style="text-align:right">Stok Min</th>
                        <th style="text-align:center">Status</th>
                    </tr>
                </thead>
                <tbody id="barangTbody"></tbody>
            </table>
            <div id="barangEmpty" class="empty-state" style="display:none;">
                <div style="font-size:40px;margin-bottom:12px;">📭</div>
                <div>Belum ada barang di kategori ini</div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════
     TAMBAH KATEGORI MODAL
══════════════════════════════ -->
<div class="modal-bg" id="tambahModal" onclick="closeTambahOutside(event)">
    <div class="tambah-modal">
        <div class="modal-close" onclick="closeTambah()">✕</div>

        <h2 class="text-[18px] font-bold text-slate-800">Tambah Kategori Baru</h2>
        <p class="text-[12px] text-slate-400 mt-1 mb-5">Isi detail kategori barang gudang</p>

        <div class="preview-card">
            <div class="preview-icon" id="prevIcon" style="background:#dbeafe;">📦</div>
            <div>
                <div style="font-size:15px;font-weight:700;color:#1e293b;" id="prevNama">Nama kategori...</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:2px;">Pratinjau tampilan kategori</div>
            </div>
        </div>

        <form method="POST" id="tambahForm">
            <input type="hidden" name="action"         value="tambah_kategori">
            <input type="hidden" name="ikon_kategori"  id="hiddenIkon"  value="📦">
            <input type="hidden" name="warna_kategori" id="hiddenWarna" value="#dbeafe">

            <div>
                <label class="form-label">Nama Kategori <span>*</span></label>
                <input type="text" name="nama_kategori" id="inputNama"
                       class="nama-input"
                       placeholder="Contoh: Beras & Serealia, Minuman Kaleng..."
                       required oninput="updatePreview()">
            </div>

            <div class="section-gap">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi_kategori" id="inputDeskripsi"
                          class="nama-input"
                          rows="3"
                          placeholder="Contoh: Beras berbagai jenis, beras ketan, tepung beras..."
                          style="resize:vertical;min-height:72px;line-height:1.6;"></textarea>
            </div>

            <div class="section-gap">
                <label class="form-label">Pilih Ikon</label>
                <div class="icon-grid" id="iconGrid"></div>
            </div>

            <div class="section-gap">
                <label class="form-label">Warna Latar Ikon</label>
                <div class="color-grid" id="colorGrid"></div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn-cancel" onclick="closeTambah()">Batal</button>
                <button type="submit" class="btn-save">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    Simpan Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════
     TOAST NOTIFIKASI
══════════════════════════════ -->
<div class="toast toast-sukses" id="toastSukses">
    <div class="toast-body">
        <div class="toast-icon-wrap">🎉</div>
        <div style="flex:1;min-width:0;">
            <div class="toast-text-title">Kategori Berhasil Ditambahkan!</div>
            <div class="toast-text-sub" id="toastSuksesNama">Kategori baru sudah tersimpan di gudang.</div>
        </div>
        <button class="toast-close-btn" onclick="hideToast('toastSukses')">✕</button>
    </div>
    <div class="toast-bar" id="barSukses"></div>
</div>

<div class="toast toast-gagal" id="toastGagal">
    <div class="toast-body">
        <div class="toast-icon-wrap">❌</div>
        <div style="flex:1;">
            <div class="toast-text-title">Gagal Menyimpan Kategori</div>
            <div class="toast-text-sub">Terjadi kesalahan. Silakan coba lagi.</div>
        </div>
        <button class="toast-close-btn" onclick="hideToast('toastGagal')">✕</button>
    </div>
    <div class="toast-bar" id="barGagal"></div>
</div>

<script>
const ICONS = [
    '📦','🛒','🎁','🏷️','🗂️','📋',
    '🍚','🌾','🍬','🍯','🛢️','🥖',
    '🧂','🍜','🥤','🥫','☕','🍵',
    '🥛','🧀','🍿','🍪','🍫','🌶️',
    '🥩','🐟','🥚','🍞','🧼','🧺',
    '🧴','🪥','🚬','🔥','⚡','💊',
    '🍹','🍶','🥦','🍎','🧹','🛍️'
];
const COLORS = [
    {bg:'#dbeafe',ac:'#2563eb'},{bg:'#dcfce7',ac:'#16a34a'},
    {bg:'#fce7f3',ac:'#db2777'},{bg:'#fef3c7',ac:'#d97706'},
    {bg:'#ede9fe',ac:'#7c3aed'},{bg:'#e0f2fe',ac:'#0284c7'},
    {bg:'#fef9c3',ac:'#ca8a04'},{bg:'#ffedd5',ac:'#ea580c'},
    {bg:'#fce7e7',ac:'#dc2626'},{bg:'#f0fdf4',ac:'#15803d'},
    {bg:'#fdf4ff',ac:'#a21caf'},{bg:'#fff7ed',ac:'#c2410c'},
];

let selIcon  = ICONS[0];
let selColor = COLORS[0];

function renderIconGrid() {
    document.getElementById('iconGrid').innerHTML = ICONS.map((ic, i) =>
        `<div class="icon-option ${i===0?'selected':''}" onclick="selectIcon('${ic}',this)">${ic}</div>`
    ).join('');
}
function renderColorGrid() {
    document.getElementById('colorGrid').innerHTML = COLORS.map((c, i) =>
        `<div class="color-dot ${i===0?'selected':''}" style="background:${c.bg}" onclick="selectColor('${c.bg}','${c.ac}',this)"></div>`
    ).join('');
}
function selectIcon(icon, el) {
    selIcon = icon;
    document.querySelectorAll('.icon-option').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('hiddenIkon').value = icon;
    updatePreview();
}
function selectColor(bg, ac, el) {
    selColor = {bg, ac};
    document.querySelectorAll('.color-dot').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('hiddenWarna').value = bg;
    updatePreview();
}
function updatePreview() {
    const n = document.getElementById('inputNama').value.trim();
    document.getElementById('prevNama').innerText         = n || 'Nama kategori...';
    document.getElementById('prevIcon').innerText         = selIcon;
    document.getElementById('prevIcon').style.background = selColor.bg;
}

function filterKategori() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    document.querySelectorAll('#kategoriGrid .cat-card').forEach(c => {
        c.style.display = c.dataset.nama.includes(q) ? '' : 'none';
    });
}

// ── Detail Modal ──
let _barang = [];
function showDetail(list, nama, icon, warna, accent, id, jumlah, totalStok) {
    _barang = list || [];
    document.getElementById('dNama').innerText = nama;
    document.getElementById('dID').innerText   = id;
    const ic = document.getElementById('dIcon');
    ic.innerHTML = icon; ic.style.background = warna;
    let ok = 0, warn = 0;
    _barang.forEach(b => {
        const s = parseInt(b.stok)||0, m = parseInt(b.stok_min)||0;
        (s <= 0 || s <= m) ? warn++ : ok++;
    });
    document.getElementById('dJumlah').innerText    = jumlah;
    document.getElementById('dTotalStok').innerText = totalStok.toLocaleString('id-ID');
    document.getElementById('dStokOk').innerText    = ok;
    document.getElementById('dStokWarn').innerText  = warn;
    document.getElementById('dJumlah').style.color    = accent;
    document.getElementById('dTotalStok').style.color = accent;
    document.getElementById('modalSearch').value = '';
    renderTable(_barang);
    document.getElementById('detailModal').classList.add('active');
}
function renderTable(list) {
    const tbody = document.getElementById('barangTbody');
    const empty = document.getElementById('barangEmpty');
    if (!list || !list.length) { tbody.innerHTML=''; empty.style.display='block'; return; }
    empty.style.display = 'none';
    const fmt = n => 'Rp ' + (parseInt(n)||0).toLocaleString('id-ID');
    tbody.innerHTML = list.map(b => {
        const s = parseInt(b.stok)||0, m = parseInt(b.stok_min)||0;
        let pc='stok-ok', lb='Aman';
        if (s<=0){pc='stok-low';lb='Habis';}
        else if (s<=m){pc='stok-warn';lb='Menipis';}
        return `<tr>
            <td class="td-id">${b.id_barang||'—'}</td>
            <td class="td-nama">${b.nama_barang||'—'}</td>
            <td class="td-merek">${b.merek||'—'}</td>
            <td>${b.satuan||'—'}</td>
            <td class="td-num">${fmt(b.harga_beli)}</td>
            <td class="td-num">${fmt(b.harga_jual)}</td>
            <td class="td-num">${s.toLocaleString('id-ID')}</td>
            <td class="td-num">${m.toLocaleString('id-ID')}</td>
            <td class="td-center"><span class="stok-pill ${pc}">${lb}</span></td>
        </tr>`;
    }).join('');
}
function filterModalBarang() {
    const q = document.getElementById('modalSearch').value.toLowerCase().trim();
    renderTable(q ? _barang.filter(b=>(b.nama_barang||'').toLowerCase().includes(q)) : _barang);
}
function closeDetail() { document.getElementById('detailModal').classList.remove('active'); }
function closeDetailOutside(e) { if(e.target===document.getElementById('detailModal')) closeDetail(); }

// ── Tambah Modal ──
function openTambah() {
    selIcon=ICONS[0]; selColor=COLORS[0];
    renderIconGrid(); renderColorGrid();
    document.getElementById('inputNama').value      = '';
    document.getElementById('inputDeskripsi').value = '';
    document.getElementById('hiddenIkon').value  = ICONS[0];
    document.getElementById('hiddenWarna').value = COLORS[0].bg;
    updatePreview();
    document.getElementById('tambahModal').classList.add('active');
    setTimeout(()=>document.getElementById('inputNama').focus(), 200);
}
function closeTambah() { document.getElementById('tambahModal').classList.remove('active'); }
function closeTambahOutside(e) { if(e.target===document.getElementById('tambahModal')) closeTambah(); }

document.addEventListener('keydown', e => {
    if (e.key==='Escape') { closeDetail(); closeTambah(); }
});

// ── Toast ──
const _toastTimers = {};
function showToast(id) {
    const t   = document.getElementById(id);
    const bar = t.querySelector('.toast-bar');
    // Reset progress bar animation
    bar.classList.remove('animating');
    void bar.offsetWidth; // reflow
    bar.classList.add('animating');
    t.classList.remove('hide');
    t.classList.add('show');
    clearTimeout(_toastTimers[id]);
    _toastTimers[id] = setTimeout(() => hideToast(id), 3500);
}
function hideToast(id) {
    const t = document.getElementById(id);
    t.classList.add('hide');
    setTimeout(() => {
        t.classList.remove('show', 'hide');
        const bar = t.querySelector('.toast-bar');
        bar.classList.remove('animating');
    }, 280);
}

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] === 'sukses'): ?>
        const _namaBaru = <?= json_encode(urldecode($_GET['nama'] ?? '')) ?>;
        if (_namaBaru) {
            document.getElementById('toastSuksesNama').innerText = '"' + _namaBaru + '" sudah tersimpan di gudang.';
        }
        showToast('toastSukses');
    <?php elseif ($_GET['status'] === 'gagal'): ?>
        showToast('toastGagal');
        console.error('DB Error: <?= addslashes(urldecode($_GET['msg'] ?? '')) ?>');
    <?php endif; ?>
<?php endif; ?>
</script>
</body>
</html>