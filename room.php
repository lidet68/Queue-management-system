<?php 
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

// Determine the action to perform
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update_room') {
        $roomId = $input['roomId'];
        $roomName = $input['roomName'];
        $departmentId = $input['departmentId'];

        // Fetch department name from the departments table using department_id
        $stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->bind_param("i", $departmentId);
        $stmt->execute();
        $stmt->bind_result($departmentName);
        $stmt->fetch();
        $stmt->close();

        // Check if department exists
        if (!$departmentName) {
            echo json_encode(['success' => false, 'message' => 'Department not found']);
            exit;
        }

        // Update room details along with department_id and department_name
        $stmt = $conn->prepare("UPDATE rooms SET name = ?, department_id = ?, department_name = ? WHERE id = ?");
        $stmt->bind_param("sisi", $roomName, $departmentId, $departmentName, $roomId);

        $response = [];
        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['error'] = $stmt->error;
        }

        $stmt->close();
        echo json_encode($response);

    } elseif ($action === 'delete_room') {
        $roomId = $input['roomId'];

        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $roomId);

        $response = [];
        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['error'] = $stmt->error;
        }

        $stmt->close();
        echo json_encode($response);

    } elseif ($action === 'fetch_departments') {
        $sql = "SELECT id, name FROM departments";
        $result = $conn->query($sql);

        $departments = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $departments[] = $row;
            }
        }

        header('Content-Type: application/json');
        echo json_encode($departments);

    } else {
        // Handle POST request for adding a new room
        $roomName = $input['roomName'];
        $departmentId = $input['departmentId'];

        // Fetch department name
        $stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->bind_param("i", $departmentId);
        $stmt->execute();
        $stmt->bind_result($departmentName);
        $stmt->fetch();
        $stmt->close();

        // Insert room with department_id and department_name
        $stmt = $conn->prepare("INSERT INTO rooms (name, department_id, department_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $roomName, $departmentId, $departmentName);

        $response = [];
        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['error'] = $stmt->error;
        }

        $stmt->close();
        echo json_encode($response);
    }
} elseif ($action === 'fetch_rooms') {
    // Handle GET request for fetching rooms
    $sql = "SELECT rooms.id, rooms.name AS roomName, rooms.department_id, departments.name AS departmentName
            FROM rooms
            INNER JOIN departments ON rooms.department_id = departments.id";
    
    $result = $conn->query($sql);

    $rooms = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($rooms);
}

// Close the connection
$conn->close();
?>
