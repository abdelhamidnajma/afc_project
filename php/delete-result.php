<?php
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Result ID is verplicht'
    ]);
    exit;
}

try {
    $conn = getDBConnection();

    // Prepare SQL statement
    $sql = "DELETE FROM results WHERE id = :id";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':id', $data['id']);
    
    // Execute statement
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Uitslag succesvol verwijderd'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Geen uitslag gevonden met dit ID'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Er is een fout opgetreden bij het verwijderen van de uitslag'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 