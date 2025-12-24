<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

session_start();
include 'db_connect.php';

// Check Auth
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    } else {
        http_response_code(401);
        echo json_encode([]);
        exit;
    }
} else {
    $user_id = $_SESSION['user_id'];
}

/*$sql = "SELECT id, drill_id as drillId, score, pass as passed, timestamp as date FROM drill_results WHERE user_id = ? ORDER BY timestamp ASC";*/
$sql = "SELECT id, drill_id as drillId, score, pass as passed, DATE_FORMAT(timestamp, '%Y-%m-%dT%H:%i:%s') as date FROM drill_results WHERE user_id = ? ORDER BY timestamp ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while($row = $result->fetch_assoc()) {
    $logs[] = [
        'id' => strval($row['id']),
        'drillId' => intval($row['drillId']),
        'date' => $row['date'],
        'score' => $row['score'] !== null ? intval($row['score']) : null,
        'passed' => $row['passed'] == 1
    ];
}

echo json_encode($logs);

$stmt->close();
$conn->close();
?>