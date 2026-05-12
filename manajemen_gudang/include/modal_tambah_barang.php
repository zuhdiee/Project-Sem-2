<?php
// include/modal_tambah_barang.php
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

<style>
    @keyframes modalSlideUp {
        from { opacity: 0; transform: translateY(20px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .inp-error { border-color: #f43f5e !important; background-color: #fff1f2 !important; }

    /* Toast Atas Tengah */
    #toastTambahBarang {
        position: fixed;
        top: 30px;
        left: 50%;
        transform: translateX(-50%) translateY(-150%);
        z-index: 9999;
        transition: transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s;
        opacity: 0;
        pointer-events: none;
    }
    #toastTambahBarang.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
        pointer-events: auto;
    }
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div id="modalTambahBarang" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(15,30,60,0.55); backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style="max-height: 92vh; animation: modalSlideUp 0.25s ease;">
        
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <h3 class="text-slate-800 font-bold text-[15px]">Tambah Barang Baru</h3>
                    <p class="text-slate-400 text-[10px]">Putra Surya Agung Inventory</p>
                </div>
            </div>
            <button onclick="closeModalTambahBarang()" class="p-2 hover:bg-slate-50 rounded-xl transition text-slate-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5"/></svg></button>
        </div>

        <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
            <form id="formTambahBarang" action="proses/tambah_barang.php" method="POST" class="space-y-5 pb-6">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 ml-1">Nama Barang *</label>
                    <input type="text" name="nama_barang" id="inp_nama_barang" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-[13px] outline-none focus:border-blue-500 transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 ml-1">Merek</label>
                        <input type="text" name="merek" id="inp_merek" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-[13px] outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 ml-1">Kategori *</label>
                        <select name="id_kategori" id="inp_id_kategori" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-[13px] outline-none">
                            <option value="" disabled selected>Pilih kategori…</option>
                            <?php foreach ($kategori_list as $kat): ?><option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 ml-1">Stok</label><input type="number" name="stok" id="inp_stok" value="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-[13px]"></div>
                    <div><label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 ml-1">Stok Min</label><input type="number" name="stok_min" id="inp_stok_min" value="10" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-[13px]"></div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 ml-1">Satuan *</label>
                        <select name="satuan" id="inp_satuan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-[13px]">
                            <option value="pcs">Pcs</option><option value="kg">Kg</option><option value="dus">Dus</option><option value="liter">Liter</option><option value="sak">Sak</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 ml-1">Harga Beli</label>
                        <input type="number" name="harga_beli" id="inp_harga_beli" oninput="cekMargin()" class="w-full bg-blue-50/50 border border-blue-100 rounded-xl px-4 py-2.5 text-[13px] font-bold text-blue-700 outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 ml-1">Harga Jual</label>
                        <input type="number" name="harga_jual" id="inp_harga_jual" oninput="cekMargin()" class="w-full bg-emerald-50/50 border border-emerald-100 rounded-xl px-4 py-2.5 text-[13px] font-bold text-emerald-700 outline-none">
                    </div>
                </div>
                
                <div id="alert_margin" class="hidden items-center gap-3 p-3 bg-rose-50 border border-rose-100 rounded-xl">
                    <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>
                    <p class="text-[11px] font-bold text-rose-600">Peringatan: Harga jual lebih rendah dari harga beli!</p>
                </div>
            </form>
        </div>

        <div class="p-6 border-t border-slate-100 flex gap-3 bg-slate-50/50 rounded-b-2xl">
            <button onclick="closeModalTambahBarang()" class="flex-1 py-3 text-[12px] font-bold text-slate-500 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-all active:scale-95">Batal</button>
            <button onclick="simpanBaru()" class="flex-[1.5] py-3 text-[12px] font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all active:scale-95">Simpan Barang</button>
        </div>
    </div>
</div>

<div id="toastTambahBarang" class="hidden items-center gap-3 bg-white border border-emerald-100 shadow-2xl p-4 rounded-2xl min-w-[320px]">
    <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white flex-shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div>
        <p class="text-slate-800 font-bold text-[13px]">Berhasil!</p>
        <p class="text-slate-500 text-[11px]">Data barang telah ditambahkan.</p>
    </div>
</div>

<script>
function openModalTambahBarang() { document.getElementById('modalTambahBarang').classList.replace('hidden', 'flex'); }
function closeModalTambahBarang() { document.getElementById('modalTambahBarang').classList.replace('flex', 'hidden'); }

function cekMargin() {
    const hb = parseFloat(document.getElementById('inp_harga_beli').value) || 0;
    const hj = parseFloat(document.getElementById('inp_harga_jual').value) || 0;
    const alertBox = document.getElementById('alert_margin');
    if (hj > 0 && hj < hb) { alertBox.classList.replace('hidden', 'flex'); } 
    else { alertBox.classList.replace('flex', 'hidden'); }
}

function simpanBaru() {
    const fields = ['inp_nama_barang', 'inp_id_kategori', 'inp_satuan'];
    let valid = true;
    fields.forEach(id => {
        const el = document.getElementById(id);
        if(!el.value) { el.classList.add('inp-error'); valid = false; } 
        else { el.classList.remove('inp-error'); }
    });
    if(!valid) return;
    const hb = parseFloat(document.getElementById('inp_harga_beli').value) || 0;
    const hj = parseFloat(document.getElementById('inp_harga_jual').value) || 0;
    if (hj < hb) { if (!confirm('Harga jual lebih rendah dari harga beli. Tetap simpan?')) return; }
    document.getElementById('formTambahBarang').submit();
}

function showToastTambahBarang() {
    const t = document.getElementById('toastTambahBarang');
    t.classList.replace('hidden', 'flex');
    setTimeout(() => t.classList.add('show'), 50);
    setTimeout(() => {
        t.classList.remove('show');
        setTimeout(() => t.classList.replace('flex', 'hidden'), 600);
    }, 3000);
}
</script>