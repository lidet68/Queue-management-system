<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "QMS";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date
$current_date = date('Y-m-d');

// Fetch the next patient from the queue (emergency first, then appointments, then regular)
$sql = "
    SELECT token_number, name, case_description, emergency, registration_date, id, 'patients' AS table_name
    FROM patients 
    WHERE emergency = 'yes' AND DATE(registration_date) = '$current_date'
    UNION ALL
    SELECT a.token_number, a.name, a.case AS case_description, 'no' AS emergency, a.book_for AS registration_date, a.id, 'appointments' AS table_name
    FROM appointments a 
    WHERE DATE(a.book_for) = '$current_date'
    UNION ALL
    SELECT token_number, name, case_description, 'no' AS emergency, registration_date, id, 'patients' AS table_name
    FROM patients 
    WHERE emergency = 'no' AND DATE(registration_date) = '$current_date'
    ORDER BY registration_date ASC, 
        CASE WHEN emergency = 'yes' THEN 0 ELSE 1 END, 
        token_number ASC
    LIMIT 1
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Fetch the next patient
    $patient = $result->fetch_assoc();

    // Insert patient into the `past_patients` table
    $insert_sql = "
        INSERT INTO past_patients (token_number, name, case_description, emergency, registration_date)
        VALUES ('{$patient['token_number']}', '{$patient['name']}', '{$patient['case_description']}', '{$patient['emergency']}', '{$patient['registration_date']}')
    ";
    $conn->query($insert_sql);

    // Remove the patient from the current table
    if ($patient['table_name'] === 'patients') {
        $delete_sql = "DELETE FROM patients WHERE id = {$patient['id']}";
    } else {
        $delete_sql = "DELETE FROM appointments WHERE id = {$patient['id']}";
    }
    $conn->query($delete_sql);

    // Return the patient data as JSON
    echo json_encode($patient);
} else {
    echo json_encode(["name" => null]);
}

$conn->close();
?>
