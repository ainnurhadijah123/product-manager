<?php
/**
 * public/index.php
 * -----------------------------------------------------------
 * READ - Menampilkan daftar produk dalam bentuk card responsif (Flexbox).
 * Ditambah: Pengecekan Login, Role (Admin/Staff), dan Perbaikan Query Kategori.
 */

session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

// === TAMBAHAN 1: Kunci Halaman ===
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// =================================

// ---------- Bonus: Search & Filter (GET) ----------
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    // PERBAIKAN: Menggunakan LEFT JOIN untuk mengambil nama kategori
    $stmt = $pdo->query(
        "SELECT p.id, p.name, c.name AS category, p.price, p.stock, p.image
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         ORDER BY p.id DESC"
    );
    $products = $stmt->fetchAll();
} else {
    // PERBAIKAN: Menggunakan LEFT JOIN dan mencari berdasarkan nama atau kategori
    $stmt = $pdo->prepare(
        "SELECT p.id, p.name, c.name AS category, p.price, p.stock, p.image
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.name LIKE :q1 OR c.name LIKE :q2
         ORDER BY p.id DESC"
    );
    $keyword = '%' . $q . '%';
    $stmt->execute(['q1' => $keyword, 'q2' => $keyword]);
    $products = $stmt->fetchAll();
}

// ---------- Pesan status hasil redirect (pola PRG) ----------
$statusMessages = [
    'created' => 'Produk baru telah berhasil ditambahkan.',
    'updated' => 'Produk baru saja diperbarui.',
    'deleted' => 'Produk ini telah dihapus.',
];
$status = $_GET['status'] ?? '';
$flashMessage = $statusMessages[$status] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    
    <!-- === TAMBAHAN CSS DROPDOWN === -->
    <style>
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropbtn {
            background-color: transparent;
            color: #374151;
            font-size: 14px;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }
        .dropbtn:hover {
            background-color: #f3f4f6;
        }
        .dropdown-content {
            display: none; 
            position: absolute;
            right: 0; 
            top: 100%;
            background-color: #ffffff;
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            z-index: 10;
            overflow: hidden;
            /* margin-top sudah dihilangkan agar menu menempel dan tidak lari-lari */
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown-content a {
            color: #374151;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: left;
            font-size: 14px;
            font-weight: 500;
            box-sizing: border-box;
            transition: background-color 0.2s;
            font-family: 'Poppins', sans-serif;
        }
        .dropdown-content a:hover {
            background-color: #f9fafb;
        }
        .text-green { color: #059669 !important; }
        .text-red { color: #dc2626 !important; }
    </style>
</head>
<body style="background-color: #f3f4f6; margin: 0; padding: 20px;">

<!-- === Navigasi === -->
<div style="max-width: 1000px; margin: 0 auto 25px auto; display: flex; justify-content: space-between; align-items: center; background-color: #ffffff; padding: 15px 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); box-sizing: border-box;">
    <div style="display: flex; gap: 20px;">
        <a href="dashboard.php" style="font-family: 'Poppins', sans-serif; font-size: 16px; text-decoration: none; color: #6b7280; font-weight: 500;">
            Dashboard
        </a>
        <a href="index.php" style="font-family: 'Poppins', sans-serif; font-size: 16px; text-decoration: none; color: #2563eb; font-weight: 600;">
            Daftar Produk
        </a>
        <a href="transaction.php" style="font-family: 'Poppins', sans-serif; font-size: 16px; text-decoration: none; color: #6b7280; font-weight: 500;">
            Transaksi Barang
        </a>
    </div>
    
    <!-- === MENU DROPDOWN PROFILE (Diperbarui di sini) === -->
    <div class="dropdown">
        <button class="dropbtn">
            <span>Halo, <b><?= htmlspecialchars($_SESSION['name'] ?? 'Pengguna') ?></b></span>
            <span style="font-size: 10px;">▼</span>
        </button>
        <div class="dropdown-content">
            <a href="change_password.php" class="text-green">Ganti Password</a>
            <a href="logout.php" class="text-red" onclick="return confirm('Apakah Anda yakin ingin logout?');">Logout</a>
        </div>
    </div>
</div> 

<!-- === Kontainer Utama === -->
<div style="max-width: 1000px; margin: 0 auto; box-sizing: border-box;">

    <header class="page-header" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <h1 style="margin: 0; color: #1f2937;">Product Manager</h1>
        <a class="btn btn-primary" href="create.php">+ Tambah Produk</a>
    </header>

    <?php if ($flashMessage): ?>
        <div class="alert alert-success" style="margin-bottom: 20px;"><?= e($flashMessage) ?></div>
    <?php endif; ?>

    <form class="search-form" method="GET" action="index.php" style="margin-bottom: 30px;">
        <input
            type="text"
            name="q"
            placeholder="Cari nama atau kategori..."
            value="<?= e($q) ?>"
        >
        <button type="submit" class="btn btn-secondary">Cari</button>
        <a class="btn btn-ghost" href="index.php">&#8635; Refresh</a>
    </form>

    <?php if (empty($products)): ?>
        <p class="empty-state">Belum ada produk yang cocok / tersimpan.</p>
    <?php else: ?>
        <div class="products">
            <?php foreach ($products as $p): ?>
                <div class="card">
                    
                    <!-- Menampilkan Gambar Produk -->
                    <?php if (!empty($p['image'])): ?>
                        <div style="width: 100%; height: 160px; overflow: hidden; border-radius: 8px; margin-bottom: 15px; border: 1px solid #f3f4f6;">
                            <img src="uploads/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div style="width: 100%; height: 160px; background-color: #f9fafb; border-radius: 8px; margin-bottom: 15px; border: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 14px; font-weight: 500;">
                            Tanpa Gambar
                        </div>
                    <?php endif; ?>

                    <h3 class="card-title"><?= e($p['name']) ?></h3>
                    <p class="card-category"><?= e($p['category']) ?></p>
                    <p class="card-price"><?= rupiah($p['price']) ?></p>
                    
                    <?php if ((int) $p['stock'] === 0): ?>
                        <p class="badge badge-out" style="color: #dc2626; font-weight: bold;">Stok Habis</p>
                    <?php elseif ((int) $p['stock'] <= 5): ?>
                        <p class="badge badge-low" style="color: #d97706; font-weight: bold;">Stok Menipis: <?= (int) $p['stock'] ?></p>
                    <?php else: ?>
                        <p class="card-stock" style="color: #059669; font-weight: bold;">Stok: <?= (int) $p['stock'] ?></p>
                    <?php endif; ?>

                    <div class="actions" style="margin-top: 15px; display: flex; gap: 10px;">
                        <a class="btn btn-secondary" href="edit.php?id=<?= (int) $p['id'] ?>">Edit</a>

                        <!-- === TAMBAHAN 3: Logika Role Admin === -->
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <form method="POST" action="delete.php" onsubmit="return confirm('Hapus produk ini?');" style="margin: 0;">
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="btn btn-danger">Hapus</button>
                            </form>
                        <?php endif; ?>
                        <!-- ===================================== -->
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>