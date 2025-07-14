<?php
session_start(); // Start session

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

// Path root proyek untuk upload gambar
define('ROOT_PATH', __DIR__);


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID ulasan tidak valid.");
}

$id = (int)$_GET['id'];
$current_user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT user_id, cover_image_url FROM reviews WHERE id = ?");
    $stmt->execute([$id]);
    $review_to_delete = $stmt->fetch();

    if (!$review_to_delete || $review_to_delete['user_id'] != $current_user_id) {
        die("Ulasan tidak ditemukan atau Anda tidak memiliki izin untuk menghapus ulasan ini.");
    }

    if ($review_to_delete['cover_image_url'] && file_exists(ROOT_PATH . '/' . $review_to_delete['cover_image_url'])) {
        unlink(ROOT_PATH . '/' . $review_to_delete['cover_image_url']);
    }

    
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, $current_user_id])) {
        header('Location: dashboard.php?status=deleted');
        exit();
    } else {
        die("Gagal menghapus ulasan.");
    }
} catch (\PDOException $e) {
    error_log("Error deleting review: " . $e->getMessage());
    die("Gagal menghapus ulasan: " . $e->getMessage());
}
?>
