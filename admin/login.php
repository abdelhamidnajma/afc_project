<?php
// Start de sessie voor authenticatie
session_start();
// Stel JSON content type in voor API responses
header('Content-Type: application/json');

// Voorkom directe toegang
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Importeer database connectie en authenticatie functies
require_once '../config/database.php';
require_once 'auth.php';

try {
    // Haal JSON input op
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    // Valideer input
    if (empty($username) || empty($password)) {
        throw new Exception('Gebruikersnaam en wachtwoord zijn verplicht');
    }
    
    // Controleer brute force pogingen
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts = checkLoginAttempts($ip);
    
    if ($attempts >= 5) {
        $lockoutTime = 900; // 15 minuten
        $lastAttempt = getLastLoginAttempt($ip);
        
        if (time() - $lastAttempt < $lockoutTime) {
            $remainingTime = $lockoutTime - (time() - $lastAttempt);
            throw new Exception("Te veel inlogpogingen. Probeer het over " . ceil($remainingTime / 60) . " minuten opnieuw.");
        } else {
            // Reset pogingen na lockout periode
            resetLoginAttempts($ip);
        }
    }
    
    // Haal gebruiker op uit database
    $stmt = $pdo->prepare("SELECT id, username, password, email, role, last_login, is_active FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        recordLoginAttempt($ip, false);
        throw new Exception('Ongeldige gebruikersnaam of wachtwoord');
    }
    
    // Controleer of gebruiker actief is
    if (!$user['is_active']) {
        recordLoginAttempt($ip, false);
        throw new Exception('Account is gedeactiveerd');
    }
    
    // Verifieer wachtwoord
    if (!password_verify($password, $user['password'])) {
        recordLoginAttempt($ip, false);
        throw new Exception('Ongeldige gebruikersnaam of wachtwoord');
    }
    
    // Succesvolle login - reset pogingen
    resetLoginAttempts($ip);
    recordLoginAttempt($ip, true);
    
    // Update laatste login tijd
    $stmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Start sessie
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['admin_login_time'] = time();
    
    // Log admin actie
    logAdminAction($user['id'], 'login', 'Succesvol ingelogd');
    
    echo json_encode([
        'success' => true,
        'message' => 'Succesvol ingelogd',
        'user' => [
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Controleer aantal login pogingen voor IP
 */
function checkLoginAttempts($ip) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE ip_address = ? 
        AND success = 0 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$ip]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int)$result['attempts'];
}

/**
 * Haal laatste login poging tijd op
 */
function getLastLoginAttempt($ip) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT UNIX_TIMESTAMP(attempt_time) as timestamp 
        FROM login_attempts 
        WHERE ip_address = ? 
        ORDER BY attempt_time DESC 
        LIMIT 1
    ");
    $stmt->execute([$ip]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? (int)$result['timestamp'] : 0;
}

/**
 * Reset login pogingen voor IP
 */
function resetLoginAttempts($ip) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

/**
 * Registreer login poging
 */
function recordLoginAttempt($ip, $success) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (ip_address, success, attempt_time) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$ip, $success ? 1 : 0]);
}

/**
 * Log admin actie
 */
function logAdminAction($userId, $action, $details) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (user_id, action, details, ip_address, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR']]);
}
?> 