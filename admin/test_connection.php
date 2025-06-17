<?php
/*
    AFC Website - Database Connectie Test
    =====================================
    
    Dit script test de database connectie en login functionaliteit.
    
    Gebruik: Ga naar admin/test_connection.php in je browser
    Auteur: AFC Development Team
    Laatste update: 2024
*/

// Schakel foutrapportage in voor debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AFC Database Connectie Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

try {
    // Test 1: Database configuratie laden
    echo "<h2>1. Database Configuratie</h2>";
    if (file_exists('../config/database.php')) {
        echo "<p class='success'>✓ Database configuratie bestand gevonden</p>";
        require_once '../config/database.php';
    } else {
        throw new Exception('Database configuratie bestand niet gevonden');
    }
    
    // Test 2: Database connectie
    echo "<h2>2. Database Connectie</h2>";
    $conn = getDB();
    if ($conn) {
        echo "<p class='success'>✓ Database connectie succesvol</p>";
    } else {
        throw new Exception('Database connectie mislukt');
    }
    
    // Test 3: Users tabel controleren
    echo "<h2>3. Users Tabel</h2>";
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ Users tabel bestaat</p>";
        
        // Controleer aantal gebruikers
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        echo "<p class='info'>Aantal gebruikers: {$userCount}</p>";
        
        // Toon alle gebruikers
        $stmt = $conn->query("SELECT id, username, email, role FROM users");
        $users = $stmt->fetchAll();
        
        if (count($users) > 0) {
            echo "<h3>Gebruikers in Database:</h3>";
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
    
    // Test 4: Login test
    echo "<h2>4. Login Test</h2>";
    if (isset($_POST['test_login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        echo "<p class='info'>Test login voor gebruiker: {$username}</p>";
        
        // Haal gebruiker op
        $stmt = $conn->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p class='success'>✓ Gebruiker gevonden</p>";
            
            // Test wachtwoord
            if (password_verify($password, $user['password'])) {
                echo "<p class='success'>✓ Wachtwoord correct</p>";
                echo "<p class='success'>Login zou succesvol moeten zijn!</p>";
            } else {
                echo "<p class='error'>✗ Wachtwoord incorrect</p>";
            }
        } else {
            echo "<p class='error'>✗ Gebruiker niet gevonden</p>";
        }
    }
    
    // Test 5: Sessie test
    echo "<h2>5. Sessie Test</h2>";
    session_start();
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "<p class='success'>✓ Sessies werken</p>";
    } else {
        echo "<p class='error'>✗ Sessies werken niet</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Fout: " . $e->getMessage() . "</p>";
}

// Test login formulier
echo "<h2>Test Login</h2>";
echo "<form method='POST'>";
echo "<p><label>Gebruikersnaam: <input type='text' name='username' required></label></p>";
echo "<p><label>Wachtwoord: <input type='password' name='password' required></label></p>";
echo "<p><input type='submit' name='test_login' value='Test Login'></p>";
echo "</form>";

echo "<hr>";
echo "<p><a href='check_admin.php'>Controleer Admin Gebruikers</a></p>";
echo "<p><a href='login.html'>Ga naar Login Pagina</a></p>";
echo "<p><a href='../index.html'>Ga naar Website</a></p>";
?> 