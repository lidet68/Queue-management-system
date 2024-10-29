<?php
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

// Debugging: Output the request method
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['book-for'])) {
        $book_for = $_POST['book-for'];
        echo "Book For: " . htmlspecialchars($book_for) . "<br>";  // Debugging: Output the book_for value

        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT name, age, phone, address, `case`, appointment_time, ticket_number FROM appointments WHERE book_for = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param('s', $book_for);

        // Execute the statement
        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $appointments = [];
                while ($row = $result->fetch_assoc()) {
                    $appointments[] = $row;
                }

                // Output appointments as JSON
                header('Content-Type: application/json');
                echo json_encode($appointments);
            } else {
                echo json_encode(["message" => "No appointments found for the selected date."]);
            }
        } else {
            die("Execute failed: " . $stmt->error);
        }

        $stmt->close();
    } else {
        die("Error: 'book-for' field is missing.");
    }
} else {
    die("Error: Request method is not POST.");
}

$conn->close();
?>
