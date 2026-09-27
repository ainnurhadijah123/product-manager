<?php
/**
 * public/create.php
 * -----------------------------------------------------------
 * CREATE - Menampilkan form (GET) dan memproses penyimpanan (POST).
 * Alur: ambil input -> normalisasi -> validasi -> prepared statement
 *       -> redirect (PRG) supaya refresh tidak mengirim ulang data.
 */

session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

$errors = [];

// Nilai default untuk mengisi ulang form jika validasi gagal
$old = ['name' => '', 'category' => '', 'price' => '', 'stock' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Ambil input dengan nilai default
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock    = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    // Simpan kembali untuk mengisi ulang form bila terjadi error
    $old = [
        'name'     => $name,
        'category' => $category,
        'price'    => $_POST['price'] ?? '',
        'stock'    => $_POST['stock'] ?? '',
    ];

    // 2) Normalisasi sudah dilakukan lewat trim() di atas.
    if ($category === '') {
        $category = 'Umum'; // sama dengan default kolom di database
    }

    // 3) Validasi aturan bisnis
    if (mb_strlen($name) < 3) {
        $errors['name'] = 'Nama minimal 3 karakter.';
    }
    if ($price === false || $price <= 0) {
        $errors['price'] = 'Harga harus lebih besar dari 0.';
    }
    if ($stock === false || $stock < 0) {
        $errors['stock'] = 'Stok tidak boleh negatif.';
    }

    // Validasi nama unik (selain constraint UNIQUE di database)
    if (empty($errors['name'])) {
        $cek = $pdo->prepare('SELECT id FROM products WHERE name = :name');
        $cek->execute(['name' => $name]);
        if ($cek->fetch()) {
            $errors['name'] = 'Nama produk sudah digunakan, gunakan nama lain.';
        }
    }

    // 4) Jika lolos, jalankan proses database lalu redirect (PRG)
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, category, price, stock)
                 VALUES (:name, :category, :price, :stock)'
            );
            $stmt->execute([
                'name'     => $name,
                'category' => $category,
                'price'    => $price,
                'stock'    => $stock,
            ]);

            header('Location: index.php?status=created');
            exit; // WAJIB: hentikan eksekusi setelah redirect
        } catch (PDOException $ex) {
            // Jaga-jaga jika constraint UNIQUE database yang menangkap duplikat
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
<title>Tambah Produk - Product Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container container-narrow">

    <header class="page-header">
        <h1>Tambah Produk</h1>
        <a class="btn btn-ghost" href="index.php">&larr; Kembali</a>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            Periksa kembali input Anda, terdapat kesalahan pada form.
        </div>
    <?php endif; ?>

    <form class="form-card" method="POST" action="create.php" novalidate>

        <label for="name">Nama produk</label>
        <input id="name" name="name" minlength="3" required
               value="<?= e($old['name']) ?>">
        <?php if (!empty($errors['name'])): ?>
            <p class="field-error"><?= e($errors['name']) ?></p>
        <?php endif; ?>

        <label for="category">Kategori</label>
        <input id="category" name="category" placeholder="Umum"
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

        <button type="submit" class="btn btn-primary">Simpan produk</button>
    </form>

</div>
</body>
</html>
