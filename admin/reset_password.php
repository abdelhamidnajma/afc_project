<?php
/*
    AFC Website - Wachtwoord Reset
    ==============================
    
    Dit script reset het wachtwoord van een gebruiker naar een bekende waarde.
    
    Gebruik: Ga naar admin/reset_password.php in je browser
    Auteur: AFC Development Team
    Laatste update: 2024
*/

// Schakel foutrapportage in voor debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Importeer database configuratie
require_once '../config/database.php';

echo "<h1>AFC Wachtwoord Reset</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Verwerk formulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? 'Admin123!';
    
    echo "<h2>Wachtwoord Reset Resultaat</h2>";
    
    try {
        // Verbinding maken met database
        $conn = getDB();
        if (!$conn) {
            throw new Exception('Database connectie mislukt');
        }
        
        // Controleer of gebruiker bestaat
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("Gebruiker '{$username}' niet gevonden");
        }
        
        // Hash nieuw wachtwoord
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update wachtwoord
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
        
        echo "<p class='success'>✓ Wachtwoord succesvol gereset voor gebruiker '{$username}'</p>";
        echo "<p class='info'>Nieuw wachtwoord: {$newPassword}</p>";
        
        // Toon gebruiker gegevens
        echo "<h3>Gebruiker Gegevens:</h3>";
        echo "<ul>";
        echo "<li>ID: {$user['id']}</li>";
        echo "<li>Gebruikersnaam: {$user['username']}</li>";
        echo "<li>E-mail: {$user['email']}</li>";
        echo "<li>Rol: {$user['role']}</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Fout: " . $e->getMessage() . "</p>";
    }
}

// Toon alle gebruikers
echo "<h2>Beschikbare Gebruikers:</h2>";
try {
    $conn = getDB();
    if ($conn) {
        $stmt = $conn->query("SELECT id, username, email, role FROM users ORDER BY username");
        $users = $stmt->fetchAll();
        
        if (count($users) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>ID</th><th>Gebruikersnaam</th><th>E-mail</th><th>Rol</th>";
            echo "</tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['username']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['role']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>Geen gebruikers gevonden</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>Database fout: " . $e->getMessage() . "</p>";
}

// Reset formulier
echo "<h2>Wachtwoord Reset Formulier</h2>";
echo "<form method='POST'>";
echo "<p><label>Gebruikersnaam: <input type='text' name='username' required></label></p>";
echo "<p><label>Nieuw wachtwoord: <input type='text' name='new_password' value='Admin123!'></label></p>";
echo "<p><input type='submit' value='Reset Wachtwoord'></p>";
echo "</form>";

echo "<hr>";
echo "<p><a href='simple_login.php'>Test Login</a></p>";
echo "<p><a href='login.html'>Ga naar Login Pagina</a></p>";
echo "<p><a href='../index.html'>Ga naar Website</a></p>";
?> 