<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "QMS";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$current_date = date('Y-m-d');

// SQL query to fetch the next patient
$sql = "
    SELECT token_number, name, case_description 
    FROM patients 
    WHERE DATE(registration_date) = '$current_date'
    ORDER BY id ASC 
    LIMIT 1"; // Get the next patient

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $patient = $result->fetch_assoc();
    echo json_encode($patient);
} else {
    echo json_encode(['error' => 'No more patients']);
}

$conn->close();
?>
