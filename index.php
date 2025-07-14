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
    // Buat objek PDO untuk koneksi database
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Tangani kesalahan koneksi database
    error_log("Database connection error: " . $e->getMessage());
    die("Koneksi database gagal: " . $e->getMessage());
}

// Helper function untuk warna acak untuk logo kartu review (DIPINDAHKAN KE PHP)
function getRandomColorForReviewCard() {
    $colors = ['#FEE2E2', '#FFFBEB', '#ECFCCB', '#D1FAE5', '#DBEAFE', '#EDE9FE', '#FCE7F3', '#FFEDD5']; // Warna pastel
    return $colors[array_rand($colors)];
}

$search_query = '';
$reviews = [];
$is_logged_in = isset($_SESSION['user_id']); // Cek status login
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$username = $is_logged_in ? htmlspecialchars($_SESSION['username']) : 'Guest';

// --- Logika Pagination ---
$reviews_per_page = 6; 
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $reviews_per_page;

// Hitung total ulasan (tanpa LIMIT/OFFSET dulu untuk mendapatkan total)
$total_reviews_stmt = $pdo->query("SELECT COUNT(*) FROM reviews");
$total_reviews = $total_reviews_stmt->fetchColumn();
$total_pages = ceil($total_reviews / $reviews_per_page);
// --- Akhir Logika Pagination ---


// Tangani pencarian jika ada parameter 'search' di URL
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    // Gabungkan dengan tabel users untuk menampilkan username penulis ulasan
    $stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.title LIKE ? OR r.author LIKE ? OR r.genre LIKE ? ORDER BY r.created_at DESC LIMIT ? OFFSET ?");
    $search_param = '%' . $search_query . '%';
    $stmt->bindValue(1, $search_param, PDO::PARAM_STR);
    $stmt->bindValue(2, $search_param, PDO::PARAM_STR);
    $stmt->bindValue(3, $search_param, PDO::PARAM_STR);
    $stmt->bindValue(4, $reviews_per_page, PDO::PARAM_INT);
    $stmt->bindValue(5, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll();
} else {
    // Jika tidak ada pencarian, tampilkan semua ulasan dengan pagination
    $stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $reviews_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll();
}

//Data dummy untuk carousel dan best book (ambil dari $reviews yang sudah difilter/dipaginasi)
$carousel_books = array_slice($reviews, 0, 6);
$best_book_review = isset($reviews[0]) ? $reviews[0] : null;
$popular_books = array_slice($reviews, 1, 6);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recensio - All Books</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <img src="uploads/logo_recensio.png" alt="Recensio Logo" class="site-logo">
            </div>
            <div class="site-branding"> 
                <h1 class="header-title">Recensio</h1>
                <p class="site-tagline">Discover and explore all book reviews from our community</p>
            </div>
            <div class="user-profile">
                <span><?php echo $username; ?></span>
                <?php if ($is_logged_in): ?>
                    <a href="logout.php" class="button primary small-button">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="button primary small-button">Login</a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav-section">
            <div class="nav-tabs">
                <a href="index.php" class="nav-tab active">All</a>
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="nav-tab">My Reviews</a>
                    <a href="add.php" class="nav-tab">Add Review</a>
                <?php else: ?>
                    <a href="login.php" class="nav-tab">My Reviews</a>
                    <a href="login.php" class="nav-tab">Add Review</a>
                <?php endif; ?>
            </div>
            <div class="search-bar">
                <input type="text" class="search-input" id="search-input" name="search" placeholder="Search books...">
                <div id="search-suggestions" class="search-suggestions"></div>
            </div>
        </nav>

        <!-- Book Carousel -->
        <section class="book-carousel">
            <div class="carousel-container">
                <div class="carousel-track" id="carousel-track">
                    <?php if (!empty($carousel_books)): ?>
                        <?php foreach ($carousel_books as $book): ?>
                            <a href="view_review.php?id=<?php echo htmlspecialchars($book['id']); ?>" class="carousel-book">
                                <img src="<?php echo htmlspecialchars($book['cover_image_url'] ?? 'uploads/covers/placeholder.png'); ?>" alt="Cover <?php echo htmlspecialchars($book['title']); ?>">
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #A0A0A0; text-align: center; width: 100%;">Tidak ada buku untuk ditampilkan di carousel.</p>
                    <?php endif; ?>
                </div>
                <button class="carousel-nav prev" onclick="moveCarousel(-1)">‹</button>
                <button class="carousel-nav next" onclick="moveCarousel(1)">›</button>
            </div>
        </section>

        <!-- Content Grid (Best Book and Popular) -->
        <div class="content-grid">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Best Book</h2>
                </div>
                <?php if ($best_book_review): ?>
                    <a href="view_review.php?id=<?php echo htmlspecialchars($best_book_review['id']); ?>" class="featured-book">
                        <div class="featured-cover">
                            <img src="<?php echo htmlspecialchars($best_book_review['cover_image_url'] ?? 'uploads/covers/placeholder.png'); ?>" alt="Cover <?php echo htmlspecialchars($best_book_review['title']); ?>">
                        </div>
                        <div class="featured-info">
                            <h3 class="featured-title"><?php echo htmlspecialchars($best_book_review['title']); ?></h3>
                            <p class="featured-author"><?php echo htmlspecialchars($best_book_review['author']); ?></p>
                            <div class="star-rating"><?php echo str_repeat('⭐', $best_book_review['rating']); ?></div>
                            <p class="featured-description">
                                <?php echo nl2br(htmlspecialchars(substr($best_book_review['review_text'], 0, 150))) . (strlen($best_book_review['review_text']) > 150 ? '...' : ''); ?>
                            </p>
                            <span class="read-more">Read more</span>
                        </div>
                    </a>
                <?php else: ?>
                    <p class="no-results">Belum ada ulasan terbaik.</p>
                <?php endif; ?>
            </div>

            <!-- Popular Section -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Populer</h2>
                </div>
                <div class="book-grid">
                    <?php if (!empty($popular_books)): ?>
                        <?php foreach ($popular_books as $book): ?>
                            <a href="view_review.php?id=<?php echo htmlspecialchars($book['id']); ?>" class="book-item">
                                <div class="book-cover1">
                                    <img src="<?php echo htmlspecialchars($book['cover_image_url'] ?? 'uploads/covers/placeholder.png'); ?>" alt="Cover <?php echo htmlspecialchars($book['title']); ?>">
                                </div>
                                <h4 class="book-title1"><?php echo htmlspecialchars($book['title']); ?></h4>
                                <p class="book-author1"><?php echo htmlspecialchars($book['author']); ?></p>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-results" style="grid-column: 1 / -1;">Tidak ada buku populer.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section untuk menampilkan semua review dengan desain kartu baru -->
        <section class="content-section all-reviews-full-list-section">
            <div class="section-header">
                <h2 class="section-title">All Reviews</h2>
            </div>
            <?php if (empty($reviews)): ?>
                <p class="no-results">Tidak ada ulasan ditemukan.</p>
            <?php else: ?>
                <div class="all-reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                        <a href="view_review.php?id=<?php echo htmlspecialchars($review['id']); ?>" class="all-review-card"> <!-- Bungkus seluruh kartu dengan <a> -->
                            <div class="card-header-main">
                                <div class="card-logo-placeholder" style="background-color: <?php echo getRandomColorForReviewCard(); ?>;">
                                    <img src="<?php echo htmlspecialchars($review['cover_image_url'] ?? 'uploads/covers/placeholder.png'); ?>" alt="Cover" class="card-cover-image-small"> <!-- Menggunakan gambar cover buku -->
                                </div>
                                <div class="card-title-author">
                                    <h3 class="card-book-title"><?php echo htmlspecialchars($review['title']); ?></h3>
                                    <p class="card-book-author"><?php echo htmlspecialchars($review['author']); ?></p>
                                </div>
                            </div>
                            <div class="card-rating-user">
                                <span class="card-stars"><?php echo str_repeat('⭐', $review['rating']); ?></span>
                                <span class="card-username"><?php echo htmlspecialchars($review['username'] ?? 'Pengguna Tidak Dikenal'); ?></span>
                                <span class="card-date"><?php echo date('d M Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <p class="card-review-text"><?php echo nl2br(htmlspecialchars(substr($review['review_text'], 0, 200))) . (strlen($review['review_text']) > 200 ? '...' : ''); ?></p>
                            <div class="card-genres">
                                <?php
                                $genres = explode(',', $review['genre']);
                                foreach ($genres as $genre) {
                                    echo '<span class="card-genre-tag">' . htmlspecialchars(trim($genre)) . '</span>';
                                }
                                ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="page-link">&larr; Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="page-link <?php echo ($i == $current_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="page-link">Next &rarr;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- Copyright Footer (di luar main-container) -->
        <footer class="footer-copyright">
            <p>&copy; <?php echo date('Y'); ?> Recensio. All rights reserved.</p>
        </footer>
    </div>

    <script src="script.js"></script>
    <script>
    function getRandomColorForReviewCard() { ... }
    </script>
</body>
</html>
