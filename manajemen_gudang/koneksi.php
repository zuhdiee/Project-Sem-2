<?php
$host = "127.0.0.1:3307";
$user = "root";
$pass = ""; // Kosongkan jika pakai XAMPP default
$db   = "inventory_psa";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>