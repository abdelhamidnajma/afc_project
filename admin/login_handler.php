<?php
session_start();
header('Content-Type: application/json');

// Include the correct database configuration
require_once '../config/database.php';

try {
    $conn = getDB();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

// Check for brute force attempts using activity_log
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $conn->prepare("
    SELECT COUNT(*) as attempts 
    FROM activity_log 
    WHERE ip_address = ? AND action = 'login_failed' AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
");
$stmt->execute([$ip]);
$attempts = $stmt->fetch()['attempts'];

if ($attempts >= 5) {
    echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please try again in 15 minutes.']);
    exit;
}

// Verify user credentials using the correct table
$stmt = $conn->prepare("
    SELECT id, username, password, email, role 
    FROM users 
    WHERE username = ? AND role IN ('admin', 'editor')
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    // Successful login
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_login_time'] = time();
    
    // Update last login
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Log successful login
    $stmt = $conn->prepare("
        INSERT INTO activity_log (user_id, action, entity_type, details, ip_address, user_agent) 
        VALUES (?, 'login', 'user', ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'], 
        json_encode(['message' => 'User logged in successfully']), 
        $ip, 
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful',
        'user' => [
            'username' => $user['username'],
            'role' => $user['role'],
            'email' => $user['email']
        ]
    ]);
} else {
    // Failed login - log the attempt
    $stmt = $conn->prepare("
        INSERT INTO activity_log (action, entity_type, details, ip_address, user_agent) 
        VALUES ('login_failed', 'user', ?, ?, ?)
    ");
    $stmt->execute([
        json_encode(['message' => 'Failed login attempt for username: ' . $username]), 
        $ip, 
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
}
?> 