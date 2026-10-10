<?php
session_start();
require __DIR__ . '/../config/db.php';

// Jika pengguna sudah login, langsung arahkan ke halaman utama (index)
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Proses saat tombol Masuk diklik
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // Cari user berdasarkan username
        $stmt = $pdo->prepare('SELECT id, name, username, password, role FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // Cek apakah user ada dan passwordnya cocok (menggunakan password_verify)
        if ($user && password_verify($password, $user['password'])) {
            // Berhasil login! Simpan data user ke dalam Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Ini akan berisi 'admin' atau 'staff'

            // Arahkan ke halaman utama
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Harap isi username dan password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Product Manager</title>
    <!-- Memanggil file CSS yang sama agar rapi -->
    <link rel="stylesheet" href="assets/style.css"> 
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f3f4f6;">
    <div class="form-card" style="width: 100%; max-width: 400px; padding: 40px;">
        <h2 style="text-align: center; margin-bottom: 20px;">Login Sistem</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required placeholder="admin / staff">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="rahasia123">

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 25px; padding: 12px;">Masuk</button>
        </form>
    </div>
</body>
</html>