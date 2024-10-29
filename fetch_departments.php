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

// Fetch departments to populate the dropdown
$departments = [];
$sql = "SELECT id, name FROM departments";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
} else {
    $departments = []; // No departments found or error occurred
}

// Close the connection
$conn->close();

// Output departments as JSON
header('Content-Type: application/json');
echo json_encode($departments);
?>
