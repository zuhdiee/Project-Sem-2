<?php
$host = "localhost";
$user = "root";
$pass = "root"; // Kosongkan jika pakai XAMPP default
$db   = "inventory_psa";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>