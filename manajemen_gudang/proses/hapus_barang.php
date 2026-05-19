<?php
// proses/hapus_barang.php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../koneksi.php';

// Role check
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['Admin', 'admin'])) {
    $_SESSION['flash_error'] = 'Anda tidak memiliki izin untuk menghapus barang.';
    header('Location: ../data_barang.php');
    exit;
}

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if (!$id) {
    $_SESSION['flash_error'] = 'ID barang tidak valid.';
    header('Location: ../data_barang.php' . $redirect);
    exit;
}

// Ambil nama barang untuk pesan
$res = mysqli_query($conn, "SELECT nama_barang FROM barang WHERE id_barang = '$id'");
$nama = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res)['nama_barang'] : '';

// Hapus transaksi_stok terkait (opsional)
mysqli_query($conn, "DELETE FROM transaksi_stok WHERE id_barang = '$id'");

// Hapus barang
if (mysqli_query($conn, "DELETE FROM barang WHERE id_barang = '$id'")) {
    $_SESSION['flash_success'] = 'Barang <strong>' . htmlspecialchars($nama) . '</strong> berhasil dihapus.';
} else {
    $_SESSION['flash_error'] = 'Gagal menghapus barang: ' . mysqli_error($conn);
}

header('Location: ../data_barang.php' . $redirect);
exit;
