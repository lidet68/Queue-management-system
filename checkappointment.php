<?php
// Database connection settings
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "QMS";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$name = isset($_POST['patient-name']) ? $_POST['patient-name'] : '';
$mobile = isset($_POST['mobile']) ? $_POST['mobile'] : '';
$ticketNumber = isset($_POST['ticket-number']) ? $_POST['ticket-number'] : '';

// Prepare SQL query
$sql = "SELECT name, phone, book_for, token_number FROM appointments WHERE 1=1";
$params = [];
$types = '';

// Filter by name
if (!empty($name)) {
    $sql .= " AND name = ?";
    $params[] = $name;
    $types .= 's'; // string
}

// Filter by mobile number
if (!empty($mobile)) {
    $sql .= " AND phone = ?";
    $params[] = $mobile;
    $types .= 's'; // string
}

// Filter by ticket number
if (!empty($ticketNumber)) {
    $sql .= " AND ticket_number = ?";
    $params[] = $ticketNumber;
    $types .= 's'; // string
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch appointment details
if ($result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
    // Output as JSON
    header('Content-Type: application/json');
    echo json_encode($appointment);
} else {
    echo json_encode(["message" => "No appointments found."]);
}

$stmt->close();
$conn->close();
?>
