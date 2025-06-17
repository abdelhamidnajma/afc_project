<?php
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Alleen GET requests zijn toegestaan'
    ]);
    exit;
}

try {
    $conn = getDBConnection();

    // Get optional parameters
    $year = (int)($_GET['year'] ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('n'));
    $type = $_GET['type'] ?? 'all';

    // Prepare SQL statement
    $sql = "SELECT id, title, description, date, time, location, type 
            FROM events";
    
    $params = [];
    $conditions = [];
    
    // Add year and month filter
    $conditions[] = "YEAR(date) = :year";
    $conditions[] = "MONTH(date) = :month";
    $params[':year'] = $year;
    $params[':month'] = $month;
    
    // Add type filter if specified
    if ($type !== 'all') {
        $conditions[] = "type = :type";
        $params[':type'] = $type;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    // Add ordering
    $sql .= " ORDER BY date ASC, time ASC";

    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'events' => $events,
        'year' => $year,
        'month' => $month
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 