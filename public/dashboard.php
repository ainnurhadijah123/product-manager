<?php
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

// Kunci halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 1. Ambil Total Produk
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $stmt->fetchColumn();

// 2. Ambil Total Stok Keseluruhan
$stmt = $pdo->query("SELECT SUM(stock) FROM products");
$total_stock = $stmt->fetchColumn() ?? 0;

// 3. Ambil Total Kategori
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$total_categories = $stmt->fetchColumn();

// 4. Ambil 5 Riwayat Transaksi Terakhir
$stmt = $pdo->query("
    SELECT t.type, t.quantity, t.created_at, p.name AS product_name, u.name AS user_name 
    FROM product_transactions t
    JOIN products p ON t.product_id = p.id
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
$recent_transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Product Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Gaya khusus untuk menyamakan kotak */
        .stat-box {
            flex: 1; 
            background-color: #ffffff; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
            padding: 30px; 
            text-align: center; 
            box-sizing: border-box;
        }
        .table-box {
            background-color: #ffffff; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
            padding: 25px; 
            width: 100%; 
            box-sizing: border-box;
        }
    </style>
</head>
<body style="background-color: #f3f4f6; margin: 0; padding: 20px;">

<!-- Navigasi -->
<div style="max-width: 1000px; margin: 0 auto 25px auto; display: flex; justify-content: space-between; align-items: center; background-color: #ffffff; padding: 15px 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); box-sizing: border-box;">
    <div style="display: flex; gap: 20px;">
        <a href="dashboard.php" style="font-family: 'Poppins', sans-serif; font-size: 16px; text-decoration: none; color: #2563eb; font-weight: 600;">Dashboard</a>
        <a href="index.php" style="font-family: 'Poppins', sans-serif; font-size: 16px; text-decoration: none; color: #6b7280; font-weight: 500;">Daftar Produk</a>
        <a href="transaction.php" style="font-family: 'Poppins', sans-serif; font-size: 16px; text-decoration: none; color: #6b7280; font-weight: 500;">Transaksi Barang</a>
    </div>
    <div style="font-family: 'Poppins', sans-serif; font-size: 14px; color: #374151;">
        Halo, <b><?= htmlspecialchars($_SESSION['name']) ?></b> | 
        <a href="change_password.php" style="color: #059669; text-decoration: none; font-weight: 600; margin-left: 5px; margin-right: 5px;">Ganti Password</a> |
        <a href="logout.php" style="color: #dc2626; text-decoration: none; font-weight: 600; margin-left: 5px;">Logout</a>
    </div>
</div>

<!-- Kontainer Utama -->
<div style="max-width: 1000px; margin: 0 auto; box-sizing: border-box;">
    <header style="margin-bottom: 25px;">
        <h1 style="margin: 0; color: #1f2937;">Dashboard Ringkasan</h1>
    </header>

    <!-- Kartu Statistik -->
    <div style="display: flex; gap: 20px; margin-bottom: 25px; width: 100%;">
        <div class="stat-box">
            <h3 style="color: #6b7280; font-size: 16px; margin-bottom: 10px; margin-top: 0;">Total Produk</h3>
            <p style="font-size: 36px; font-weight: 700; color: #2563eb; margin: 0;"><?= $total_products ?></p>
        </div>
        <div class="stat-box">
            <h3 style="color: #6b7280; font-size: 16px; margin-bottom: 10px; margin-top: 0;">Total Stok Fisik</h3>
            <p style="font-size: 36px; font-weight: 700; color: #059669; margin: 0;"><?= $total_stock ?></p>
        </div>
        <div class="stat-box">
            <h3 style="color: #6b7280; font-size: 16px; margin-bottom: 10px; margin-top: 0;">Kategori</h3>
            <p style="font-size: 36px; font-weight: 700; color: #d97706; margin: 0;"><?= $total_categories ?></p>
        </div>
    </div>

    <!-- Tabel Riwayat Transaksi -->
    <div class="table-box">
        <h3 style="margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; color: #1f2937;">5 Transaksi Terakhir</h3>
        <?php if (empty($recent_transactions)): ?>
            <p style="color: #6b7280; margin: 0;">Belum ada riwayat transaksi.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; min-width: 600px; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb; color: #4b5563;">
                            <th style="padding: 12px 15px; width: 20%;">Waktu</th>
                            <th style="padding: 12px 15px; width: 30%;">Produk</th>
                            <th style="padding: 12px 15px; width: 15%;">Jenis</th>
                            <th style="padding: 12px 15px; width: 15%;">Jumlah</th>
                            <th style="padding: 12px 15px; width: 20%;">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $t): ?>
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 12px 15px; color: #6b7280; font-size: 14px;"><?= date('d M Y H:i', strtotime($t['created_at'])) ?></td>
                                <td style="padding: 12px 15px; font-weight: 500; color: #1f2937;"><?= htmlspecialchars($t['product_name']) ?></td>
                                <td style="padding: 12px 15px;">
                                    <?php if ($t['type'] === 'in'): ?>
                                        <span style="background-color: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; white-space: nowrap;">MASUK</span>
                                    <?php else: ?>
                                        <span style="background-color: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; white-space: nowrap;">KELUAR</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 15px; font-weight: bold; color: #1f2937;"><?= $t['quantity'] ?></td>
                                <td style="padding: 12px 15px; color: #6b7280; font-size: 14px;"><?= htmlspecialchars($t['user_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>