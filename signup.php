<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    echo "Error fetching departments: " . $conn->error;
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $department_id = $_POST['department'];

    // Basic input validation
    if (empty($username) || empty($phone) || empty($password) || empty($confirm_password) || empty($department_id)) {
        echo "<p class='error'>All fields are required!</p>";
    } elseif ($password !== $confirm_password) {
        echo "<p class='error'>Passwords do not match!</p>";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the SQL statement to avoid SQL injection
        $stmt = $conn->prepare("INSERT INTO users (username, phone, password, department_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $phone, $hashed_password, $department_id);

        // Execute the statement
        if ($stmt->execute()) {
            header("Location: login.html");
            exit(); // Always exit after a header redirect
        } else {
            echo "<p class='error'>Error: " . $stmt->error . "</p>";
        }

        // Close the statement
        $stmt->close();
    }
}

// Close the connection
$conn->close();
?>
