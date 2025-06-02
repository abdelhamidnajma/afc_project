<?php
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // Get all results, ordered by date (newest first)
    $sql = "SELECT * FROM results ORDER BY date DESC, created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates for display
    foreach ($results as &$result) {
        $result['date'] = date('Y-m-d', strtotime($result['date']));
    }
    
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 