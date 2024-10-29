<?php
$servername = "localhost";
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$dbname = "QMS"; // Change this to your database name

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted email
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'];

    // Generate a unique token
    $token = bin2hex(random_bytes(25));

    // Set token expiration (48 hours from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+48 hours'));

    // Insert into invitations table
    $stmt = $conn->prepare("INSERT INTO invitations (email, token, expires_at, used, created_at) VALUES (?, ?, ?, 0, NOW())");
    if (!$stmt) {
        echo json_encode(["message" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("sss", $email, $token, $expires_at);
    if ($stmt->execute()) {
        // Create invite link
        $inviteLink = "http://localhost/theqmspro/signup2.html?token=" . urlencode($token);

        // Send invitation email (basic example)
        $subject = "You're Invited to Sign Up";
        $message = "You have been invited to sign up. Click the link below to complete your registration:\n\n" . $inviteLink;

        if (mail($email, $subject, $message)) {
            echo json_encode(["message" => "Invitation sent successfully to " . htmlspecialchars($email)]);
        } else {
            echo json_encode(["message" => "Failed to send invitation email."]);
        }
    } else {
        echo json_encode(["message" => "Failed to insert invitation into database: " . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>
