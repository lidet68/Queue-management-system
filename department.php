<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$department_id = $_SESSION['department_id'];

$conn = new mysqli('localhost', 'username', 'password', 'qms');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
$stmt->bind_param('i', $department_id);
$stmt->execute();
$stmt->bind_result($department_name);
$stmt->fetch();

echo "<h1>Welcome to the $department_name Department</h1>";

$stmt->close();
$conn->close();
?>