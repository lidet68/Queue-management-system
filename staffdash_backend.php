<?php
// Start the session
session_start();

// Check if the user is logged in and department is set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['department'])) {
    // Redirect to login if not logged in
    header("Location: login.php");
    exit();
}

// Get the department from the session
$department = $_SESSION['department'];

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "QMS";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare the SQL statement to fetch patients related to the logged-in user's department
$sql = "SELECT token_number, name, urgency, `case` FROM patients WHERE department = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

// Output the table rows
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['token_number']) . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['urgency']) . "</td>";
    echo "<td>" . htmlspecialchars($row['case']) . "</td>";
    echo "</tr>";
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
