<?php

    session_start();

    // FIX: Adjusted path to go back 2 levels (accounts/manage_login/ -> root)
    if(file_exists("../../db_files/connection.php")) {
        include("../../db_files/connection.php");
    } elseif(file_exists("../../db_access/connection.php")) {
        include("../../db_access/connection.php");
    } else {
        die("Error: Could not locate database connection file.");
    }

    $error_message = "";

    //determine if a user is already logged in and, if so, redirect to the homepage
    $user_already_logged_in = !empty($_SESSION['username']);
    if($user_already_logged_in)
    {
        $error_message = "A user is already logged in. To change user, please logout first.";
    }
    elseif($_SERVER['REQUEST_METHOD'] == "POST")
    {
        //something was posted
        // No need for mysqli_real_escape_string anymore
        $username = trim($_POST['username']);

        if(!empty($username))
        {
            // Fetch user details (Secure)
            $stmt = $conn->prepare("SELECT `email` FROM `users` WHERE `username` = ? AND `verified` = 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result)
            {
                if ($result->num_rows != 1)
                {
                    $error_message = "Could not find that username on file or the account is not verified. Please try again.";
                }
                else
                {
                    $user_data = $result->fetch_assoc();
                    $email = $user_data['email'];
                }
            }
            else
            {
                $error_message = "Database error. Please try again later.";
            }    
            $stmt->close();        
        }
        else
        {
            $error_message = "Please enter a username to reset your password.";
        }
    

        if(empty($error_message))
        {
            try {
                $pkey = bin2hex(random_bytes(16)); // Generates a 32-character hex token
            } catch (Exception $e) {
                $error_message = "Internal error generating security token.";
                error_log("Security token generation failed: " . $e->getMessage());
            }

            if(empty($error_message)) {
                // Update pkey and timestamp (Secure)
                $stmt = $conn->prepare("UPDATE `users` SET `pkey` = ?, `password_reset_request_timestamp` = CURRENT_TIMESTAMP WHERE `username` = ? LIMIT 1");
                $stmt->bind_param("ss", $pkey, $username);
                $result = $stmt->execute();
                $stmt->close();
                
                if ($result)
                {
                    //send email
                    $subject = "Password reset request.";
                    // This file is in the same directory, so direct include is fine
                    include('password_reset_email.php');
                    $headers = "From: donotreply@poolpracticetracker.com \r\n";
                    $headers .= "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    
                    // Note: It's important to check the mail function's return value for success
                    $success = mail($email,$subject,$email_message,$headers);

                    if ($success)
                    {
                        header("Location: /accounts/manage_login/password_reset_requested.php");
                        die();
                    }
                    else
                    {
                        $error_message = "Unable to process your request. Please try again later.";
                    }
                }
                else
                {
                    $error_message = "Database error during request. Please try again later. Error: " . $conn->error;
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Login - Pool Practice Tracker</title>
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
                <h2>Reset Password</h2>
            </div>
            <form class="user-form" action="/accounts/manage_login/request_password_reset.php " method="post">
                <?php if(!empty($error_message)): ?>
                    <div style="width: 92%; margin: 0px auto; padding: 10px; border: 1px solid #a94442; color: #a94442; background: #f2dede; border-radius: 5px; text-align: left;">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>
                <div class="input-group"> <!--this isn't an input, but the input-group style works fine -->
                    Enter your username below. We will send an email to the email address for your username
                    with a link to reset your password.
                </div>
                <div><br></div>
                <div class="input-group">
                    <label for="">Username</label>
                    <input id="text" type="text" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                <div class="input-group">
                    <button id="button" type="submit" value="submit" name="submit" class="user-form-btn" style="cursor: pointer;">Submit</button>
                </div>
                <p style="font-size: 12px">
                    Forgot your username? <a href="/accounts/manage_login/recover_user.php">Click here.</a>
                </p>
            </form>
        </main>

        <?php 
        include('../../temps/footer.php'); 
        ?>

    </body>
</html>