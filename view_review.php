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

// Pastikan ID ulasan diberikan di URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID ulasan tidak valid.");
}

$id = (int)$_GET['id'];
$review = null;

try {
    $stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->execute([$id]);
    $review = $stmt->fetch();

    if (!$review) {
        die("Ulasan tidak ditemukan.");
    }
} catch (\PDOException $e) {
    error_log("Error fetching review details: " . $e->getMessage());
    die("Gagal mengambil detail ulasan: " . $e->getMessage());
}

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$username_logged_in = $is_logged_in ? htmlspecialchars($_SESSION['username']) : 'Guest';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($review['title']); ?> - Recensio</title>
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
                <span><?php echo $username_logged_in; ?></span>
                <?php if ($is_logged_in): ?>
                    <a href="logout.php" class="button primary small-button">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="button primary small-button">Login</a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Navigasi -->
        <nav class="nav-section">
            <div class="nav-tabs">
                <a href="index.php" class="nav-tab">All</a>
                <a href="dashboard.php" class="nav-tab active">My Reviews</a>
                <a href="add.php" class="nav-tab">Add Review</a>
            </div>
            <div class="search-bar">
                <input type="text" class="search-input" id="search-input" name="search" placeholder="Search books...">
                <div id="search-suggestions" class="search-suggestions"></div>
            </div>
        </nav>

        <!-- Bagian Konten Detail Ulasan -->
        <section class="content-section">
            <div class="review-detail-main-grid"> <!-- Wrapper utama untuk layout 2 kolom -->
                <div class="book-cover-section"> <!-- Kolom untuk gambar cover -->
                    <?php if ($review['cover_image_url']): ?>
                        <img src="<?php echo htmlspecialchars($review['cover_image_url']); ?>" alt="Cover Buku" class="detail-book-cover">
                    <?php else: ?>
                        <img src="uploads/covers/placeholder.png" alt="Tidak Ada Cover" class="detail-book-cover placeholder">
                    <?php endif; ?>
                </div>
                
                <div class="book-info-and-all-details"> <!-- Kolom untuk semua informasi teks -->
                    <div class="book-main-info">
                        <h2><?php echo htmlspecialchars($review['title']); ?></h2>
                        <p class="author-detail"><?php echo htmlspecialchars($review['author']); ?></p>
                        <p class="rating-detail"><?php echo str_repeat('⭐', $review['rating']); ?> (<?php echo htmlspecialchars($review['rating']); ?>/5)</p>
                    </div>

                    <div class="description-and-physical-info">
                        <div class="description-section">
                        <h3>Deskripsi Buku</h3>
                        <p><?php echo nl2br(htmlspecialchars($review['book_description'])); ?></p>
                        <h3>Ulasan</h3>
                        <p><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                    </div>

                    <div class="additional-info-section">
                        <h3>Detail Fisik</h3>
                        <p><strong>Deskripsi Fisik:</strong> <?php echo nl2br(htmlspecialchars($review['physical_description'] ?? '-')); ?></p>
                        <p><strong>Jumlah Halaman:</strong> <?php echo htmlspecialchars($review['page_count'] ?? '-'); ?></p>
                        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($review['isbn'] ?? '-'); ?></p>
                        <p><strong>Genre:</strong> <?php echo htmlspecialchars($review['genre'] ?? '-'); ?></p>
                        <p><strong>Ditambahkan Oleh:</strong> <?php echo htmlspecialchars($review['username'] ?? 'Pengguna Tidak Dikenal'); ?></p>
                        <p><strong>Tanggal Ulasan:</strong> <?php echo date('d M Y', strtotime($review['created_at'])); ?></p>
                    </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <script src="script.js"></script>
</body>
</html>
