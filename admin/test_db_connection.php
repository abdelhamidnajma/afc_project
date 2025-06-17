<?php
// Test Database Connection for Login System
echo "<h1>Database Connection Test voor Login Systeem</h1>";

// Test 1: Check database configuration
echo "<h2>1. Database Configuratie Check</h2>";
try {
    require_once '../config/database.php';
    echo "<p style='color: green;'>✓ Database configuratie geladen</p>";
    echo "<p>Database Host: " . DB_HOST . "</p>";
    echo "<p>Database Name: " . DB_NAME . "</p>";
    echo "<p>Database User: " . DB_USER . "</p>";
    echo "<p>Database Charset: " . DB_CHARSET . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database configuratie fout: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Test database connection
echo "<h2>2. Database Verbinding Test</h2>";
try {
    $conn = getDB();
    if (!$conn) {
        throw new Exception("Geen database verbinding");
    }
    echo "<p style='color: green;'>✓ Database verbinding succesvol</p>";
    
    // Test basic query
    $stmt = $conn->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Huidige database: <strong>" . $result['current_db'] . "</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database verbinding mislukt: " . $e->getMessage() . "</p>";
    exit;
}

// Test 3: Check if required tables exist
echo "<h2>3. Database Tabellen Check</h2>";
$requiredTables = ['users', 'activity_log'];
$missingTables = [];

foreach ($requiredTables as $table) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✓ Tabel '$table' bestaat (" . $result['count'] . " records)</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Tabel '$table' ontbreekt: " . $e->getMessage() . "</p>";
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "<p style='color: red;'><strong>Oplossing:</strong> Voer het database setup script uit</p>";
    echo "<p><a href='setup_database.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Database Setup Uitvoeren</a></p>";
    exit;
}

// Test 4: Check admin user
echo "<h2>4. Admin Gebruiker Check</h2>";
try {
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Admin gebruiker gevonden:</p>";
        echo "<ul>";
        echo "<li>ID: " . $admin['id'] . "</li>";
        echo "<li>Username: " . htmlspecialchars($admin['username']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($admin['email']) . "</li>";
        echo "<li>Role: " . htmlspecialchars($admin['role']) . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Admin gebruiker niet gevonden</p>";
        echo "<p><a href='fix_admin_login.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Gebruiker Aanmaken</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fout bij controleren admin gebruiker: " . $e->getMessage() . "</p>";
}

// Test 5: Test password verification
echo "<h2>5. Wachtwoord Verificatie Test</h2>";
try {
    $testPassword = "Admin@123";
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && password_verify($testPassword, $result['password'])) {
        echo "<p style='color: green;'>✓ Wachtwoord verificatie werkt correct</p>";
    } else {
        echo "<p style='color: red;'>✗ Wachtwoord verificatie mislukt</p>";
        echo "<p><a href='fix_admin_login.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Wachtwoord Repareren</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fout bij wachtwoord test: " . $e->getMessage() . "</p>";
}

// Test 6: Test login_handler.php connection
echo "<h2>6. Login Handler Database Test</h2>";
try {
    // Simulate what login_handler.php does
    $testUsername = "admin";
    $testPassword = "Admin@123";
    
    $stmt = $conn->prepare("SELECT id, username, password, email, role FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($testPassword, $user['password'])) {
        echo "<p style='color: green;'>✓ Login handler database logica werkt</p>";
        echo "<p>Gebruiker gevonden: " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['email']) . ")</p>";
    } else {
        echo "<p style='color: red;'>✗ Login handler database logica mislukt</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fout bij login handler test: " . $e->getMessage() . "</p>";
}

// Test 7: Test auth.php connection
echo "<h2>7. Auth.php Database Test</h2>";
try {
    // Test the same logic as auth.php
    $testUsername = "admin";
    $testPassword = "Admin@123";
    
    $stmt = $conn->prepare("SELECT id, username, password, email, role FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($testPassword, $user['password'])) {
        echo "<p style='color: green;'>✓ Auth.php database logica werkt</p>";
    } else {
        echo "<p style='color: red;'>✗ Auth.php database logica mislukt</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fout bij auth.php test: " . $e->getMessage() . "</p>";
}

// Test 8: Test activity logging
echo "<h2>8. Activity Logging Test</h2>";
try {
    $stmt = $conn->prepare("
        INSERT INTO activity_log (action, entity_type, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'test_login',
        'system',
        json_encode(['message' => 'Database connection test']),
        '127.0.0.1',
        'Test Script'
    ]);
    echo "<p style='color: green;'>✓ Activity logging werkt</p>";
    
    // Clean up test entry
    $conn->exec("DELETE FROM activity_log WHERE action = 'test_login'");
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Activity logging mislukt: " . $e->getMessage() . "</p>";
}

// Test 9: Show all users
echo "<h2>9. Alle Gebruikers in Database</h2>";
try {
    $stmt = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Geen gebruikers gevonden in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fout bij ophalen gebruikers: " . $e->getMessage() . "</p>";
}

// Test 10: Login credentials
echo "<h2>10. Login Gegevens</h2>";
echo "<div style='background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; margin: 20px 0;'>";
echo "<p><strong>Admin Login Gegevens:</strong></p>";
echo "<p>Username: <code>admin</code></p>";
echo "<p>Password: <code>Admin@123</code></p>";
echo "<p>Email: <code>admin@afc.nl</code></p>";
echo "</div>";

echo "<h2>11. Test Links</h2>";
echo "<p><a href='login.html' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Test Login Pagina</a></p>";
echo "<p><a href='register.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Test Registratie Pagina</a></p>";
echo "<p><a href='fix_admin_login.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Admin Login Repareren</a></p>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Terug</a></p>";
?> 