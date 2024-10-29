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

// Function to generate a unique random ticket number
function generateUniqueTicketNumber($conn) {
    do {
        // Generate a random alphanumeric string of 8 characters
        $ticketNumber = strtoupper(bin2hex(random_bytes(4)));  // 8 characters
        // Check if the ticket number already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE ticket_number = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param('s', $ticketNumber);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    } while ($count > 0); // Repeat if the ticket number is not unique

    return $ticketNumber;
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

// Function to check if the 20-minute gap is respected and suggest the next available time
// Alert only if the same case exists with the same time
function isAppointmentTimeAvailable($conn, $book_for, $time, $case, &$suggested_time) {
    // Check if there is a previous appointment with the same case
    $stmt = $conn->prepare("SELECT appointment_time FROM appointments WHERE book_for = ? AND `case` = ? ORDER BY appointment_time DESC LIMIT 1");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('ss', $book_for, $case);
    $stmt->execute();
    $stmt->bind_result($latest_time);
    $stmt->fetch();
    $stmt->close();

    if ($latest_time) {
        // Convert times to DateTime objects
        $latestTimeObj = new DateTime($latest_time);
        $newTimeObj = new DateTime($time);

        // Calculate the difference
        $timeDifference = $latestTimeObj->diff($newTimeObj);
        $minutesDiff = ($timeDifference->h * 60) + $timeDifference->i; // Total minutes

        if ($minutesDiff < 20) {
            // Calculate the next available time (20 minutes after the latest appointment)
            $latestTimeObj->modify('+20 minutes');
            $suggested_time = $latestTimeObj->format('H:i'); // Suggested available time
            return false; // Less than 20 minutes difference
        }
    }

    return true;
}

// Handle form submission for appointments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book-for'])) {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $case = $_POST['case'];
    $book_for = $_POST['book-for'];
    $time = $_POST['time'];

    // Variable to hold the suggested time if conflict occurs
    $suggested_time = "";

    // Check if the 20-minute gap is respected only for the same case
    if (!isAppointmentTimeAvailable($conn, $book_for, $time, $case, $suggested_time)) {
        echo "<script>
            alert('Sorry, the selected time for the same case is too close to the previous appointment. Please select a time at least 20 minutes later. Suggested time: " . $suggested_time . "');
            window.history.back();
        </script>";
        exit();
    }

    // Continue with booking if time is available
    $tokenNumber = generateNextTokenNumber($conn, $book_for);
    
    // Generate a unique ticket number
    $ticketNumber = generateUniqueTicketNumber($conn);

    $stmt = $conn->prepare("INSERT INTO appointments (name, age, phone, address, `case`, book_for, appointment_time, token_number, ticket_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param('sisssssss', $name, $age, $phone, $address, $case, $book_for, $time, $tokenNumber, $ticketNumber);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to ticket_display.php with the ticket number
        header("Location: ticket_display.php?ticket_number=" . urlencode($ticketNumber));
        exit();
    } else {
        echo "Execute failed: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch departments to populate the dropdown
if (isset($_GET['action']) && $_GET['action'] === 'fetch_departments') {
    $departments = [];
    $sql = "SELECT id, name FROM departments WHERE name != 'Reception'";
    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $departments[] = $row;
            }
        } else {
            echo "No departments found.";
        }
    } else {
        echo "SQL error: " . $conn->error;
    }

    // Output departments as JSON
    header('Content-Type: application/json');
    echo json_encode($departments);
}

// Close the connection
$conn->close();
?>
