<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$userName = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna';
$userRole = $_SESSION['role'] ?? '';

$roleLabels = [
    'Admin'    => 'Admin Gudang',
    'Owner'    => 'Owner',
    'Karyawan' => 'Karyawan',
];
$userRoleLabel = $roleLabels[$userRole] ?? 'Pengguna';

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
<header class="h-14 md:h-16 flex items-center justify-between px-4 md:px-8 bg-white/95 backdrop-blur-md absolute top-0 left-0 w-full z-10 border-b border-slate-100 shadow-sm">

    <!-- Kiri: hamburger (mobile) + tanggal -->
    <div class="flex items-center gap-3">
        <!-- Hamburger button — mobile only -->
        <button onclick="openSidebar()" class="md:hidden w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Date — hidden on small screens, shown on md+ with sidebar offset -->
        <div class="hidden md:flex items-center gap-2 text-slate-400 md:ml-80">
            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-[11px] font-semibold" id="header-datetime"></span>
        </div>
        <!-- Date mobile (compact) -->
        <span class="md:hidden text-[10px] font-semibold text-slate-400" id="header-datetime-mobile"></span>
    </div>

    <!-- Kanan: user info -->
    <div class="flex items-center gap-2 md:gap-3">

        <!-- Badge role — hidden on very small, show from sm -->
        <span class="hidden sm:inline-block px-2 md:px-3 py-1 rounded-full text-[9px] md:text-[10px] font-bold uppercase tracking-wider
            <?php
                if ($userRole === 'Admin')    echo 'bg-blue-50 text-blue-600 border border-blue-100';
                elseif ($userRole === 'Owner') echo 'bg-purple-50 text-purple-600 border border-purple-100';
                else                          echo 'bg-slate-100 text-slate-500 border border-slate-200';
            ?>">
            <?= htmlspecialchars($userRoleLabel) ?>
        </span>

        <!-- Divider -->
        <div class="hidden sm:block w-px h-6 bg-slate-200"></div>

        <!-- Nama + avatar -->
        <div class="flex items-center gap-2">
            <p class="hidden sm:block text-[12px] md:text-[13px] font-bold text-slate-700 leading-tight max-w-[100px] md:max-w-none truncate"><?= htmlspecialchars($userName) ?></p>
            <div class="w-8 h-8 md:w-9 md:h-9 bg-gradient-to-br from-blue-600 to-blue-800 rounded-full flex items-center justify-center text-white font-bold text-[11px] shadow-md ring-2 ring-white flex-shrink-0">
                <?= htmlspecialchars($initials) ?>
            </div>
        </div>

    </div>

</header>

<script>
(function() {
    const days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    function update() {
        const now = new Date();
        const full = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear()
            + '  •  ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
        const short = now.getDate() + ' ' + months[now.getMonth()] + ' • ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
        const el = document.getElementById('header-datetime');
        const elm = document.getElementById('header-datetime-mobile');
        if (el) el.textContent = full;
        if (elm) elm.textContent = short;
    }
    update();
    setInterval(update, 30000);
})();
</script>