<?php
session_start(); 

// Konfigurasi koneksi database
$host = 'localhost';
$db   = 'ulas_buku_db'; 
$user = 'root';        
$pass = '';           
$charset = 'utf8mb4';

// Data Source Name (DSN) untuk koneksi PDO
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

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$current_user_id = $_SESSION['user_id'];
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';

// Tangani pengiriman formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $genre = trim($_POST['genre'] ?? ''); 
    $bookDescription = trim($_POST['book_description'] ?? ''); 
    $physicalDescription = trim($_POST['physical_description'] ?? ''); 
    $pageCount = (int)($_POST['page_count'] ?? 0); 
    $isbn = trim($_POST['isbn'] ?? ''); 
    $rating = (int)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');

    $coverImageUrl = null;
    // Tangani unggahan file
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cover_image'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        $fileType = $file['type'];

        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); 
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExt, $allowed)) {
            if ($fileError === 0) {
                if ($fileSize < 5000000) { 
                    $fileNameNew = uniqid('', true) . "." . $fileExt;
                    $fileDestination = ROOT_PATH . '/uploads/covers/' . $fileNameNew;

                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        $coverImageUrl = 'uploads/covers/' . $fileNameNew; 
                    } else {
                        $message = '<p class="error">Gagal mengunggah gambar.</p>';
                    }
                } else {
                    $message = '<p class="error">Ukuran gambar terlalu besar (maks 5MB).</p>';
                }
            } else {
                $message = '<p class="error">Terjadi kesalahan saat mengunggah gambar.</p>';
            }
        } else {
            $message = '<p class="error">Tipe file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF.</p>';
        }
    }

    if (empty($title) || empty($author) || empty($genre) || empty($bookDescription) || empty($physicalDescription) || $pageCount <= 0 || empty($isbn) || empty($reviewText) || $rating < 1 || $rating > 5) {
        $message = '<p class="error">Harap lengkapi semua bidang wajib dan pastikan rating antara 1-5, serta jumlah halaman lebih dari 0.</p>';
    } else {
        try {
            // Perbarui query INSERT untuk menyertakan kolom baru
            $stmt = $pdo->prepare("INSERT INTO reviews (title, author, genre, book_description, physical_description, page_count, isbn, cover_image_url, rating, review_text, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $author, $genre, $bookDescription, $physicalDescription, $pageCount, $isbn, $coverImageUrl, $rating, $reviewText, $current_user_id])) {
                header('Location: dashboard.php?status=added');
                exit();
            } else {
                $message = '<p class="error">Gagal menambahkan ulasan.</p>';
            }
        } catch (\PDOException $e) {
            $message = '<p class="error">Gagal menambahkan ulasan: ' . $e->getMessage() . '</p>';
            error_log("Error adding review: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Ulasan Buku Baru - Recensio</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <img src="uploads/logo_recensio.png" alt="Recensio Logo" class="site-logo">
            </div>
            <h1 class="header-title">Recensio</h1>
            <div class="user-profile">
                <span><?php echo $username; ?></span>
                <a href="logout.php" class="button primary small-button">Logout</a>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav-section">
            <div class="nav-tabs">
                <a href="index.php" class="nav-tab">All</a>
                <a href="dashboard.php" class="nav-tab">My Reviews</a>
                <a href="add.php" class="nav-tab active">Add Review</a>
            </div>
        </nav>

        <!-- Bagian Konten untuk Formulir Tambah Ulasan -->
        <section class="content-section">
            <h1>Add Review</h1>
            <?php echo $message; ?>
            <form action="add.php" method="POST" id="review-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Judul Buku:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="author">Penulis:</label>
                    <input type="text" id="author" name="author" required>
                </div>
                <div class="form-group">
                    <label for="genre">Genre:</label>
                    <input type="text" id="genre" name="genre" required>
                </div>
                <div class="form-group">
                    <label for="book_description">Deskripsi Buku:</label>
                    <textarea id="book_description" name="book_description" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="physical_description">Deskripsi Fisik:</label>
                    <textarea id="physical_description" name="physical_description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="page_count">Jumlah Halaman:</label>
                    <input type="number" id="page_count" name="page_count" min="1" required>
                </div>
                <div class="form-group">
                    <label for="isbn">ISBN:</label>
                    <input type="text" id="isbn" name="isbn" required>
                </div>
                <div class="form-group">
                    <label for="cover_image" class="file-upload-label">Cover Buku (Opsional):</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="cover_image" name="cover_image" accept="image/jpeg, image/png, image/gif">
                        <label for="cover_image" class="custom-file-upload">Choose File</label>
                        <span id="file-name-display" class="file-name-display">No file chosen</span>
                    </div>
                    <small>Maksimal 5MB (JPG, PNG, GIF)</small>
                </div>
                <div class="form-group">
                    <label for="rating">Rating (1-5):</label>
                    <input type="number" id="rating" name="rating" min="1" max="5" required>
                </div>
                <div class="form-group">
                    <label for="review_text">Ulasan Anda:</label>
                    <textarea id="review_text" name="review_text" rows="8" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button primary form-submit-button">Simpan Ulasan</button>
                </div>
            </form>
        </section>
    </div>
    <script src="script.js"></script>
</body>
</html>
