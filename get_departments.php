<?php
// Database credentials
$servername = "localhost";
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$dbname = "QMS"; // Change this to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch departments, excluding 'Reception'
$sql = "SELECT id, name FROM departments WHERE name != 'Reception'";
$result = $conn->query($sql);

$departments = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Close the connection
$conn->close();

// Output departments as JSON
header('Content-Type: application/json');
echo json_encode($departments);
?>
