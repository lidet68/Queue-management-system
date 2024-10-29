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

// Get form data
$name = $_POST['patient-name'];
$case_description = $_POST['case']; // Assuming the column is named case_description
$age = $_POST['age'];
$address = $_POST['address'];
$phone = $_POST['phone'];
$sex = $_POST['sex'];
$emergency = $_POST['emergency'];

// Function to generate the next queue number
function generateNextQueueNumber($conn) {
    $date = date('Y-m-d');
    
    // Count the number of patients and appointments registered today
    $sql = "SELECT COUNT(*) FROM patients WHERE DATE(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $stmt->bind_result($patient_count);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $stmt->bind_result($appointment_count);
    $stmt->fetch();
    $stmt->close();

    // Calculate the next queue number
    $next_queue_number = $patient_count + $appointment_count + 1;

    return $next_queue_number;
}

// Generate the next queue number
$queue_number = generateNextQueueNumber($conn);

// Prepare SQL statement to insert patient data
$sql = "INSERT INTO patients (name, case_description, age, address, phone, sex, emergency, queue_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters
if (!$stmt->bind_param("ssiisssi", $name, $case_description, $age, $address, $phone, $sex, $emergency, $queue_number)) {
    die("Bind failed: " . $stmt->error);
}

// Execute the statement
if ($stmt->execute()) {
    echo "New patient registered successfully. Queue Number: " . $queue_number;
} else {
    echo "Execute failed: " . $stmt->error;
}

// Close connections
$stmt->close();
$conn->close();
?>
