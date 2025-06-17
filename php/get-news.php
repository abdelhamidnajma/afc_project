<?php
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Alleen GET requests zijn toegestaan'
    ]);
    exit;
}

try {
    $conn = getDBConnection();

    // Get optional parameters
    $category = $_GET['category'] ?? 'all';
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = (int)($_GET['offset'] ?? 0);
    $featured = $_GET['featured'] ?? false;

    // Prepare SQL statement
    $sql = "SELECT id, title, content, excerpt, image_url, category, author, published_at 
            FROM news";
    
    $params = [];
    $conditions = [];
    
    // Add category filter if specified
    if ($category !== 'all') {
        $conditions[] = "category = :category";
        $params[':category'] = $category;
    }
    
    // Add featured filter if specified
    if ($featured) {
        $conditions[] = "featured = 1";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    // Add ordering and pagination
    $sql .= " ORDER BY published_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM news";
    if (!empty($conditions)) {
        $countSql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $key => $value) {
        if ($key !== ':limit' && $key !== ':offset') {
            $countStmt->bindValue($key, $value);
        }
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'news' => $news,
        'total' => $totalCount,
        'limit' => $limit,
        'offset' => $offset
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 