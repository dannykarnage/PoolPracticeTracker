<?php

    //include('/home/u449903691/domains/poolpracticetracker.com/public_html/functions.php');

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Username requested - Pool Practice Tracker</title>
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
                            <h2>Username requested</h2>
                        </p>
                        <p>
                            Thank you for your request to recover your username. If we found a user 
                            with the email address you provided, we will send an email containing your username.
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
