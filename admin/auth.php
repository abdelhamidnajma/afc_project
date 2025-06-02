<?php
// Enable error reporting but log errors to a file instead of displaying
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

ob_start();

session_start();
require_once '../config/database.php';
require_once 'validation.php';

// Initialize database if not already done
initializeDatabase();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Handle JSON requests (registration)
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'register':
                try {
                    // Validate required fields
                    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                        throw new Exception('Alle velden zijn verplicht');
                    }

                    // Validate input
                    validateUserInput([
                        'username' => $data['username'],
                        'email' => $data['email'],
                        'password' => $data['password'],
                        'role' => $data['role'] ?? 'editor'
                    ]);

                    $db = getDB();

                    // Check if username already exists
                    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
                    $stmt->execute([$data['username']]);
                    if ($stmt->fetch()) {
                        throw new Exception('Deze gebruikersnaam is al in gebruik');
                    }

                    // Check if email already exists
                    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
                    $stmt->execute([$data['email']]);
                    if ($stmt->fetch()) {
                        throw new Exception('Dit e-mailadres is al in gebruik');
                    }

                    // Hash password
                    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                    // Insert new user
                    $stmt = $db->prepare('
                        INSERT INTO users (username, email, password, role, created_at, updated_at)
                        VALUES (?, ?, ?, ?, NOW(), NOW())
                    ');
                    
                    if ($stmt->execute([$data['username'], $data['email'], $hashedPassword, $data['role'] ?? 'editor'])) {
                        // Log the registration
                        logActivity(
                            $db->lastInsertId(),
                            'register',
                            'user',
                            $db->lastInsertId(),
                            ['username' => $data['username'], 'email' => $data['email']]
                        );
                        echo json_encode(['success' => true, 'message' => 'Registratie succesvol']);
                    } else {
                        throw new Exception('Er is een fout opgetreden bij het registreren');
                    }
                } catch (Exception $e) {
                    error_log('Registration error: ' . $e->getMessage());
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit;
        }
    }
    
    // Handle form POST requests (login)
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                try {
                    if (!isset($_POST['username']) || !isset($_POST['password'])) {
                        echo json_encode(['success' => false, 'message' => 'Missing credentials']);
                        exit;
                    }

                    $user = getUserByUsername($_POST['username']);
                    if ($user && verifyPassword($_POST['password'], $user['password'])) {
                        // Check if account is locked
                        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                            echo json_encode([
                                'success' => false, 
                                'message' => 'Account is vergrendeld. Probeer het later opnieuw.'
                            ]);
                            exit;
                        }

                        // Update login status
                        updateUserLoginAttempts($user['id'], true);
                        
                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];

                        // Log successful login
                        logActivity($user['id'], 'login', 'user', $user['id']);

                        echo json_encode(['success' => true, 'user' => [
                            'username' => $user['username'],
                            'role' => $user['role']
                        ]]);
                    } else {
                        // Update failed login attempt
                        if ($user) {
                            updateUserLoginAttempts($user['id'], false);
                        }
                        echo json_encode(['success' => false, 'message' => 'Ongeldige inloggegevens']);
                    }
                } catch (Exception $e) {
                    error_log('Login error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Er is een fout opgetreden bij het inloggen']);
                }
                break;

            case 'logout':
                if (isset($_SESSION['user_id'])) {
                    logActivity($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id']);
                }
                session_destroy();
                echo json_encode(['success' => true]);
                break;

            case 'check_auth':
                if (isset($_SESSION['user_id'])) {
                    echo json_encode(['success' => true, 'user' => [
                        'username' => $_SESSION['username'],
                        'role' => $_SESSION['role']
                    ]]);
                } else {
                    echo json_encode(['success' => false]);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit;
    }
}

// Authentication middleware
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /admin.html');
        exit;
    }
}

// Role-based access control
function requireRole($roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], (array)$roles)) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit;
    }
}
?> 