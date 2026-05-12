<?php
// proses/tambah_barang.php
include '../koneksi.php'; // Sesuaikan dengan lokasi file koneksi kamu

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama_barang = $_POST['nama_barang'];
    $merek       = $_POST['merek'];
    $id_kategori = $_POST['id_kategori'];
    $satuan      = $_POST['satuan'];
    $harga_beli  = $_POST['harga_beli'];
    $harga_jual  = $_POST['harga_jual'];
    $stok        = $_POST['stok'];
    $stok_min    = !empty($_POST['stok_min']) ? $_POST['stok_min'] : 10;

    // Logika pembuatan ID Barang otomatis (Contoh: BRG001)
    $query_id = mysqli_query($conn, "SELECT id_barang FROM barang ORDER BY id_barang DESC LIMIT 1");
    $data_id  = mysqli_fetch_assoc($query_id);
    if ($data_id) {
        $last_id = $data_id['id_barang'];
        $no_urut = (int) substr($last_id, 3) + 1;
        $id_barang = "BRG" . str_pad($no_urut, 3, "0", STR_PAD_LEFT);
    } else {
        $id_barang = "BRG001";
    }

    // Query Insert
    $sql = "INSERT INTO barang (id_barang, nama_barang, merek, id_kategori, satuan, harga_beli, harga_jual, stok, stok_min) 
            VALUES ('$id_barang', '$nama_barang', '$merek', '$id_kategori', '$satuan', '$harga_beli', '$harga_jual', '$stok', '$stok_min')";

    if (mysqli_query($conn, $sql)) {
        // Redirect kembali ke halaman data barang dengan status sukses
        header("Location: ../data_barang.php?status=success");
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}
?>