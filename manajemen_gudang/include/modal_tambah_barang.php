<?php
// include/tambah_barang.php
// Ambil daftar kategori dari database
// Pastikan koneksi $conn sudah tersedia dari halaman yang meng-include file ini
$kategori_list = [];
if (isset($conn)) {
    $q = mysqli_query($conn, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $kategori_list[] = $row;
        }
    }
}
?>

<!-- =====================================================
     MODAL TAMBAH BARANG BARU
     Cara pakai:
       1. include file ini sebelum </body>
       2. Tombol pemicu: onclick="openModalTambahBarang()"
===================================================== -->

<!-- Overlay -->
<div id="modalTambahBarang"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background: rgba(15,30,60,0.55); backdrop-filter: blur(4px);">

    <!-- Panel Modal -->
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col"
         style="max-height: 92vh; animation: modalSlideUp 0.25s ease;">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73L11 21.73a2 2 0 0 0 2 0L20 17.73A2 2 0 0 0 21 16z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="12" y1="22.08" x2="12" y2="12" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-[15px] font-bold text-slate-800 leading-tight">Tambah Barang Baru</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Isi informasi lengkap untuk inventaris gudang</p>
                </div>
            </div>
            <button onclick="closeModalTambahBarang()"
                    class="w-8 h-8 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M18 6 6 18M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <!-- Body (scrollable) -->
        <div class="overflow-y-auto flex-1 px-6 py-5">
            <form id="formTambahBarang" method="POST" action="proses/tambah_barang.php">

                <!-- Seksi: Informasi Dasar -->
                <p class="text-[10px] font-bold uppercase tracking-widest text-blue-600 mb-3">Informasi Dasar</p>

                <!-- Nama Barang -->
                <div class="mb-4">
                    <label class="block text-[11px] font-bold text-slate-600 mb-1.5">
                        Nama Barang <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nama_barang" id="inp_nama_barang"
                           placeholder="Contoh: Indomie Goreng, Minyak Goreng 1L…"
                           class="w-full px-3 py-2.5 text-[12px] border border-slate-200 rounded-xl outline-none
                                  focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all
                                  placeholder:text-slate-300 text-slate-800" />
                </div>

                <!-- Kategori + Satuan -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">
                            Kategori <span class="text-rose-500">*</span>
                        </label>
                        <select name="id_kategori" id="inp_id_kategori"
                                class="w-full px-3 py-2.5 text-[12px] border border-slate-200 rounded-xl outline-none
                                       focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all
                                       text-slate-800 bg-white appearance-none cursor-pointer"
                                style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E\"); background-repeat:no-repeat; background-position:right 12px center;">
                            <option value="" disabled selected>Pilih kategori…</option>
                            <?php if (!empty($kategori_list)): ?>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= htmlspecialchars($kat['id_kategori']) ?>">
                                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Fallback statis jika query gagal -->
                                <option value="1">Sembako</option>
                                <option value="2">Elektronik</option>
                                <option value="3">Alat Tulis</option>
                                <option value="4">Minuman</option>
                                <option value="5">Snack</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">
                            Satuan <span class="text-rose-500">*</span>
                        </label>
                        <select name="satuan" id="inp_satuan"
                                class="w-full px-3 py-2.5 text-[12px] border border-slate-200 rounded-xl outline-none
                                       focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all
                                       text-slate-800 bg-white appearance-none cursor-pointer"
                                style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E\"); background-repeat:no-repeat; background-position:right 12px center;">
                            <option value="" disabled selected>Pilih satuan…</option>
                            <option value="Pcs">Pcs</option>
                            <option value="Dus">Dus</option>
                            <option value="Kg">Kg</option>
                            <option value="Liter">Liter</option>
                            <option value="Pack">Pack</option>
                            <option value="Karton">Karton</option>
                            <option value="Lusin">Lusin</option>
                            <option value="Botol">Botol</option>
                        </select>
                    </div>
                </div>

                <hr class="border-slate-100 my-4">

                <!-- Seksi: Stok & Harga -->
                <p class="text-[10px] font-bold uppercase tracking-widest text-blue-600 mb-3">Stok &amp; Harga</p>

                <!-- Stok Awal + Stok Minimum -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">
                            Stok Awal <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="stok" id="inp_stok" min="0"
                               placeholder="0"
                               class="w-full px-3 py-2.5 text-[12px] border border-slate-200 rounded-xl outline-none
                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all
                                      placeholder:text-slate-300 text-slate-800" />
                        <p class="text-[10px] text-slate-400 mt-1">Stok ≤ 10 → <em>Hampir Habis</em></p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">
                            Stok Minimum
                        </label>
                        <input type="number" name="stok_minimum" id="inp_stok_min" min="0"
                               placeholder="10"
                               class="w-full px-3 py-2.5 text-[12px] border border-slate-200 rounded-xl outline-none
                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all
                                      placeholder:text-slate-300 text-slate-800" />
                        <p class="text-[10px] text-slate-400 mt-1">Opsional</p>
                    </div>
                </div>

                <!-- Harga Beli + Harga Jual -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">
                            Harga Beli <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-slate-400 pointer-events-none">Rp</span>
                            <input type="number" name="harga_beli" id="inp_harga_beli" min="0"
                                   placeholder="0"
                                   class="w-full pl-8 pr-3 py-2.5 text-[12px] border border-slate-200 rounded-xl outline-none
                                          focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all
                                          placeholder:text-slate-300 text-slate-800" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1.5">
                            Harga Jual <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-slate-400 pointer-events-none">Rp</span>
                            <input type="number" name="harga_jual" id="inp_harga_jual" min="0"
                                   placeholder="0"
                                   class="w-full pl-8 pr-3 py-2.5 text-[12px] border border-slate-200 rounded-xl outline-none
                                          focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all
                                          placeholder:text-slate-300 text-slate-800" />
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100 my-4">

                <!-- Deskripsi -->
                <div class="mb-2">
                    <label class="block text-[11px] font-bold text-slate-600 mb-1.5">
                        Deskripsi
                        <span class="font-normal text-slate-400">(opsional)</span>
                    </label>
                    <textarea name="deskripsi" id="inp_deskripsi" rows="3"
                              placeholder="Catatan tambahan mengenai barang ini…"
                              class="w-full px-3 py-2.5 text-[12px] border border-slate-200 rounded-xl outline-none
                                     focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all
                                     placeholder:text-slate-300 text-slate-800 resize-none"></textarea>
                </div>

            </form>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2 flex-shrink-0 bg-slate-50/50 rounded-b-2xl">
            <button type="button" onclick="closeModalTambahBarang()"
                    class="px-4 py-2 text-[11px] font-bold text-slate-600 bg-white border border-slate-200
                           rounded-xl hover:bg-slate-100 transition-all">
                Batal
            </button>
            <button type="button" onclick="submitTambahBarang()"
                    class="flex items-center gap-1.5 px-4 py-2 text-[11px] font-bold text-white bg-blue-600
                           rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Simpan Barang
            </button>
        </div>

    </div>
</div>

<!-- Toast Notifikasi -->
<div id="toastTambahBarang"
     class="fixed bottom-6 right-6 z-[60] hidden items-center gap-3 px-4 py-3
            bg-slate-800 text-white text-[12px] font-medium rounded-xl shadow-2xl">
    <div class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0">
        <svg class="w-3 h-3" fill="none" stroke="white" viewBox="0 0 24 24">
            <path d="M20 6 9 17l-5-5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <span>Barang berhasil ditambahkan!</span>
</div>

<!-- CSS animasi -->
<style>
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(18px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0)   scale(1);    }
}
/* Kelas helper: flex tapi tersembunyi */
#modalTambahBarang.show { display: flex !important; }
#toastTambahBarang.show  { display: flex !important; }
/* Highlight error pada input */
.inp-error {
    border-color: #f43f5e !important;
    box-shadow: 0 0 0 3px rgba(244,63,94,0.12) !important;
}
</style>

<script>
/* ── Buka / Tutup Modal ── */
function openModalTambahBarang() {
    const modal = document.getElementById('modalTambahBarang');
    modal.classList.remove('hidden');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModalTambahBarang() {
    const modal = document.getElementById('modalTambahBarang');
    modal.classList.add('hidden');
    modal.classList.remove('show');
    document.body.style.overflow = '';
    resetFormTambahBarang();
}

/* Tutup modal saat klik overlay */
document.getElementById('modalTambahBarang').addEventListener('click', function(e) {
    if (e.target === this) closeModalTambahBarang();
});

/* Tutup modal dengan tombol Escape */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModalTambahBarang();
});

/* ── Validasi & Submit ── */
function submitTambahBarang() {
    const fields = [
        { id: 'inp_nama_barang',  val: () => document.getElementById('inp_nama_barang').value.trim() },
        { id: 'inp_id_kategori',  val: () => document.getElementById('inp_id_kategori').value },
        { id: 'inp_satuan',       val: () => document.getElementById('inp_satuan').value },
        { id: 'inp_stok',         val: () => document.getElementById('inp_stok').value },
        { id: 'inp_harga_beli',   val: () => document.getElementById('inp_harga_beli').value },
        { id: 'inp_harga_jual',   val: () => document.getElementById('inp_harga_jual').value },
    ];

    let valid = true;
    fields.forEach(function(f) {
        const el = document.getElementById(f.id);
        el.classList.remove('inp-error');
        if (!f.val()) {
            el.classList.add('inp-error');
            valid = false;
            /* Hapus highlight saat user mulai mengetik */
            el.addEventListener('input', function() {
                el.classList.remove('inp-error');
            }, { once: true });
        }
    });

    if (!valid) return;

    /* Submit form ke server */
    document.getElementById('formTambahBarang').submit();

    /* ── ATAU: jika pakai AJAX, ganti bagian di atas dengan fetch() ── */
    /*
    const formData = new FormData(document.getElementById('formTambahBarang'));
    fetch('proses/tambah_barang.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeModalTambahBarang();
                showToastTambahBarang();
                // Refresh tabel jika perlu: location.reload();
            } else {
                alert(data.message);
            }
        });
    */
}

/* ── Reset Form ── */
function resetFormTambahBarang() {
    ['inp_nama_barang','inp_stok','inp_stok_min','inp_harga_beli','inp_harga_jual','inp_deskripsi']
        .forEach(function(id) {
            var el = document.getElementById(id);
            if (el) { el.value = ''; el.classList.remove('inp-error'); }
        });
    ['inp_id_kategori','inp_satuan'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) { el.selectedIndex = 0; el.classList.remove('inp-error'); }
    });
}

/* ── Toast Notifikasi ── */
function showToastTambahBarang() {
    const toast = document.getElementById('toastTambahBarang');
    toast.classList.remove('hidden');
    toast.classList.add('show');
    setTimeout(function() {
        toast.classList.add('hidden');
        toast.classList.remove('show');
    }, 3000);
}
</script>
