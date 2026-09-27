<?php
/**
 * config/db.php
 * -----------------------------------------------------------
 * Koneksi PDO ke database store_db.
 * - ERRMODE_EXCEPTION  : error query akan melempar exception (mudah di-debug)
 * - FETCH_ASSOC        : hasil fetch berupa associative array
 * - EMULATE_PREPARES   : false -> gunakan prepared statement asli dari MySQL
 *                         (lebih aman terhadap SQL Injection)
 * -----------------------------------------------------------
 * Sesuaikan host/user/password sesuai environment Anda
 * (default XAMPP/Laragon: user root, password kosong).
 */

$host   = 'localhost';
$dbname = 'store_db';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Jangan tampilkan detail error database ke pengguna akhir di production.
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
