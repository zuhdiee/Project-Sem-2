<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Penjualan | Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f1f5f9; 
            color: #334155;
            font-size: 13px;
        }
        
        .sidebar { background: white; border-right: 1px solid #e2e8f0; }
        
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 12px;
            color: #64748b; font-weight: 500; font-size: 13px; 
            transition: all 0.2s ease; margin-bottom: 8px;
        }

        .nav-link:hover { color: #2563eb; background: #eff6ff; }
        
        .nav-active { 
            background: #2563eb; color: white !important; 
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .nav-label {
            font-size: 10px; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.05em;
            margin: 24px 0 10px 16px;
        }

        .modern-card {
            background: white; border-radius: 20px;
            border: 1px solid #e2e8f0; padding: 24px;
        }

        .chart-placeholder {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-64 sidebar flex flex-col p-5 h-full shrink-0">
        <div class="flex items-center gap-3 mb-10 px-2">
            <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center shrink-0 shadow-lg shadow-blue-200">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
            <div class="flex flex-col">
                <h2 class="text-[14px] font-extrabold tracking-tight text-slate-800 leading-none">Putra Surya Agung</h2>
                <p class="text-[9px] text-blue-600 font-bold uppercase tracking-wider mt-1">Logistic System</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto pr-2">
            <div class="nav-label">Utama</div>
            <a href="dashboard.php" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            <div class="nav-label">Manajemen Stok</div>
            <a href="data_barang.php" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                Data Barang
            </a>
           <a href="kategori_barang.php" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                Kategori Barang
            </a>
            <a href="barang_masuk.php" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Barang Masuk
            </a>
            <a href="barang_keluar.php" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Barang Keluar
            </a>
            <div class="nav-label">Laporan</div>
            <a href="analisis_penjualan.php" class="nav-link nav-active">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Analisis Penjualan
            </a>
        </nav>

        <a href="logout.php" class="nav-link mt-auto text-rose-500 hover:bg-rose-50 border-t border-slate-50 pt-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
            Logout
        </a>
    </aside>

    <main class="flex-1 flex flex-col overflow-y-auto">
        <header class="h-16 flex items-center justify-between px-8 shrink-0 bg-white/80 backdrop-blur-md sticky top-0 z-10 border-b border-slate-200">
            <div class="flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-lg w-80 border border-slate-200">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg>
                <input type="text" placeholder="Cari laporan..." class="bg-transparent border-none outline-none text-[11px] w-full text-slate-600">
            </div>
            
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-[12px] font-bold text-slate-800 leading-none">Erike Adi Mulya</p>
                    <p class="text-[9px] text-blue-600 font-bold uppercase mt-1">Warehouse Admin</p>
                </div>
                <div class="w-9 h-9 bg-blue-100 rounded-full border border-blue-200 flex items-center justify-center text-blue-600 font-bold text-xs shadow-sm">EA</div>
            </div>
        </header>

        <div class="p-8">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-[20px] font-bold text-slate-800 tracking-tight">Analisis Penjualan</h1>
                    <p class="text-slate-500 text-[11px]">Pantau tren keluar masuk barang secara real-time.</p>
                </div>
                <div class="flex gap-2">
                    <button class="bg-white border border-slate-200 px-4 py-2 rounded-xl text-[11px] font-bold text-slate-600 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg>
                        Bulan Ini
                    </button>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-[11px] font-bold shadow-lg shadow-blue-100 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2.5"/></svg>
                        Cetak Laporan
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 modern-card">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-[14px] font-bold text-slate-800">Tren Pergerakan Barang</h2>
                        <div class="flex gap-4 text-[10px] font-bold uppercase tracking-widest">
                            <span class="flex items-center gap-1.5 text-blue-600"><span class="w-2 h-2 bg-blue-600 rounded-full"></span> Masuk</span>
                            <span class="flex items-center gap-1.5 text-rose-500"><span class="w-2 h-2 bg-rose-500 rounded-full"></span> Keluar</span>
                        </div>
                    </div>
                    <div class="h-[300px] chart-placeholder flex items-center justify-center">
                        <p class="text-slate-400 text-[11px] font-medium">[ Visualisasi Grafik Garis / Batang ]</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="modern-card bg-slate-900 text-white border-none relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Total Barang Keluar</p>
                            <h3 class="text-3xl font-extrabold mb-4">4,520 <span class="text-xs font-normal text-slate-400">Pcs</span></h3>
                            <p class="text-[10px] text-emerald-400 font-bold flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 15l7-7 7 7" stroke-width="3"/></svg>
                                12% Meningkat dari bulan lalu
                            </p>
                        </div>
                        <div class="absolute -right-4 -bottom-4 opacity-10">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                    </div>

                    <div class="modern-card">
                        <h2 class="text-[13px] font-bold text-slate-800 mb-4">Barang Terlaris</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 font-bold text-[10px]">01</div>
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-800">Indomie Goreng</p>
                                        <p class="text-[9px] text-slate-400">Sembako</p>
                                    </div>
                                </div>
                                <span class="text-[11px] font-extrabold text-slate-700">850 Keluar</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-50 pt-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400 font-bold text-[10px]">02</div>
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-800">Minyak Goreng 1L</p>
                                        <p class="text-[9px] text-slate-400">Sembako</p>
                                    </div>
                                </div>
                                <span class="text-[11px] font-extrabold text-slate-700">620 Keluar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-auto py-4 px-8 border-t border-slate-200 text-[10px] font-bold text-slate-400 flex justify-between bg-white/50">
            <span>&copy; 2026 PSA LOGISTIC SYSTEM</span>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                <span class="tracking-widest uppercase">Data Sinkron</span>
            </div>
        </footer>
    </main>

</body>
</html>