<?php


header('Content-Type: application/json');

$host = 'localhost';
$db   = 'ulas_buku_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=> false,
];

$suggestions = [];
$query = $_GET['query'] ?? '';

if (!empty($query)) {
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $stmt = $pdo->prepare("SELECT id, title, author, genre FROM reviews WHERE title LIKE ? OR author LIKE ? OR genre LIKE ? LIMIT 10");
        $search_param = '%' . $query . '%';
        $stmt->execute([$search_param, $search_param, $search_param]);
        $suggestions = $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error fetching search suggestions: " . $e->getMessage());
        echo json_encode(['error' => 'Database error occurred.']);
        exit();
    }
}

echo json_encode($suggestions);
?>