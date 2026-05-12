<?php
// Guard session — tidak double start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

// Harus login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: transaksi_barang.php");
    exit;
}

// ── Ambil & sanitasi input ────────────────────────────────────────
$jenis      = in_array($_POST['jenis'] ?? '', ['masuk', 'keluar']) ? $_POST['jenis'] : null;
$id_barang  = trim($_POST['id_barang']     ?? '');
$jumlah     = floatval($_POST['jumlah']    ?? 0);
$pihak      = trim($_POST['pihak_terkait'] ?? '');
$keterangan = trim($_POST['keterangan']    ?? '');
$tanggal    = trim($_POST['tanggal']       ?? '');
$id_user    = (int) $_SESSION['id'];

// ── Validasi ──────────────────────────────────────────────────────
if (!$jenis || !$id_barang || $jumlah <= 0 || !$pihak) {
    $_SESSION['flash_error'] = 'Semua field wajib diisi dengan benar.';
    header("Location: transaksi_barang.php");
    exit;
}

// ── Cek barang & stok ─────────────────────────────────────────────
$stmt = $conn->prepare("SELECT stok, stok_min, nama_barang FROM barang WHERE id_barang = ?");
$stmt->bind_param('s', $id_barang);
$stmt->execute();
$barang = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$barang) {
    $_SESSION['flash_error'] = 'Barang tidak ditemukan.';
    header("Location: transaksi_barang.php");
    exit;
}

// Cegah stok negatif
if ($jenis === 'keluar' && $barang['stok'] < $jumlah) {
    $_SESSION['flash_error'] = "Stok tidak mencukupi! Stok saat ini: {$barang['stok']}.";
    header("Location: transaksi_barang.php");
    exit;
}

// ── Transaksi DB ──────────────────────────────────────────────────
$conn->begin_transaction();

try {
    // Generate ID transaksi
    $prefix = 'TRX-' . date('Ymd') . '-';
    $res    = $conn->query("SELECT COUNT(*) AS cnt FROM transaksi_stok WHERE id_transaksi LIKE '{$prefix}%'");
    $cnt    = (int) $res->fetch_assoc()['cnt'];
    $id_transaksi = $prefix . str_pad($cnt + 1, 5, '0', STR_PAD_LEFT);

    $created_at = $tanggal ? date('Y-m-d H:i:s', strtotime($tanggal)) : date('Y-m-d H:i:s');

    // Insert transaksi_stok
    // Kolom: id_transaksi, id_barang, jenis, jumlah, keterangan, id_user, created_at, supplier, no_struk
    $stmt = $conn->prepare("
        INSERT INTO transaksi_stok
            (id_transaksi, id_barang, jenis, jumlah, keterangan, id_user, created_at, supplier, no_struk)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
    ");
    $stmt->bind_param('sssdsiss',
        $id_transaksi,
        $id_barang,
        $jenis,
        $jumlah,
        $keterangan,
        $id_user,
        $created_at,
        $pihak        // kolom supplier = nama supplier / penerima
    );
    $stmt->execute();
    $stmt->close();

    // Update stok barang
    if ($jenis === 'masuk') {
        $stmt = $conn->prepare("UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
    } else {
        $stmt = $conn->prepare("UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
    }
    $stmt->bind_param('ds', $jumlah, $id_barang);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION['flash_success'] = "Transaksi {$jenis} berhasil! ({$id_transaksi})";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_error'] = 'Gagal menyimpan: ' . $e->getMessage();
}

header("Location: transaksi_barang.php");
exit;