<?php
// Prevent direct access
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(403);
    die("Forbidden");
}

// Env variables should be set in your server config or .htaccess
// For now, using the variables from your uploaded code for consistency
$dbhost = getenv('DB_HOST') ?: 'localhost';
$dbuser = getenv('DB_USER') ?: 'u449903691_eooaokt';
$dbpass = getenv('DB_PASS') ?: 'DKarnag3*ppt';
$dbname = getenv('DB_NAME') ?: 'u449903691_eooaokt';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    // Return JSON error for API consumers
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$conn->set_charset("utf8mb4");
?>