<?php
session_start(); // Mulai sesi

// Konfigurasi koneksi database
$host = 'localhost';
$db   = 'ulas_buku_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Koneksi database gagal: " . $e->getMessage());
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = '<p class="error">Semua bidang harus diisi.</p>';
    } elseif ($password !== $confirm_password) {
        $message = '<p class="error">Konfirmasi password tidak cocok.</p>';
    } elseif (strlen($password) < 6) {
        $message = '<p class="error">Password minimal 6 karakter.</p>';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $message = '<p class="error">Username sudah digunakan. Silakan pilih username lain.</p>';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                if ($stmt->execute([$username, $password_hash])) {
                    $message = '<p class="success">Pendaftaran berhasil! Silakan <a href="login.php">login</a>.</p>';
                } else {
                    $message = '<p class="error">Terjadi kesalahan saat mendaftar.</p>';
                }
            }
        } catch (\PDOException $e) {
            $message = '<p class="error">Terjadi kesalahan saat mendaftar: ' . $e->getMessage() . '</p>';
            error_log("Error during registration: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru - Recensio</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page-body"> <!-- Menggunakan body auth-page-body untuk background gradasi hijau -->
    <div class="auth-form">
        <h1>Daftar Akun Baru</h1>
        <?php echo $message; ?>
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required>
                    <span class="password-toggle" data-target="password"></span>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password:</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span class="password-toggle" data-target="confirm_password"></span>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="button primary">Daftar</button>
            </div>
        </form>
        <p style="text-align: center; margin-top: 20px; color: #C8E6C9;">Sudah punya akun? <a href="login.php" style="color: #FFFFFF; text-decoration: underline;">Login di sini</a></p>
        <p style="text-align: center; margin-top: 10px;"><a href="index.php" style="color: #C8E6C9; text-decoration: underline;">Kembali ke Beranda</a></p>
    </div>
    <script src="script.js"></script>
</body>
</html>
