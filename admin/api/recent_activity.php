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
    
    // Get recent activity from activity_log
    $stmt = $conn->prepare("
        SELECT al.*, u.username 
        FROM activity_log al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    $formattedActivities = [];
    foreach ($activities as $activity) {
        $timeAgo = timeAgo($activity['created_at']);
        $description = getActivityDescription($activity);
        
        $formattedActivities[] = [
            'id' => $activity['id'],
            'action' => $activity['action'],
            'description' => $description,
            'time_ago' => $timeAgo,
            'username' => $activity['username'] ?? 'System',
            'created_at' => $activity['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'activities' => $formattedActivities
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading activity: ' . $e->getMessage(),
        'activities' => []
    ]);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Zojuist';
    } elseif ($time < 3600) {
        return floor($time / 60) . ' minuten geleden';
    } elseif ($time < 86400) {
        return floor($time / 3600) . ' uur geleden';
    } elseif ($time < 2592000) {
        return floor($time / 86400) . ' dagen geleden';
    } else {
        return date('d-m-Y H:i', strtotime($datetime));
    }
}

function getActivityDescription($activity) {
    $action = $activity['action'];
    $username = $activity['username'] ?? 'Onbekende gebruiker';
    
    switch ($action) {
        case 'login':
            return "$username is ingelogd";
        case 'logout':
            return "$username is uitgelogd";
        case 'register':
            return "Nieuwe gebruiker geregistreerd: $username";
        case 'create':
            return "$username heeft content aangemaakt";
        case 'update':
            return "$username heeft content bijgewerkt";
        case 'delete':
            return "$username heeft content verwijderd";
        case 'login_failed':
            return "Mislukte login poging";
        default:
            return "Activiteit: $action";
    }
}
?> 