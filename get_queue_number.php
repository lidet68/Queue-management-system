<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Database connection details
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "database_name";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get ticket number from POST request
$ticket_number = isset($_POST['ticket_number']) ? $_POST['ticket_number'] : '';

if (!empty($ticket_number)) {
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT token_number FROM patients WHERE ticket_number = ?");
    $stmt->bind_param("s", $ticket_number);
    $stmt->execute();
    $stmt->bind_result($token_number);

    // Fetch result
    if ($stmt->fetch()) {
        // Return the token number as JSON
        echo json_encode(['token_number' => $token_number]);
    } else {
        // Ticket number not found
        echo json_encode(['token_number' => null]);
    }

    // Close statement
    $stmt->close();
} else {
    echo json_encode(['token_number' => null]);
}

// Close connection
$conn->close();
?>
