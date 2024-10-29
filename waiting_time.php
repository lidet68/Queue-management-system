<?php 
// Start the session
session_start();

// Check if the user is logged in and department is set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['department'])) {
    header("Location: login.php");
    exit();
}

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

// Get the token number from the request (GET or POST)
$token_number = isset($_GET['token_number']) ? $_GET['token_number'] : null;

if ($token_number) {
    // Prepare the SQL to fetch the patient details based on token number
    $sql = "
        SELECT 
            p.name, 
            p.case_description, 
            IF(p.emergency = 'yes', 0, COUNT(a.id) * 20)-20 AS waiting_time
        FROM 
            patients p
        LEFT JOIN 
            appointments a ON p.token_number = a.token_number
        WHERE 
            p.token_number = ?
        GROUP BY 
            p.token_number, p.name, p.case_description
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
    } else {
        $patient = null; // No patient found
    }

    // Close the statement
    $stmt->close();
} else {
    $patient = null; // No token number provided
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting Time</title>
</head>
<body>
    <h1>Waiting Time Information</h1>

    <?php if ($patient): ?>
        <p><strong>Token Number:</strong> <?php echo htmlspecialchars($token_number); ?></p>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['name']); ?></p>
        <p><strong>Case Description:</strong> <?php echo htmlspecialchars($patient['case_description']); ?></p>
        <p><strong>Estimated Waiting Time:</strong> <?php echo htmlspecialchars($patient['waiting_time']); ?> minutes</p>
    <?php else: ?>
        <p>No patient found for the provided token number.</p>
    <?php endif; ?>
    
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
