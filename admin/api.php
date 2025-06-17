<?php
// Start de sessie voor authenticatie
session_start();
// Stel JSON content type in voor API responses
header('Content-Type: application/json');

// Importeer database connectie
require_once '../config/database.php';

// Controleer of gebruiker is ingelogd
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niet ingelogd']);
    exit;
}

// Controleer sessie timeout
$sessionTimeout = 8 * 60 * 60; // 8 uur
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $sessionTimeout) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessie verlopen']);
    exit;
}

try {
    // Haal JSON input op
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    // Verwerk verschillende API acties
    switch ($action) {
        case 'get_dashboard_stats':
            // Haal dashboard statistieken op
            $stats = getDashboardStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'get_recent_activity':
            // Haal recente activiteit op
            $activity = getRecentActivity();
            echo json_encode(['success' => true, 'data' => $activity]);
            break;
            
        case 'get_users':
            // Haal gebruikerslijst op
            $users = getUsers();
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'create_user':
            createUser($input);
            break;
            
        case 'update_user':
            updateUser($input);
            break;
            
        case 'delete_user':
            deleteUser($input);
            break;
            
        case 'get_news':
            // Haal nieuwsartikelen op
            $news = getNews();
            echo json_encode(['success' => true, 'data' => $news]);
            break;
            
        case 'create_news':
            createNews($input);
            break;
            
        case 'update_news':
            updateNews($input);
            break;
            
        case 'delete_news':
            deleteNews($input);
            break;
            
        case 'get_contact_messages':
            getContactMessages($input);
            break;
            
        case 'update_contact_status':
            updateContactStatus($input);
            break;
            
        case 'get_system_logs':
            getSystemLogs($input);
            break;
            
        case 'get_teams':
            // Haal teams op
            $teams = getTeams();
            echo json_encode(['success' => true, 'data' => $teams]);
            break;
            
        case 'get_events':
            // Haal evenementen op
            $events = getEvents();
            echo json_encode(['success' => true, 'data' => $events]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ongeldige actie']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Haal dashboard statistieken op
 */
function getDashboardStats() {
    $conn = getDB();
    $stats = [];
    
    try {
        // Tel gebruikers
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'];
        
        // Tel nieuwsartikelen
        $stmt = $conn->query("SELECT COUNT(*) as count FROM news");
        $stats['total_news'] = $stmt->fetch()['count'];
        
        // Tel teams
        $stmt = $conn->query("SELECT COUNT(*) as count FROM teams");
        $stats['total_teams'] = $stmt->fetch()['count'];
        
        // Tel evenementen
        $stmt = $conn->query("SELECT COUNT(*) as count FROM events");
        $stats['total_events'] = $stmt->fetch()['count'];
        
        // Tel recente activiteit
        $stmt = $conn->query("SELECT COUNT(*) as count FROM activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['recent_activity'] = $stmt->fetch()['count'];
        
    } catch (Exception $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Haal recente activiteit op
 */
function getRecentActivity() {
    $conn = getDB();
    
    try {
        $stmt = $conn->prepare("
            SELECT al.*, u.username 
            FROM activity_log al 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error getting recent activity: " . $e->getMessage());
        return [];
    }
}

/**
 * Haal gebruikerslijst op
 */
function getUsers() {
    $conn = getDB();
    
    try {
        $stmt = $conn->query("
            SELECT id, username, email, role, last_login, created_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error getting users: " . $e->getMessage());
        return [];
    }
}

/**
 * Create user
 */
function createUser($input) {
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'editor';
    
    if (empty($username) || empty($email) || empty($password)) {
        throw new Exception('Alle velden zijn verplicht');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Ongeldig e-mailadres');
    }
    
    try {
        $conn = getDB();
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('Gebruikersnaam bestaat al');
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('E-mailadres bestaat al');
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, role) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $hashedPassword, $role]);
        
        $userId = $conn->lastInsertId();
        
        // Log activity
        logAdminAction($_SESSION['admin_user_id'], 'create_user', "Created user: $username");
        
        echo json_encode([
            'success' => true,
            'message' => 'Gebruiker succesvol aangemaakt',
            'user_id' => $userId
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Fout bij aanmaken gebruiker: ' . $e->getMessage());
    }
}

/**
 * Update user
 */
function updateUser($input) {
    $userId = $input['user_id'] ?? 0;
    $email = trim($input['email'] ?? '');
    $role = $input['role'] ?? '';
    
    if (!$userId) {
        throw new Exception('Gebruiker ID is verplicht');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Ongeldig e-mailadres');
    }
    
    try {
        $conn = getDB();
        
        // Update user
        $stmt = $conn->prepare("
            UPDATE users 
            SET email = ?, role = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$email, $role, $userId]);
        
        // Log action
        logAdminAction($_SESSION['admin_user_id'], 'update_user', "Updated user ID: $userId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Gebruiker succesvol bijgewerkt'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Fout bij bijwerken gebruiker: ' . $e->getMessage());
    }
}

/**
 * Delete user
 */
function deleteUser($input) {
    $userId = $input['user_id'] ?? 0;
    
    if (!$userId) {
        throw new Exception('Gebruiker ID is verplicht');
    }
    
    // Don't allow self-deletion
    if ($userId == $_SESSION['admin_user_id']) {
        throw new Exception('Je kunt je eigen account niet verwijderen');
    }
    
    try {
        $conn = getDB();
        
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Log action
        logAdminAction($_SESSION['admin_user_id'], 'delete_user', "Deleted user ID: $userId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Gebruiker succesvol verwijderd'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Fout bij verwijderen gebruiker: ' . $e->getMessage());
    }
}

/**
 * Haal nieuwsartikelen op
 */
function getNews() {
    $conn = getDB();
    
    try {
        $stmt = $conn->prepare("
            SELECT n.*, u.username as author_name 
            FROM news n 
            LEFT JOIN users u ON n.author_id = u.id 
            ORDER BY n.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error getting news: " . $e->getMessage());
        return [];
    }
}

/**
 * Create news
 */
function createNews($input) {
    $title = trim($input['title'] ?? '');
    $content = trim($input['content'] ?? '');
    $excerpt = trim($input['excerpt'] ?? '');
    $category = $input['category'] ?? 'club';
    $authorId = $_SESSION['admin_user_id'];
    
    if (empty($title) || empty($content)) {
        throw new Exception('Titel en inhoud zijn verplicht');
    }
    
    // Generate excerpt if not provided
    if (empty($excerpt)) {
        $excerpt = substr(strip_tags($content), 0, 150) . '...';
    }
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    try {
        $conn = getDB();
        
        $stmt = $conn->prepare("
            INSERT INTO news (title, slug, content, excerpt, category, author_id, status, published_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'published', NOW())
        ");
        $stmt->execute([$title, $slug, $content, $excerpt, $category, $authorId]);
        
        // Log action
        logAdminAction($_SESSION['admin_user_id'], 'create_news', "Created news: $title");
        
        echo json_encode([
            'success' => true,
            'message' => 'Nieuwsbericht succesvol aangemaakt'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Fout bij aanmaken nieuws: ' . $e->getMessage());
    }
}

/**
 * Update news
 */
function updateNews($input) {
    $newsId = $input['news_id'] ?? 0;
    $title = trim($input['title'] ?? '');
    $content = trim($input['content'] ?? '');
    $excerpt = trim($input['excerpt'] ?? '');
    $category = $input['category'] ?? '';
    $status = $input['status'] ?? 'published';
    
    if (!$newsId || empty($title) || empty($content)) {
        throw new Exception('Nieuws ID, titel en inhoud zijn verplicht');
    }
    
    try {
        $conn = getDB();
        
        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        
        $stmt = $conn->prepare("
            UPDATE news 
            SET title = ?, slug = ?, content = ?, excerpt = ?, category = ?, status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$title, $slug, $content, $excerpt, $category, $status, $newsId]);
        
        // Log action
        logAdminAction($_SESSION['admin_user_id'], 'update_news', "Updated news ID: $newsId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Nieuwsbericht succesvol bijgewerkt'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Fout bij bijwerken nieuws: ' . $e->getMessage());
    }
}

/**
 * Delete news
 */
function deleteNews($input) {
    $newsId = $input['news_id'] ?? 0;
    
    if (!$newsId) {
        throw new Exception('Nieuws ID is verplicht');
    }
    
    try {
        $conn = getDB();
        
        $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
        $stmt->execute([$newsId]);
        
        // Log action
        logAdminAction($_SESSION['admin_user_id'], 'delete_news', "Deleted news ID: $newsId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Nieuwsbericht succesvol verwijderd'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Fout bij verwijderen nieuws: ' . $e->getMessage());
    }
}

/**
 * Get contact messages
 */
function getContactMessages($input) {
    $limit = $input['limit'] ?? 50;
    $offset = $input['offset'] ?? 0;
    $status = $input['status'] ?? 'all';
    
    try {
        $conn = getDB();
        
        $sql = "
            SELECT al.*, u.username 
            FROM activity_log al 
            LEFT JOIN users u ON al.user_id = u.id 
            WHERE al.action = 'contact_request'
        ";
        
        if ($status !== 'all') {
            $sql .= " AND al.details->>'$.status' = ?";
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        
        if ($status !== 'all') {
            $stmt->execute([$status, $limit, $offset]);
        } else {
            $stmt->execute([$limit, $offset]);
        }
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Fout bij ophalen contactberichten: ' . $e->getMessage());
    }
}

/**
 * Update contact status
 */
function updateContactStatus($input) {
    $messageId = $input['message_id'] ?? 0;
    $status = $input['status'] ?? '';
    
    if (!$messageId || empty($status)) {
        throw new Exception('Bericht ID en status zijn verplicht');
    }
    
    try {
        $conn = getDB();
        
        // Update the status in the activity_log details
        $stmt = $conn->prepare("
            UPDATE activity_log 
            SET details = JSON_SET(details, '$.status', ?) 
            WHERE id = ? AND action = 'contact_request'
        ");
        $stmt->execute([$status, $messageId]);
        
        // Log action
        logAdminAction($_SESSION['admin_user_id'], 'update_contact_status', "Updated contact message ID: $messageId to status: $status");
        
        echo json_encode([
            'success' => true,
            'message' => 'Status succesvol bijgewerkt'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Fout bij bijwerken status: ' . $e->getMessage());
    }
}

/**
 * Get system logs
 */
function getSystemLogs($input) {
    $limit = $input['limit'] ?? 100;
    $offset = $input['offset'] ?? 0;
    $action = $input['action'] ?? '';
    
    try {
        $conn = getDB();
        
        $sql = "
            SELECT al.*, u.username 
            FROM activity_log al 
            LEFT JOIN users u ON al.user_id = u.id 
        ";
        
        if (!empty($action)) {
            $sql .= " WHERE al.action = ?";
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($action)) {
            $stmt->execute([$action, $limit, $offset]);
        } else {
            $stmt->execute([$limit, $offset]);
        }
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Fout bij ophalen systeemlogs: ' . $e->getMessage());
    }
}

/**
 * Log admin action
 */
function logAdminAction($userId, $action, $details) {
    try {
        $conn = getDB();
        
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, details, ip_address, user_agent) 
            VALUES (?, ?, 'admin', ?, ?, ?)
        ");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->execute([$userId, $action, json_encode(['message' => $details]), $ipAddress, $userAgent]);
        
    } catch (Exception $e) {
        error_log('Failed to log admin action: ' . $e->getMessage());
    }
}

/**
 * Haal teams op
 */
function getTeams() {
    $conn = getDB();
    
    try {
        $stmt = $conn->query("
            SELECT * FROM teams 
            ORDER BY category, name 
            LIMIT 10
        ");
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error getting teams: " . $e->getMessage());
        return [];
    }
}

/**
 * Haal evenementen op
 */
function getEvents() {
    $conn = getDB();
    
    try {
        $stmt = $conn->query("
            SELECT * FROM events 
            ORDER BY event_date DESC, event_time DESC 
            LIMIT 10
        ");
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error getting events: " . $e->getMessage());
        return [];
    }
}
?> 