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
    <title>Dashboard - Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-[#e2effc] flex">

    <aside class="w-64 bg-[#2d527c] min-h-screen text-white flex flex-col p-6 fixed">
        <div class="flex items-center gap-3 mb-10">
            <div class="bg-white p-1 rounded-md text-[#2d527c] font-bold text-xl italic">G</div>
            <h1 class="font-bold text-lg leading-tight uppercase tracking-widest">Putra<br>Surya Agung</h1>
        </div>

        <nav class="flex-1 space-y-2">
            <a href="#" class="flex items-center gap-3 bg-[#3b82f6] p-3 rounded-lg"><img src="icon_dash.svg" class="w-5"> Dashboard</a>
            <a href="#" class="flex items-center gap-3 hover:bg-[#345e8c] p-3 rounded-lg">Data Barang</a>
            <a href="#" class="flex items-center gap-3 hover:bg-[#345e8c] p-3 rounded-lg">Barang Keluar</a>
            <a href="#" class="flex items-center gap-3 hover:bg-[#345e8c] p-3 rounded-lg">Barang Masuk</a>
            <a href="#" class="flex items-center gap-3 hover:bg-[#345e8c] p-3 rounded-lg">Kategori Barang</a>
            <a href="#" class="flex items-center gap-3 hover:bg-[#345e8c] p-3 rounded-lg">Laporan</a>
        </nav>

        <button class="mt-auto w-full bg-[#3b82f6] py-3 rounded-2xl font-bold hover:bg-blue-600 transition">Logout</button>
    </aside>

    <main class="flex-1 ml-64 p-8">
        
        <div class="flex justify-between items-center mb-8">
            <div class="relative w-1/2">
                <input type="text" placeholder="Search..." class="w-full bg-[#d7e6f5] px-10 py-3 rounded-xl focus:outline-none">
                <svg class="w-5 h-5 absolute left-3 top-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-gray-700 font-medium">Halo, <?php echo $_SESSION['nama'];?>!</span>
                <div class="w-10 h-10 bg-[#2d527c] rounded-full border-2 border-white"></div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-[#2d527c]">Dashboard</h2>
            <div class="flex gap-3">
                <button class="bg-[#48a183] text-white px-4 py-2 rounded-xl flex items-center gap-2"><span>+</span> tambah barang</button>
                <button class="bg-[#3b82f6] text-white px-4 py-2 rounded-xl">Input Barang Masuk</button>
                <button class="bg-[#f2994a] text-white px-4 py-2 rounded-xl">Input Barang Keluar</button>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-6 mb-8">
            <div class="col-span-3 bg-white p-6 rounded-3xl flex justify-around items-center shadow-sm">
                <div class="text-center">
                    <p class="text-gray-400 text-sm font-semibold">Total Barang</p>
                    <h3 class="text-3xl font-bold">1250</h3>
                </div>
                <div class="text-center">
                    <p class="text-gray-400 text-sm font-semibold">Barang Masuk</p>
                    <h3 class="text-3xl font-bold">500</h3>
                </div>
                <div class="text-center">
                    <p class="text-gray-400 text-sm font-semibold">Barang Keluar</p>
                    <h3 class="text-3xl font-bold">300</h3>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl flex items-center gap-4 border-l-8 border-yellow-400 shadow-sm">
                <span class="text-4xl">⚠️</span>
                <div>
                    <h4 class="text-[#2d527c] font-bold">Stok hampir habis</h4>
                    <p class="text-gray-500 font-semibold text-sm">10 Item</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-3xl shadow-sm">
                <h4 class="font-bold text-[#2d527c] mb-4">Grafik Penjualan</h4>
                <canvas id="myChart" height="200"></canvas>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm">
                <h4 class="font-bold text-[#2d527c] mb-4">Riwayat Aktivitas</h4>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#2d527c] text-white">
                            <th class="p-2 first:rounded-l-lg">Tanggal</th>
                            <th class="p-2">Nama Barang</th>
                            <th class="p-2">Jumlah</th>
                            <th class="p-2 last:rounded-r-lg">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr class="text-gray-600">
                            <td class="py-3">29-01-2026</td>
                            <td class="py-3 font-semibold">Mie Goreng</td>
                            <td class="py-3">2 Dus</td>
                            <td class="py-3"><span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-xs">Re-Stocked</span></td>
                        </tr>
                        </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('myChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                datasets: [{
                    label: 'Penjualan',
                    data: [12, 19, 3, 5, 2, 3],
                    backgroundColor: ['#ef4444', '#3b82f6', '#ef4444', '#3b82f6', '#ef4444', '#3b82f6'],
                    borderRadius: 5
                }]
            }
        });
    </script>
</body>
</html>