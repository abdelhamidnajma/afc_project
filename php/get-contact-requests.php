<?php
require_once 'config.php';

// Headers instellen voor JSON response
header('Content-Type: application/json');

// Controleer of het een GET request is
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Alleen GET requests zijn toegestaan'
    ]);
    exit;
}

try {
    $conn = getDBConnection();

    // Optionele parameters ophalen
    $status = $_GET['status'] ?? 'all';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);

    // SQL statement voorbereiden
    $sql = "SELECT id, first_name, last_name, phone, email, subject, message, status, created_at 
            FROM contact_requests";
    
    $params = [];
    
    // Status filter toevoegen indien opgegeven
    if ($status !== 'all') {
        $sql .= " WHERE status = :status";
        $params[':status'] = $status;
    }
    
    // Sortering en paginering toevoegen
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = $conn->prepare($sql);
    
    // Parameters binden
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $contact_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Totaal aantal ophalen voor paginering
    $countSql = "SELECT COUNT(*) as total FROM contact_requests";
    if ($status !== 'all') {
        $countSql .= " WHERE status = :status";
    }
    
    $countStmt = $conn->prepare($countSql);
    if ($status !== 'all') {
        $countStmt->bindParam(':status', $status);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'contact_requests' => $contact_requests,
        'total' => $totalCount,
        'limit' => $limit,
        'offset' => $offset
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database fout: ' . $e->getMessage()
    ]);
}
?> 