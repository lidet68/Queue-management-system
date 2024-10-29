<?php 
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

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

// Security header to prevent clickjacking
header('X-Frame-Options: DENY');

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    // Basic input validation
    if (empty($username) || empty($password)) {
        echo "<p class='error'>" . htmlspecialchars("Username and password are required!") . "</p>";
    } else {
        // Prepare the SQL statement to avoid SQL injection
        $stmt = $conn->prepare("SELECT users.id, users.password, departments.name AS department_name 
                                FROM users 
                                JOIN departments ON users.department_id = departments.id 
                                WHERE users.username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($user_id, $hashed_password, $department_name);
        
        if ($stmt->num_rows > 0) {
            $stmt->fetch();
            // Verify password
            if (password_verify($password, $hashed_password)) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // Store user info in session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['department'] = $department_name;

                // Redirect based on department
                if ($department_name === 'reception') {
                    header("Location: receptiondashboard.php");
                } else {
                    // Redirect to staff dashboard with department filtering
                    header("Location: staffdash.php");
                }
                exit();
            } else {
                echo "<p class='error'>" . htmlspecialchars("Invalid username or password!") . "</p>";
            }
        } else {
            echo "<p class='error'>" . htmlspecialchars("Invalid username or password!") . "</p>";
        }

        // Close the statement
        $stmt->close();
    }
}

// Close the connection
$conn->close();
?>
