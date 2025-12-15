<?php

    // FIX: Commented out absolute path
    //include('/home/u449903691/domains/poolpracticetracker.com/public_html/functions.php');

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Reset Password Requested - Pool Practice Tracker</title>
        <link rel="stylesheet" href="/styles/general.css">
        <link rel="stylesheet" href="/styles/header.css">
    </head>
    <body>
        
        <header>
            <?php 
            // FIX: Changed absolute path to relative path
            include('../../temps/header.php'); 
            ?>
        </header>

        <main>
            <div style="margin-top: 66px"></div>
            <div class="text-grid-three-by-one">
                <div class="left-section"> </div>
                <div class="middle-section">
                    <div class="left-justified-paragraph">
                        <p>
                            <h2>We received your password reset request.</h2>
                        </p>
                        <p>
                            We sent you an email with a link to confirm your request. Please lick the link
                            in the email to reset your password.
                        </p>
                        <p style="font-size: 14px">
                            (If you have troulbe finding the email, be sure to check your "Spam" box and filter for 
                            the confirmation email. If you still have trouble, use the "Contact Us" link above to 
                            contact a site administrator about your issue.)
                        </p>
                    </div>
                </div>
                <div class="right-section"> </div>
            </div>
        </main>

        <footer>

            <?php 
            // FIX: Changed absolute path to relative path
            include('../../temps/footer.php'); 
            ?>

        </footer>

    </body>
</html>
