<?php
// =====================================================================
// SISIPKAN BAGIAN INI KE DALAM halaman kategori_barang.php kamu
// =====================================================================
// 1. Tombol "Tambah Kategori" (ganti tombol yang sudah ada):
//    <button onclick="openModal()" class="btn-tambah-kategori">
//        <i class="ti ti-plus"></i> Tambah Kategori
//    </button>
//
// 2. Salin seluruh kode <style>, modal HTML, dan <script> di bawah ini
//    ke dalam halaman kategori_barang.php kamu.
//
// 3. Sesuaikan action form (insert ke database) di bagian PHP handler.
// =====================================================================
?>

<!-- ===== HANDLER PHP (letakkan di bagian ATAS file, sebelum DOCTYPE) ===== -->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_kategori') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $ikon_kategori = trim($_POST['ikon_kategori'] ?? '📦');
    $warna_kategori = trim($_POST['warna_kategori'] ?? '#dbeafe');

    if (!empty($nama_kategori)) {
        // Sesuaikan koneksi database kamu
        // Contoh menggunakan mysqli:
        // $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori, ikon, warna) VALUES (?, ?, ?)");
        // $stmt->bind_param("sss", $nama_kategori, $ikon_kategori, $warna_kategori);
        // $stmt->execute();
        // $stmt->close();

        // Redirect setelah simpan
        header("Location: kategori_barang.php?status=sukses");
        exit;
    } else {
        $error_msg = "Nama kategori tidak boleh kosong.";
    }
}
?>

<!-- ===== CSS (tambahkan ke dalam <style> atau file CSS kamu) ===== -->
<style>
/* ---- Tombol Tambah Kategori ---- */
.btn-tambah-kategori {
    background: #2563eb;
    color: #fff;
    border: none;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: inherit;
    transition: background 0.15s;
}
.btn-tambah-kategori:hover { background: #1d4ed8; }

/* ---- Overlay latar belakang kabur ---- */
#modalOverlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 35, 80, 0.45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
#modalOverlay.active {
    display: flex;
}

/* ---- Card Modal ---- */
.modal-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 28px;
    width: 420px;
    max-width: 95vw;
    box-shadow: 0 12px 48px rgba(15, 35, 80, 0.20);
    border: 0.5px solid #c7d8f0;
    animation: slideUp 0.2s ease;
    position: relative;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ---- Header Modal ---- */
.modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 18px;
}
.modal-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e3a5f;
}
.modal-subtitle {
    font-size: 12px;
    color: #6b7280;
    margin-top: 3px;
}
.btn-close-modal {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: #f0f4fb;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-size: 16px;
    flex-shrink: 0;
    transition: background 0.15s;
}
.btn-close-modal:hover { background: #dce8f8; }

.modal-divider {
    border: none;
    border-top: 0.5px solid #e8eef8;
    margin: 0 -28px 18px;
}

/* ---- Pratinjau ---- */
.preview-row {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f0f6ff;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 18px;
    border: 0.5px solid #dbeafe;
}
.preview-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    background: #dbeafe;
    transition: background 0.2s;
}
.preview-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e3a5f;
}
.preview-meta {
    font-size: 11px;
    color: #6b7280;
    margin-top: 2px;
}

/* ---- Form Grup ---- */
.form-group-modal {
    margin-bottom: 16px;
}
.form-label-modal {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.form-input-modal {
    width: 100%;
    padding: 9px 12px;
    border-radius: 8px;
    border: 1px solid #d0ddf0;
    font-size: 13px;
    color: #1e3a5f;
    background: #f8fafd;
    outline: none;
    font-family: inherit;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.form-input-modal:focus {
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}
.form-error {
    font-size: 11px;
    color: #ef4444;
    margin-top: 4px;
    display: none;
}

/* ---- Grid Ikon ---- */
.icon-picker-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
    margin-top: 6px;
}
.icon-pick-item {
    aspect-ratio: 1;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    border: 2px solid transparent;
    background: #f0f4fb;
    transition: all 0.15s;
    user-select: none;
}
.icon-pick-item:hover { background: #dce8fb; }
.icon-pick-item.selected {
    border-color: #3b82f6;
    background: #eff6ff;
}

/* ---- Picker Warna ---- */
.color-picker-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 6px;
}
.color-pick-item {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid transparent;
    transition: transform 0.15s, border-color 0.15s;
}
.color-pick-item:hover { transform: scale(1.15); }
.color-pick-item.selected {
    border-color: #1e3a5f;
    transform: scale(1.2);
}

/* ---- Footer Modal ---- */
.modal-footer-btns {
    display: flex;
    gap: 10px;
    margin-top: 22px;
}
.btn-batal {
    flex: 1;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #d0ddf0;
    background: #f8fafd;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    color: #6b7280;
    font-family: inherit;
    transition: background 0.15s;
}
.btn-batal:hover { background: #e0e8f8; }
.btn-simpan {
    flex: 2;
    padding: 10px;
    border-radius: 8px;
    border: none;
    background: #2563eb;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: background 0.15s;
}
.btn-simpan:hover { background: #1d4ed8; }

/* ---- Notif sukses (opsional) ---- */
.notif-sukses {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #2563eb;
    color: #fff;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    z-index: 2000;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 20px rgba(37,99,235,0.35);
    animation: fadeInDown 0.3s ease;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

<!-- ===== NOTIF SUKSES (tampil jika redirect dengan ?status=sukses) ===== -->
<?php if (isset($_GET['status']) && $_GET['status'] === 'sukses'): ?>
<div class="notif-sukses" id="notifSukses">
    <i class="ti ti-circle-check"></i>
    Kategori berhasil ditambahkan!
</div>
<script>
    setTimeout(() => {
        const n = document.getElementById('notifSukses');
        if (n) n.style.opacity = '0', n.style.transition = 'opacity 0.4s', setTimeout(() => n.remove(), 400);
    }, 3000);
</script>
<?php endif; ?>

<!-- ===== MODAL POPUP TAMBAH KATEGORI ===== -->
<!-- Letakkan sebelum closing </body> -->
<div id="modalOverlay">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitleText">
        <!-- Header -->
        <div class="modal-header">
            <div>
                <div class="modal-title" id="modalTitleText">Tambah Kategori Baru</div>
                <div class="modal-subtitle">Isi detail kategori barang gudang</div>
            </div>
            <button class="btn-close-modal" onclick="closeModal()" aria-label="Tutup modal">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <hr class="modal-divider">

        <!-- Pratinjau -->
        <div class="preview-row">
            <div class="preview-icon" id="prevIconDisp">📦</div>
            <div>
                <div class="preview-name" id="prevNameDisp">Nama kategori...</div>
                <div class="preview-meta">Pratinjau tampilan kategori</div>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="kategori_barang.php" onsubmit="return validateForm()">
            <input type="hidden" name="action" value="tambah_kategori">
            <input type="hidden" name="ikon_kategori" id="hiddenIcon" value="📦">
            <input type="hidden" name="warna_kategori" id="hiddenWarna" value="#dbeafe">

            <!-- Nama Kategori -->
            <div class="form-group-modal">
                <label class="form-label-modal" for="namaKategori">
                    Nama Kategori <span style="color:#ef4444">*</span>
                </label>
                <input
                    class="form-input-modal"
                    type="text"
                    id="namaKategori"
                    name="nama_kategori"
                    placeholder="Contoh: Dus, Renceng, Lusin..."
                    oninput="updatePreview()"
                    autocomplete="off"
                    maxlength="50"
                >
                <div class="form-error" id="errNama">Nama kategori tidak boleh kosong.</div>
            </div>

            <!-- Pilih Ikon -->
            <div class="form-group-modal">
                <label class="form-label-modal">Pilih Ikon</label>
                <div class="icon-picker-grid" id="iconPickerGrid">
                    <?php
                    $icons = ['📦','🥤','🍬','🧴','🧃','🛒','🧹','🥫','🍪','📋','🎁','🧇'];
                    foreach ($icons as $i => $icon):
                    ?>
                    <div
                        class="icon-pick-item <?= $i === 0 ? 'selected' : '' ?>"
                        data-icon="<?= htmlspecialchars($icon) ?>"
                        onclick="selectIcon(this)"
                        title="Pilih ikon <?= htmlspecialchars($icon) ?>"
                    ><?= $icon ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pilih Warna -->
            <div class="form-group-modal">
                <label class="form-label-modal">Warna Latar Ikon</label>
                <div class="color-picker-row" id="colorPickerRow">
                    <?php
                    $colors = [
                        '#dbeafe' => 'Biru',
                        '#dcfce7' => 'Hijau',
                        '#fce7f3' => 'Merah Muda',
                        '#fff3e0' => 'Oranye',
                        '#f3e8ff' => 'Ungu',
                        '#e0f7fa' => 'Cyan',
                        '#fef9c3' => 'Kuning',
                        '#fce4ec' => 'Mawar',
                    ];
                    $first = true;
                    foreach ($colors as $hex => $label):
                    ?>
                    <div
                        class="color-pick-item <?= $first ? 'selected' : '' ?>"
                        data-bg="<?= $hex ?>"
                        style="background: <?= $hex ?>;"
                        onclick="selectColor(this)"
                        title="<?= $label ?>"
                    ></div>
                    <?php $first = false; endforeach; ?>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="modal-footer-btns">
                <button type="button" class="btn-batal" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-simpan">
                    <i class="ti ti-check"></i> Simpan Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
// Buka modal
function openModal() {
    document.getElementById('modalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    document.getElementById('namaKategori').focus();
}

// Tutup modal
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.style.overflow = '';
    resetForm();
}

// Reset form ke kondisi awal
function resetForm() {
    document.getElementById('namaKategori').value = '';
    document.getElementById('prevNameDisp').textContent = 'Nama kategori...';
    document.getElementById('prevIconDisp').textContent = '📦';
    document.getElementById('prevIconDisp').style.background = '#dbeafe';
    document.getElementById('hiddenIcon').value = '📦';
    document.getElementById('hiddenWarna').value = '#dbeafe';
    document.getElementById('errNama').style.display = 'none';

    document.querySelectorAll('.icon-pick-item').forEach((el, i) => {
        el.classList.toggle('selected', i === 0);
    });
    document.querySelectorAll('.color-pick-item').forEach((el, i) => {
        el.classList.toggle('selected', i === 0);
    });
}

// Update pratinjau nama
function updatePreview() {
    const val = document.getElementById('namaKategori').value.trim();
    document.getElementById('prevNameDisp').textContent = val || 'Nama kategori...';
    if (val) document.getElementById('errNama').style.display = 'none';
}

// Pilih ikon
function selectIcon(el) {
    document.querySelectorAll('.icon-pick-item').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    const icon = el.dataset.icon;
    document.getElementById('prevIconDisp').textContent = icon;
    document.getElementById('hiddenIcon').value = icon;
}

// Pilih warna
function selectColor(el) {
    document.querySelectorAll('.color-pick-item').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    const bg = el.dataset.bg;
    document.getElementById('prevIconDisp').style.background = bg;
    document.getElementById('hiddenWarna').value = bg;
}

// Validasi form sebelum submit
function validateForm() {
    const nama = document.getElementById('namaKategori').value.trim();
    if (!nama) {
        document.getElementById('errNama').style.display = 'block';
        document.getElementById('namaKategori').focus();
        return false;
    }
    return true;
}

// Tutup modal saat klik overlay (bukan modal card)
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Tutup modal dengan tombol Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>
