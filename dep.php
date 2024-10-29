<?php 
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "QMS";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle loading departments
if (isset($_GET['action']) && $_GET['action'] == 'load') {
    $sql = "SELECT * FROM departments";
    $result = $conn->query($sql);

    if ($result === false) {
        die("SQL Error: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row['name']) . 
                " <span class='edit-btn' onclick='editDepartment(" . intval($row['id']) . ", \"" . htmlspecialchars($row['name']) . "\")'>✏️</span>
                <span class='delete-btn' onclick='deleteDepartment(" . intval($row['id']) . ")'>🗑️</span></li>";
        }
    } else {
        echo "<li>No departments found</li>";
    }
    exit();
}

// Handle adding/editing a department
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['departmentName'])) {
    $departmentName = $conn->real_escape_string($_POST['departmentName']);
    
    if (isset($_POST['editDepartment'])) {
        // Editing
        $departmentId = intval($_POST['editDepartment']);
        $sql = "UPDATE departments SET name='$departmentName' WHERE id='$departmentId'";
    } else {
        // Adding
        $sql = "INSERT INTO departments (name) VALUES ('$departmentName')";
    }

    if ($conn->query($sql) === TRUE) {
        echo 'success';
    } else {
        echo 'error: ' . $conn->error;
    }
    exit();
}

// Handle deleting a department
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteDepartment'])) {
    $departmentId = intval($_POST['deleteDepartment']);
    $sql = "DELETE FROM departments WHERE id='$departmentId'";
    
    if ($conn->query($sql) === TRUE) {
        echo 'success';
    } else {
        echo 'error: ' . $conn->error;
    }
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php"); // Redirect to login page after logout
    exit();
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin</title>
    <style>
        /* Reset some default styles */
        body, h2, ul, li, p {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
        }

        body {
            display: flex;
            height: 100vh;
            background-color: #ffffff;
            flex-direction: column; /* To allow header on top and content below */
        }

        /* Header styles */
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

        .header h2 {
            margin: 0;
        }

        .logout-btn {
            color: #fff;
            background-color: #606a76;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #505962;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 150px;
            background-color: #D9D9D9;
            padding-top: 0px; /* Adjusted for fixed header */
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 50px; /* Adjusted for fixed header */
            bottom: 50px;
            left: 0;
            padding-bottom: 20px;
            border-radius: 5px;
            margin-top: 70px; /* Below the fixed header */
        }

        .sidebar ul {
            list-style: none;
            padding-left: 0;
            flex-grow: 1;
            margin-top: 20px;
        }

        .sidebar ul li {
            margin-bottom: 10px;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #495B67;
            padding: 10px 20px;
            display: block;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .sidebar ul li a:hover, .sidebar ul li a.active {
            background-color: #606a76;
        }

        /* Content area styles */
        .content {
            margin-left: 220px;
            margin-top: 70px; /* Adjusted for fixed header */
            padding: 30px;
            background-color: #ffffff;
            width: calc(100% - 220px);
            height: calc(100vh - 70px);
            overflow-y: auto;
        }

        .content h2 {
            color: #333;
            margin-bottom: 20px;
        }

        /* Department list styles */
        .department-list {
            background-color: #D9D9D9;
            padding: 20px;
            border-radius: 10px;
        }

        .department-list ul {
            list-style: none;
            padding-left: 0;
        }

        .department-list ul li {
            background-color: #f7f8fc;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .department-list ul li .delete-btn {
            cursor: pointer;
            color: #ff4c4c;
            font-size: 18px;
        }

        /* Add department button */
        .add-department-btn {
            background-color: #28a745;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            margin-top: 20px;
        }

        .add-department-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h2>User Admin</h2>
        <a href="?logout=true" class="logout-btn">Logout</a>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul>
            <li><a href="user.html" id="usersLink">Users</a></li>
            <li><a href="room.html" id="roomsLink">Rooms</a></li>
            <li><a href="#" id="departmentsLink" class="active">Departments</a></li>
        </ul>
    </div>

    <!-- Content Area -->
    <div class="content">
        <h2>List of Departments</h2>
        <div class="department-list">
            <ul id="departmentsList">
                <!-- Department items will be loaded here -->
            </ul>
            <button class="add-department-btn">➕ Add Department</button>
        </div>
    </div>

    <!-- JavaScript code -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Load departments when the page is loaded
            loadDepartments();

            // Add department button handler
            document.querySelector('.add-department-btn').addEventListener('click', function() {
                let departmentName = prompt("Enter Department Name:");
                if (departmentName) {
                    addDepartment(departmentName);
                }
            });

            // Load departments from the database
            function loadDepartments() {
                fetch('backend.php?action=load')
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('departmentsList').innerHTML = data;
                    })
                    .catch(error => console.error('Error loading departments:', error));
            }

            // Edit a department
            window.editDepartment = function(id, currentName) {
                const newName = prompt("Edit Department Name:", currentName);
                if (newName) {
                    fetch('backend.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `editDepartment=${id}&departmentName=${encodeURIComponent(newName)}`
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data === 'success') {
                            loadDepartments();
                        } else {
                            alert('Error editing department: ' + data);
                        }
                    });
                }
            }

            // Add a department
            function addDepartment(name) {
                fetch('backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `departmentName=${encodeURIComponent(name)}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        loadDepartments();
                    } else {
                        alert('Error adding department: ' + data);
                    }
                });
            }

            // Delete a department
            window.deleteDepartment = function(id) {
                if (confirm('Are you sure you want to delete this department?')) {
                    fetch('backend.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `deleteDepartment=${id}`
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data === 'success') {
                            loadDepartments();
                        } else {
                            alert('Error deleting department: ' + data);
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>
