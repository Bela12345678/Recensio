<?php
session_start(); 

// Konfigurasi koneksi database
$host = 'localhost';
$db   = 'ulas_buku_db'; // Nama database yang telah Anda buat
$user = 'root';        // Sesuaikan dengan username database Anda
$pass = '';            // Sesuaikan dengan password database Anda
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

// Helper function untuk warna acak untuk logo kartu review (DIPINDAHKAN KE PHP)
function getRandomColorForReviewCard() {
    $colors = ['#FEE2E2', '#FFFBEB', '#ECFCCB', '#D1FAE5', '#DBEAFE', '#EDE9FE', '#FCE7F3', '#FFEDD5']; // Warna pastel
    return $colors[array_rand($colors)];
}

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$reviews = [];
$message = ''; 
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';

// Periksa pesan status dari pengalihan
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'added') {
        $message = '<p class="success">Ulasan berhasil ditambahkan!</p>';
    } elseif ($_GET['status'] == 'updated') { 
        $message = '<p class="success">Ulasan berhasil diperbarui!</p>';
    } elseif ($_GET['status'] == 'deleted') { 
        $message = '<p class="success">Ulasan berhasil dihapus!</p>';
    }
}

try {
    // Ambil ulasan hanya untuk pengguna yang sedang login
    $stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$current_user_id]);
    $reviews = $stmt->fetchAll();
    
} catch (\PDOException $e) {
    error_log("Error fetching user's reviews: " . $e->getMessage());
    die("Gagal mengambil ulasan Anda: " . $e->getMessage());
}


$reviews_json = json_encode($reviews);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Recensio</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="main-container">
        <div class="header">
            <div class="logo">
                <img src="uploads/logo_recensio.png" alt="Recensio Logo" class="site-logo">
            </div>
            <h1 class="header-title">Recensio</h1>
            <div class="user-profile">
                <span class="user-name"><?php echo $username; ?></span>
                <a href="logout.php" class="button primary small-button">Logout</a>
            </div>
        </div>
        
        <div class="nav-tabs">
            <a href="index.php" class="nav-tab">All</a>
            <a href="dashboard.php" class="nav-tab active">My Reviews</a>
            <a href="add.php" class="nav-tab">Add Review</a>
        </div>
        
        <div class="search-container">
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search your reviews..." id="searchInput">
            </div>
            <select class="filter-dropdown" id="filterSelect">
                <option value="all">All Reviews</option>
                <option value="5">5 Stars</option>
                <option value="4">4 Stars</option>
                <option value="3">3 Stars</option>
                <option value="2">2 Stars</option>
                <option value="1">1 Star</option>
            </select>
        </div>
        
        <div class="content">
            <?php echo $message; // Menampilkan pesan status ?>
            <div class="reviews-grid" id="reviewsGrid">
            </div>
            <div class="empty-state" id="emptyState" style="display: none;">
                <h2>No Reviews Found</h2>
                <p>You haven't written any reviews yet, or no reviews match your search.</p>
                <a href="add.php" class="add-review-btn">Write Your First Review</a>
            </div>
        </div>
    </div>
    
    <!-- Edit Review Modal -->
    <div id="editModal" class="modal" style="display: none;"> 
        <div class="modal-content">
            <h2>Edit Review</h2>
            <form id="editForm">
                <input type="hidden" id="editReviewId"> 
                <div class="form-group">
                    <label for="editTitle">Book Title</label>
                    <input type="text" id="editTitle" required>
                </div>
                <div class="form-group">
                    <label for="editAuthor">Author</label>
                    <input type="text" id="editAuthor" required>
                </div>
                <div class="form-group">
                    <label for="editGenre">Genre:</label>
                    <input type="text" id="editGenre" required>
                </div>
                <div class="form-group">
                    <label for="editBookDescription">Deskripsi Buku:</label>
                    <textarea id="editBookDescription" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editPhysicalDescription">Deskripsi Fisik:</label>
                    <textarea id="editPhysicalDescription" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editPageCount">Jumlah Halaman:</label>
                    <input type="number" id="editPageCount" min="1" required>
                </div>
                <div class="form-group">
                    <label for="editISBN">ISBN:</label>
                    <input type="text" id="editISBN" required>
                </div>
                <div class="form-group">
                    <label for="editRating">Rating</label>
                    <select id="editRating" required>
                        <option value="1">1 Star</option>
                        <option value="2">2 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="5">5 Stars</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editReviewText">Review</label>
                    <textarea id="editReviewText" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="modal-btn primary">Update Review</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', { 
                day: 'numeric', 
                month: 'short',
                year: 'numeric' 
            });
        }

        // Data reviews akan diisi dari PHP
        window.reviews = <?php echo $reviews_json; ?>;
    </script>
    <script src="script.js"></script>
</body>
</html>
