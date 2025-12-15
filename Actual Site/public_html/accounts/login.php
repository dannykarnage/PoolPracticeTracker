<?php
    session_start();

    // FIX: Changed absolute path to relative path
    //include('../../functions.php');
    include('../db_files/connection.php');
    $login_error = "";
    
    //determine if a user is already logged in and, if so, redirect to the homepage
    $user_already_logged_in = !empty($_SESSION['username']);
    if($user_already_logged_in)
    {
        $login_error = "A user is already logged in. To change user, please logout first.";
    }
    elseif($_SERVER['REQUEST_METHOD'] == "POST")
    {
        // No need for mysqli_real_escape_string or md5() here as we use prepared statements and password_verify()
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if(!empty($username) && !empty($password))
        {
            // 1. Check for verified user status and fetch stored hash (using prepared statements)
            $stmt = $conn->prepare("SELECT `password`, `password_reset_required` FROM `users` WHERE `username` = ? AND `verified` = 1 limit 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result && $result->num_rows == 1)
            {
                $user_data = $result->fetch_assoc();

                if($user_data['password_reset_required'] == 1)
                {
                    $login_error = "The password on that account has been disabled. Please click on the <b>Having Trouble?</b> link below to request your password be reset.";
                }
                // CRITICAL SECURITY FIX: Use password_verify() instead of md5() comparison
                elseif(password_verify($password, $user_data['password']))
                {
                    $_SESSION['username'] = $username; // Use the cleaned username
                    header("Location: /index.php");
                    die();
                }
            }
            $stmt->close();
            
            if(empty($login_error))
            {
                // 2. If login failed, check if the unverified user exists to provide a specific error message (using prepared statements)
                $stmt = $conn->prepare("SELECT `username` FROM `users` WHERE `username` = ? AND `verified` = 0 limit 1");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if($result && $result->num_rows == 1)
                {
                    $login_error = "That account has not yet been verified. Please click the link in the verification email to verify your account before logging in.";
                }
                else
                {
                    $login_error = "Incorrect password or user not found.";
                }
                $stmt->close();
            }
        }
        else
        {
            $login_error = "Invalid login credentials. Please try again.";
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
        // FIX: Changed absolute path to relative path
        include('../temps/header.php'); 
        ?>

        <main class="main-section">
            <div class="user-heading">
                <h2>Login</h2>
            </div>
            <form class="user-form" action="/accounts/login.php " method="post">
                <?php if(!empty($login_error)): ?>
                    <div style="width: 92%; margin: 0px auto; padding: 10px; border: 1px solid #a94442; color: #a94442; background: #f2dede; border-radius: 5px; text-align: left;">
                        <p><?php echo htmlspecialchars($login_error); ?></p>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <label for="">Username</label>
                    <input id="text" type="text" name="username">
                </div>
                <div class="input-group">
                    <label for="">Password</label>
                    <input id="text" type="password" name="password">
                </div>
                <div class="input-group">
                    <button id="button" type="submit" value="login" name="login" class="user-form-btn" style="cursor: pointer;">Login</button>
                </div>
                <p>
                    New here? <a href="/accounts/register.php">Sign up!</a>
                </p>
                <p style="font-size: 12px">
                    Having trouble? <a href="/accounts/manage_login/request_password_reset.php">Click here.</a>
                </p>
            </form>
        </main>

        <?php 
        // FIX: Changed absolute path to relative path
        include('../temps/footer.php'); 
        ?>

    </body>
</html>