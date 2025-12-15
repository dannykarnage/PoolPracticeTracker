<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

session_start();
include 'db_connect.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->password)) {
    echo json_encode(['success' => false, 'message' => 'Missing credentials']);
    exit;
}

$username = $conn->real_escape_string($data->username);
$password = $data->password;

// Fetch user
$stmt = $conn->prepare("SELECT id, username, password, verified FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['verified'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Account not verified']);
        exit;
    }

    if (password_verify($password, $row['password'])) {
        // Password correct
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        
        echo json_encode([
            'success' => true, 
            'user' => [
                'id' => $row['id'],
                'username' => $row['username']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>