<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: #f1f5f9; 
        }
        .login-container {
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="max-w-2xl w-full flex bg-white rounded-[2rem] overflow-hidden login-container min-h-[380px]">
        
        <div class="hidden md:flex md:w-[42%] bg-gradient-to-br from-[#1e3a8a] to-[#3b82f6] p-8 flex-col justify-between text-white relative">
            <div class="relative z-10">
                <div class="inline-flex p-2 bg-white/20 rounded-xl backdrop-blur-md mb-4">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight leading-tight">
                    Putra <br> Surya Agung
                </h1>
                <p class="text-blue-100 mt-1 text-[9px] uppercase tracking-[0.2em] font-bold opacity-80">Inventory System</p>
            </div>

            <div class="relative z-10">
                <p class="text-[9px] text-blue-100 font-medium italic opacity-70 leading-relaxed">
                    "Kelola Gudang Anda dengan sistem yang cepat, akurat dan efisien."
                </p>
            </div>
            
            <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <div class="w-full md:w-[58%] p-8 md:p-10 flex flex-col justify-center">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-slate-800 tracking-tight">Sign In</h2>
                <p class="text-slate-500 text-[11px] mt-1">Silakan masuk ke akun Anda.</p>
            </div>

            <?php if(isset($_GET['pesan'])): ?>
                <div class="mb-4 p-2.5 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                    <p class="text-red-700 text-[10px] font-semibold">
                        <?php 
                            if($_GET['pesan'] == "password_salah") echo "Password salah!";
                            if($_GET['pesan'] == "user_tidak_ada") echo "User tidak ditemukan!";
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form action="proses_login.php" method="POST" class="space-y-4">
                <div class="space-y-1">
                    <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Username</label>
                    <input type="text" name="username" placeholder="Username" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm">
                </div>

                <div class="space-y-1">
                    <label class="text-[9px] uppercase tracking-widest font-bold text-slate-400 ml-1">Password</label>
                    <input type="password" name="password" placeholder="••••••••" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all text-sm">
                </div>

                <button type="submit" 
                    class="w-full bg-[#1e3a8a] text-white py-3 rounded-xl font-bold text-[11px] shadow-lg shadow-blue-100 hover:bg-blue-800 hover:-translate-y-0.5 active:scale-[0.98] transition-all mt-2">
                    Masuk ke Sistem
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-slate-300 text-[8px] uppercase tracking-widest font-medium">&copy; 2026 PSA Logistic</p>
            </div>
        </div>
    </div>

</body>
</html>