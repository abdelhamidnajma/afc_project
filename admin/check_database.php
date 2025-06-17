<?php


// Schakel foutrapportage in voor debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start sessie en importeer benodigde bestanden
session_start();
require_once '../config/database.php';

echo "<h1>Database Controle</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

try {
    $conn = getDB();
    echo "<p>✅ Database verbinding succesvol</p>";

    $tables = ['users', 'teams', 'matches', 'news', 'events', 'settings', 'activity_log'];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "<p>✅ Tabel '$table' bestaat</p>";
        } else {
            echo "<p>❌ Tabel '$table' bestaat niet</p>";
        }
    }

    $stmt = $conn->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    $requiredColumns = ['id', 'username', 'email', 'password', 'role'];
    foreach ($requiredColumns as $column) {
        $exists = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $column) {
                $exists = true;
                break;
            }
        }
        if ($exists) {
            echo "<p>✅ Kolom '$column' bestaat in users tabel</p>";
        } else {
            echo "<p>❌ Kolom '$column' ontbreekt in users tabel</p>";
        }
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>📊 Aantal gebruikers: " . $result['count'] . "</p>";

    if ($result['count'] == 0) {
        echo "<p>⚠️ Geen gebruikers gevonden. Maak een admin gebruiker aan.</p>";
        
        $hashedPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
        if ($stmt->execute(['admin', 'admin@afc.nl', $hashedPassword])) {
            echo "<p>✅ Admin gebruiker aangemaakt</p>";
        } else {
            echo "<p>❌ Fout bij aanmaken admin gebruiker</p>";
        }
    }

    foreach (['teams', 'matches', 'news', 'events'] as $table) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<p>📊 Aantal records in $table: " . $result['count'] . "</p>";
    }

    $testUser = ['username' => 'test_user', 'email' => 'test@test.com', 'password' => 'Test123!'];
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$testUser['username'], $testUser['email'], password_hash($testUser['password'], PASSWORD_DEFAULT)])) {
        echo "<p>✅ Test gebruiker toegevoegd</p>";
        
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$testUser['username']]);
        echo "<p>✅ Test gebruiker verwijderd</p>";
    } else {
        echo "<p>❌ Fout bij test gebruiker</p>";
    }

    $testPassword = 'Test123!';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
    if (password_verify($testPassword, $hashedPassword)) {
        echo "<p>✅ Wachtwoord verificatie werkt</p>";
    } else {
        echo "<p>❌ Wachtwoord verificatie werkt niet</p>";
    }

} catch (Exception $e) {
    echo "<p>❌ Database fout: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='check_admin.php'>Controleer Admin Gebruikers</a></p>";
echo "<p><a href='login.html'>Ga naar Login Pagina</a></p>";
echo "<p><a href='../index.html'>Ga naar Website</a></p>";
?> 