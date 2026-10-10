<?php
/**
 * public/edit.php
 * -----------------------------------------------------------
 * READ one - menampilkan data produk berdasarkan ID untuk diedit.
 * UPDATE   - memproses perubahan data dengan prepared statement + Upload Gambar.
 */

session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

$errors = [];

// ---------- Ambil ID dari query string (GET) ----------
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

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

$old = $product; 

// ---------- Proses UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
        'image'    => $product['image'], // Pertahankan nama gambar lama untuk form
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

    if (empty($errors['name'])) {
        $cek = $pdo->prepare('SELECT id FROM products WHERE name = :name AND id != :id');
        $cek->execute(['name' => $name, 'id' => $id]);
        if ($cek->fetch()) {
            $errors['name'] = 'Nama produk sudah digunakan, gunakan nama lain.';
        }
    }

    // ---------- Proses Upload Gambar Baru (Jika Ada) ----------
    $imageName = $product['image']; // Default: gunakan gambar lama

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['image'] = 'Terjadi kesalahan saat mengunggah gambar.';
        } else {
            if ($file['size'] > 2 * 1024 * 1024) {
                $errors['image'] = 'Ukuran gambar maksimal 2MB.';
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file['type'], $allowedTypes)) {
                $errors['image'] = 'Format gambar harus JPG, JPEG, atau PNG.';
            }

            if (empty($errors['image'])) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newImageName = uniqid('img_', true) . '.' . $ext;
                $targetPath = __DIR__ . '/uploads/' . $newImageName;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $imageName = $newImageName; // Gunakan nama gambar baru
                    
                    // Hapus gambar lama dari server (jika ada file fisiknya)
                    if (!empty($product['image'])) {
                        $oldImagePath = __DIR__ . '/uploads/' . $product['image'];
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                } else {
                    $errors['image'] = 'Gagal menyimpan gambar.';
                }
            }
        }
    }

    // ---------- Simpan Perubahan ke Database ----------
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET name = :name, category = :category, price = :price, stock = :stock, image = :image
                 WHERE id = :id'
            );
            $stmt->execute([
                'name'     => $name,
                'category' => $category,
                'price'    => $price,
                'stock'    => $stock,
                'image'    => $imageName, // Update dengan gambar baru (atau tetap yang lama)
                'id'       => $id,
            ]);

            header('Location: index.php?status=updated');
            exit; 
        } catch (PDOException $ex) {
            $errors['name'] = 'Gagal menyimpan perubahan ke database.';
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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

    <!-- WAJIB: Tambahkan enctype="multipart/form-data" -->
    <form class="form-card" method="POST" action="edit.php" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <label for="name">Nama produk</label>
        <input id="name" name="name" minlength="3" required
               value="<?= e($old['name']) ?>">
        <?php if (!empty($errors['name'])): ?>
            <p class="field-error" style="color: red; font-size: 14px;"><?= e($errors['name']) ?></p>
        <?php endif; ?>

        <label for="category">Kategori</label>
        <input id="category" name="category"
               value="<?= e($old['category']) ?>">

        <label for="price">Harga</label>
        <input id="price" name="price" type="number" min="1" step="0.01" required
               value="<?= e((string) $old['price']) ?>">
        <?php if (!empty($errors['price'])): ?>
            <p class="field-error" style="color: red; font-size: 14px;"><?= e($errors['price']) ?></p>
        <?php endif; ?>

        <label for="stock">Stok</label>
        <input id="stock" name="stock" type="number" min="0" required
               value="<?= e((string) $old['stock']) ?>">
        <?php if (!empty($errors['stock'])): ?>
            <p class="field-error" style="color: red; font-size: 14px;"><?= e($errors['stock']) ?></p>
        <?php endif; ?>

        <!-- Kolom Gambar -->
        <label for="image">Gambar Produk</label>
        
        <?php if (!empty($old['image'])): ?>
            <div style="margin-bottom: 15px;">
                <p style="font-size: 14px; color: #6b7280; margin-bottom: 5px;">Gambar saat ini:</p>
                <img src="uploads/<?= e($old['image']) ?>" alt="Gambar Produk" style="max-width: 150px; border-radius: 8px; border: 1px solid #e5e7eb;">
            </div>
        <?php endif; ?>

        <input id="image" name="image" type="file" accept="image/jpeg, image/png, image/jpg" style="padding: 10px 0;">
        <p style="font-size: 12px; color: #9ca3af; margin-top: -5px;">*Biarkan kosong jika tidak ingin mengubah gambar. Maksimal 2MB.</p>
        
        <?php if (!empty($errors['image'])): ?>
            <p class="field-error" style="color: red; font-size: 14px;"><?= e($errors['image']) ?></p>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Simpan perubahan Anda</button>
    </form>

</div>
</body>
</html>