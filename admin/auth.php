<?php
// Schakel foutrapportage in maar log fouten naar een bestand in plaats van te tonen
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Start output buffering
ob_start();

// Start sessie en importeer benodigde bestanden
session_start();
require_once '../config/database.php';
require_once 'validation.php';

// Debug: Log de request
error_log("Auth.php aangeroepen - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// Verwerk POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Verwerk JSON requests (registratie)
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'register':
                try {
                    // Valideer verplichte velden
                    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                        throw new Exception('Alle velden zijn verplicht');
                    }

                    // Valideer input
                    validateUserInput([
                        'username' => $data['username'],
                        'email' => $data['email'],
                        'password' => $data['password'],
                        'role' => $data['role'] ?? 'editor'
                    ]);

                    $conn = getDB();
                    if (!$conn) {
                        throw new Exception('Database connection failed');
                    }

                    // Controleer of gebruikersnaam al bestaat
                    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
                    $stmt->execute([$data['username']]);
                    if ($stmt->fetch()) {
                        throw new Exception('Deze gebruikersnaam is al in gebruik');
                    }

                    // Controleer of e-mail al bestaat
                    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
                    $stmt->execute([$data['email']]);
                    if ($stmt->fetch()) {
                        throw new Exception('Dit e-mailadres is al in gebruik');
                    }

                    // Hash wachtwoord en maak gebruiker aan
                    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('
                        INSERT INTO users (username, email, password, role) 
                        VALUES (?, ?, ?, ?)
                    ');
                    $stmt->execute([
                        $data['username'],
                        $data['email'],
                        $hashedPassword,
                        $data['role'] ?? 'editor'
                    ]);

                    // Log activiteit
                    $userId = $conn->lastInsertId();
                    logActivity($userId, 'user_registered', 'Nieuwe gebruiker geregistreerd');

                    echo json_encode([
                        'success' => true,
                        'message' => 'Gebruiker succesvol geregistreerd'
                    ]);

                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                break;

            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Ongeldige actie'
                ]);
                break;
        }
    } else {
        // Verwerk normale POST requests (login)
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Debug: Log login poging
        error_log("Login poging voor gebruiker: " . $username);

        try {
            // Valideer input
            if (empty($username) || empty($password)) {
                throw new Exception('Gebruikersnaam en wachtwoord zijn verplicht');
            }

            // Probeer database connectie te krijgen
            $conn = getDB();
            if (!$conn) {
                throw new Exception('Database connection failed');
            }

            // Debug: Log database connectie succes
            error_log("Database connectie succesvol");

            // Haal gebruiker op
            $stmt = $conn->prepare('
                SELECT id, username, password, email, role, login_attempts, locked_until 
                FROM users 
                WHERE username = ? 
                LIMIT 1
            ');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Debug: Log gebruiker resultaat
            error_log("Gebruiker gevonden: " . ($user ? 'ja' : 'nee'));

            if (!$user) {
                throw new Exception('Ongeldige gebruikersnaam of wachtwoord');
            }

            // Controleer of gebruiker vergrendeld is
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $lockoutTime = date('H:i', strtotime($user['locked_until']));
                throw new Exception("Account is vergrendeld tot $lockoutTime");
            }

            // Verifieer wachtwoord
            $passwordValid = password_verify($password, $user['password']);
            error_log("Wachtwoord verificatie: " . ($passwordValid ? 'succesvol' : 'mislukt'));

            if (!$passwordValid) {
                // Update login pogingen
                updateUserLoginAttempts($user['id'], false);
                
                // Controleer of account nu vergrendeld moet worden
                if ($user['login_attempts'] + 1 >= 5) {
                    throw new Exception('Account is vergrendeld vanwege te veel mislukte inlogpogingen');
                }
                
                throw new Exception('Ongeldige gebruikersnaam of wachtwoord');
            }

            // Reset login pogingen en update laatste login
            updateUserLoginAttempts($user['id'], true);

            // Start sessie
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['admin_login_time'] = time();

            // Debug: Log succesvolle login
            error_log("Succesvol ingelogd voor gebruiker: " . $user['username']);

            // Log succesvolle login
            logActivity($user['id'], 'user_login', 'Succesvol ingelogd');

            echo json_encode([
                'success' => true,
                'message' => 'Succesvol ingelogd',
                'redirect' => 'dashboard.html'
            ]);

        } catch (Exception $e) {
            // Debug: Log fout
            error_log("Login fout: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
} else {
    // Niet-POST requests
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?> 