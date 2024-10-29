<?php
// Database credentials
$servername = "localhost";
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$dbname = "QMS";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch all patients
$sql = "SELECT id, name, case_description, age, address, sex, regular, emergency, registration_date FROM patients";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #E1E1E1;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #D9D9D9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            margin: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #F0F0F0;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Patient List</h1>
        <?php
        if ($result->num_rows > 0) {
            // Output data in table format
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Case</th><th>Age</th><th>Address</th><th>Sex</th><th>Regular</th><th>Emergency</th><th>Registration Date</th></tr>";
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . $row["name"] . "</td>";
                echo "<td>" . $row["case_description"] . "</td>";
                echo "<td>" . $row["age"] . "</td>";
                echo "<td>" . $row["address"] . "</td>";
                echo "<td>" . $row["sex"] . "</td>";
                echo "<td>" . $row["regular"] . "</td>";
                echo "<td>" . $row["emergency"] . "</td>";
                echo "<td>" . $row["registration_date"] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No patients found.</p>";
        }
        // Close connection
        $conn->close();
        ?>
    </div>
</body>
</html>
