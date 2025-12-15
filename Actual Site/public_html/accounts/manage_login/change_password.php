<?php

    session_start();
    include('../../db_files/connection.php');
    $error_message = "";
    $outcome = "";
    $username = ""; // Initialize username

    if(isset($_GET['pkey']))
    {
        // Path 1: Password reset request via email link
        $pkey = $_GET['pkey'];

        // Check pkey validity and verification status (using prepared statements)
        $stmt = $conn->prepare("SELECT `username` FROM `users` WHERE `verified` = 1 AND `pkey` = ?");
        $stmt->bind_param("s", $pkey);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result)
        {
            $error_message = "Database issue accessing account. Please try again later.";
        }
        elseif($result->num_rows != 1)
        {
            $error_message = "Could not locate a matching password reset request. Please try resetting your password again.";
        }
        else
        {
            $row = $result->fetch_assoc();
            $username = $row['username'];
            
            // Check for expiration (using prepared statements)
            $stmt_time = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, `password_reset_request_timestamp`, CURRENT_TIMESTAMP) AS `difference` FROM `users` WHERE `pkey` = ?");
            $stmt_time->bind_param("s", $pkey);
            $stmt_time->execute();
            $result_time = $stmt_time->get_result();

            if (!$result_time)
            {
                $error_message = "Database issue accessing time verification. Please try again later or use the Contact Us link above to report the issue.";
            }
            else
            {
                $row_time = $result_time->fetch_assoc();
                $timediff = (int) $row_time['difference'];
                if($timediff > 86400) // 24 hours in seconds
                {
                    $error_message = "That password reset request has expired. Please use attempt to reset your password again.";
                }
                else
                {
                    // Set password_reset_required flag (using prepared statements)
                    $stmt_update = $conn->prepare("UPDATE `users` SET `password_reset_required` = 1 WHERE `pkey` = ?");
                    $stmt_update->bind_param("s", $pkey);
                    $result_update = $stmt_update->execute();
                    
                    if(!$result_update)
                    {
                        $error_message = "An error occurred with your request. Please try to reset your password again.";
                    }
                }
                $stmt_time->close();
            }
        }
        $stmt->close();
    }
    elseif(isset(($_POST['submit'])))
    {
        // Path 2: Form submission to change or reset password
        if(isset($_SESSION['username']))
        {
            // Logged-in user is changing their password
            $username = $_SESSION['username'];
            $current_password = trim($_POST['current_password']);
            
            // Fetch stored hash for comparison (using prepared statements)
            $stmt = $conn->prepare("SELECT `password` FROM `users` WHERE `username` = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result)
            {
                $row = $result->fetch_assoc();
                
                if(!password_verify($current_password, $row['password']))
                {
                    $error_message = "Current password is incorrect. Please try again.";
                }
            }
            $stmt->close();
        }
        else
        {
            // Anonymous user is resetting password via pkey
            $pkey = $_POST['pkey'];
            
            // Fetch username using pkey (using prepared statements)
            $stmt = $conn->prepare("SELECT `username` FROM `users` WHERE `pkey` = ?");
            $stmt->bind_param("s", $pkey);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result && $result->num_rows == 1)
            {
                $row = $result->fetch_assoc();
                $username = $row['username'];
            }
            else
            {
                $error_message = "An error occurred while resetting the password. Please try again or contact a site admin.";
            }
            $stmt->close();
        }

        if(empty($error_message))
        {
            $password_1 = trim($_POST['new_password']);
            $password_2 = trim($_POST['confirm_password']);
            
            if($password_1 !== $password_2)
            {
                $error_message = "Passwords do not match. Please try again.";
            }
            elseif(strlen($password_1) < 8 || strlen($password_1) > 32)
            {
                $error_message = "Password must be between 8 and 32 characters in length.";
            }
            elseif(!preg_match('/^[a-zA-Z0-9._!@#$%^&*()-]*$/', $password_1) || preg_match('/^[\/]*$/', $password_1))
            {
                $error_message = "Password must only contain letters, numbers, or symbols ._!@#$%^&*()-";
            }
            else
            {
                $new_password_hash = password_hash($password_1, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE `users` SET `password` = ?, `pkey` = NULL, `password_reset_required` = 0 WHERE `username` = ?");
                $stmt->bind_param("ss", $new_password_hash, $username);
                $result = $stmt->execute();
                
                if($result)
                {
                    header("Location: /accounts/manage_login/password_changed.php");
                }
                else
                {
                    $error_message = "Something went wrong while changing the password. Please try again.";
                }
                $stmt->close();
            }
        }

    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>
            Reset Password - Pool Practice Tracker
        </title>
        <link rel="stylesheet" type="text/css" href="/styles/general.css">
        <link rel="stylesheet" type="text/css" href="/styles/header.css">
        <link rel="stylesheet" type="text/css" href="/styles/user-handling.css">

    </head>
    <body>
        
        <?php 
        include('../../temps/header.php'); 
        ?>

        <main class="main-section">
            <div class="user-heading">
                <h2>
                    <?php if(!isset($_GET['pkey'])): ?>
                        Change Password
                    <?php else: ?>
                        Reset Password
                    <?php endif; ?>
                </h2>
            </div>
            <form class="user-form" action="/accounts/manage_login/change_password.php " method="post">
                <?php if(!empty($error_message)): ?>
                    <div style="width: 92%; margin: 0px auto; padding: 10px; border: 1px solid #a94442; color: #a94442; background: #f2dede; border-radius: 5px; text-align: left;">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>
                <?php if(!(isset($_GET['pkey']) && !empty($error_message))): ?>
                    <?php if(!isset($_GET['pkey']) && isset($_SESSION['username'])): ?>
                        <div class="input-group">
                            <label for="">Current Password</label>
                            <input id="text" type="password" name="current_password">
                        </div>
                    <?php else: ?>
                        <div>
                            <input id="pkey" type="hidden" name="pkey" value="<?php echo htmlspecialchars($pkey ?? ''); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="input-group">
                        <label for="">New Password</label>
                        <input id="text" type="password" name="new_password">
                    </div>
                    <div class="input-group">
                        <!-- Note: The label in the original file was "Current Password", which is confusing. I changed it to "Confirm Password" -->
                        <label for="">Confirm Password</label>
                        <input id="text" type="password" name="confirm_password">
                    </div>
                    <div class="input-group">
                        <button id="button" type="submit" value="change_password" name="submit" class="user-form-btn" style="cursor: pointer;">Submit</button>
                    </div>
                <?php endif; ?>
            </form>
        </main>

        <?php 
        include('../../temps/footer.php'); 
        ?>

    </body>
</html>