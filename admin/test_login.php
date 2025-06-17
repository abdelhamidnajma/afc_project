<?php
// Test Login and Registration System
echo "<h1>Login and Registration System Test</h1>";

// Include database configuration
require_once '../config/database.php';

try {
    $conn = getDB();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test 1: Check if users table exists
    echo "<h2>1. Database Tables Check</h2>";
    $tables = ['users', 'activity_log'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✓ Table '$table' exists (" . $result['count'] . " records)</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Table '$table' missing: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test 2: Check admin user
    echo "<h2>2. Admin User Check</h2>";
    $stmt = $conn->query("SELECT username, email, role FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($admins) > 0) {
        echo "<p style='color: green;'>✓ Admin users found:</p>";
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>" . htmlspecialchars($admin['username']) . " (" . htmlspecialchars($admin['email']) . ") - " . htmlspecialchars($admin['role']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ No admin users found</p>";
    }
    
    // Test 3: Test password hashing
    echo "<h2>3. Password Hashing Test</h2>";
    $testPassword = "Test@123";
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
    $verifyResult = password_verify($testPassword, $hashedPassword);
    
    if ($verifyResult) {
        echo "<p style='color: green;'>✓ Password hashing and verification works</p>";
    } else {
        echo "<p style='color: red;'>✗ Password hashing failed</p>";
    }
    
    // Test 4: Test activity logging
    echo "<h2>4. Activity Logging Test</h2>";
    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_log (action, entity_type, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'test',
            'system',
            json_encode(['message' => 'Test activity log entry']),
            '127.0.0.1',
            'Test User Agent'
        ]);
        echo "<p style='color: green;'>✓ Activity logging works</p>";
        
        // Clean up test entry
        $conn->exec("DELETE FROM activity_log WHERE action = 'test'");
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Activity logging failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 5: Show login credentials
    echo "<h2>5. Login Information</h2>";
    echo "<p><strong>Default Admin Login:</strong></p>";
    echo "<p>Username: <code>admin</code></p>";
    echo "<p>Password: <code>Admin@123</code></p>";
    echo "<p>Email: <code>admin@afc.nl</code></p>";
    
    echo "<h2>6. Test Links</h2>";
    echo "<p><a href='login.html' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Test Login Page</a></p>";
    echo "<p><a href='register.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Test Registration Page</a></p>";
    echo "<p><a href='setup_database.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>Setup Database</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Solution:</strong> Run the database setup script first</p>";
    echo "<p><a href='setup_database.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Setup Database</a></p>";
}

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?> 