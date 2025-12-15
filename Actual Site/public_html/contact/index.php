<?php

    session_start();

    $success = false;
    $errors = array();
    $name = "";
    $email = "";
    $subject = "";
    $message = "";

    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
        if(isset($_POST['submit']))
        {
            // Note: Connection is not needed here as no DB access occurs, but kept for context if DB changes later.
            // include("/home/u449903691/domains/poolpracticetracker.com/public_html/db_files/connection.php"); 
            
            // --- SECURITY FIX: Use filter_var and trim for sanitization ---
            // Sanitize and trim inputs for safety before use (prevents XSS/malformed data in email)
            $name = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
            $subject = trim(filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $message = trim(filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

            // Validate fields
            if(empty($name))
            {
                array_push($errors, "Name is required.");
            }
            if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            {
                array_push($errors, "A valid email address is required.");
            }
            if(empty($subject))
            {
                array_push($errors, "Please include a subject");
            }
            if(empty($message))
            {
                array_push($errors, "You must include a message for your email");
            }

            if (count($errors) == 0)
            {
                // Construct plain text email body
                $full_message = "You have a new email.\r\n";
                $full_message .= "\r\n";
                $full_message .= "Name: " .$name . "\r\n";
                $full_message .= "Email: " .$email . "\r\n";
                $full_message .= "\r\n";
                $full_message .= $message;

                // Set headers
                // Use the submitting user's sanitized email as the Reply-To for better deliverability
                $headers = "From: contactform@poolpracticetracker.com \r\n";
                $headers .= "Reply-To: " . $email . "\r\n"; // Added Reply-To
                $headers .= "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/plain;charset=UTF-8" . "\r\n";
                
                // Send email
                $success = mail("contactform@poolpracticetracker.com",$subject,$full_message,$headers);
            }
        }
    }

    if($success)
    {
        header("Location: /contact/thankyou.php");
        die(); // Always die after header redirect
    }

?>

<html>
    <head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-0531879237494766" crossorigin="anonymous"></script>
        <title>
            Contact Us - Pool Practice Tracker
        </title>
        <link rel="stylesheet" type="text/css" href="/styles/general.css">
        <link rel="stylesheet" type="text/css" href="/styles/header.css">
        <link rel="stylesheet" type="text/css" href="/styles/user-handling.css">
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    </head>
    <body>
        
        <?php 
        // FIX: Changed absolute path to relative path
        include('../temps/header.php'); 
        ?>

        <main class="main-section">

            <div class="user-heading">
                <h2>Contact Us</h2>
            </div>
            <?php if(count($errors) > 0): ?>
                <div style="width: 92%; margin: 0px auto; padding: 10px; border: 1px solid #a94442; color: #a94442; background: #f2dede; border-radius: 5px; text-align: left;">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form class="user-form" action="/contact/index.php " method="post">
                <div class="input-group">
                    <label for="">Your name: </label>
                    <!-- SECURITY FIX: Added htmlspecialchars to prevent XSS on form re-population -->
                    <input id="name" type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
                </div>
                <div class="input-group">
                    <label for="">Your email address: </label>
                    <!-- SECURITY FIX: Added htmlspecialchars to prevent XSS on form re-population -->
                    <input id="email" type="text" name="email" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="input-group">
                    <label for="">Subject: </label>
                    <!-- SECURITY FIX: Added htmlspecialchars to prevent XSS on form re-population -->
                    <input id="subject" type="text" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                </div>
                <div class="input-group">
                    <label for="">Message: </label>
                    <!-- SECURITY FIX: Added htmlspecialchars to prevent XSS on form re-population -->
                    <textarea id="message" name="message" cols="55" rows="15"><?php echo htmlspecialchars($message); ?></textarea>
                </div>
                <div class="g-recaptcha" data-sitekey="6LdhASUjAAAAAM8x8JpWFkP0Oe-JbiYr6GeUyVm4"></div>
                <div class="input-group">
                    <button id="button" type="submit" value="send" name="submit" class="user-form-btn" style="cursor: pointer;">Send Email</button>
                </div>
            </form>

        </main>

    </body>
</html>
