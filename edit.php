<?php
session_start(); // Mulai sesi

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

$review = null;
$message = '';
$current_user_id = $_SESSION['user_id'];
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';

// Pastikan ID ulasan diberikan di URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID ulasan tidak valid.");
}

$id = (int)$_GET['id'];

// Ambil data ulasan yang akan diedit dan verifikasi kepemilikan
try {
    // Perbarui query SELECT untuk menyertakan kolom baru
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $current_user_id]);
    $review = $stmt->fetch();

    if (!$review) {
        die("Ulasan tidak ditemukan atau Anda tidak memiliki izin untuk mengedit ulasan ini.");
    }
} catch (\PDOException $e) {
    error_log("Error fetching review for edit: " . $e->getMessage());
    die("Gagal mengambil data ulasan: " . $e->getMessage());
}

// Tangani pengiriman formulir untuk pembaruan
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

    $coverImageUrl = $review['cover_image_url']; 

    // Tangani unggahan file untuk pembaruan
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

                    // Hapus file gambar lama jika ada dan unggahan baru berhasil
                    if ($review['cover_image_url'] && file_exists(ROOT_PATH . '/' . $review['cover_image_url'])) {
                        unlink(ROOT_PATH . '/' . $review['cover_image_url']);
                    }

                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        $coverImageUrl = 'uploads/covers/' . $fileNameNew; 
                    } else {
                        $message = '<p class="error">Gagal memperbarui gambar.</p>';
                    }
                } else {
                    $message = '<p class="error">Ukuran gambar terlalu besar (maks 5MB).</p>';
                }
            } else {
                $message = '<p class="error">Terjadi kesalahan saat memperbarui gambar.</p>';
            }
        } else {
            $message = '<p class="error">Tipe file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF.</p>';
        }
    } elseif (isset($_POST['remove_cover_image']) && $_POST['remove_cover_image'] == '1') {
        // Logika untuk menghapus gambar yang sudah ada
        if ($review['cover_image_url'] && file_exists(ROOT_PATH . '/' . $review['cover_image_url'])) {
            unlink(ROOT_PATH . '/' . $review['cover_image_url']);
        }
        $coverImageUrl = null; 
    }

    // Validasi yang diperbarui
    if (empty($title) || empty($author) || empty($genre) || empty($bookDescription) || empty($physicalDescription) || $pageCount <= 0 || empty($isbn) || empty($reviewText) || $rating < 1 || $rating > 5) {
        $message = '<p class="error">Harap lengkapi semua bidang wajib dan pastikan rating antara 1-5, serta jumlah halaman lebih dari 0.</p>';
    } else {
        try {
            // Perbarui query UPDATE untuk menyertakan kolom baru
            $stmt = $pdo->prepare("UPDATE reviews SET title = ?, author = ?, genre = ?, book_description = ?, physical_description = ?, page_count = ?, isbn = ?, cover_image_url = ?, rating = ?, review_text = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$title, $author, $genre, $bookDescription, $physicalDescription, $pageCount, $isbn, $coverImageUrl, $rating, $reviewText, $id, $current_user_id])) {
                header('Location: dashboard.php?status=updated');
                exit();
            } else {
                $message = '<p class="error">Gagal memperbarui ulasan.</p>';
            }
        } catch (\PDOException $e) {
            $message = '<p class="error">Gagal memperbarui ulasan: ' . $e->getMessage() . '</p>';
            error_log("Error updating review: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ulasan Buku - Recensio</title>
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
                <a href="dashboard.php" class="nav-tab">Reading</a>
                <a href="#" class="nav-tab">Completed</a>
                <a href="#" class="nav-tab">Wishlist</a>
                <a href="dashboard.php" class="nav-tab">My Reviews</a>
                <a href="add.php" class="nav-tab active">Add Review</a>
            </div>
            <div class="search-bar">
                <input type="text" class="search-input" id="search-input" name="search" placeholder="Search books...">
                <div id="search-suggestions" class="search-suggestions"></div>
            </div>
        </nav>

        <!-- Bagian Konten untuk Formulir Edit Ulasan -->
        <section class="content-section">
            <h1>Edit Review</h1>
            <?php echo $message; ?>
            <form action="edit.php?id=<?php echo htmlspecialchars($id); ?>" method="POST" id="review-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Judul Buku:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($review['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="author">Penulis:</label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($review['author']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="genre">Genre:</label>
                    <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($review['genre']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="book_description">Deskripsi Buku:</label>
                    <textarea id="book_description" name="book_description" rows="5" required><?php echo htmlspecialchars($review['book_description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="physical_description">Deskripsi Fisik:</label>
                    <textarea id="physical_description" name="physical_description" rows="3" required><?php echo htmlspecialchars($review['physical_description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="page_count">Jumlah Halaman:</label>
                    <input type="number" id="page_count" name="page_count" min="1" value="<?php echo htmlspecialchars($review['page_count']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="isbn">ISBN:</label>
                    <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($review['isbn']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="current_cover">Cover Saat Ini:</label>
                    <?php if ($review['cover_image_url']): ?>
                        <img src="<?php echo htmlspecialchars($review['cover_image_url']); ?>" alt="Cover Buku" style="max-width: 150px; height: auto; display: block; margin-bottom: 10px; border-radius: 8px;">
                        <input type="checkbox" id="remove_cover_image" name="remove_cover_image" value="1">
                        <label for="remove_cover_image">Hapus Cover Saat Ini</label>
                        <br>
                    <?php else: ?>
                        <p>Tidak ada cover saat ini.</p>
                    <?php endif; ?>
                    <label for="cover_image" class="file-upload-label">Ganti Cover Buku (Opsional):</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="cover_image" name="cover_image" accept="image/jpeg, image/png, image/gif">
                        <label for="cover_image" class="custom-file-upload">Choose File</label>
                        <span id="file-name-display" class="file-name-display"><?php echo $review['cover_image_url'] ? basename($review['cover_image_url']) : 'No file chosen'; ?></span>
                    </div>
                    <small>Maksimal 5MB (JPG, PNG, GIF)</small>
                </div>
                <div class="form-group">
                    <label for="rating">Rating (1-5):</label>
                    <input type="number" id="rating" name="rating" min="1" max="5" value="<?php echo htmlspecialchars($review['rating']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="review_text">Ulasan Anda:</label>
                    <textarea id="review_text" name="review_text" rows="8" required><?php echo htmlspecialchars($review['review_text']); ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button primary form-submit-button">Perbarui Ulasan</button>
                </div>
            </form>
        </section>
    </div>
    <script src="script.js"></script>
</body>
</html>
