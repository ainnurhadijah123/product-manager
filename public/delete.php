<?php
/**
 * public/delete.php
 * -----------------------------------------------------------
 * DELETE - operasi destruktif, sehingga:
 * 1) Hanya menerima method POST (tidak boleh lewat link GET).
 * 2) Wajib disertai CSRF token yang valid.
 * 3) Diakhiri dengan redirect (PRG) ke index.php.
 */

session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method tidak diizinkan.');
}

// Verifikasi CSRF token sebelum melakukan aksi apa pun
if (!csrf_verify($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Token keamanan tidak valid, silakan muat ulang halaman.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null) {
    http_response_code(400);
    exit('ID produk tidak valid.');
}

$stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);

header('Location: index.php?status=deleted');
exit;
