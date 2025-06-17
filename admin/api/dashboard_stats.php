<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

try {
    require_once '../../config/database.php';
    $conn = getDB();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    $stats = [];
    
    // Count users
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    $stats['users'] = $result['count'];
    
    // Count news articles (if news table exists)
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM news");
        $result = $stmt->fetch();
        $stats['news'] = $result['count'];
    } catch (Exception $e) {
        $stats['news'] = 0;
    }
    
    // Count teams (if teams table exists)
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM teams");
        $result = $stmt->fetch();
        $stats['teams'] = $result['count'];
    } catch (Exception $e) {
        $stats['teams'] = 0;
    }
    
    // Count events (if events table exists)
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM events");
        $result = $stmt->fetch();
        $stats['events'] = $result['count'];
    } catch (Exception $e) {
        $stats['events'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading statistics: ' . $e->getMessage(),
        'stats' => [
            'users' => 0,
            'news' => 0,
            'teams' => 0,
            'events' => 0
        ]
    ]);
}
?> 