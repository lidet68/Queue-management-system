<?php 
// Start the session
session_start();

// Check if the user is logged in and department is set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['department'])) {
    // Redirect to login if not logged in
    header("Location: login.php");
    exit();
}

// Get the department from the session
$department = $_SESSION['department'];

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

// Get current date
$current_date = date('Y-m-d');

// Prepare the SQL statements
$sql_emergency = "
    SELECT id, name, case_description, 'yes' AS emergency, DATE(registration_date) AS registration_date, NULL AS appointment_time, NULL AS token_number 
    FROM patients 
    WHERE emergency = 'yes' 
      AND DATE(registration_date) = '$current_date' 
      AND case_description = ?
    ORDER BY DATE(registration_date) DESC, id DESC
";

$sql_appointments = "
    SELECT a.id, a.name, a.case AS case_description, 'no' AS emergency, DATE(a.book_for) AS registration_date, a.appointment_time, a.token_number
    FROM appointments a
    WHERE DATE(a.book_for) = '$current_date' 
      AND a.case = ?
    ORDER BY DATE(a.book_for) DESC, a.id DESC
";

$sql_non_emergency = "
    SELECT id, name, case_description, 'no' AS emergency, DATE(registration_date) AS registration_date, NULL AS appointment_time, token_number 
    FROM patients 
    WHERE emergency = 'no' 
      AND DATE(registration_date) = '$current_date' 
      AND case_description = ?
    ORDER BY DATE(registration_date) DESC, id DESC
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

// Prepare the combined query
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

// Bind the department parameter for each query
$stmt->bind_param("sss", $department, $department, $department);

// Execute the combined query
$stmt->execute();
$result = $stmt->get_result();
if ($result === false) {
    die("Query failed: " . $stmt->error);
}

// Fetch all results into an array
$patients = [];
$waiting_time_per_patient = 20; // minutes

foreach ($result as $index => $row) {
    // Calculate waiting time
    $waiting_time = ($index)* $waiting_time_per_patient; // Index gives the position in the list
    $row['waiting_time'] = $waiting_time; // Add waiting time to the row
    $patients[] = $row;
}

// Get the current top patient (if any)
$current_patient = [
    'token_number' => 'N/A',
    'name' => 'N/A',
    'case_description' => 'N/A'
];

if (!empty($patients)) {
    $current_patient = [
        'token_number' => htmlspecialchars($patients[0]['token_number']),
        'name' => htmlspecialchars($patients[0]['name']),
        'case_description' => htmlspecialchars($patients[0]['case_description']),
        'id' => $patients[0]['id'] // Include ID for transfer purposes
    ];
    
    // Set the current patient in the session for transfer
    $_SESSION['current_patient'] = $current_patient;
}

// Close statements
$stmt->close();

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /* Your CSS styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #E1E1E1; /* Common background color */
            margin: 0;
            padding: 20px;
            height: 100%;
            overflow-y: auto;
        }
        

        .dashboard {
            background-color:#d9d9d9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .header {
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

        .user-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-grow: 1;
        }

        .header nav a {
            color: #ffffff;
            text-decoration: none;
            margin-left: 15px;
            transition: color 0.3s;
        }

        .header nav a:hover {
            color: #77B551;
        }

        .current-number {
            text-align: center;
            margin: 20px 0;
        }

        .current-number .number {
            font-size: 48px;
            color: #E63946;
        }

        .patient-info {
            text-align: center;
            margin: 20px 0;
        }

        .buttons {
            margin: 20px 0;
        }

        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn.transfer {
            background-color: #77B551;
            color: white;
        }

        .btn.no-show {
            background-color: #AC2828;
            color: white;
        }

        .btn.next {
            background-color: #495B67;
            color: white;
        }

        

        .btn:hover {
            opacity: 0.8;
        }

        .waiting-list {
            margin-top: 20px;
        }

        .waiting-list table {
            width: 100%;
            border-collapse: collapse;
        }

        .waiting-list th,
        .waiting-list td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .waiting-list th {
            background-color: #f2f2f2;
        }

        .waiting-list tr:hover {
            background-color: #e0e0e0;
        }
    </style>
    <script>
        

        async function getNextPatient() {
            try {
                const response = await fetch('next_patient.php');
                const patient = await response.json();

                if (patient.name) {
                    document.querySelector('.current-number .number').innerText = patient.token_number || 'N/A';
                    document.querySelector('.patient-info h3').innerText = `Name: ${patient.name || 'N/A'}`;
                    document.querySelector('.patient-info p').innerText = `Case: ${patient.case_description || 'N/A'}`;

                    // Reload the current page after fetching the next patient
                    setTimeout(() => {
                        window.location.reload();  // Reload current page
                    }, 500);  // Delay to show new patient

                    // Open another page and reload it (if already open)
                    setTimeout(() => {
                        window.open('waitinglist.php', '_blank');  // Replace 'otherpage.html' with your page
                    }, 500);  // Adjust this as per the delay needed
                } else {
                    alert('No more patients in the queue.');
                }
            } catch (error) {
                console.error('Error fetching next patient:', error);
                alert('Failed to fetch the next patient.');
            }
        }

        function goToTransfer() {
            window.location.href = 'transfer.php';
        }
       
        async function markNoShow() {
            try {
       
                    const response = await fetch('no_show.php');
                    const patient = await response.json();

if (patient.name) {
    document.querySelector('.current-number .number').innerText = patient.token_number || 'N/A';
    document.querySelector('.patient-info h3').innerText = `Name: ${patient.name || 'N/A'}`;
    document.querySelector('.patient-info p').innerText = `Case: ${patient.case_description || 'N/A'}`;

    // Reload the current page after fetching the next patient
    setTimeout(() => {
        window.location.reload();  // Reload current page
    }, 500);  // Delay to show new patient

    // Open another page and reload it (if already open)
    setTimeout(() => {
        window.open('waitinglist.php', '_blank');  // Replace 'otherpage.html' with your page
    }, 500);  // Adjust this as per the delay needed
} else {
    alert('No more patients in the queue.');
}
} catch (error) {
console.error('Error fetching next patient:', error);
alert('Failed to fetch the next patient.');
}
        }

        function logout() {
            window.location.href = 'login.php';
        }
    </script>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> </span>
                <nav>
                    <a href="staffdash.php" onclick="location.reload();">Dashboard</a>
                    
                    <a href="login.html" onclick="logout();">Logout</a>
                </nav>
            </div>
        </header>

        <div class="current-number">
            <h2>Current Number</h2>
            <span class="number"><?php echo $current_patient['token_number']; ?></span>
        </div>

        <div class="patient-info">
            <h3>Name: <?php echo $current_patient['name']; ?></h3>
            <p>Case: <?php echo $current_patient['case_description']; ?></p>
            <div class="buttons">
                <button class="btn transfer" onclick="goToTransfer()">Transfer Patient</button>
              
                <button class="btn no-show"onclick="markNoShow()">No Show</button>
                <button class="btn next" onclick="getNextPatient()">Next Patient</button>
              

            </div>
        </div>

        <div class="waiting-list">
            <h3>Waiting List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Name</th>
                        <th>Case Description</th>
                        <th>Emergency</th>
                        <th>Waiting Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($patients)): ?>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['token_number']); ?></td>
                                <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['case_description']); ?></td>
                                <td><?php echo htmlspecialchars($patient['emergency']); ?></td>
                                <td><?php echo htmlspecialchars($patient['waiting_time']); ?> minutes</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No patients in the queue</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
