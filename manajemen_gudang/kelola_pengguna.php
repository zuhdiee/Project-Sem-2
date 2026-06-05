<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Hanya Admin yang boleh akses
if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php?pesan=akses_ditolak");
    exit();
}

$success = '';
$error   = '';

// ── HAPUS USER ──────────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    if ($id_hapus === (int) $_SESSION['id']) {
        $error = 'Tidak dapat menghapus akun Anda sendiri.';
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id = $id_hapus");
        $success = 'Pengguna berhasil dihapus.';
    }
}

// ── RESET PASSWORD ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $id_reset    = (int) $_POST['id_user'];
    $pw_baru     = $_POST['password_baru'];
    $pw_konfirm  = $_POST['konfirmasi_password'];

    if (strlen($pw_baru) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($pw_baru !== $pw_konfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hashed = password_hash($pw_baru, PASSWORD_BCRYPT);
        mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE id = $id_reset");
        $success = 'Password berhasil direset.';
    }
}

// ── EDIT USER ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $id_edit      = (int) $_POST['id_user'];
    $nama_edit    = trim(mysqli_real_escape_string($conn, $_POST['nama_lengkap']));
    $username_edit = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $role_edit    = $_POST['role'];
    $allowed      = ['Admin', 'Owner', 'Karyawan'];

    if (empty($nama_edit) || empty($username_edit)) {
        $error = 'Nama dan username tidak boleh kosong.';
    } elseif (!in_array($role_edit, $allowed)) {
        $error = 'Role tidak valid.';
    } else {
        // Cek username duplikat (selain diri sendiri)
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_edit' AND id != $id_edit");
        if (mysqli_num_rows($cek) > 0) {
            $error = 'Username sudah digunakan pengguna lain.';
        } else {
            mysqli_query($conn, "UPDATE users SET nama_lengkap = '$nama_edit', username = '$username_edit', role = '$role_edit' WHERE id = $id_edit");
            $success = 'Data pengguna berhasil diperbarui.';
        }
    }
}

// ── AMBIL DATA USER ──────────────────────────────────────────
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="include/favicon.png" type="image/png">
    <title>Kelola Pengguna | Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; color: #334155; font-size: 13px; }
        /* ── Toast Notification ── */
        #toast-container{position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:9999;display:flex;flex-direction:column;align-items:center;gap:10px;pointer-events:none}
        .toast{display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:16px;font-size:12.5px;font-weight:600;min-width:320px;max-width:520px;box-shadow:0 12px 40px rgba(0,0,0,0.15),0 2px 8px rgba(0,0,0,0.08);pointer-events:all;opacity:0;transform:translateY(-20px) scale(0.96);transition:opacity 0.35s cubic-bezier(0.34,1.56,0.64,1),transform 0.35s cubic-bezier(0.34,1.56,0.64,1);position:relative;overflow:hidden}
        .toast.show{opacity:1;transform:translateY(0) scale(1)}
        .toast.hide{opacity:0;transform:translateY(-16px) scale(0.96);transition:opacity 0.25s ease,transform 0.25s ease}
        .toast-ok {background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1.5px solid #6ee7b7;color:#065f46}
        .toast-err{background:linear-gradient(135deg,#fff1f2,#ffe4e6);border:1.5px solid #fca5a5;color:#9f1239}
        .toast-icon{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px}
        .toast-icon-ok {background:#bbf7d0}
        .toast-icon-err{background:#fecdd3}
        .toast-body{flex:1}
        .toast-title{font-size:12px;font-weight:700;margin-bottom:2px}
        .toast-msg{font-size:11px;font-weight:500;opacity:0.85;line-height:1.4}
        .toast-close{width:22px;height:22px;border-radius:6px;border:none;cursor:pointer;background:rgba(0,0,0,0.07);color:inherit;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;transition:background 0.15s}
        .toast-close:hover{background:rgba(0,0,0,0.14)}
        .toast-progress{position:absolute;bottom:0;left:0;height:3px;border-radius:0 0 16px 16px;animation:toast-bar 4.5s linear forwards}
        .toast-progress-ok {background:linear-gradient(90deg,#34d399,#059669)}
        .toast-progress-err{background:linear-gradient(90deg,#fb7185,#e11d48)}
        @keyframes toast-bar{from{width:100%}to{width:0%}}
        @keyframes modal-fadein { from{opacity:0} to{opacity:1} }
        @keyframes card-slidein { from{transform:translateY(24px) scale(0.95);opacity:0} to{transform:translateY(0) scale(1);opacity:1} }
        .modal-bg  { animation: modal-fadein 0.2s ease both; }
        .modal-card { animation: card-slidein 0.28s cubic-bezier(0.34,1.56,0.64,1) 0.04s both; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

<!-- ── Toast Container ── -->
<div id="toast-container"></div>

<?php include_once 'include/side_panel.php'; ?>

<main class="flex-1 flex flex-col overflow-y-auto">
<?php include_once 'include/header.php'; ?>

<div class="p-8 pt-20">

    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Kelola Pengguna</h1>
            <p class="text-slate-400 text-[11px] mt-0.5">Manajemen akun pengguna sistem SIMGUDANG</p>
        </div>
        <button onclick="bukaModalTambah()"
            class="inline-flex items-center gap-2 bg-[#1e3a8a] text-white px-4 py-2.5 rounded-xl text-[12px] font-bold shadow-lg shadow-blue-100 hover:bg-blue-800 hover:-translate-y-0.5 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Pengguna
        </button>
    </div>

    <?php /* Flash messages handled by toast – see #toast-container */ ?>

    <?php if ($error): ?>
    <div class="notif mb-5 flex items-center gap-3 bg-white border border-rose-200 border-l-4 border-l-rose-500 rounded-xl px-4 py-3 shadow-sm" id="notifEl">
        <div class="w-7 h-7 rounded-lg bg-rose-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-slate-800 text-[12px] font-bold leading-none mb-0.5">Gagal!</p>
            <p class="text-slate-500 text-[11px]"><?= htmlspecialchars($error) ?></p>
        </div>
        <button onclick="document.getElementById('notifEl').remove()" class="text-slate-300 hover:text-slate-500 transition ml-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    <?php endif; ?>

    <!-- Tabel -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:28%">
                    <col style="width:22%">
                    <col style="width:15%">
                    <col style="width:30%">
                </colgroup>
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-5 py-3.5 text-left text-[10px] uppercase tracking-widest font-bold text-slate-400">#</th>
                        <th class="px-5 py-3.5 text-left text-[10px] uppercase tracking-widest font-bold text-slate-400">Nama Lengkap</th>
                        <th class="px-5 py-3.5 text-left text-[10px] uppercase tracking-widest font-bold text-slate-400">Username</th>
                        <th class="px-5 py-3.5 text-left text-[10px] uppercase tracking-widest font-bold text-slate-400">Role</th>
                        <th class="px-5 py-3.5 text-left text-[10px] uppercase tracking-widest font-bold text-slate-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php $no = 1; while ($u = mysqli_fetch_assoc($users)): ?>
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-5 py-4 text-slate-400 text-[12px]"><?= $no++ ?></td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#1e3a8a] to-[#3b82f6] flex items-center justify-center flex-shrink-0">
                                    <span class="text-white text-[11px] font-bold"><?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?></span>
                                </div>
                                <span class="font-semibold text-slate-700 text-[13px]"><?= htmlspecialchars($u['nama_lengkap']) ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-500 text-[12px] font-mono"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="px-5 py-4">
                            <?php
                            $badge = match($u['role']) {
                                'Admin'    => 'bg-violet-100 text-violet-700',
                                'Owner'    => 'bg-amber-100 text-amber-700',
                                'Karyawan' => 'bg-sky-100 text-sky-700',
                                default    => 'bg-slate-100 text-slate-600'
                            };
                            ?>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold <?= $badge ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button onclick="bukaModalEdit(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= $u['role'] ?>')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-[11px] font-semibold hover:bg-blue-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>
                                <button onclick="bukaModalReset(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES) ?>')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-[11px] font-semibold hover:bg-amber-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    Reset PW
                                </button>
                                <?php if ($u['id'] != $_SESSION['id']): ?>
                                <button onclick="bukaModalHapus(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES) ?>')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 text-rose-600 rounded-lg text-[11px] font-semibold hover:bg-rose-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Hapus
                                </button>
                                <?php else: ?>
                                <span class="text-[11px] text-slate-300 font-medium italic">— Akun Anda</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── MODAL TAMBAH PENGGUNA ───────────────────────────────── -->
<div id="modalTambah" class="modal-bg fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(15,30,60,0.5);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-card">
        <div class="bg-gradient-to-br from-[#1e3a8a] to-[#3b82f6] px-6 py-5 rounded-t-2xl">
            <h3 class="text-[14px] font-bold text-white">Tambah Pengguna Baru</h3>
            <p class="text-blue-200 text-[11px] mt-0.5">Isi data pengguna yang akan ditambahkan</p>
        </div>
        <form action="register.php" method="GET" onsubmit="event.preventDefault(); window.location='register.php'">
        </form>
        <div class="p-6">
            <p class="text-slate-500 text-[12px] mb-4">Pengguna baru dapat didaftarkan melalui halaman Register.</p>
            <div class="flex gap-3">
                <button onclick="tutupModal('modalTambah')" class="flex-1 py-2.5 text-[12px] font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Batal</button>
                <a href="register.php" class="flex-1 py-2.5 text-[12px] font-bold text-white bg-[#1e3a8a] rounded-xl hover:bg-blue-800 transition text-center">Ke Halaman Register</a>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL EDIT PENGGUNA ────────────────────────────────── -->
<div id="modalEdit" class="modal-bg fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(15,30,60,0.5);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-card">
        <div class="bg-gradient-to-br from-[#1e3a8a] to-[#3b82f6] px-6 py-5 rounded-t-2xl">
            <h3 class="text-[14px] font-bold text-white">Edit Pengguna</h3>
            <p class="text-blue-200 text-[11px] mt-0.5">Ubah data pengguna</p>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="id_user" id="edit_id_user">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit_nama" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Username</label>
                    <input type="text" name="username" id="edit_username" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm">
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Role</label>
                <select name="role" id="edit_role" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm text-slate-700">
                    <option value="Admin">Admin</option>
                    <option value="Owner">Owner</option>
                    <option value="Karyawan">Karyawan</option>
                </select>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="tutupModal('modalEdit')" class="flex-1 py-2.5 text-[12px] font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Batal</button>
                <button type="submit" class="flex-1 py-2.5 text-[12px] font-bold text-white bg-[#1e3a8a] rounded-xl hover:bg-blue-800 transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL RESET PASSWORD ───────────────────────────────── -->
<div id="modalReset" class="modal-bg fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(15,30,60,0.5);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm modal-card">
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 px-6 py-5 rounded-t-2xl">
            <h3 class="text-[14px] font-bold text-white">Reset Password</h3>
            <p class="text-amber-100 text-[11px] mt-0.5">Reset password untuk <span id="reset_nama_label" class="font-bold"></span></p>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id_user" id="reset_id_user">
            <div class="space-y-1">
                <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Password Baru</label>
                <input type="password" name="password_baru" id="reset_pw" placeholder="••••••••" required
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-50 focus:border-amber-400 outline-none transition-all text-sm">
            </div>
            <div class="space-y-1">
                <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" id="reset_pw_konfirm" placeholder="••••••••" required
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-50 focus:border-amber-400 outline-none transition-all text-sm">
            </div>
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" onclick="toggleResetPw()" class="w-4 h-4 rounded border-slate-300 focus:ring-amber-400">
                    <span class="text-[11px] text-slate-500 font-medium">Tampilkan Password</span>
                </label>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="tutupModal('modalReset')" class="flex-1 py-2.5 text-[12px] font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Batal</button>
                <button type="submit" class="flex-1 py-2.5 text-[12px] font-bold text-white bg-amber-500 rounded-xl hover:bg-amber-600 transition">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL HAPUS ────────────────────────────────────────── -->
<div id="modalHapus" class="modal-bg fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(15,30,60,0.5);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm modal-card p-6 text-center">
        <div class="w-12 h-12 rounded-2xl bg-rose-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>
        <h3 class="text-[15px] font-bold text-slate-800 mb-1">Hapus Pengguna?</h3>
        <p class="text-slate-500 text-[12px] mb-5">Akun <span id="hapus_nama_label" class="font-semibold text-slate-700"></span> akan dihapus permanen dan tidak dapat dikembalikan.</p>
        <div class="flex gap-3">
            <button onclick="tutupModal('modalHapus')" class="flex-1 py-2.5 text-[12px] font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Batal</button>
            <a id="hapus_link" href="#" class="flex-1 py-2.5 text-[12px] font-bold text-white bg-rose-500 rounded-xl hover:bg-rose-600 transition">Ya, Hapus</a>
        </div>
    </div>
</div>

<script>
    // ── Toast System ──────────────────────────────────────────────
    function showToast(type, title, message) {
        const container = document.getElementById('toast-container');
        const isOk = type === 'ok';

        const toast = document.createElement('div');
        toast.className = 'toast ' + (isOk ? 'toast-ok' : 'toast-err');
        toast.innerHTML = `
            <div class="toast-icon ${isOk ? 'toast-icon-ok' : 'toast-icon-err'}">
                ${isOk
                    ? `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#059669"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`
                    : `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#e11d48"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round"/></svg>`
                }
            </div>
            <div class="toast-body">
                <div class="toast-title">${title}</div>
                <div class="toast-msg">${message}</div>
            </div>
            <button class="toast-close" onclick="dismissToast(this.closest('.toast'))">✕</button>
            <div class="toast-progress ${isOk ? 'toast-progress-ok' : 'toast-progress-err'}"></div>
        `;
        container.appendChild(toast);

        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));

        const timer = setTimeout(() => dismissToast(toast), 4500);
        toast._timer = timer;
    }
    function dismissToast(toast) {
        if (!toast) return;
        clearTimeout(toast._timer);
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }

    // ── Trigger dari PHP ───────────────────────────────────────────
    <?php if ($success): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showToast('ok', 'Berhasil!', <?= json_encode($success) ?>);
    });
    <?php endif; ?>
    <?php if ($error): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showToast('err', 'Gagal!', <?= json_encode($error) ?>);
    });
    <?php endif; ?>

    // Notif auto-hilang (legacy fallback)
    document.addEventListener('DOMContentLoaded', () => {
        const n = document.getElementById('notifEl');
        if (n) setTimeout(() => n.remove(), 5000);
    });

    function tutupModal(id) {
        const m = document.getElementById(id);
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    function bukaModal(id) {
        const m = document.getElementById(id);
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    // Tutup modal klik backdrop
    ['modalTambah','modalEdit','modalReset','modalHapus'].forEach(id => {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) tutupModal(id);
        });
    });

    function bukaModalTambah() { bukaModal('modalTambah'); }

    function bukaModalEdit(id, nama, username, role) {
        document.getElementById('edit_id_user').value  = id;
        document.getElementById('edit_nama').value     = nama;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_role').value     = role;
        bukaModal('modalEdit');
    }

    function bukaModalReset(id, nama) {
        document.getElementById('reset_id_user').value     = id;
        document.getElementById('reset_nama_label').textContent = nama;
        document.getElementById('reset_pw').value          = '';
        document.getElementById('reset_pw_konfirm').value  = '';
        bukaModal('modalReset');
    }

    function bukaModalHapus(id, nama) {
        document.getElementById('hapus_nama_label').textContent = nama;
        document.getElementById('hapus_link').href = 'kelola_pengguna.php?hapus=' + id;
        bukaModal('modalHapus');
    }

    function toggleResetPw() {
        const type = document.getElementById('reset_pw').type === 'password' ? 'text' : 'password';
        document.getElementById('reset_pw').type = type;
        document.getElementById('reset_pw_konfirm').type = type;
    }
</script>

<?php include_once 'include/footer.php'; ?>

</main>
</body>
</html>