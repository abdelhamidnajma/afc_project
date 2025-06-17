<?php
/*
    AFC Website - Admin Gebruiker Aanmaken
    ======================================
    
    Dit script maakt een nieuwe admin gebruiker aan met bekende gegevens.
    
    Gebruik: Ga naar admin/create_admin.php in je browser
    Auteur: AFC Development Team
    Laatste update: 2024
*/

// Schakel foutrapportage in voor debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Importeer database configuratie
require_once '../config/database.php';

echo "<h1>AFC Admin Gebruiker Aanmaken</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

try {
    // Verbinding maken met database
    $conn = getDB();
    if (!$conn) {
        throw new Exception('Database connectie mislukt');
    }
    
    echo "<p class='success'>✓ Database connectie succesvol</p>";
    
    // Controleer of users tabel bestaat
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        throw new Exception('Users tabel bestaat niet. Maak eerst de database aan.');
    }
    
    echo "<p class='success'>✓ Users tabel bestaat</p>";
    
    // Admin gebruiker gegevens
    $adminUsername = 'admin';
    $adminEmail = 'admin@afc.nl';
    $adminPassword = 'Admin123!';
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    // Controleer of admin gebruiker al bestaat
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE username = ?");
    $stmt->execute([$adminUsername]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "<p class='info'>Admin gebruiker bestaat al:</p>";
        echo "<ul>";
        echo "<li>ID: {$existingUser['id']}</li>";
        echo "<li>Gebruikersnaam: {$existingUser['username']}</li>";
        echo "<li>E-mail: {$existingUser['email']}</li>";
        echo "<li>Rol: {$existingUser['role']}</li>";
        echo "</ul>";
        
        // Update wachtwoord naar bekende waarde
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $adminUsername]);
        
        echo "<p class='success'>✓ Wachtwoord bijgewerkt naar: {$adminPassword}</p>";
        
    } else {
        // Maak nieuwe admin gebruiker aan
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, role, is_active) 
            VALUES (?, ?, ?, 'admin', TRUE)
        ");
        $stmt->execute([$adminUsername, $adminEmail, $hashedPassword]);
        
        $userId = $conn->lastInsertId();
        echo "<p class='success'>✓ Nieuwe admin gebruiker aangemaakt (ID: {$userId})</p>";
    }
    
    // Toon alle gebruikers
    echo "<h2>Alle Gebruikers in Database:</h2>";
    $stmt = $conn->query("SELECT id, username, email, role, is_active, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Gebruikersnaam</th><th>E-mail</th><th>Rol</th><th>Actief</th><th>Aangemaakt</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            $status = $user['is_active'] ? 'Ja' : 'Nee';
            $created = date('d-m-Y H:i', strtotime($user['created_at']));
            
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$status}</td>";
            echo "<td>{$created}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>Geen gebruikers gevonden</p>";
    }
    
    echo "<h2>Login Gegevens:</h2>";
    echo "<div style='background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd;'>";
    echo "<p><strong>Gebruikersnaam:</strong> {$adminUsername}</p>";
    echo "<p><strong>Wachtwoord:</strong> {$adminPassword}</p>";
    echo "<p><strong>E-mail:</strong> {$adminEmail}</p>";
    echo "</div>";
    
    echo "<h2>Test Login:</h2>";
    echo "<p>Gebruik deze gegevens om in te loggen:</p>";
    echo "<ul>";
    echo "<li><a href='simple_login.php'>Eenvoudige Login Test</a></li>";
    echo "<li><a href='login.html'>Normale Login Pagina</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Fout: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), 'Users tabel bestaat niet') !== false) {
        echo "<p><a href='create_database.php'>Maak Database Aan</a></p>";
    }
}

echo "<hr>";
echo "<p><a href='check_database_simple.php'>Controleer Database</a></p>";
echo "<p><a href='simple_login.php'>Test Login</a></p>";
echo "<p><a href='login.html'>Ga naar Login Pagina</a></p>";
echo "<p><a href='../index.html'>Ga naar Website</a></p>";
?> 