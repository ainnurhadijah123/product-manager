<?php
/**
 * public/delete.php
 * -----------------------------------------------------------
 * DELETE - Memproses penghapusan data produk beserta file gambarnya.
 */

session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Validasi token keamanan (CSRF)
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Token keamanan tidak valid, silakan muat ulang halaman.');
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($id) {
        // 2. Ambil data produk untuk mengetahui nama file gambarnya
        $stmt = $pdo->prepare('SELECT image FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        if ($product) {
            // 3. Hapus file fisik gambar dari folder uploads/ jika ada
            if (!empty($product['image'])) {
                $imagePath = __DIR__ . '/uploads/' . $product['image'];
                
                // Cek apakah file benar-benar ada di folder, lalu hapus (unlink)
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // 4. Hapus data dari database MySQL
            $delStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
            $delStmt->execute(['id' => $id]);
        }
    }

    // 5. Kembali ke halaman utama dengan pesan sukses
    header('Location: index.php?status=deleted');
    exit;
} else {
    // Jika file ini diakses langsung lewat URL (GET), kembalikan ke index
    header('Location: index.php');
    exit;
}