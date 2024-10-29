<?php
// Start session or use any preferred method to fetch ticket data
// Start output buffering to capture the output and handle it later
ob_start();

// Check if ticket number is set in the query string
if (!isset($_GET['ticket_number'])) {
    // Redirect to an error page or handle the error if ticket number is missing
    header("Location: error.html");
    exit();
}

// Retrieve the ticket number from the query string
$ticketNumber = $_GET['ticket_number'];

// Database connection settings
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "QMS";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch appointment details using the ticket number
$stmt = $conn->prepare("SELECT name, age, phone, address, `case`, book_for FROM appointments WHERE ticket_number = ?");
$stmt->bind_param('s', $ticketNumber);
$stmt->execute();
$stmt->bind_result($name, $age, $phone, $address, $case, $book_for);
$stmt->fetch();
$stmt->close();

// Close the connection
$conn->close();

// Check if the appointment was found
if (!$name) {
    // Redirect to an error page or handle the error if the ticket number is not found
    header("Location: error.html");
    exit();
}

// Start the HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Ticket Information</title>
    <style>
        /* CSS Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #1E1E1E; /* Dark background */
            color: white;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 90%; /* Responsive width */
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        .top-rectangle {
            background-color: #62747c; /* Rectangle color */
            padding: 15px;
            border-radius: 8px 8px 0 0; /* Rounded top corners */
            display: flex;
            justify-content: space-between; /* Space between buttons */
            align-items: center; /* Center buttons vertically */
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }

        .header-btn {
            background-color: #D9D9D9;
            color: black;
            border: none;
            padding: 10px;
            font-size: 15px; /* Adjusted button text size */
            cursor: pointer;
            border-radius: 20px;
            flex: 1; /* Allow buttons to grow */
            margin: 10px; /* Adjust margin for better spacing */
            min-width: 150px; /* Set a minimum width for buttons */
        }

        .header-btn:hover {
            background-color: #B0B0B0;
        }

        .info-card {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }

        .information {
            font-size: 16px; /* Smaller text */
            margin-bottom: 5px; /* Reduced margin */
        }

        .thank-you {
            font-size: 14px; /* Smaller text */
            margin: 5px 0; /* Reduced margin */
        }

        .ticket-number {
            font-size: 18px; /* Smaller ticket text */
            color: black; /* Change text color to black */
            margin: 10px 0 0; /* Reduced top margin to move up */
        }

        .ticket-number .number {
            color: red; /* Ticket number color */
            font-size: 32px; /* Increased size of ticket number */
            font-weight: bold; /* Bold for the ticket number */
        }

        .done-btn {
            background-color: #D9D9D9;
            color: black;
            border: none;
            padding: 8px 16px; /* Smaller button padding */
            font-size: 14px; /* Smaller button text */
            cursor: pointer;
            border-radius: 5px;
            margin-top: 20px;
        }

        .done-btn:hover {
            background-color: #B0B0B0; /* Button hover effect */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-rectangle">
            <button class="header-btn" onclick="window.location.href='getappointment.html'">GET APPOINTMENT</button>
            <button class="header-btn" onclick="window.location.href='checkqueue.html'">CHECK QUEUE</button>
        </div>

        <div class="info-card">
            <div class="ticket-number">
                THANK YOU FOR FILLING THE FORM YOUR TICKET NUMBER IS<br>
                <span class="number"><?php echo htmlspecialchars($ticketNumber); ?></span>
            </div>
            <div class="information">INFORMATION</div>
            <div class="thank-you">THANK YOU FOR FILLING THE FORM</div>
            <button class="done-btn" onclick="window.location.href='checkqueue.html'">DONE</button>
        </div>
    </div>
</body>
</html>