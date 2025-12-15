<?php

    //include('/home/u449903691/domains/poolpracticetracker.com/public_html/functions.php');

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Thank you for your message! - Pool Practice Tracker</title>
        <link rel="stylesheet" href="/styles/general.css">
        <link rel="stylesheet" href="/styles/header.css">
    </head>
    <body>
        
        <header>
            <?php 
            // FIX: Changed absolute path to relative path
            include('../temps/header.php') 
            ?>
        </header>

        <main>
            <div style="margin-top: 66px"></div>
            <div class="text-grid-three-by-one">
                <div class="left-section"> </div>
                <div class="middle-section">
                    <div class="left-justified-paragraph">
                        <p>
                            <h2>Thank you contacting PoolPracticeTracker!</h2>
                        </p>
                        <p>
                            We will review your message as soon as possible. If a response is required, please be
                            patient until we can respond.
                        </p>
                        <p>
                            In the meantime, check out the latest videos from the 
                            <a href="https://www.youtube.com/channel/UCvYec5b7AdatNJQpe9P1e4A?view_as=subscriber?sub_confirmation=1" 
                            style="text-decoration: none;">Cellar Cue Sports YouTube channel.</a>
                        </p>
                        <p>
                            -the PoolPracticeTracker.com team
                        </p>
                    </div>
                </div>
                <div class="right-section"> </div>
            </div>
        </main>

        <footer>

            <?php 
            // FIX: Changed absolute path to relative path
            include('../temps/footer.php'); 
            ?>

        </footer>

    </body>
</html>
