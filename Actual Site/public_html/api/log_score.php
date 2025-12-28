<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

session_start();
include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    if (isset($data->user_id)) {
        $user_id = intval($data->user_id); // Fallback for dev/testing
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
} else {
    $user_id = $_SESSION['user_id'];
}

if (!isset($data->drillId)) {
    echo json_encode(['success' => false, 'message' => 'Missing drill ID']);
    exit;
}

$drillId = intval($data->drillId);
$score = isset($data->score) ? intval($data->score) : null;
$pass = isset($data->passed) ? ($data->passed ? 1 : 0) : null;

$stmt = $conn->prepare("INSERT INTO drill_results (user_id, drill_id, score, pass, timestamp) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iiii", $user_id, $drillId, $score, $pass);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$stmt->close();
$conn->close();
?>