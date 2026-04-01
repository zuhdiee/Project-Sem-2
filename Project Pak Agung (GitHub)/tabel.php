<?php
session_start(); 

if (isset($_POST['submit'])) {
    $newData = [
        'nama' => $_POST['nama'],
        'nim' => $_POST['nim'],
        'semester' => $_POST['semester'],
        'waktu' => date('d-m-Y H:i:s')
    ];
    if (!isset($_SESSION['mahasiswa'])) { $_SESSION['mahasiswa'] = [];}
    array_unshift($_SESSION['mahasiswa'], $newData);

}
if (isset($_GET['clear'])) {
    session_destroy();
    header("Location: form.php");
    exit;
}

$data_mhs = isset($_SESSION['mahasiswa']) ? $_SESSION['mahasiswa'] : [];
$total_data = count($data_mhs);
$limit = 5;
$total_pages = ceil($total_data / $limit);

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
if ($page < 1){$page = 1;}
if ($page > $total_pages && $total_pages > 0) ($page = $total_pages);

$offset = ($page - 1) * $limit;
$data_page = array_slice($data_mhs, $offset, $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Data Mahasiswa Terdaftar</h4>
            </div>
            <div class="card-body">
                
                <?php if (empty($_SESSION['mahasiswa'])): ?>
                    <div class="alert alert-info text-center">
                        Belum ada data mahasiswa. Silakan input dulu.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>NIM</th>
                                    <th>Semester</th>
                                    <th>Waktu Input</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                // Loop data dari session
                                foreach ($data_page as $mhs): 
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($mhs['nama']); ?></td>
                                    <td><?= htmlspecialchars($mhs['nim']); ?></td>
                                    <td><?= htmlspecialchars($mhs['semester']); ?></td>
                                    <td><?= $mhs['waktu']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1) : ?>
                        <nav aria-label="page navigation" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= ($page <=1) ? 'disabled' : ''; ?>">
                                    <a href="?page=<?= $page - 1; ?>" class="page-item">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                    <li class="page-item<?= ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i;?></a>
                                    </li>
                                <?php endfor?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '';?>">
                                    <a href="?page=<?= $page + 1; ?>" class="page-link"></a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif;?>
                <?php endif; ?>

                <div class="mt-4 d-flex gap-2">
                    <a href="form.php" class="btn btn-primary">Input Lagi</a>
                    <a href="index.php" class="btn btn-secondary">Ke Home</a>
                    <a href="tabel.php?clear=true" class="btn btn-danger ms-auto" onclick="return confirm('Hapus semua data session?')">Reset Data</a>
                </div>

            </div>
        </div>
    </div>

</body>
</html>