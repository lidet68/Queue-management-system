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

// Fetch users along with their departments
$sql = "SELECT users.username, departments.name AS department_name 
        FROM users 
        JOIN departments ON users.department_id = departments.id";
$result = $conn->query($sql);

$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} else {
    $users = []; // No users found or error occurred
}

// Close the connection
$conn->close();

// Output users as JSON
header('Content-Type: application/json');
echo json_encode($users);
?>
