<?php
// Turn on error reporting for this script only
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Diagnostics</h1>";

// 1. Check Directory Structure
echo "<h2>1. Checking Database Files</h2>";
$db_files_exists = file_exists(__DIR__ . '/db_files/connection.php');
$db_access_exists = file_exists(__DIR__ . '/db_access/connection.php');

if ($db_files_exists) {
    echo "<p style='color:green'>Found <b>db_files/connection.php</b> (This matches your code).</p>";
    include(__DIR__ . '/db_files/connection.php');
} elseif ($db_access_exists) {
    echo "<p style='color:orange'>Found <b>db_access/connection.php</b>. Your code is looking for 'db_files'. You should rename this folder to 'db_files'.</p>";
    include(__DIR__ . '/db_access/connection.php');
} else {
    echo "<p style='color:red'><b>CRITICAL:</b> Could not find 'db_files' OR 'db_access' folder.</p>";
    die();
}

// 2. Check Database Connection
echo "<h2>2. Database Connection</h2>";
if ($conn->connect_error) {
    echo "<p style='color:red'>Connection Failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color:green'>Database Connected Successfully!</p>";
    
    // 3. Check Users Table Structure
    echo "<h2>3. Users Table Structure</h2>";
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        $pass_col_safe = false;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "</tr>";
            
            // Check password column length
            if ($row['Field'] === 'password') {
                if (strpos($row['Type'], 'varchar(255)') !== false || strpos($row['Type'], 'varchar(60)') !== false || strpos($row['Type'], 'text') !== false) {
                    $pass_col_safe = true;
                } elseif (strpos($row['Type'], 'varchar(32)') !== false || strpos($row['Type'], 'char(32)') !== false) {
                    $pass_col_safe = false;
                }
            }
        }
        echo "</table>";
        
        if ($pass_col_safe) {
            echo "<p style='color:green'><b>Pass:</b> 'password' column looks long enough for hashes.</p>";
        } else {
            echo "<p style='color:red'><b>FAIL:</b> 'password' column is too short! It looks like it might be VARCHAR(32). <b>You MUST change it to VARCHAR(255) in phpMyAdmin for login to work.</b></p>";
        }
    } else {
        echo "<p style='color:red'>Could not describe users table. Error: " . $conn->error . "</p>";
    }
}
?>