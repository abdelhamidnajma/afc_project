<?php
// Check and Fix Admin User
echo "<h1>Admin Gebruiker Controleren en Repareren</h1>";

try {
    require_once '../config/database.php';
    $conn = getDB();
    
    if (!$conn) {
        throw new Exception("Database verbinding mislukt");
    }
    
    echo "<h2>1. Database Verbinding</h2>";
    echo "<p style='color: green;'>✓ Database verbinding succesvol</p>";
    
    // Check if admin user exists
    echo "<h2>2. Admin Gebruiker Controleren</h2>";
    $stmt = $conn->prepare("SELECT id, username, email, role, password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Admin gebruiker gevonden</p>";
        echo "<p>ID: " . $admin['id'] . "</p>";
        echo "<p>Username: " . htmlspecialchars($admin['username']) . "</p>";
        echo "<p>Email: " . htmlspecialchars($admin['email']) . "</p>";
        echo "<p>Role: " . htmlspecialchars($admin['role']) . "</p>";
        
        // Test password
        $testPassword = "Admin@123";
        if (password_verify($testPassword, $admin['password'])) {
            echo "<p style='color: green;'>✓ Wachtwoord is correct</p>";
        } else {
            echo "<p style='color: red;'>✗ Wachtwoord is incorrect - repareren...</p>";
            
            // Fix password
            $newPassword = password_hash($testPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
            $stmt->execute([$newPassword]);
            
            echo "<p style='color: green;'>✓ Wachtwoord gerepareerd</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin gebruiker niet gevonden - aanmaken...</p>";
        
        // Create admin user
        $password = password_hash("Admin@123", PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, role) 
            VALUES ('admin', 'admin@afc.nl', ?, 'admin')
        ");
        $stmt->execute([$password]);
        
        echo "<p style='color: green;'>✓ Admin gebruiker aangemaakt</p>";
    }
    
    // Test login logic
    echo "<h2>3. Login Logica Testen</h2>";
    $testUsername = "admin";
    $testPassword = "Admin@123";
    
    $stmt = $conn->prepare("SELECT id, username, password, email, role FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($testPassword, $user['password'])) {
        echo "<p style='color: green;'>✓ Login logica werkt correct</p>";
        echo "<p>Gebruiker: " . htmlspecialchars($user['username']) . "</p>";
        echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
        echo "<p>Role: " . htmlspecialchars($user['role']) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Login logica mislukt</p>";
    }
    
    // Show all users
    echo "<h2>4. Alle Gebruikers</h2>";
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
    }
    
    echo "<h2>5. Login Gegevens</h2>";
    echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<p><strong>Admin Login Gegevens:</strong></p>";
    echo "<p>Username: <code>admin</code></p>";
    echo "<p>Password: <code>Admin@123</code></p>";
    echo "<p>Email: <code>admin@afc.nl</code></p>";
    echo "</div>";
    
    echo "<h2>6. Test Links</h2>";
    echo "<p><a href='login.html' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Test Login Nu</a></p>";
    echo "<p><a href='simple_db_test.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Database Test</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Fout: " . $e->getMessage() . "</p>";
    echo "<p><a href='setup_database.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Database Setup Uitvoeren</a></p>";
}
?> 