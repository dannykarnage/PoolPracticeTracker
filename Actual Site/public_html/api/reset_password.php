<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->token) || !isset($data->username) || !isset($data->new_password)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$token = $conn->real_escape_string($data->token);
$username = $conn->real_escape_string($data->username);
$new_password = $data->new_password;

// Check token and timestamp
// Assuming `pkey` stores the token
$stmt = $conn->prepare("SELECT id, password_reset_request_timestamp FROM users WHERE username = ? AND pkey = ? LIMIT 1");
$stmt->bind_param("ss", $username, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $requestTime = strtotime($row['password_reset_request_timestamp']);
    $currentTime = time();
    $timeDiff = ($currentTime - $requestTime) / 60; // Difference in minutes

    if ($timeDiff <= 15) {
        // Valid request
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear the token/timestamp
        $update = $conn->prepare("UPDATE users SET password = ?, pkey = NULL, password_reset_request_timestamp = '0000-00-00 00:00:00' WHERE id = ?");
        $update->bind_param("si", $hashed_password, $row['id']);
        
        if ($update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Password successfully updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Reset link has expired (valid for 15 minutes).']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid reset token or username.']);
}

$conn->close();
?>