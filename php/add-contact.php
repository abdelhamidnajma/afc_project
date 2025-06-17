<?php
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Alleen POST requests zijn toegestaan'
    ]);
    exit;
}

// Get form data
$first_name = sanitizeInput($_POST['first_name'] ?? '');
$last_name = sanitizeInput($_POST['last_name'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$subject = sanitizeInput($_POST['subject'] ?? '');
$message = sanitizeInput($_POST['message'] ?? '');

// Validate required fields
$required_fields = ['first_name', 'last_name', 'email', 'subject', 'message'];
foreach ($required_fields as $field) {
    if (empty($$field)) {
        echo json_encode([
            'success' => false,
            'message' => "Veld '$field' is verplicht"
        ]);
        exit;
    }
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Ongeldig e-mailadres'
    ]);
    exit;
}

try {
    $conn = getDBConnection();

    // Prepare SQL statement
    $sql = "INSERT INTO contact_requests (first_name, last_name, phone, email, subject, message) 
            VALUES (:first_name, :last_name, :phone, :email, :subject, :message)";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    
    // Execute statement
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Bericht succesvol verzonden! We nemen zo snel mogelijk contact met u op.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Er is een fout opgetreden bij het verzenden van het bericht'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 