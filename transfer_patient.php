<?php
// Start the session
session_start();

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

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get patient ID and department from the form
    $patient_id = $_POST['patient_id'];
    $department = $_POST['department'];

    // Ensure patient ID is valid
    if (empty($patient_id)) {
        die("Invalid patient ID.");
    }

    // Prepare the transfer query
    $sql = "UPDATE patients SET case_description = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters and execute the query
    $stmt->bind_param("si", $department, $patient_id);
    if ($stmt->execute()) {
        header("Location: staffdash.php");
        exit(); // Always exit after a header redirect
    } else {
        echo "Failed to transfer patient: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>
