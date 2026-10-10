<?php
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

// Kunci halaman agar hanya bisa diakses jika sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$name = '';
$category_input = '';
$price = '';
$stock = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_input = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');

    if (empty($name)) $errors['name'] = 'Nama produk wajib diisi.';
    if (empty($category_input)) $errors['category'] = 'Kategori wajib diisi.';
    if ($price === '') $errors['price'] = 'Harga wajib diisi.';
    if ($stock === '') $errors['stock'] = 'Stok wajib diisi.';

    $imagePath = null;
    if (empty($errors)) {
        // Logika Upload Gambar
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['image']['tmp_name'];
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['image']['name']);
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            move_uploaded_file($tmpName, $uploadDir . $fileName);
            $imagePath = $fileName;
        }

        try {
            // 1. CARI ATAU BUAT KATEGORI BARU
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$category_input]);
            $category_id = $stmt->fetchColumn();

            // Jika belum ada, tambahkan kategori baru tersebut ke tabel
            if (!$category_id) {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$category_input]);
                $category_id = $pdo->lastInsertId();
            }

            // 2. GENERATE SKU OTOMATIS
            $sku = 'PRD-' . strtoupper(substr(md5(uniqid()), 0, 6));

            // 3. SIMPAN PRODUK KE DATABASE
            $stmt = $pdo->prepare(
                "INSERT INTO products (sku, name, category_id, price, stock, image) 
                 VALUES (:sku, :name, :category_id, :price, :stock, :image)"
            );
            $stmt->execute([
                'sku' => $sku,
                'name' => $name,
                'category_id' => $category_id,
                'price' => $price,
                'stock' => $stock,
                'image' => $imagePath
            ]);

            // Jika sukses, kembali ke index
            header('Location: index.php?status=created');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = "ERROR DATABASE: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk - Product Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container container-narrow">
        <header class="page-header">
            <h1>Tambah Produk</h1>
            <a class="btn btn-ghost" href="index.php">&larr; Kembali</a>
        </header>

        <?php if (!empty($errors) && !isset($errors['database'])): ?>
            <div class="alert alert-error">
                Periksa kembali input Anda, terdapat kesalahan pada form.
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="create.php" enctype="multipart/form-data">
                
                <?php if (isset($errors['database'])): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($errors['database']) ?></div>
                <?php endif; ?>

                <div style="margin-bottom: 15px;">
                    <label for="name" style="display: block; margin-bottom: 5px; font-weight: 500;">Nama produk</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="category" style="display: block; margin-bottom: 5px; font-weight: 500;">Kategori</label>
                    <input type="text" id="category" name="category" value="<?= htmlspecialchars($category_input) ?>" required placeholder="Contoh: Peralatan Minum" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="price" style="display: block; margin-bottom: 5px; font-weight: 500;">Harga</label>
                    <input type="number" id="price" name="price" value="<?= htmlspecialchars($price) ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="stock" style="display: block; margin-bottom: 5px; font-weight: 500;">Stok</label>
                    <input type="number" id="stock" name="stock" value="<?= htmlspecialchars($stock) ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 25px;">
                    <label for="image" style="display: block; margin-bottom: 5px; font-weight: 500;">Gambar Produk (Opsional - Maks 2MB)</label>
                    <input type="file" id="image" name="image" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; background-color: #fff;">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px;">Simpan produk</button>
            </form>
        </div>
    </div>
</body>
</html>