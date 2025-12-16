<?php
include 'db_connect.php';

if (isset($_GET['vkey'])) {
    $vkey = $conn->real_escape_string($_GET['vkey']);

    // Check if key exists and account is not yet verified
    $check = $conn->prepare("SELECT verified, vkey FROM users WHERE verified = 0 AND vkey = ? LIMIT 1");
    $check->bind_param("s", $vkey);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 1) {
        // Validate key
        $update = $conn->prepare("UPDATE users SET verified = 1 WHERE vkey = ?");
        $update->bind_param("s", $vkey);
        
        if ($update->execute()) {
            // Success: Redirect to React Login page with a success message query param
            // Assuming your React app is at /new-site/
            header("Location: https://poolpracticetracker.com/new-site/login?verified=true");
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
    } else {
        echo "This account is invalid or already verified.";
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>