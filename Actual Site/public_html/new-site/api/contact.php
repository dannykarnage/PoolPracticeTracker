<?php
// Hostinger / Shared Hosting Friendly Contact Form API
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

// 1. Get Input
$data = json_decode(file_get_contents("php://input"));

// 2. Bot Protection (Honeypot)
if (isset($data->website_check) && !empty($data->website_check)) {
    // If the hidden field is filled, it's a bot. Return success to fool them.
    echo json_encode(['success' => true, 'message' => 'Message sent.']); 
    exit;
}

// 3. Validation
if (!isset($data->subject) || !isset($data->email) || !isset($data->message)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$subject = strip_tags(trim($data->subject));
$replyToEmail = filter_var($data->email, FILTER_VALIDATE_EMAIL);
$messageContent = strip_tags(trim($data->message));

if (!$replyToEmail) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// 4. Configuration for Hostinger
// IMPORTANT: This email MUST exist in your Hostinger Email Dashboard.
// Do not use a Gmail/Yahoo address here. It must be @yourdomain.com
$sender = 'no-reply@poolpracticetracker.com'; 
$recipient = 'info@poolpracticetracker.com';

$emailSubject = "Contact: " . $subject;

// 5. Construct Email Body
$emailBody = "New message from PoolPracticeTracker:\n\n";
$emailBody .= "Subject: " . $subject . "\n";
$emailBody .= "From User: " . $replyToEmail . "\n\n";
$emailBody .= "--------------------------------------\n";
$emailBody .= $messageContent . "\n";
$emailBody .= "--------------------------------------\n";

// 6. Headers
// We set the FROM to your domain email (authentication)
// We set the REPLY-TO to the user's email (so you can hit reply)
$headers = "From: PoolTracker <" . $sender . ">\r\n";
$headers .= "Reply-To: " . $replyToEmail . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// 7. Send using the "-f" parameter
// The "-f" parameter sets the Return-Path, which is crucial for Hostinger delivery.
$success = mail($recipient, $emailSubject, $emailBody, $headers, "-f" . $sender);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    // If native mail fails, server logs might be needed, but usually it's a sender mismatch
    echo json_encode(['success' => false, 'message' => 'Server could not send email. Check sender configuration.']);
}
?>