<?php
require_once '../config/database.php';

try {
    $db = getDB();
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Test insert user
    $username = 'testuser_' . rand(1000, 9999);
    $email = $username . '@example.com';
    $password = password_hash('Test@1234', PASSWORD_DEFAULT);

    $stmt = $db->prepare('INSERT INTO users (username, email, password, role, created_at, updated_at) VALUES (?, ?, ?, "user", NOW(), NOW())');
    $stmt->execute([$username, $email, $password]);

    echo "User inserted successfully: $username, $email\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
