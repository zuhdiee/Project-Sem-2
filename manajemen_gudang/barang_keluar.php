<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Keluar | Putra Surya Agung</title>
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

        .content-card {
            background: white; border-radius: 20px;
            border: 1px solid #e2e8f0; padding: 24px;
        }

        .input-field {
            width: 100%; background-color: #f8fafc;
            border: 1px solid #e2e8f0; border-radius: 10px;
            padding: 10px 14px; font-size: 12px; outline: none;
        }

        .input-field:focus { border-color: #f43f5e; background-color: white; }

        label {
            display: block; font-size: 11px; font-weight: 600;
            color: #64748b; margin-bottom: 6px;
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
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                Kategori Barang
            </a>
            <a href="barang_masuk.php" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Barang Masuk
            <a href="barang_keluar.php" class="nav-link nav-active"">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Barang Keluar
            </a>
            

            <div class="nav-label">Laporan</div>
            <a href="analisis_penjualan.php" class="nav-link">
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
                <input type="text" placeholder="Cari data pengeluaran..." class="bg-transparent border-none outline-none text-[11px] w-full text-slate-600">
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
            <div class="mb-8">
                <h1 class="text-[20px] font-bold text-slate-800 tracking-tight">Barang Keluar</h1>
                <p class="text-slate-500 text-[11px]">Catat pengeluaran stok barang dari gudang.</p>
            </div>

            <div class="content-card mb-8">
                <h2 class="text-[14px] font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <span class="w-1.5 h-5 bg-rose-500 rounded-full"></span>
                    Input barang keluar
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label>Tanggal Keluar</label>
                            <input type="date" class="input-field">
                        </div>
                        <div>
                            <label>Penerima / Customer</label>
                            <input type="text" class="input-field" placeholder="Nama penerima">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label>Pilih Barang</label>
                            <select class="input-field">
                                <option value="">Cari nama barang...</option>
                            </select>
                        </div>
                        <div>
                            <label>No. Faktur / Surat Jalan</label>
                            <input type="text" class="input-field" placeholder="Contoh: OUT-001">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label>Jumlah Keluar</label>
                            <input type="number" class="input-field" placeholder="0">
                        </div>
                        <div>
                            <label>Keterangan</label>
                            <textarea class="input-field h-[45px] resize-none" placeholder="Alasan pengeluaran..."></textarea>
                        </div>
                        <button class="w-full bg-rose-500 hover:bg-rose-600 text-white py-2.5 rounded-xl text-[11px] font-bold shadow-lg shadow-rose-100 flex items-center justify-center gap-2 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5"/></svg>
                            kurangi stok
                        </button>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <h2 class="text-[14px] font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <span class="w-1.5 h-5 bg-orange-400 rounded-full"></span>
                    Riwayat Barang Keluar (Terbaru)
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-slate-400 text-[10px] uppercase tracking-wider border-b border-slate-100">
                                <th class="pb-4 font-bold">No</th>
                                <th class="pb-4 font-bold">Waktu</th>
                                <th class="pb-4 font-bold">Nama Barang</th>
                                <th class="pb-4 font-bold">Penerima</th>
                                <th class="pb-4 font-bold">Jumlah</th>
                                <th class="pb-4 font-bold">No. Faktur</th>
                            </tr>
                        </thead>
                        <tbody class="text-[11px] text-slate-600">
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 italic">Belum ada pengeluaran stok hari ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="mt-auto py-4 px-8 border-t border-slate-200 text-[10px] font-bold text-slate-400 flex justify-between bg-white/50">
            <span>&copy; 2026 PSA LOGISTIC SYSTEM</span>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                <span class="tracking-widest uppercase">Server Online</span>
            </div>
        </footer>
    </main>

</body>
</html>