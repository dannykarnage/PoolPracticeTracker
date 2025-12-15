<?php
    $dbhost = getenv('DB_HOST');
    $dbuser = getenv('DB_USER');
    $dbpass = getenv('DB_PASS');
    $dbname = getenv('DB_NAME');
    
    // Initialize $conn using Object-Oriented mysqli
    $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

    // Check connection
    if ($conn->connect_error) {
        // Log the error detail internally, but only show a generic message to the user
        error_log("Connection failed: " . $conn->connect_error);
        die("Database connection failed. Please try again later.");
    }

    // Set character set to UTF-8 for security and compatibility
    $conn->set_charset("utf8mb4");
?>
