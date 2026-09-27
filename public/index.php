<?php
/**
 * public/index.php
 * -----------------------------------------------------------
 * READ - Menampilkan daftar produk dalam bentuk card responsif (Flexbox).
 * Bonus: pencarian/filter produk memakai method GET (?q=...).
 * Juga menampilkan pesan status hasil PRG dari create/edit/delete.
 */

session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

// ---------- Bonus: Search & Filter (GET) ----------
// Input GET tetap divalidasi/dinormalisasi dan tetap memakai
// prepared statement, bukan digabung langsung ke string SQL.
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    $stmt = $pdo->query(
        "SELECT id, name, category, price, stock
         FROM products ORDER BY id DESC"
    );
    $products = $stmt->fetchAll();
} else {
    // Catatan: PDO::ATTR_EMULATE_PREPARES = false (di config/db.php) berarti
    // prepared statement asli dari MySQL dipakai, dan MySQL TIDAK mengizinkan
    // satu nama parameter (mis. :q) dipakai lebih dari sekali dalam satu query.
    // Solusinya: pakai dua placeholder berbeda (:q1 dan :q2) dengan nilai sama.
    $stmt = $pdo->prepare(
        "SELECT id, name, category, price, stock
         FROM products
         WHERE name LIKE :q1 OR category LIKE :q2
         ORDER BY id DESC"
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
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

    <header class="page-header">
        <h1>Product Manager</h1>
        <a class="btn btn-primary" href="create.php">+ Tambah Produk</a>
    </header>
<div>
    <navbar class="navbar">
        <h4>Aplikasi Manajemen Produk</h4>
    </navbar>
</div>
    <?php if ($flashMessage): ?>
        <div class="alert alert-success"><?= e($flashMessage) ?></div>
    <?php endif; ?>

    <form class="search-form" method="GET" action="index.php">
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
                    <h3 class="card-title"><?= e($p['name']) ?></h3>
                    <p class="card-category"><?= e($p['category']) ?></p>
                    <p class="card-price"><?= rupiah($p['price']) ?></p>
                    

                    <?php if ((int) $p['stock'] === 0): ?>
        <p class="badge badge-out">Stok Habis</p>
    <?php elseif ((int) $p['stock'] <= 5): ?>
        <p class="badge badge-low">Stok Menipis: <?= (int) $p['stock'] ?></p>
    <?php else: ?>
        <p class="card-stock">Stok: <?= (int) $p['stock'] ?></p>
    <?php endif; ?>
                    <div class="actions">
                        <a class="btn btn-secondary"
                           href="edit.php?id=<?= (int) $p['id'] ?>">Edit</a>

                        <form method="POST" action="delete.php"
                              onsubmit="return confirm('Hapus produk ini?');">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <button type="submit" class="btn btn-danger">Hapus</button>
                        </form>
                    </div>
                    
                </div>
                
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>