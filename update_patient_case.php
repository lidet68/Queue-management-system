<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['patient_id']) || !isset($_POST['department']) || !isset($_POST['patient_source'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_POST['patient_id'];
$department_id = $_POST['department'];
$patient_source = $_POST['patient_source'];

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "QMS";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update query based on patient source
if ($patient_source === 'appointment') {
    $sql = "UPDATE appointments SET department_id = ? WHERE id = ?";
} else if ($patient_source === 'patient') {
    $sql = "UPDATE patients SET department_id = ? WHERE id = ?";
} else {
    die("Invalid patient source.");
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ii", $department_id, $patient_id);

if ($stmt->execute()) {
    echo "Patient's case updated successfully.";
} else {
    echo "Error updating patient case: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
