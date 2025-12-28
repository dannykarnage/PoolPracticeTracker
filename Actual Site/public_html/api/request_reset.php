<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit;
}

$username = $conn->real_escape_string($data->username);

// Check if user exists and get email
$stmt = $conn->prepare("SELECT id, email FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $email = $row['email'];
    // Generate a unique token using random bytes
    $token = bin2hex(random_bytes(32));
    // Current timestamp for expiration check later
    // Storing it in 'password_reset_request_timestamp' column as requested
    $timestamp = date('Y-m-d H:i:s'); 
    
    // We also need to store the token. 
    // Based on your SQL file, you have a `pkey` column (varchar 45). Let's use that for the reset token.
    // If `pkey` is used for something else, we might need a new column, but standard practice suggests it's for this or similar.
    // Assuming `pkey` is available for this transient token.
    
    $update = $conn->prepare("UPDATE users SET pkey = ?, password_reset_request_timestamp = ? WHERE username = ?");
    $update->bind_param("sss", $token, $timestamp, $username);
    
    if ($update->execute()) {
        // Send Email
        $to = $email;
        $subject = "Pool Practice Tracker - Password Reset Request";
        // Link to the React app route for resetting password
        $resetLink = "https://poolpracticetracker.com/new-site/reset-password?token=" . $token . "&user=" . urlencode($username);
        
        $message = "
        <html>
        <head>
        <title>Password Reset</title>
        </head>
        <body>
        <p>Someone is trying to reset your password. If you did not make this request, please disregard this email.</p>
        <p>Otherwise, <a href='" . $resetLink . "'>click here to reset your password</a>.</p>
        <p>This link is valid for 15 minutes.</p>
        </body>
        </html>
        ";
    
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@poolpracticetracker.com" . "\r\n";
    
        if(mail($to, $subject, $message, $headers)) {
            echo json_encode(['success' => true, 'message' => 'If that username exists, an email has been sent.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    // Security best practice: Don't reveal if username exists or not
    echo json_encode(['success' => true, 'message' => 'If that username exists, an email has been sent.']);
}

$conn->close();
?>