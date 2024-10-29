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

// SQL query to fetch emergency patients for today and future dates
$sql_emergency = "
    SELECT id, name, case_description, 'yes' AS emergency, DATE(registration_date) AS registration_date, NULL AS appointment_time ,token_number
    FROM patients 
    WHERE emergency = 'yes' AND DATE(registration_date) >= '$current_date'
    ORDER BY CASE WHEN DATE(registration_date) = '$current_date' THEN id END DESC
";

// SQL query to fetch patients with appointments after today
$sql_appointments = "
    SELECT a.id, a.name, a.case AS case_description, 'no' AS emergency, DATE(a.book_for) AS registration_date, a.appointment_time , a.token_number
    FROM appointments a
    WHERE DATE(a.book_for) >= '$current_date'
    ORDER BY CASE WHEN DATE(a.book_for) = '$current_date' THEN a.id END DESC
";

// SQL query to fetch non-emergency, non-appointment patients registered after today
$sql_non_emergency = "
    SELECT id, name, case_description, 'no' AS emergency, DATE(registration_date) AS registration_date, NULL AS appointment_time ,token_number
    FROM patients 
    WHERE emergency = 'no' AND DATE(registration_date) >= '$current_date'
    ORDER BY CASE WHEN DATE(registration_date) = '$current_date' THEN id END DESC
";

// Combine the queries using UNION ALL
$sql = "($sql_emergency) UNION ALL ($sql_appointments) UNION ALL ($sql_non_emergency) 
        ORDER BY 
            registration_date ASC, 
            token_number ASC,
            CASE 
                WHEN emergency = 'yes' THEN 0 
                WHEN appointment_time IS NOT NULL THEN 1 
                ELSE 2 
            END, 
            appointment_time ASC";

$result = $conn->query($sql);

// Check for SQL errors
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting List</title>
    <style>
        /* Common styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #E1E1E1; /* Common background color */
            margin: 0;
            padding: 20px;
            height: 100%;
            overflow-y: auto;
        }

        .headermain {
            width: 100%;
            background-color: #495B67;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .headermain h2 {
            margin: 0;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            background-color: #d9d9d9;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 120px auto; /* Increased space below the fixed header */
        }

        .dashboard {
            display: flex;
            margin-top: 0;
        }

        .menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-right: 20px;
        }

        .menu button {
            padding: 15px;
            background-color: #D9D9D9;
            color: black;
            border: 2px solid white;
            border-radius: 0;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
            font-size: 16px;
        }

        .menu button:first-child {
            border-top-right-radius: 15px;
        }

        .menu button:last-child {
            border-bottom-right-radius: 15px;
        }

        .menu button:hover {
            background-color: #b0b0b0;
            color: black;
        }

        /* Content styles */
        .content, .main-content {
            flex: 3;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .header {
            background-color: #F0F0F0;
            padding: 15px;
            border-bottom: 1px solid #ccc;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 5px 5px 0 0;
        }

        /* Form styles */
        .registration-form, .result {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #F8F8F8;
        }

        .registration-form h2, .main-content h2 {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            justify-content: flex-start;
        }

        .form-group label {
            flex: 1;
            margin-right: 5px;
            font-size: 14px;
            white-space: nowrap;
        }

        .form-group input[type="text"], 
        .form-group input[type="number"], 
        .form-group select {
            flex: 2;
            padding: 5px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* Button styles */
        .submit-button, .button-group {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .submit-button button, .btn {
            background-color: #555;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .submit-button button:hover, .btn:hover {
            background-color: #333;
        }

        .btn.next-queue, .btn.new-queue {
            background-color: #495B67;
            color: white;
        }

        /* Address styling */
        .form-group input[type="text"]#address {
            width: 100%;
            padding: 10px;
            border: 2px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group input[type="text"]#address:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.3);
        }

        .table-container {
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .date-divider {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 18px;
            color: #333;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }

        tr:hover {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="headermain">
        <h2> Reception</h2>
    </div>
    <div class="container">
        <div class="dashboard">
            <div class="menu">
                <button id="dashboardBtn">Dashboard</button>
                <button id="waitingListBtn">Waiting list</button>
                <button id="registerBtn">Register</button>
                <button id="checkAppointmentBtn">Check appointment</button>
                <button id="logoutBtn">Logout</button>
            </div>

            <div class="main-content">
                <div class="header">Upcoming Patient Appointments</div>
                <div class="table-container">
                <?php
                    if ($result->num_rows > 0) {
                        $prev_date = null;
                        $token_counter = 1; // Initialize the token counter

                        // Display emergency patients first
                        $emergency_patients = [];
                        while($row = $result->fetch_assoc()) {
                            if ($row['emergency'] == 'yes') {
                                $emergency_patients[] = $row;
                            }
                        }

                        // Print emergency patients
                        if (!empty($emergency_patients)) {
                            echo "<div class='date-divider'>Emergency Patients</div>";
                            echo "<table>";
                            echo "<tr>";
                            echo "<th>No</th>";
                            echo "<th>Name</th>";
                            echo "<th>Case</th>";
                            echo "<th>Priority</th>";
                            echo "<th>Token Number</th>"; // Added column for token number
                            echo "</tr>";

                            foreach ($emergency_patients as $patient) {
                                echo "<tr>";
                                echo "<td>-</td>"; // No token number for emergency patients
                                echo "<td>" . htmlspecialchars($patient["name"]) . "</td>";
                                echo "<td>" . htmlspecialchars($patient["case_description"]) . "</td>";
                                echo "<td>High</td>";
                                echo "<td>-</td>"; // No token number for emergency patients
                                echo "</tr>";
                            }
                            echo "</table>";
                        }

                        // Reset result pointer and fetch non-emergency patients
                        $result->data_seek(0);

                        $prev_date = null;
                        while($row = $result->fetch_assoc()) {
                            if ($row['emergency'] == 'no') {
                                if ($prev_date && $prev_date != $row['registration_date']) {
                                    // Close the previous table
                                    echo "</table>";
                                    $token_counter = 1; // Reset the token counter for each new date
                                }

                                // Print the date divider and table header if a new date section begins
                                if ($prev_date !== $row['registration_date']) {
                                    if ($prev_date !== null) {
                                        // Add a date divider for the new date
                                        echo "<div class='date-divider'>Date: " . htmlspecialchars($row['registration_date']) . "</div>";
                                    }
                                    echo "<table>";
                                    echo "<tr>";
                                    echo "<th>No</th>";
                                    echo "<th>Name</th>";
                                    echo "<th>Case</th>";
                                    echo "<th>Priority</th>";
                                    echo "<th>Appointment Time</th>";
                                    echo "<th>Token Number</th>"; // Added column for token number
                                    echo "</tr>";
                                }

                                // Assign a token number based on the descending order of IDs
                                $token_number = $token_counter;

                                echo "<tr>";
                                echo "<td>" . $token_counter . "</td>";
                                echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["case_description"]) . "</td>";
                                echo "<td>Low</td>";
                                echo "<td>" . ($row["appointment_time"] ? $row["appointment_time"] : '-') . "</td>";
                                echo "<td>" . ($row["token_number"] ? $row["token_number"] : '-') . "</td>"; // Display token number
                                echo "</tr>";

                                $prev_date = $row['registration_date'];
                                $token_counter++;
                            }
                        }
                        // Close the last table
                        echo "</table>";
                    } else {
                        echo "<p>No patients found.</p>";
                    }
                    $conn->close();
                ?>
                </div>
            </div>
        </div>
        <script>
            document.getElementById('dashboardBtn').onclick = function() {
                window.location.href = 'receptiondashboard.php';
            };
            document.getElementById('waitingListBtn').onclick = function() {
                window.location.href = 'waitinglist.php';
            };
            document.getElementById('registerBtn').onclick = function() {
                window.location.href = 'pat_reg.html';
            };
            document.getElementById('checkAppointmentBtn').onclick = function() {
                window.location.href = 'checkappointment.html';
            };
            document.getElementById('logoutBtn').onclick = function() {
                window.location.href = 'login.html';
            };
        </script>
    </div>
</body>
</html>