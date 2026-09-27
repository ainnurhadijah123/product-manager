<?php
/**
 * public/edit.php
 * -----------------------------------------------------------
 * READ one - menampilkan data produk berdasarkan ID untuk diedit.
 * UPDATE   - memproses perubahan data dengan prepared statement.
 */

session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

$errors = [];

// ---------- Ambil ID dari query string (GET) ----------
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Saat form di-submit, ID diambil dari hidden input POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if ($id === false || $id === null) {
    http_response_code(400);
    exit('ID produk tidak valid.');
}

// ---------- Ambil data produk yang akan diedit ----------
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}

$old = $product; // nilai yang ditampilkan di form (default: data lama)

// ---------- Proses UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check terlebih dahulu untuk aksi yang mengubah data
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Token keamanan tidak valid, silakan muat ulang halaman.');
    }

    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock    = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    $old = [
        'id'       => $id,
        'name'     => $name,
        'category' => $category,
        'price'    => $_POST['price'] ?? '',
        'stock'    => $_POST['stock'] ?? '',
    ];

    if ($category === '') {
        $category = 'Umum';
    }

    if (mb_strlen($name) < 3) {
        $errors['name'] = 'Nama minimal 3 karakter.';
    }
    if ($price === false || $price <= 0) {
        $errors['price'] = 'Harga harus lebih besar dari 0.';
    }
    if ($stock === false || $stock < 0) {
        $errors['stock'] = 'Stok tidak boleh negatif.';
    }

    // Nama unik, tapi abaikan baris milik produk ini sendiri
    if (empty($errors['name'])) {
        $cek = $pdo->prepare('SELECT id FROM products WHERE name = :name AND id != :id');
        $cek->execute(['name' => $name, 'id' => $id]);
        if ($cek->fetch()) {
            $errors['name'] = 'Nama produk sudah digunakan, gunakan nama lain.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET name = :name, category = :category, price = :price, stock = :stock
                 WHERE id = :id'
            );
            $stmt->execute([
                'name'     => $name,
                'category' => $category,
                'price'    => $price,
                'stock'    => $stock,
                'id'       => $id,
            ]);

            header('Location: index.php?status=updated');
            exit; // PRG: hentikan eksekusi setelah redirect
        } catch (PDOException $ex) {
            $errors['name'] = 'Nama produk sudah digunakan, gunakan nama lain.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Produk - Product Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container container-narrow">

    <header class="page-header">
        <h1>Edit Produk</h1>
        <a class="btn btn-ghost" href="index.php">&larr; Kembali</a>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            Periksa lagi input Anda, terjadi kesalahan pada form ini.
        </div>
    <?php endif; ?>

    <form class="form-card" method="POST" action="edit.php" novalidate>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <label for="name">Nama produk</label>
        <input id="name" name="name" minlength="3" required
               value="<?= e($old['name']) ?>">
        <?php if (!empty($errors['name'])): ?>
            <p class="field-error"><?= e($errors['name']) ?></p>
        <?php endif; ?>

        <label for="category">Kategori</label>
        <input id="category" name="category"
               value="<?= e($old['category']) ?>">

        <label for="price">Harga</label>
        <input id="price" name="price" type="number" min="1" step="0.01" required
               value="<?= e((string) $old['price']) ?>">
        <?php if (!empty($errors['price'])): ?>
            <p class="field-error"><?= e($errors['price']) ?></p>
        <?php endif; ?>

        <label for="stock">Stok</label>
        <input id="stock" name="stock" type="number" min="0" required
               value="<?= e((string) $old['stock']) ?>">
        <?php if (!empty($errors['stock'])): ?>
            <p class="field-error"><?= e($errors['stock']) ?></p>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Simpan perubahan Anda</button>
    </form>

</div>
</body>
</html>
