<?php
session_start();
require __DIR__ . '/../config/db.php';

// Kunci halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Ambil data password user saat ini dari database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Validasi
    if (!password_verify($old_password, $user['password'])) {
        $msg = "Password lama salah!";
        $msgType = "error";
    } elseif ($new_password !== $confirm_password) {
        $msg = "Password baru dan konfirmasi tidak cocok!";
        $msgType = "error";
    } elseif (strlen($new_password) < 6) {
        $msg = "Password baru minimal 6 karakter!";
        $msgType = "error";
    } else {
        // Hash password baru dan simpan ke database
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$hashed_password, $_SESSION['user_id']]);
        
        $msg = "Password berhasil diubah!";
        $msgType = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ganti Password - Product Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px; }
        .card { max-width: 400px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px; }
        .alert-error { background-color: #fee2e2; color: #991b1b; }
        .alert-success { background-color: #d1fae5; color: #065f46; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-size: 14px; color: #374151; font-weight: 500; }
        input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        .btn-group { display: flex; gap: 10px; margin-top: 25px; }
        .btn { flex: 1; padding: 10px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; text-align: center; text-decoration: none; }
        .btn-primary { background-color: #2563eb; color: white; }
        .btn-secondary { background-color: #e5e7eb; color: #374151; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="margin-top: 0; color: #1f2937;">Ganti Password</h2>
        
        <?php if ($msg): ?>
            <div class="alert <?= $msgType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Password Lama</label>
                <input type="password" name="old_password" required>
            </div>
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>
            <div class="btn-group">
                <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</body>
</html>