<?php
// Database credentials
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

// Get the current date
$current_date = date('Y-m-d');

// Function to get the next token number
function getNextTokenNumber($conn, $current_date) {
    // Check if a record for today exists
    $sql = "SELECT last_token_number FROM tokens WHERE date = '$current_date'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Record exists, fetch the last token number and increment
        $row = $result->fetch_assoc();
        $next_token_number = $row['last_token_number'] + 1;
        // Update the last token number for today
        $update_sql = "UPDATE tokens SET last_token_number = $next_token_number WHERE date = '$current_date'";
        $conn->query($update_sql);
    } else {
        // No record for today, start with token number 1
        $next_token_number = 1;
        // Insert new record for today
        $insert_sql = "INSERT INTO tokens (date, last_token_number) VALUES ('$current_date', $next_token_number)";
        $conn->query($insert_sql);
    }

    return $next_token_number;
}

// Generate a token number
$token_number = getNextTokenNumber($conn, $current_date);

// Your existing code to fetch and display patients
// ...

// Insert new patient with the generated token number
// Example SQL to insert new patient with token number
// $insert_patient_sql = "INSERT INTO patients (name, case_description, token_number, registration_date) VALUES ('John Doe', 'Flu', $token_number, '$current_date')";
// $conn->query($insert_patient_sql);

echo "Generated Token Number: " . $token_number;

// Close connection
$conn->close();
?>
