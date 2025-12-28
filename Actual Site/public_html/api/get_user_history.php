<?php
header('Content-Type: application/json');

// --- CORS FIX (SECURED) ---
// Define allowed origins (Production + Development)
$allowed_origins = [
    'https://poolpracticetracker.com',
    'https://www.poolpracticetracker.com',
    'http://localhost:5173', // Vite default
    'http://localhost:3000'  // React default
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // Cache for 1 day
}

// Handle preflight OPTIONS request if necessary
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

session_start();
include 'db_connect.php';

// Check Auth
if (!isset($_SESSION['user_id'])) {
    // Fallback: Check if user_id is passed in via URL (React passes this as a backup)
    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
} else {
    $user_id = $_SESSION['user_id'];
}

// Use DATE_FORMAT to ensure consistent ISO format for React
// Note: We select drill_id as 'drillId' to match the React interface casing
$sql = "SELECT 
            id, 
            drill_id as drillId, 
            score, 
            pass as passed, 
            DATE_FORMAT(timestamp, '%Y-%m-%dT%H:%i:%s') as date 
        FROM drill_results 
        WHERE user_id = ? 
        ORDER BY timestamp ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // SECURITY FIX: Do not expose $conn->error to the client
    error_log("Database prepare failed: " . $conn->error); // Log to server error log
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while($row = $result->fetch_assoc()) {
    $logs[] = [
        'id' => strval($row['id']),
        'drillId' => intval($row['drillId']),
        'date' => $row['date'],
        // Ensure score is an integer or null, not a string
        'score' => $row['score'] !== null ? intval($row['score']) : null,
        // Convert DB 1/0 to boolean true/false
        'passed' => $row['passed'] == 1
    ];
}

echo json_encode($logs);

$stmt->close();
$conn->close();
?>