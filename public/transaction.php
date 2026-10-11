<?php
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

// Pastikan hanya yang sudah login yang bisa mengakses
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success_msg = '';

// Mengambil daftar produk untuk ditampilkan di dropdown pilihan
$stmt = $pdo->query("SELECT id, name, stock FROM products ORDER BY name ASC");
$products = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $type = $_POST['type'] ?? '';
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $notes = trim($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id']; // Mengambil ID user yang sedang login

    if (!$product_id) $errors[] = "Silakan pilih produk.";
    if (!in_array($type, ['in', 'out'])) $errors[] = "Tipe transaksi tidak valid.";
    if (!$quantity || $quantity <= 0) $errors[] = "Jumlah barang harus lebih dari 0.";

    if (empty($errors)) {
        try {
            // === MULAI DATABASE TRANSACTION ===
            $pdo->beginTransaction();

            // 1. Cek stok saat ini terlebih dahulu
            $stmtCheck = $pdo->prepare("SELECT stock, name FROM products WHERE id = ? FOR UPDATE");
            $stmtCheck->execute([$product_id]);
            $product = $stmtCheck->fetch();

            if (!$product) {
                throw new Exception("Produk tidak ditemukan di database.");
            }

            $current_stock = (int) $product['stock'];
            $new_stock = $current_stock;

            // Logika Barang Masuk / Keluar
            if ($type === 'in') {
                $new_stock += $quantity;
            } else if ($type === 'out') {
                if ($quantity > $current_stock) {
                    throw new Exception("Gagal! Stok '{$product['name']}' tidak mencukupi. (Stok saat ini: $current_stock)");
                }
                $new_stock -= $quantity;
            }

            // 2. Update stok di tabel products
            $stmtUpdate = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmtUpdate->execute([$new_stock, $product_id]);

            // 3. Catat riwayat transaksi di tabel product_transactions
            $stmtInsert = $pdo->prepare("INSERT INTO product_transactions (product_id, user_id, type, quantity, notes) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([$product_id, $user_id, $type, $quantity, $notes]);

            // === JIKA SEMUA SUKSES, SIMPAN PERMANEN (COMMIT) ===
            $pdo->commit();
            $success_msg = "Berhasil! Transaksi dicatat dan stok telah diperbarui.";
            
        } catch (Exception $e) {
            // === JIKA ADA ERROR (MISAL STOK KURANG), BATALKAN SEMUA (ROLLBACK) ===
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Barang - Product Manager</title>
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
            /* margin-top: 5px; <- INI YANG DIHAPUS AGAR TIDAK KABUR */
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
        <a href="dashboard.php" style="font-family: 'Poppins', sans-serif; font-size: 16px; text-decoration: none; color: #6b7280; font-weight: 500;">Dashboard</a>
        <a href="index.php" style="font-family: 'Poppins', sans-serif; font-size: 16px; text-decoration: none; color: #6b7280; font-weight: 500;">Daftar Produk</a>
        <a href="transaction.php" style="font-family: 'Poppins', sans-serif; font-size: 16px; text-decoration: none; color: #2563eb; font-weight: 600;">Transaksi Barang</a>
    </div>
    
    <!-- === MENU DROPDOWN PROFILE === -->
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
    <header style="margin-bottom: 25px; text-align: center;">
        <h1 style="margin: 0; color: #1f2937;">Transaksi Barang</h1>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="max-width: 600px; margin: 0 auto 20px auto;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div class="alert alert-success" style="max-width: 600px; margin: 0 auto 20px auto;"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <!-- Kotak Formulir -->
    <div class="form-card" style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <form method="POST" action="transaction.php">
            
            <div style="margin-bottom: 15px;">
                <label for="product_id" style="display: block; margin-bottom: 5px; font-weight: 500;">Pilih Produk</label>
                <select id="product_id" name="product_id" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; background-color: #fff;">
                    <option value="">-- Pilih Produk --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['name']) ?> (Stok saat ini: <?= $p['stock'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="type" style="display: block; margin-bottom: 5px; font-weight: 500;">Jenis Transaksi</label>
                <select id="type" name="type" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; background-color: #fff;">
                    <option value="in">Barang Masuk (Tambah Stok)</option>
                    <option value="out">Barang Keluar (Kurangi Stok)</option>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="quantity" style="display: block; margin-bottom: 5px; font-weight: 500;">Jumlah (Qty)</label>
                <input type="number" id="quantity" name="quantity" min="1" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            
            <div style="margin-bottom: 25px;">
                <label for="notes" style="display: block; margin-bottom: 5px; font-weight: 500;">Catatan (Opsional)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Misal: Retur dari supplier, Penjualan kasir..." style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px;">Proses Transaksi</button>
        </form>
    </div>
</div>
</body>
</html>