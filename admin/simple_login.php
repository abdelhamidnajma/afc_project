<?php
/*
    AFC Website - Eenvoudige Login Test
    ===================================
    
    Dit script test de login functionaliteit zonder database initialisatie.
    
    Gebruik: Ga naar admin/simple_login.php in je browser
    Auteur: AFC Development Team
    Laatste update: 2024
*/

// Schakel foutrapportage in voor debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start sessie
session_start();

// Importeer database configuratie
require_once '../config/database.php';

echo "<h1>AFC Eenvoudige Login Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Verwerk login formulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Login Resultaat</h2>";
    
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

        echo "<p class='success'>✓ Database connectie succesvol</p>";

        // Haal gebruiker op
        $stmt = $conn->prepare('
            SELECT id, username, password, email, role, login_attempts, locked_until 
            FROM users 
            WHERE username = ? 
            LIMIT 1
        ');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception('Ongeldige gebruikersnaam of wachtwoord');
        }

        echo "<p class='success'>✓ Gebruiker gevonden</p>";

        // Controleer of gebruiker vergrendeld is
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $lockoutTime = date('H:i', strtotime($user['locked_until']));
            throw new Exception("Account is vergrendeld tot $lockoutTime");
        }

        // Verifieer wachtwoord
        if (!password_verify($password, $user['password'])) {
            throw new Exception('Ongeldige gebruikersnaam of wachtwoord');
        }

        echo "<p class='success'>✓ Wachtwoord correct</p>";

        // Start sessie
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_login_time'] = time();

        echo "<p class='success'>✓ Login succesvol!</p>";
        echo "<p class='info'>Welkom, {$user['username']}!</p>";
        echo "<p><a href='dashboard.html'>Ga naar Dashboard</a></p>";

    } catch (Exception $e) {
        echo "<p class='error'>✗ Fout: " . $e->getMessage() . "</p>";
    }
}

// Toon login formulier
echo "<h2>Login Formulier</h2>";
echo "<form method='POST'>";
echo "<p><label>Gebruikersnaam: <input type='text' name='username' required></label></p>";
echo "<p><label>Wachtwoord: <input type='password' name='password' required></label></p>";
echo "<p><input type='submit' value='Inloggen'></p>";
echo "</form>";

// Toon database status
echo "<h2>Database Status</h2>";
try {
    $conn = getDB();
    if ($conn) {
        echo "<p class='success'>✓ Database connectie werkt</p>";
        
        // Controleer users tabel
        $stmt = $conn->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ Users tabel bestaat</p>";
            
            // Toon aantal gebruikers
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt->fetch()['count'];
            echo "<p class='info'>Aantal gebruikers: {$userCount}</p>";
            
            if ($userCount > 0) {
                // Toon alle gebruikers
                $stmt = $conn->query("SELECT id, username, email, role FROM users");
                $users = $stmt->fetchAll();
                
                echo "<h3>Gebruikers:</h3>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Gebruikersnaam</th><th>E-mail</th><th>Rol</th></tr>";
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>{$user['username']}</td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>{$user['role']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p class='error'>✗ Users tabel bestaat niet</p>";
        }
    } else {
        echo "<p class='error'>✗ Database connectie mislukt</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database fout: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='check_database_simple.php'>Controleer Database</a></p>";
echo "<p><a href='create_database.php'>Maak Database</a></p>";
echo "<p><a href='login.html'>Ga naar Login Pagina</a></p>";
echo "<p><a href='../index.html'>Ga naar Website</a></p>";
?> 