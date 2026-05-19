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

<!-- Header -->
<header class="h-16 flex items-center justify-between px-8 bg-white/95 backdrop-blur-md absolute top-0 left-0 w-full z-0 border-b border-slate-100 shadow-sm">

    <!-- Kiri: tanggal & waktu -->
    <div class="ml-80 flex items-center gap-2 text-slate-400">
        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="text-[11px] font-semibold" id="header-datetime"></span>
    </div>

    <!-- Kanan: user info -->
    <div class="flex items-center gap-3">

        <!-- Badge role -->
        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
            <?php
                if ($userRole === 'Admin')    echo 'bg-blue-50 text-blue-600 border border-blue-100';
                elseif ($userRole === 'Owner') echo 'bg-purple-50 text-purple-600 border border-purple-100';
                else                          echo 'bg-slate-100 text-slate-500 border border-slate-200';
            ?>">
            <?= htmlspecialchars($userRoleLabel) ?>
        </span>

        <!-- Divider -->
        <div class="w-px h-6 bg-slate-200"></div>

        <!-- Nama + avatar -->
        <div class="flex items-center gap-2.5">
            <p class="text-[13px] font-bold text-slate-700 leading-tight"><?= htmlspecialchars($userName) ?></p>
            <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-blue-800 rounded-full flex items-center justify-center text-white font-bold text-[11px] shadow-md ring-2 ring-white">
                <?= htmlspecialchars($initials) ?>
            </div>
        </div>

    </div>

</header>

<script>
(function() {
    const el = document.getElementById('header-datetime');
    if (!el) return;
    const days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    function update() {
        const now = new Date();
        el.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear()
            + '  •  ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
    }
    update();
    setInterval(update, 30000);
})();
</script>