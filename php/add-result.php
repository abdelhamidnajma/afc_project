<?php
require_once 'config.php';

// Headers instellen voor JSON response
header('Content-Type: application/json');

// JSON data ophalen uit request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verplichte velden valideren
$required_fields = ['competition', 'team1', 'team2', 'score1', 'score2', 'date'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode([
            'success' => false,
            'message' => "Veld '$field' is verplicht"
        ]);
        exit;
    }
}

try {
    $conn = getDBConnection();

    // SQL statement voorbereiden
    $sql = "INSERT INTO results (competition, team1, team2, score1, score2, date) 
            VALUES (:competition, :team1, :team2, :score1, :score2, :date)";
    
    $stmt = $conn->prepare($sql);
    
    // Parameters binden
    $stmt->bindParam(':competition', $data['competition']);
    $stmt->bindParam(':team1', $data['team1']);
    $stmt->bindParam(':team2', $data['team2']);
    $stmt->bindParam(':score1', $data['score1']);
    $stmt->bindParam(':score2', $data['score2']);
    $stmt->bindParam(':date', $data['date']);
    
    // Statement uitvoeren
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Uitslag succesvol toegevoegd',
            'id' => $conn->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Er is een fout opgetreden bij het toevoegen van de uitslag'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 