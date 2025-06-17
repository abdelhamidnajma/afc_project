<?php
session_start();
require_once '../config/database.php';

// Log de uitlogactie als gebruiker was ingelogd
if (isset($_SESSION['user_id'])) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address) 
            VALUES (?, 'logout', 'Gebruiker uitgelogd', ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Fout bij loggen uitlogactie: " . $e->getMessage());
    }
}

// Sessie vernietigen
session_destroy();

// Zelfs als loggen mislukt, nog steeds sessie vernietigen
$response = [
    'success' => true,
    'message' => 'Succesvol uitgelogd'
];

header('Content-Type: application/json');
echo json_encode($response);
?> 