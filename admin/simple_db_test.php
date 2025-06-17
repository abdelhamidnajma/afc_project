<?php
// Simple Database Connection Test
echo "<h1>Database Verbinding Test</h1>";

// Test 1: Check if config file exists
echo "<h2>1. Configuratie Bestand</h2>";
$configPath = '../config/database.php';
if (file_exists($configPath)) {
    echo "<p style='color: green;'>✓ Configuratie bestand gevonden</p>";
} else {
    echo "<p style='color: red;'>✗ Configuratie bestand niet gevonden op: " . $configPath . "</p>";
    echo "<p>Huidige directory: " . __DIR__ . "</p>";
    exit;
}

// Test 2: Load configuration
echo "<h2>2. Configuratie Laden</h2>";
try {
    require_once $configPath;
    echo "<p style='color: green;'>✓ Configuratie geladen</p>";
    echo "<p>Database: " . DB_NAME . "</p>";
    echo "<p>Host: " . DB_HOST . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Configuratie fout: " . $e->getMessage() . "</p>";
    exit;
}

// Test 3: Database connection
echo "<h2>3. Database Verbinding</h2>";
try {
    $conn = getDB();
    if ($conn) {
        echo "<p style='color: green;'>✓ Database verbinding succesvol</p>";
        
        // Test query
        $stmt = $conn->query("SELECT DATABASE() as db");
        $result = $stmt->fetch();
        echo "<p>Huidige database: <strong>" . $result['db'] . "</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ Database verbinding mislukt</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database fout: " . $e->getMessage() . "</p>";
    exit;
}

// Test 4: Check users table
echo "<h2>4. Gebruikers Tabel</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Gebruikers tabel bestaat (" . $result['count'] . " gebruikers)</p>";
    
    // Show admin user
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
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
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Gebruikers tabel fout: " . $e->getMessage() . "</p>";
}

// Test 5: Test login logic
echo "<h2>5. Login Logica Test</h2>";
try {
    $testUsername = "admin";
    $testPassword = "Admin@123";
    
    $stmt = $conn->prepare("SELECT id, username, password, email, role FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($testPassword, $user['password'])) {
        echo "<p style='color: green;'>✓ Login logica werkt correct</p>";
        echo "<p>Gebruiker: " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['email']) . ")</p>";
    } else {
        echo "<p style='color: red;'>✗ Login logica mislukt</p>";
        if ($user) {
            echo "<p>Gebruiker gevonden maar wachtwoord incorrect</p>";
        } else {
            echo "<p>Gebruiker niet gevonden</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Login test fout: " . $e->getMessage() . "</p>";
}

// Test 6: Show all users
echo "<h2>6. Alle Gebruikers</h2>";
try {
    $stmt = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created</th></tr>";
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
        echo "<p>Geen gebruikers gevonden</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fout bij ophalen gebruikers: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Login Gegevens</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Admin Login:</strong></p>";
echo "<p>Username: <code>admin</code></p>";
echo "<p>Password: <code>Admin@123</code></p>";
echo "</div>";

echo "<h2>Test Links</h2>";
echo "<p><a href='login.html' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Test Login</a></p>";
echo "<p><a href='fix_admin_login.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Admin Repareren</a></p>";
?> 