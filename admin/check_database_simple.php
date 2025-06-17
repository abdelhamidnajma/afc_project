<?php
/*
    AFC Website - Eenvoudige Database Check
    =======================================
    
    Dit script controleert of de database bestaat en toegankelijk is.
    
    Gebruik: Ga naar admin/check_database_simple.php in je browser
    Auteur: AFC Development Team
    Laatste update: 2024
*/

// Schakel foutrapportage in voor debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AFC Database Check</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

try {
    // Test 1: Basis MySQL connectie (zonder database)
    echo "<h2>1. MySQL Connectie Test</h2>";
    
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'afc_website';
    
    // Probeer eerst verbinding te maken zonder database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    echo "<p class='success'>✓ MySQL connectie succesvol</p>";
    
    // Test 2: Controleer of database bestaat
    echo "<h2>2. Database Bestaan</h2>";
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ Database '$dbname' bestaat</p>";
        
        // Test 3: Verbinding met specifieke database
        echo "<h2>3. Database Verbinding</h2>";
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        echo "<p class='success'>✓ Verbinding met '$dbname' succesvol</p>";
        
        // Test 4: Controleer users tabel
        echo "<h2>4. Users Tabel</h2>";
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ Users tabel bestaat</p>";
            
            // Test 5: Controleer aantal gebruikers
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt->fetch()['count'];
            echo "<p class='info'>Aantal gebruikers: {$userCount}</p>";
            
            if ($userCount > 0) {
                // Toon alle gebruikers
                $stmt = $pdo->query("SELECT id, username, email, role FROM users");
                $users = $stmt->fetchAll();
                
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
            } else {
                echo "<p class='error'>✗ Geen gebruikers gevonden</p>";
                echo "<p><a href='check_admin.php'>Maak Admin Gebruiker</a></p>";
            }
            
        } else {
            echo "<p class='error'>✗ Users tabel bestaat niet</p>";
            echo "<p><a href='check_database.php'>Maak Database Tabellen</a></p>";
        }
        
    } else {
        echo "<p class='error'>✗ Database '$dbname' bestaat niet</p>";
        echo "<p>Je moet eerst de database aanmaken in phpMyAdmin of via MySQL command line.</p>";
        echo "<p><a href='http://localhost/phpmyadmin' target='_blank'>Open phpMyAdmin</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Fout: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='test_connection.php'>Uitgebreide Test</a></p>";
echo "<p><a href='login.html'>Ga naar Login Pagina</a></p>";
echo "<p><a href='../index.html'>Ga naar Website</a></p>";
?> 