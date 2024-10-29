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

// Function to generate the next token number
function generateNextTokenNumber($conn, $date) {
    $stmt = $conn->prepare("INSERT INTO token_numbers (date, max_token_number) VALUES (?, 0) ON DUPLICATE KEY UPDATE max_token_number = max_token_number");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT max_token_number FROM token_numbers WHERE date = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $stmt->bind_result($maxToken);
    $stmt->fetch();
    $stmt->close();

    $nextToken = intval($maxToken) + 1;

    $stmt = $conn->prepare("UPDATE token_numbers SET max_token_number = ? WHERE date = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('is', $nextToken, $date);
    $stmt->execute();
    $stmt->close();

    return str_pad($nextToken, 4, '0', STR_PAD_LEFT);
}

// Handle form submission for patients
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient-name'])) {
    $name = $_POST['patient-name'];
    $case_description = $_POST['case'];
    $age = $_POST['age'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $sex = $_POST['sex'];
    $emergency = $_POST['emergency'];
    $registration_date = date('Y-m-d'); 

    $token_number = generateNextTokenNumber($conn, $registration_date);

    $sql = "INSERT INTO patients (name, case_description, age, address, phone, sex, emergency, registration_date, token_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    if (!$stmt->bind_param("ssiisssss", $name, $case_description, $age, $address, $phone, $sex, $emergency, $registration_date, $token_number)) {
        die("Bind failed: " . $stmt->error);
    }

    if ($stmt->execute()) {
        echo "<div style='color: green;'>New patient registered successfully. Token Number: " . $token_number . "</div>";
    } else {
        echo "Execute failed: " . $stmt->error;
    }

    $stmt->close();
}

// Close the connection
$conn->close();
?>
