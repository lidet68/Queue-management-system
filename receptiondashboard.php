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

// SQL query to fetch emergency patients for today and future dates along with their room numbers
$sql_emergency = "
    SELECT p.id, p.case_description, 'yes' AS emergency, DATE(p.registration_date) AS registration_date, 
           r.name AS room_name, NULL AS token_number, NULL AS appointment_time 
    FROM patients p
    LEFT JOIN rooms r ON p.case_description = r.department_name  -- Match case description with department name
    WHERE p.emergency = 'yes' AND DATE(p.registration_date) >= '$current_date'
    ORDER BY CASE WHEN DATE(p.registration_date) = '$current_date' THEN p.id END DESC
";

// SQL query to fetch patients with appointments after today along with their room numbers
$sql_appointments = "
    SELECT a.id, a.case AS case_description, 'no' AS emergency, DATE(a.book_for) AS registration_date, 
           r.name AS room_name, a.token_number, a.appointment_time
    FROM appointments a
    LEFT JOIN rooms r ON a.case = r.department_name  -- Match appointment case with department name
    WHERE DATE(a.book_for) >= '$current_date'
    ORDER BY CASE WHEN DATE(a.book_for) = '$current_date' THEN a.id END DESC
";

// SQL query to fetch non-emergency, non-appointment patients registered after today along with their room numbers
$sql_non_emergency = "
    SELECT p.id, p.case_description, 'no' AS emergency, DATE(p.registration_date) AS registration_date, 
           r.name AS room_name, p.token_number, NULL AS appointment_time 
    FROM patients p
    LEFT JOIN rooms r ON p.case_description = r.department_name  -- Match case description with department name
    WHERE p.emergency = 'no' AND DATE(p.registration_date) >= '$current_date'
    ORDER BY CASE WHEN DATE(p.registration_date) = '$current_date' THEN p.id END DESC
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
    <title>Reception Dashboard</title>
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
        .dashboard{
            width: 90%;
            max-width: 1200px;
            background-color: #d9d9d9;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 120px auto; /* Increased space below the fixed header */
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

        .dashboard {
            width: 90%;
            max-width: 1200px;
            background-color: #d9d9d9;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 120px auto; /* Increased space below the fixed header */
            display: flex;
            margin-top: 0;
        }

        .menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-right: 20px;
            margin-top:20px
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

        .content {
            flex: 3;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .queue-info {
    background-color: #f0f8ff;
    padding: 20px;
    border: 2px solid #4CAF50;
    border-radius: 8px;
    font-size: 26px;
    margin-bottom: 20px;
    text-align: center; /* Center the text */
}

.queue-info strong {
    color: red; /* Set the text color to red for strong elements */
}


        .up-next {
            margin: 20px 0;
            font-weight: bold;
        }

        .appointment {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            margin: 5px 0;
            background-color: #3f51b5;
            color: white;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="headermain">
        <h2>Reception Dashboard</h2>
    </div>

    <div class="dashboard">
        <div class="menu">
            <button id="dashboardBtn">Dashboard</button>
            <button id="waitingListBtn">Waiting list</button>
            <button id="registerBtn">Register</button>
            <button id="checkAppointmentBtn">Check appointment</button>
            <button id="logoutBtn">Logout</button>
        </div>

        <div class="content">
            <div class="queue-info">
                <?php 
                // Fetch the first entry
                if ($row = $result->fetch_assoc()) {
                    $first_appointment = htmlspecialchars($row['token_number'] ?? 'Emergency');
                    $first_room = htmlspecialchars($row['room_name'] ?? 'N/A');
                    echo "<p>Now: <strong>$first_appointment</strong></p>";
                    echo "<p>ROOM: <strong>$first_room</strong></p>";
                } else {
                    echo "<p>No upcoming appointments.</p>";
                }
                ?>
            </div>

            <div class="up-next">UP NEXT</div>

            <?php 
            // Display remaining entries
            while ($row = $result->fetch_assoc()): ?>
                <div class="appointment">
                    <span><?php echo htmlspecialchars($row['token_number'] ?? 'N/A'); ?></span>
                    <span><?php echo htmlspecialchars($row['room_name'] ?? 'N/A'); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        // JavaScript for menu actions
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
</body>
</html>

<?php 
$conn->close(); // Close the database connection
?>
