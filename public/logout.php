<?php
session_start();
session_destroy(); // Hapus semua data sesi
header('Location: login.php'); // Kembalikan ke halaman login
exit;