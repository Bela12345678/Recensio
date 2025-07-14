<?php
session_start();

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

    if (empty($username) || empty($password)) {
        $message = '<p class="error">Username dan password tidak boleh kosong.</p>';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit();
            } else {
                $message = '<p class="error">Username atau password salah.</p>';
            }
        } catch (\PDOException $e) {
            $message = '<p class="error">Terjadi kesalahan saat login: ' . $e->getMessage() . '</p>';
            error_log("Error during login: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Recensio</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page-body">
    <div class="auth-form">
        <h1>Login ke Recensio</h1>
        <?php echo $message; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="button primary">Login</button>
            </div>
        </form>
        <p style="text-align: center; margin-top: 20px; color: #C8E6C9;">Belum punya akun? <a href="register.php" style="color: #FFFFFF; text-decoration: underline;">Daftar di sini</a></p>
        <p style="text-align: center; margin-top: 10px;"><a href="index.php" style="color: #C8E6C9; text-decoration: underline;">Kembali ke Beranda</a></p>
    </div>
    <script src="script.js"></script>
</body>
</html>
