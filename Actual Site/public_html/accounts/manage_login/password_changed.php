<?php

    session_start();
    // FIX: Commented out absolute path
    //include('/home/u449903691/domains/poolpracticetracker.com/public_html/functions.php');

    if(isset($_SESSION['username']))
    {
        unset($_SESSION['username']);
    }

    if(isset($_SESSION['username']))
    {
        die('something went wrong');
    }
?>

<!DOCTYPE html>

<html>
    <head>
        <title>
            Password Changed - Pool Practice Tracker
        </title>
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
                            <h2>Password changed</h2>
                        </p>
                        <p>
                            Your password has been changed and you have been logged out. Please 
                            log in again to use Pool Practice Tracker!
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
