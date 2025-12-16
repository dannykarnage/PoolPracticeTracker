<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connect.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->password) || !isset($data->email)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$username = $conn->real_escape_string($data->username);
$email = $conn->real_escape_string($data->email);
$password = $data->password;

// Basic validation
if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Check if username or email already exists
$check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username or Email already exists']);
    $check->close();
    exit;
}
$check->close();

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$vkey = md5(time() . $username); // Unique verification key

// Insert new user with verified = 0
$stmt = $conn->prepare("INSERT INTO users (username, password, email, vkey, verified) VALUES (?, ?, ?, ?, 0)");
$stmt->bind_param("ssss", $username, $hashed_password, $email, $vkey);

if ($stmt->execute()) {
    // Send Verification Email
    $to = $email;
    $subject = "Pool Practice Tracker - Verify Your Email";
    // Change this URL to match your actual domain and path to the verify script
    $verifyLink = "https://poolpracticetracker.com/new-site/api/verify.php?vkey=" . $vkey;
    
    $message = "
    <html>
    <head>
    <title>Verify Your Account</title>
    </head>
    <body>
    <h2>Welcome to Pool Practice Tracker!</h2>
    <p>Please click the link below to verify your account:</p>
    <p><a href='" . $verifyLink . "'>Verify Account</a></p>
    <p>Or copy this link: " . $verifyLink . "</p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@poolpracticetracker.com" . "\r\n";

    // Attempt to send email
    // Note: mail() depends on server configuration. 
    if(mail($to, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Registration successful! Please check your email to verify.']);
    } else {
        // User created but email failed - manual intervention or resend logic might be needed
        echo json_encode(['success' => true, 'message' => 'Account created, but failed to send verification email. Please contact support.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>