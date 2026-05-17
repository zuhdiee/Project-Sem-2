<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Ambil nama dari session — login.php wajib set $_SESSION['nama_lengkap'] dan $_SESSION['role']
$userName = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna';
$userRole = $_SESSION['role'] ?? '';

// Label role sesuai enum di DB: Admin | Owner | Karyawan
$roleLabels = [
    'Admin'    => 'Admin Gudang',
    'Owner'    => 'Owner',
    'Karyawan' => 'Karyawan',
];
$userRoleLabel = $roleLabels[$userRole] ?? 'Pengguna';

// Inisial dari nama lengkap (maks 2 huruf)
$initials = '';
foreach (explode(' ', trim($userName)) as $part) {
    if ($part !== '') {
        $initials .= strtoupper($part[0]);
        if (strlen($initials) >= 2) break;
    }
}
if ($initials === '') $initials = 'US';
?>

<!-- Header tetap h-16, absolute, z-0 — tampilan tidak berubah -->
<header class="h-16 flex items-center justify-between px-8 bg-white/90 backdrop-blur-md absolute top-0 left-0 w-full z-0 border-b border-slate-100">

    <!-- Search bar dengan margin kiri agar muncul dari balik sidebar -->
    <div class="flex items-center gap-3 bg-slate-50 px-5 py-2 rounded-full w-96 border border-slate-200/60 ml-80 transition-all">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <input type="text" placeholder="Cari kategori..." class="bg-transparent border-none outline-none text-sm w-full text-slate-600 placeholder:text-slate-400">
    </div>

    <!-- User info — nama & role dari session DB -->
    <div class="flex items-center gap-3">
        <div class="text-right">
            <p class="text-[13px] font-bold text-[#1e3a8a] leading-tight"><?= htmlspecialchars($userName) ?></p>
            <p class="text-[10px] text-blue-500 font-semibold mt-0.5 uppercase tracking-wide"><?= htmlspecialchars($userRoleLabel) ?></p>
        </div>
        <div class="w-10 h-10 bg-[#1e3a8a] rounded-full flex items-center justify-center text-white font-bold text-xs shadow-md border-2 border-white">
            <?= htmlspecialchars($initials) ?>
        </div>
    </div>

</header>