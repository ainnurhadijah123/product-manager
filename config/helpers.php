<?php
/**
 * config/helpers.php
 * -----------------------------------------------------------
 * Kumpulan fungsi bantu kecil yang dipakai berulang di halaman lain,
 * supaya create.php, edit.php, dan delete.php tidak duplikasi kode.
 */

/**
 * Ambil (atau buat baru) CSRF token yang disimpan di session.
 * Token ini dipasang sebagai hidden input pada setiap form yang
 * mengubah data (create, update, delete).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Verifikasi token yang dikirim form terhadap token di session.
 * hash_equals() dipakai agar perbandingan tahan terhadap timing attack.
 */
function csrf_verify(?string $token): bool
{
    return hash_equals($_SESSION['csrf'] ?? '', $token ?? '');
}

/**
 * Shortcut aman untuk mencetak string ke konteks HTML (mencegah XSS).
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka menjadi format Rupiah: Rp 1.234.567
 */
function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}
