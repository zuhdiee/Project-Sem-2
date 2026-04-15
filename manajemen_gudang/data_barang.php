<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang | Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f1f5f9; 
            color: #334155;
            font-size: 13px;
        }
        
        /* Sidebar - Identik dengan Dashboard */
        .sidebar { background: white; border-right: 1px solid #e2e8f0; }
        
        .nav-link {
            display: flex; 
            align-items: center; 
            gap: 12px;
            padding: 12px 16px; 
            border-radius: 12px;
            color: #64748b; 
            font-weight: 500; 
            font-size: 13px; 
            transition: all 0.2s ease;
            margin-bottom: 8px;
        }

        .nav-link:hover { color: #2563eb; background: #eff6ff; }
        
        .nav-active { 
            background: #2563eb; 
            color: white !important; 
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .nav-label {
            font-size: 10px; 
            font-weight: 700; 
            color: #94a3b8;
            text-transform: uppercase; 
            letter-spacing: 0.05em;
            margin: 24px 0 10px 16px;
        }

        /* Content Cards */
        .modern-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07);
            border: 1px solid #cbd5e1; 
        }

        /* Table Specific Styles */
        thead th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            padding: 12px 16px;
            border-bottom: 2px solid #f1f5f9;
        }

        tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
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
                <h2 class="text-[16px] font-extrabold tracking-tight text-slate-800 leading-none">Putra Surya Agung</h2>
                <p class="text-[10px] text-blue-600 font-bold uppercase tracking-wider mt-1">Logistic System</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto pr-2">
            <div class="nav-label">Utama</div>
            <a href="dashboard.php" class="nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            
            <div class="nav-label">Manajemen Stok</div>
            <a href="data_barang.php" class="nav-link nav-active">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3zM9 5v4m6-4v4M4 11h16"/></svg>
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
                <input type="text" placeholder="Cari data barang..." class="bg-transparent border-none outline-none text-[11px] w-full text-slate-600">
            </div>
            
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-[12px] font-bold text-slate-800 leading-none">Erike Adi Mulya</p>
                    <p class="text-[9px] text-blue-600 font-bold uppercase mt-1">Admin Gudang</p>
                </div>
                <div class="w-9 h-9 bg-blue-100 rounded-full border border-blue-200 flex items-center justify-center text-blue-600 font-bold text-xs shadow-sm">EA</div>
            </div>
        </header>

        <div class="p-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-[20px] font-bold text-slate-800 tracking-tight">Data Barang</h1>
                    <p class="text-slate-500 text-[11px]">Kelola dan pantau seluruh daftar inventaris gudang.</p>
                </div>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-[11px] font-bold shadow-lg shadow-blue-200 flex items-center gap-2 transition-all active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Tambah Barang Baru
                </button>
            </div>

            <div class="grid grid-cols-3 gap-5 mb-8">
                <div class="modern-card p-5 border-l-4 border-l-blue-600">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Total Variasi</p>
                    <h3 class="text-xl font-extrabold text-slate-800">1.250 <span class="text-[10px] font-normal text-slate-400 ml-1">Barang</span></h3>
                </div>
                <div class="modern-card p-5 border-l-4 border-l-emerald-600">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Stok Aman</p>
                    <h3 class="text-xl font-extrabold text-slate-800">1.140</h3>
                </div>
                <div class="modern-card p-5 border-l-4 border-l-rose-600">
                    <p class="text-[10px] font-bold text-rose-600 uppercase mb-1">Stok Menipis</p>
                    <h3 class="text-xl font-extrabold text-rose-700">110</h3>
                </div>
            </div>

            <div class="modern-card overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div class="flex gap-2">
                        <select class="text-[10px] font-bold bg-white border border-slate-200 rounded-lg px-3 py-1.5 outline-none text-slate-600">
                            <option>Semua Kategori</option>
                        </select>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Menampilkan 10 data barang</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="w-16 text-center">No</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th class="text-center">Stok</th>
                                <th>Satuan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-[12px]">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="text-center text-slate-400 font-bold">1</td>
                                <td class="font-bold text-slate-800">Indomie Goreng</td>
                                <td>Sembako</td>
                                <td class="text-center font-bold">150</td>
                                <td class="text-slate-500">Dus</td>
                                <td><span class="status-badge bg-emerald-100 text-emerald-600">Tersedia</span></td>
                                <td class="text-center">
                                    <div class="flex justify-center gap-2">
                                        <button class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"/></svg></button>
                                        <button class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg></button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="text-center text-slate-400 font-bold">2</td>
                                <td class="font-bold text-slate-800">Minyak Goreng 1L</td>
                                <td>Sembako</td>
                                <td class="text-center font-bold text-rose-600">8</td>
                                <td class="text-slate-500">Pcs</td>
                                <td><span class="status-badge bg-rose-100 text-rose-600">Hampir Habis</span></td>
                                <td class="text-center">
                                    <div class="flex justify-center gap-2">
                                        <button class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"/></svg></button>
                                        <button class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg></button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="text-center text-slate-400 font-bold">3</td>
                                <td class="font-bold text-slate-800">Minyak Goreng 1L</td>
                                <td>Sembako</td>
                                <td class="text-center font-bold text-rose-600">8</td>
                                <td class="text-slate-500">Pcs</td>
                                <td><span class="status-badge bg-rose-100 text-rose-600">Hampir Habis</span></td>
                                <td class="text-center">
                                    <div class="flex justify-center gap-2">
                                        <button class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"/></svg></button>
                                        <button class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg></button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="text-center text-slate-400 font-bold">4</td>
                                <td class="font-bold text-slate-800">Minyak Goreng 1L</td>
                                <td>Sembako</td>
                                <td class="text-center font-bold text-rose-600">8</td>
                                <td class="text-slate-500">Pcs</td>
                                <td><span class="status-badge bg-rose-100 text-rose-600">Hampir Habis</span></td>
                                <td class="text-center">
                                    <div class="flex justify-center gap-2">
                                        <button class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"/></svg></button>
                                        <button class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 flex justify-center bg-white">
                    <div class="flex gap-1">
                        <button class="w-7 h-7 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-slate-50 transition"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="3"/></svg></button>
                        <button class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold text-[10px] shadow-md shadow-blue-100">1</button>
                        <button class="w-7 h-7 rounded-lg border border-slate-200 text-slate-500 font-bold text-[10px] hover:bg-slate-50 transition">2</button>
                        <button class="w-7 h-7 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-slate-50 transition"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="3"/></svg></button>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-auto py-4 px-8 border-t border-slate-200 text-[10px] font-bold text-slate-400 flex justify-between bg-white/50 backdrop-blur-sm">
            <span>&copy; 2026 PSA LOGISTIC SYSTEM</span>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                <span class="tracking-widest uppercase">Server Online</span>
            </div>
        </footer>
    </main>

</body>
</html>