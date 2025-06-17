<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
$logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if ($logged_in) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'username' => $_SESSION['admin_username'] ?? '',
            'role' => $_SESSION['admin_role'] ?? '',
            'email' => $_SESSION['admin_email'] ?? ''
        ]
    ]);
} else {
    echo json_encode([
        'logged_in' => false,
        'message' => 'Not logged in'
    ]);
}
?> 