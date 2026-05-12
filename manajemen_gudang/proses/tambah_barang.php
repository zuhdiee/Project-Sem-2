<?php
// proses/tambah_barang.php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $merek       = mysqli_real_escape_string($conn, $_POST['merek'] ?? '');
    $id_kategori = mysqli_real_escape_string($conn, $_POST['id_kategori']);
    $satuan      = mysqli_real_escape_string($conn, $_POST['satuan']);
    $harga_beli  = floatval($_POST['harga_beli'] ?? 0);
    $harga_jual  = floatval($_POST['harga_jual'] ?? 0);
    $stok        = floatval($_POST['stok'] ?? 0);
    $stok_min    = !empty($_POST['stok_min']) ? floatval($_POST['stok_min']) : 10;
    $keterangan  = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');
    $supplier    = mysqli_real_escape_string($conn, $_POST['supplier'] ?? 'Stok Awal');
    $id_user     = (int) ($_SESSION['id'] ?? 1);

    // Validasi wajib
    if (!$nama_barang || !$id_kategori || !$satuan) {
        $_SESSION['flash_error'] = 'Field wajib (Nama Barang, Kategori, Satuan) harus diisi.';
        header("Location: ../transaksi_barang.php");
        exit;
    }

    // Generate ID Barang unik (BRG001, BRG002, ...)
    $res     = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(id_barang, 4) AS UNSIGNED)) AS max_no FROM barang WHERE id_barang LIKE 'BRG%'");
    $max_no  = (int) mysqli_fetch_assoc($res)['max_no'];
    $no_urut = $max_no + 1;
    do {
        $id_barang = "BRG" . str_pad($no_urut, 3, "0", STR_PAD_LEFT);
        $cek = mysqli_query($conn, "SELECT id_barang FROM barang WHERE id_barang = '$id_barang'");
        if (mysqli_num_rows($cek) > 0) $no_urut++;
        else break;
    } while (true);

    // Generate ID Transaksi — FORMAT SAMA dengan proses_transaksi.php
    // TRX-YYYYMMDD-XXXXX
    $prefix  = 'TRX-' . date('Ymd') . '-';
    $res_trx = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM transaksi_stok WHERE id_transaksi LIKE '{$prefix}%'");
    $cnt_trx = (int) mysqli_fetch_assoc($res_trx)['cnt'];
    $id_transaksi = $prefix . str_pad($cnt_trx + 1, 5, '0', STR_PAD_LEFT);

    // Insert ke tabel barang
    $sql = "INSERT INTO barang (id_barang, nama_barang, merek, id_kategori, satuan, harga_beli, harga_jual, stok, stok_min) 
            VALUES ('$id_barang', '$nama_barang', '$merek', '$id_kategori', '$satuan', '$harga_beli', '$harga_jual', '$stok', '$stok_min')";

    if (mysqli_query($conn, $sql)) {

        // Insert ke transaksi_stok jika stok awal > 0
        if ($stok > 0) {
            $ket_final      = $keterangan ?: "Stok awal: $nama_barang";
            $supplier_final = $supplier   ?: 'Stok Awal';
            $now            = date('Y-m-d H:i:s');

            $sql_trx = "INSERT INTO transaksi_stok 
                            (id_transaksi, id_barang, jenis, jumlah, keterangan, id_user, created_at, supplier, no_struk)
                        VALUES 
                            ('$id_transaksi', '$id_barang', 'masuk', $stok, '$ket_final', $id_user, '$now', '$supplier_final', 0)";

            if (!mysqli_query($conn, $sql_trx)) {
                $_SESSION['flash_error'] = 'Barang tersimpan, tapi gagal catat transaksi: ' . mysqli_error($conn);
                header("Location: ../transaksi_barang.php");
                exit;
            }
        }

        $_SESSION['flash_success'] = "Barang <strong>$nama_barang</strong> berhasil ditambahkan!" .
                                     ($stok > 0 ? " Stok awal <strong>$stok $satuan</strong> tercatat di riwayat." : '');
        header("Location: ../transaksi_barang.php");
        exit;

    } else {
        $_SESSION['flash_error'] = 'Gagal menyimpan barang: ' . mysqli_error($conn);
        header("Location: ../transaksi_barang.php");
        exit;
    }
}

header("Location: ../transaksi_barang.php");
exit;