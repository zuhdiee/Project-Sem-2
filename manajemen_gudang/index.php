<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Putra Surya Agung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-blue-50 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-[2rem] shadow-xl flex flex-col md:flex-row w-full max-w-4xl overflow-hidden mx-4">
        
        <div class="md:w-1/2 flex flex-col items-center justify-center p-8 border-b md:border-b-0 md:border-r border-gray-100">
            <img src="https://via.placeholder.com/150" alt="Logo PSA" class="w-32 mb-4"> <h2 class="text-2xl font-bold text-[#1e40af] tracking-wider text-center">PUTRA SURYA AGUNG</h2>
        </div>

        <div class="md:w-1/2 p-8">
            <div class="text-center mb-8">
                <div class="flex justify-center mb-2">
                    <svg class="w-12 h-12 text-[#1e40af]" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Sistem Manajemen Gudang</h3>
                <p class="text-sm text-gray-500">Silakan masuk untuk mengelola stok</p>
            </div>

            <?php 
            if(isset($_GET['pesan'])){
                if($_GET['pesan'] == "password_salah"){
                    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4 text-center text-sm font-semibold'>Password salah bos!</div>";
                } else if($_GET['pesan'] == "user_tidak_ada"){
                    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4 text-center text-sm font-semibold'>Username gak ketemu!</div>";
                }
            }
            ?>
            <form action="proses_login.php" method="POST" class="space-y-4">
                <div>
                    <input type="text" name="username" autocomplete="off" placeholder="Email / Username" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <input type="password" name="password" autocomplete="off" placeholder="Password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" 
                    class="w-full bg-[#2d527c] text-white py-3 rounded-xl font-bold hover:bg-blue-900 transition duration-300 shadow-lg">
                    Login
                </button>
            </form>
        </div>
    </div>

</body>
</html>