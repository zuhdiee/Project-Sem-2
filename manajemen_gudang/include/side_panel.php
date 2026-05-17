<?php
// Pastikan session aktif dan ambil role user
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';

// Mengambil nama file yang sedang aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-72 bg-[#1e3a8a] text-white flex flex-col p-6 h-screen shrink-0 rounded-r-[30px] shadow-[10px_0_30px_-5px_rgba(0,0,0,0.3)] z-10">
    
    <!-- Logo Section -->
    <div class="flex items-center gap-3 mb-12 px-2">
        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shrink-0 shadow-md">
            <img src="include/logo.png" alt="Logo" class="max-w-full max-h-full object-contain">
        </div>
        <div class="flex flex-col">
            <h2 class="text-lg font-bold tracking-tight leading-none text-white whitespace-nowrap">Putra Surya Agung</h2>
            <p class="text-[10px] text-blue-300 font-medium uppercase tracking-[0.2em] mt-1">Logistic System</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto pr-2">
        
        <!-- Utama Section -->
        <div class="text-blue-200/50 text-[12px] font-semibold px-4 mb-3">Utama</div>
        
        <!-- Dashboard -->
        <a href="dashboard.php" 
           class="flex items-center gap-4 px-6 py-3.5 rounded-full transition-all mb-8 <?php echo ($current_page == 'dashboard.php') ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-900/40' : 'text-white hover:text-blue-200 hover:bg-white/5'; ?>">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-bold">Dashboard</span>
        </a>

        <!-- Manajemen Stok Section -->
        <div class="text-blue-200/50 text-[12px] font-semibold px-4 mb-4 mt-2">Manajemen Stok</div>
        <div class="space-y-4"> <!-- Mengatur jarak agar lebih rapi -->
            
            <!-- Data Barang -->
            <a href="data_barang.php" 
               class="flex items-center gap-4 px-6 py-3 rounded-full transition-all <?php echo ($current_page == 'data_barang.php') ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-900/40 font-bold' : 'text-white hover:text-blue-200 hover:bg-white/5 font-medium'; ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <span>Data Barang</span>
            </a>

            <!-- Kategori Barang -->
            <a href="kategori_barang.php" 
               class="flex items-center gap-4 px-6 py-3 rounded-full transition-all <?php echo ($current_page == 'kategori_barang.php') ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-900/40 font-bold' : 'text-white hover:text-blue-200 hover:bg-white/5 font-medium'; ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                <span>Kategori Barang</span>
            </a>

            <!-- Barang Masuk -->
            <a href="barang_masuk.php" 
               class="flex items-center gap-4 px-6 py-3 rounded-full transition-all <?php echo ($current_page == 'barang_masuk.php') ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-900/40 font-bold' : 'text-white hover:text-blue-200 hover:bg-white/5 font-medium'; ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                <span>Barang Masuk</span>
            </a>

            <!-- Barang Keluar -->
            <a href="barang_keluar.php" 
               class="flex items-center gap-4 px-6 py-3 rounded-full transition-all <?php echo ($current_page == 'barang_keluar.php') ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-900/40 font-bold' : 'text-white hover:text-blue-200 hover:bg-white/5 font-medium'; ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                <span>Barang Keluar</span>
            </a>
        </div>

        <!-- Laporan Section -->
        <div class="text-blue-200/50 text-[12px] font-semibold px-4 mb-4 mt-10">Laporan</div>
        <?php if (in_array($role, ['admin', 'owner'])): ?>
        <a href="analisis_penjualan.php" 
           class="flex items-center gap-4 px-6 py-3 rounded-full transition-all <?php echo ($current_page == 'analisis_penjualan.php') ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-900/40 font-bold' : 'text-white hover:text-blue-200 hover:bg-white/5 font-medium'; ?>">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Analisis Penjualan</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="mt-auto pt-4 flex justify-center">
        <a href="logout.php" class="flex items-center justify-center w-[70%] py-2 bg-[#be123c] hover:bg-red-700 text-white font-bold rounded-full shadow-lg shadow-black/20 transition-all active:scale-95">
            <span class="text-sm">Logout</span>
        </a>
    </div>
</aside>