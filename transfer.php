<?php
// Start the session
session_start();

// Check if current patient data is available in the session
if (!isset($_SESSION['current_patient'])) {
    // Redirect back to dashboard or handle error
    header("Location: dashboard.php");
    exit();
}

// Get the current patient data from the session
$current_patient = $_SESSION['current_patient'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Patient</title>
    <style>
        /* Common styles */
        body {
            font-family: Arial, sans-serif;
            background-color: white; /* General background color */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #D9D9D9;
            padding: 15px; /* Reduced padding */
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 90%; /* Full width for smaller screens */
            max-width: 400px; /* Reduced max-width */
        }

        .transfer-form {
            margin: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
        }

        .submit-btn {
            padding: 10px 20px;
            background-color: #427C08;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #427C08;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="transfer-form">
            <h2>Transfer Patient: <?php echo htmlspecialchars($current_patient['name']); ?></h2>
            <p>Token Number: <?php echo htmlspecialchars($current_patient['token_number']); ?></p>

            <form action="transfer_patient.php" method="POST">
                <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($current_patient['id']); ?>">

                <div class="form-group">
                    <label for="department">Transfer to Department:</label>
                    <select id="department" name="department" required>
                        <!-- Options will be populated by JavaScript -->
                    </select>
                </div>

                <button type="submit" class="submit-btn">Transfer Patient</button>
            </form>
        </div>
    </div>
    <script>
        // Fetch departments and populate the dropdown
        fetch('get_departments.php')
            .then(response => response.json())
            .then(departments => {
                const select = document.getElementById('department');
                departments.forEach(department => {
                    const option = document.createElement('option');
                    option.value = department.name; // Assuming department has a 'name'
                    option.textContent = department.name; // Assuming department has a 'name'
                    select.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching departments:', error));

        function logout() {
            window.location.href = 'login.php'; // Redirect to login page
        }
    </script>
</body>
</html>