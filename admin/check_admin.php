<?php
/*
    AFC Website - Admin Gebruiker Controle Script
    =============================================
    
    Dit script controleert of er een admin gebruiker bestaat in de database
    en maakt er een aan als dat niet het geval is.
    
    Gebruik: Ga naar admin/check_admin.php in je browser
    Auteur: AFC Development Team
    Laatste update: 2024
*/

// Schakel foutrapportage in voor debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start sessie en importeer benodigde bestanden
session_start();
require_once '../config/database.php';
require_once 'validation.php';

// Initialiseer database
initializeDatabase();

echo "<h1>AFC Admin Gebruiker Controle</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

try {
    $conn = getDB();
    if (!$conn) {
        throw new Exception('Database connectie mislukt');
    }
    
    echo "<p class='info'>✓ Database connectie succesvol</p>";
    
    // Controleer of users tabel bestaat
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        throw new Exception('Users tabel bestaat niet');
    }
    
    echo "<p class='info'>✓ Users tabel gevonden</p>";
    
    // Controleer of er admin gebruikers bestaan
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminUsers = $stmt->fetchAll();
    
    if (count($adminUsers) > 0) {
        echo "<p class='success'>✓ Admin gebruikers gevonden:</p>";
        echo "<ul>";
        foreach ($adminUsers as $user) {
            echo "<li><strong>{$user['username']}</strong> ({$user['email']}) - {$user['role']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>✗ Geen admin gebruikers gevonden</p>";
        
        // Maak een admin gebruiker aan
        $adminUsername = 'admin';
        $adminEmail = 'admin@afc.nl';
        $adminPassword = 'admin123'; // Dit moet je later wijzigen!
        
        // Hash wachtwoord
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        
        // Controleer of gebruikersnaam al bestaat
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$adminUsername]);
        if ($stmt->fetch()) {
            echo "<p class='error'>✗ Gebruikersnaam 'admin' bestaat al</p>";
        } else {
            // Maak admin gebruiker aan
            $stmt = $conn->prepare('
                INSERT INTO users (username, email, password, role, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ');
            $stmt->execute([$adminUsername, $adminEmail, $hashedPassword, 'admin']);
            
            echo "<p class='success'>✓ Admin gebruiker aangemaakt!</p>";
            echo "<p><strong>Gebruikersnaam:</strong> {$adminUsername}</p>";
            echo "<p><strong>Wachtwoord:</strong> {$adminPassword}</p>";
            echo "<p class='error'><strong>BELANGRIJK:</strong> Wijzig dit wachtwoord na het inloggen!</p>";
        }
    }
    
    // Toon alle gebruikers
    echo "<h2>Alle Gebruikers</h2>";
    $stmt = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $allUsers = $stmt->fetchAll();
    
    if (count($allUsers) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Gebruikersnaam</th><th>E-mail</th><th>Rol</th><th>Aangemaakt</th></tr>";
        foreach ($allUsers as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>Geen gebruikers gevonden</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Fout: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='login.html'>Ga naar Login Pagina</a></p>";
echo "<p><a href='../index.html'>Ga naar Website</a></p>";
?> 