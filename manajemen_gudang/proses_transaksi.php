<?php
// ─────────────────────────────────────────────────────────────
//  proses_transaksi.php
//  Menangani 2 aksi:
//    1. aksi = transaksi  → catat barang masuk/keluar (stok lama)
//    2. aksi = barang_baru → tambah barang baru sekaligus catat stok awal
// ─────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
include 'koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: transaksi_barang.php"); exit;
}

$id_user = (int) $_SESSION['id'];
$aksi    = trim($_POST['aksi'] ?? 'transaksi');

// ════════════════════════════════════════════════════════════
//  HELPER: generate ID transaksi
// ════════════════════════════════════════════════════════════
function generateIdTrx(mysqli $conn): string {
    $prefix = 'TRX-' . date('Ymd') . '-';
    $res    = $conn->query("SELECT COUNT(*) AS cnt FROM transaksi_stok WHERE id_transaksi LIKE '{$prefix}%'");
    $cnt    = (int) $res->fetch_assoc()['cnt'];
    return $prefix . str_pad($cnt + 1, 5, '0', STR_PAD_LEFT);
}

// ════════════════════════════════════════════════════════════
//  HELPER: generate ID barang
// ════════════════════════════════════════════════════════════
function generateIdBarang(mysqli $conn): string {
    $res = $conn->query("SELECT id_barang FROM barang ORDER BY id_barang DESC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $angka = (int) substr($row['id_barang'], 3);
        return 'BRG' . str_pad($angka + 1, 3, '0', STR_PAD_LEFT);
    }
    return 'BRG001';
}

// ════════════════════════════════════════════════════════════
//  AKSI 1: Transaksi stok barang yang sudah ada
// ════════════════════════════════════════════════════════════
if ($aksi === 'transaksi') {

    // Ambil input
    $jenis      = in_array($_POST['jenis'] ?? '', ['masuk','keluar']) ? $_POST['jenis'] : null;
    $id_barang  = trim($_POST['id_barang']     ?? '');
    $jumlah     = floatval($_POST['jumlah']    ?? 0);
    $supplier   = trim($_POST['pihak_terkait'] ?? trim($_POST['supplier'] ?? ''));
    $keterangan = trim($_POST['keterangan']    ?? '');
    $no_struk   = trim($_POST['no_struk']      ?? '');
    $tanggal    = trim($_POST['tanggal']       ?? '');

    // Validasi wajib
    if (!$jenis || !$id_barang || $jumlah <= 0 || !$supplier) {
        $_SESSION['flash_error'] = 'Semua field wajib diisi dengan benar.';
        header("Location: transaksi_barang.php"); exit;
    }

    // Cek barang & stok
    $stmt = $conn->prepare("SELECT stok, stok_min, nama_barang FROM barang WHERE id_barang = ?");
    $stmt->bind_param('s', $id_barang);
    $stmt->execute();
    $barang = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$barang) {
        $_SESSION['flash_error'] = 'Barang tidak ditemukan.';
        header("Location: transaksi_barang.php"); exit;
    }
    if ($jenis === 'keluar' && (float)$barang['stok'] < $jumlah) {
        $_SESSION['flash_error'] = "Stok tidak mencukupi! Stok saat ini: " . number_format($barang['stok'], 0, ',', '.') . ".";
        header("Location: transaksi_barang.php"); exit;
    }

    // Tentukan waktu transaksi
    $created_at = $tanggal ? date('Y-m-d H:i:s', strtotime($tanggal)) : date('Y-m-d H:i:s');

    // Transaksi DB
    $conn->begin_transaction();
    try {
        $id_transaksi = generateIdTrx($conn);

        // Insert ke transaksi_stok
        // Kolom DB: id_transaksi, id_barang, jenis, jumlah, keterangan, id_user, created_at, supplier, no_struk
        $stmt = $conn->prepare("
            INSERT INTO transaksi_stok
                (id_transaksi, id_barang, jenis, jumlah, keterangan, id_user, created_at, supplier, no_struk)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sssdsisss',
            $id_transaksi,
            $id_barang,
            $jenis,
            $jumlah,
            $keterangan,
            $id_user,
            $created_at,
            $supplier,
            $no_struk
        );
        $stmt->execute();
        $stmt->close();

        // Catatan: UPDATE stok barang ditangani otomatis oleh trigger `after_transaksi_insert` di DB.
        // Tidak perlu query UPDATE manual di sini.

        $conn->commit();
        $label = $jenis === 'masuk' ? 'masuk' : 'keluar';
        $_SESSION['flash_success'] = "Transaksi barang {$label} berhasil dicatat! ({$id_transaksi})";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_error'] = 'Gagal menyimpan transaksi: ' . $e->getMessage();
    }

    header("Location: transaksi_barang.php"); exit;
}

// ════════════════════════════════════════════════════════════
//  AKSI 2: Tambah barang baru + catat stok awal
// ════════════════════════════════════════════════════════════
if ($aksi === 'barang_baru') {

    // Ambil input barang
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $merek       = trim($_POST['merek']       ?? '');
    $id_kategori = trim($_POST['id_kategori'] ?? '');
    $satuan      = trim($_POST['satuan']      ?? 'pcs');
    $stok_awal   = floatval($_POST['stok_awal']  ?? 0);
    $stok_min    = floatval($_POST['stok_min']    ?? 10);
    $harga_beli  = floatval($_POST['harga_beli']  ?? 0);
    $harga_jual  = floatval($_POST['harga_jual']  ?? 0);
    $supplier    = trim($_POST['supplier']    ?? 'Stok Awal');
    $keterangan  = trim($_POST['keterangan']  ?? '');
    $tanggal     = trim($_POST['tanggal']     ?? '');

    // Validasi
    if (!$nama_barang || !$id_kategori || !$satuan) {
        $_SESSION['flash_error'] = 'Nama barang, kategori, dan satuan wajib diisi.';
        header("Location: transaksi_barang.php"); exit;
    }

    $created_at = $tanggal ? date('Y-m-d H:i:s', strtotime($tanggal)) : date('Y-m-d H:i:s');

    $conn->begin_transaction();
    try {
        $id_barang    = generateIdBarang($conn);
        $id_transaksi = generateIdTrx($conn);

        // 1. Insert ke tabel barang dengan stok = 0 dulu.
        //    Trigger `after_transaksi_insert` akan otomatis menambahkan stok
        //    saat transaksi masuk dicatat di bawah.
        $stok_init = 0;
        $stmt = $conn->prepare("
            INSERT INTO barang
                (id_barang, nama_barang, merek, id_kategori, satuan, harga_beli, harga_jual, stok, stok_min)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sssssdddd',
            $id_barang, $nama_barang, $merek, $id_kategori,
            $satuan, $harga_beli, $harga_jual, $stok_init, $stok_min
        );
        $stmt->execute();
        $stmt->close();

        // 2. Catat stok awal sebagai transaksi masuk (jika stok > 0)
        //    Trigger akan otomatis update stok di tabel barang.
        if ($stok_awal > 0) {
            $ket_stok_awal = $keterangan ?: "Stok awal: {$nama_barang}";
            $no_struk      = '';
            $jenis_masuk   = 'masuk';
            $stmt = $conn->prepare("
                INSERT INTO transaksi_stok
                    (id_transaksi, id_barang, jenis, jumlah, keterangan, id_user, created_at, supplier, no_struk)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssdsisss',
                $id_transaksi,
                $id_barang,
                $jenis_masuk,
                $stok_awal,
                $ket_stok_awal,
                $id_user,
                $created_at,
                $supplier,
                $no_struk
            );
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        $_SESSION['flash_success'] = "Barang baru \"{$nama_barang}\" berhasil ditambahkan! (ID: {$id_barang})";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_error'] = 'Gagal menambah barang: ' . $e->getMessage();
    }

    // Redirect ke data_barang.php agar barang baru langsung terlihat di tabel inventaris.
    // Transaksi stok-nya tetap tercatat dan muncul di transaksi_barang.php.
    header("Location: data_barang.php"); exit;
}

// Fallback
header("Location: transaksi_barang.php");
exit;