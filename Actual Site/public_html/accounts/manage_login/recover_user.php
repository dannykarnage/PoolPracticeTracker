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
    $email = "";

    //determine if a user is already logged in and, if so, redirect to the homepage
    $user_already_logged_in = !empty($_SESSION['username']);
    if($user_already_logged_in)
    {
        $error_message = "A user is already logged in. To change user, please logout first.";
    }
    elseif($_SERVER['REQUEST_METHOD'] == "POST")
    {
        $email = trim($_POST['email']);
        $username = null;
        
        if(!empty($email))
        {
            // Fetch username by email (Secure)
            $stmt = $conn->prepare("SELECT `username` FROM `users` WHERE `email` = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result)
            {
                if ($result->num_rows == 1) {
                    $row = $result->fetch_assoc();
                    $username = $row['username'];
                }
                // If 0 rows are found, we continue to the success page for security reasons (don't reveal valid emails)
                
                if (!empty($username))
                {
                    //send email
                    $subject = "Username request";
                    // This file is in the same directory, so direct include is fine
                    include('recover_username_email.php');
                    $headers = "From: donotreply@poolpracticetracker.com \r\n";
                    $headers .= "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    
                    // Note: It's important to check the mail function's return value for success
                    $success = mail($email,$subject,$email_message,$headers);

                    if (!$success)
                    {
                        $error_message = "Unable to process your request. Please try again later.";
                    }
                }

                // Always redirect to the same page to prevent user enumeration
                header("Location: /accounts/manage_login/username_requested.php");
                die();
            }
            else
            {
                $error_message = "A problem occurred while trying to locate that email address. Please try again. Error: " . $conn->error;
            }
            $stmt->close();
        }
        else
        {
            $error_message = "Email address is required.";
        }
    }

?>

<!DOCTYPE html>

<html>
    <head>
        <title>
            Recover Username - Pool Practice Tracker
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
                <h2>Recover Username</h2>
            </div>
            <form class="user-form" action="/accounts/manage_login/recover_user.php " method="post">
                <?php if(!empty($error_message)): ?>
                    <div style="width: 92%; margin: 0px auto; padding: 10px; border: 1px solid #a94442; color: #a94442; background: #f2dede; border-radius: 5px; text-align: left;">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>
                <div class="input-group"> <!--this isn't an input, but the input-group style works fine -->
                    Enter your email address below. If we have an account with the submitted email, we will
                    send you an email containing your username.
                </div>
                <div><br></div>
                <div class="input-group">
                    <label for="">Email address</label>
                    <input id="text" type="text" name="email" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="input-group">
                    <button id="button" type="submit" value="submit" name="submit" class="user-form-btn" style="cursor: pointer;">Submit</button>
                </div>
            </form>
        </main>

        <?php 
        include('../../temps/footer.php'); 
        ?>

    </body>
</html>