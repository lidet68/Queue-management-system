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

// Retrieve ticket number from POST request
$ticket_number = $_POST['ticket_number'] ?? '';

// Prepare SQL statement to get token number and case based on ticket number
$stmt = $conn->prepare("SELECT token_number, `case` FROM appointments WHERE ticket_number = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param('s', $ticket_number);
$stmt->execute();
$stmt->bind_result($token_number, $case);
$stmt->fetch();
$stmt->close();

// Check if case was found
if (!$case) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ticket number not found']);
    exit();
}

// Get current date
$current_date = date('Y-m-d');

// Prepare SQL statements for fetching patients by case
$sql_emergency = "
    SELECT id, name, case_description, 'yes' AS emergency, DATE(registration_date) AS registration_date, NULL AS appointment_time, NULL AS token_number 
    FROM patients 
    WHERE emergency = 'yes' 
      AND DATE(registration_date) = '$current_date' 
      AND case_description = '$case'
    ORDER BY DATE(registration_date) DESC, id DESC
";

$sql_appointments = "
    SELECT a.id, a.name, a.case AS case_description, 'no' AS emergency, DATE(a.book_for) AS registration_date, a.appointment_time, a.token_number
    FROM appointments a
    WHERE DATE(a.book_for) = '$current_date' 
      AND a.case = '$case'
    ORDER BY DATE(a.book_for) DESC, a.id DESC
";

$sql_non_emergency = "
    SELECT id, name, case_description, 'no' AS emergency, DATE(registration_date) AS registration_date, NULL AS appointment_time, token_number 
    FROM patients 
    WHERE emergency = 'no' 
      AND DATE(registration_date) = '$current_date' 
      AND case_description = '$case'
    ORDER BY DATE(registration_date) DESC, id DESC
";

// Combine the queries using UNION ALL
$sql = "($sql_emergency) UNION ALL ($sql_appointments) UNION ALL ($sql_non_emergency)  
        ORDER BY 
            registration_date ASC, 
            CASE 
                WHEN emergency = 'yes' THEN 0 
                WHEN appointment_time IS NOT NULL THEN 1 
                ELSE 2 
            END, 
            appointment_time ASC";

$result = $conn->query($sql);
$patients = [];
$specific_waiting_time = null;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
        if ($row['token_number'] == $token_number) {
            $specific_waiting_time = count($patients) * 20-20; // Calculate waiting time based on position
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'token_number' => $token_number,
    'specific_waiting_time' => $specific_waiting_time,
    'patients' => $patients
]);

$conn->close();
?>
