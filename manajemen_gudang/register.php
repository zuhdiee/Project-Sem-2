<?php
session_start();
include 'koneksi.php';

// Tidak perlu login Admin — bisa diakses publik dari link "Daftar di sini"

$error       = '';
$success     = '';
$nama_sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username     = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $nama_lengkap = trim(mysqli_real_escape_string($conn, $_POST['nama_lengkap']));
    $password     = $_POST['password'];
    $konfirmasi   = $_POST['konfirmasi_password'];
    $role         = 'Karyawan';

    if (empty($username) || empty($nama_lengkap) || empty($password) || empty($konfirmasi)) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 8) {
        $error = 'Password terlalu lemah. Minimal 8 karakter.';
    } elseif (!preg_match('/[A-Za-z]/', $password)) {
        $error = 'Password terlalu lemah. Harus mengandung huruf.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password terlalu lemah. Harus mengandung angka.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($cek) > 0) {
            $error = 'Username sudah digunakan. Pilih username lain.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $insert = mysqli_query($conn,
                "INSERT INTO users (username, password, nama_lengkap, role)
                 VALUES ('$username', '$hashed', '$nama_lengkap', '$role')"
            );
            if ($insert) {
                $success     = true;
                $nama_sukses = htmlspecialchars($nama_lengkap);
            } else {
                $error = 'Terjadi kesalahan saat menyimpan data. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="include/favicon.png" type="image/png">
    <title>Register | Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; }
        .card { box-shadow: 0 24px 48px -12px rgba(0,0,0,0.12); }
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .alert-success { animation: slideInDown 0.5s cubic-bezier(0.34,1.56,0.64,1) forwards; }
    </style>
</head>

<body class="flex flex-col items-center justify-center min-h-screen p-6">

<div class="w-full max-w-xl">

    <?php if ($success): ?>
    <div class="alert-success mb-5 flex items-start gap-3 bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-3.5" id="notifSukses">
        <div class="w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0 mt-0.5">
            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-emerald-800 text-[12px] font-bold leading-snug">Berhasil!</p>
            <p class="text-emerald-600 text-[11px] mt-0.5 leading-relaxed">
                Akun <span class="font-semibold"><?= $nama_sukses ?></span> berhasil dibuat.
                <a href="index.php" class="underline font-bold">Login sekarang →</a>
            </p>
        </div>
        <button onclick="document.getElementById('notifSukses').remove()" class="text-emerald-400 hover:text-emerald-600 transition flex-shrink-0 mt-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    <?php endif; ?>

    <!-- Card -->
    <div class="bg-white rounded-[2rem] card overflow-hidden">

        <!-- Header -->
        <div class="bg-gradient-to-br from-[#1e3a8a] to-[#3b82f6] px-10 pt-9 pb-8 text-center relative overflow-hidden">
            <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative z-10">
                <div class="inline-flex p-2.5 bg-white/20 rounded-xl backdrop-blur-md mb-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Putra Surya Agung</h1>
                <p class="text-blue-200 text-[9px] uppercase tracking-[0.22em] font-bold mt-1 opacity-80">Inventory System</p>
            </div>
        </div>

        <!-- Form Area -->
        <div class="px-10 py-8">

            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-800 tracking-tight">Tambah Pengguna</h2>
                <p class="text-slate-400 text-[11px] mt-0.5">Buat akun baru untuk pengguna sistem.</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-5 p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-rose-200 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-3 h-3 text-rose-700" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <p class="text-rose-800 text-[12px] font-bold">Gagal Mendaftar</p>
                    <p class="text-rose-600 text-[11px] mt-0.5"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-4">

                <!-- Row 1: Nama + Username -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" placeholder="Nama Lengkap"
                            value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '' ?>"
                            required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Username</label>
                        <input type="text" name="username" placeholder="Username"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                            required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm">
                    </div>
                </div>

                <!-- Row 2: Role (fixed = Karyawan) -->
                <div class="space-y-1">
                    <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Role</label>
                    <input type="hidden" name="role" value="Karyawan">
                    <div class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-sm text-slate-400 flex items-center gap-2 cursor-not-allowed select-none">
                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Karyawan</span>
                        <span class="ml-auto text-[10px] text-slate-400 font-medium">Ditetapkan otomatis</span>
                    </div>
                    <p class="text-[10px] text-slate-400 ml-1 mt-1">Role hanya bisa diubah oleh Admin melalui Kelola Pengguna.</p>
                </div>

                <!-- Row 3: Password -->
                <div class="space-y-1">
                    <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm">
                </div>

                <!-- Row 4: Konfirmasi Password -->
                <div class="space-y-1">
                    <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Konfirmasi Password</label>
                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="••••••••" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm">
                </div>

                <!-- Show Password -->
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" id="showPassword" onclick="togglePassword()"
                            class="w-4 h-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                        <span class="text-[11px] text-slate-500 font-medium">Tampilkan Password</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full bg-[#1e3a8a] text-white py-3 rounded-xl font-bold text-[11px] shadow-lg shadow-blue-100 hover:bg-blue-800 hover:-translate-y-0.5 active:scale-[0.98] transition-all mt-2">
                    Buat Akun
                </button>

            </form>

            <div class="mt-5 text-center">
                <a href="index.php" class="text-[11px] text-blue-700 hover:text-blue-900 font-semibold transition">
                    ← Kembali ke Login
                </a>
            </div>

            <div class="mt-4 text-center">
                <p class="text-slate-300 text-[8px] uppercase tracking-widest font-medium">&copy; 2026 PSA Logistic</p>
            </div>

        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const type = document.getElementById('password').type === 'password' ? 'text' : 'password';
        document.getElementById('password').type = type;
        document.getElementById('konfirmasi_password').type = type;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const notif = document.getElementById('notifSukses');
        if (notif) setTimeout(() => notif.remove(), 5000);
    });
</script>

</body>
</html>