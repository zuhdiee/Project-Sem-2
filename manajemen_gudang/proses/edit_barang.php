<?php
// proses/edit_barang.php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../data_barang.php');
    exit;
}

// role check (only Admin allowed)
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['Admin', 'admin'])) {
    $_SESSION['flash_error'] = 'Anda tidak memiliki izin untuk mengedit barang.';
    header('Location: ../data_barang.php');
    exit;
}

$id_barang   = mysqli_real_escape_string($conn, $_POST['id_barang'] ?? '');
$nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang'] ?? '');
$merek       = mysqli_real_escape_string($conn, $_POST['merek'] ?? '');
$id_kategori = mysqli_real_escape_string($conn, $_POST['id_kategori'] ?? '');
$satuan      = mysqli_real_escape_string($conn, $_POST['satuan'] ?? '');
$harga_beli  = floatval($_POST['harga_beli'] ?? 0);
$harga_jual  = floatval($_POST['harga_jual'] ?? 0);
$stok        = floatval($_POST['stok'] ?? 0);
$stok_min    = !empty($_POST['stok_min']) ? floatval($_POST['stok_min']) : 0;

if (!$id_barang || !$nama_barang || !$id_kategori || !$satuan) {
    $_SESSION['flash_error'] = 'Field wajib (Nama Barang, Kategori, Satuan) harus diisi.';
    header('Location: ../edit_barang.php?id=' . urlencode($id_barang));
    exit;
}

$sql = "UPDATE barang SET 
            nama_barang = '$nama_barang',
            merek = '$merek',
            id_kategori = '$id_kategori',
            satuan = '$satuan',
            harga_beli = $harga_beli,
            harga_jual = $harga_jual,
            stok = $stok,
            stok_min = $stok_min
        WHERE id_barang = '$id_barang'";

if (mysqli_query($conn, $sql)) {
    $_SESSION['flash_success'] = "Perubahan barang <strong>$nama_barang</strong> berhasil disimpan.";
    header('Location: ../data_barang.php');
    exit;
} else {
    $_SESSION['flash_error'] = 'Gagal menyimpan perubahan: ' . mysqli_error($conn);
    header('Location: ../edit_barang.php?id=' . urlencode($id_barang));
    exit;
}
