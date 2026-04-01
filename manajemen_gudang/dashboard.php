<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: index.php?pesan=belum_login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; color: #1e293b; }
        .sidebar-active { background: rgba(255, 255, 255, 0.1); border-radius: 0.75rem; }
        .glass-card { background: white; border: 1px solid #e2e8f0; border-radius: 1.5rem; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.03); }
        
        .gradient-area {
            background: linear-gradient(135deg, rgba(255,255,255,1) 0%, rgba(241,245,249,1) 100%);
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-72 bg-[#1e3a8a] text-white flex flex-col p-6 shrink-0 h-full justify-between z-20">
        <div>
            <div class="flex items-center gap-3 mb-10 px-2">
                <div class="bg-white/20 p-2 rounded-xl">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <span class="text-lg font-extrabold tracking-tight whitespace-nowrap">Putra Surya Agung</span>
            </div>

            <nav class="space-y-1.5 text-sm font-medium">
                <a href="#" class="sidebar-active flex items-center gap-3 p-3 transition-all">
                    <span>🏠</span> Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-2xl transition-all opacity-70 hover:opacity-100">
                    <span>📦</span> Data Barang
                </a>
                <a href="#" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-2xl transition-all opacity-70 hover:opacity-100">
                    <span>⬇️</span> Barang Masuk
                </a>
                <a href="#" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-2xl transition-all opacity-70 hover:opacity-100">
                    <span>⬆️</span> Barang Keluar
                </a>
                <a href="#" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-2xl transition-all opacity-70 hover:opacity-100">
                    <span>📊</span> Laporan
                </a>
            </nav>
        </div>

        <a href="logout.php" class="bg-red-500/10 hover:bg-red-500 text-red-400 hover:text-white p-3 rounded-2xl text-center text-xs font-bold transition-all border border-red-500/20 mb-4">
            Logout
        </a>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 h-full">
        
        <main class="flex-1 overflow-y-auto p-8">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Ringkasan Gudang</h1>
                    <p class="text-slate-400 text-sm">Selamat datang kembali, <span class="font-semibold text-blue-600">Admin!</span></p>
                </div>
                <div class="flex gap-2.5">
                    <button class="bg-[#1e3a8a] text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-100 hover:scale-105 transition-all">+ Tambah Barang</button>
                    <button class="bg-blue-500 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-50 hover:scale-105 transition-all">Input Masuk</button>
                </div>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="glass-card p-6 border-l-4 border-blue-500">
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Total Barang</p>
                    <h3 class="text-2xl font-extrabold text-slate-800">1.250 <span class="text-xs font-normal text-slate-400 ml-1">Pcs</span></h3>
                </div>
                <div class="glass-card p-6 border-l-4 border-emerald-500">
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Barang Masuk</p>
                    <h3 class="text-2xl font-extrabold text-slate-800">500</h3>
                </div>
                <div class="glass-card p-6 border-l-4 border-orange-500">
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Barang Keluar</p>
                    <h3 class="text-2xl font-extrabold text-slate-800">300</h3>
                </div>
                <div class="glass-card p-6 bg-orange-50 border border-orange-200">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-orange-500 text-sm">⚠️</span>
                        <p class="text-orange-700 text-xs font-bold uppercase tracking-wider">Stok Tipis</p>
                    </div>
                    <h3 class="text-2xl font-extrabold text-orange-800">10 <span class="text-xs font-normal opacity-70">Item</span></h3>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-4">
                <div class="lg:col-span-2 glass-card p-6 gradient-area">
                    <h4 class="text-sm font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-blue-500 rounded-full"></span> Tren Penjualan
                    </h4>
                    <div class="h-64 bg-slate-50 rounded-2xl border border-dashed border-slate-200 flex items-center justify-center italic text-slate-400 text-xs">
                        [ Area Grafik Terintegrasi ]
                    </div>
                </div>

                <div class="glass-card p-6 gradient-area">
                    <h4 class="text-sm font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-orange-500 rounded-full"></span> Aktivitas Terbaru
                    </h4>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-slate-100 shadow-sm">
                            <div>
                                <p class="text-xs font-bold text-slate-800">Mie Goreng</p>
                                <p class="text-[10px] text-slate-400">29-01-2026 • 2 Dus</p>
                            </div>
                            <span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-lg">Restock</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="h-12 bg-white shrink-0 border-t border-slate-100 flex items-center justify-between px-8 text-[10px] text-slate-400 uppercase tracking-widest font-bold">
            <p>&copy; 2026 Putra Surya Agung Logistic System</p>
            <div class="flex gap-2 items-center">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                <span>System Status: Online</span>
            </div>
        </footer>
    </div>

</body>
</html>