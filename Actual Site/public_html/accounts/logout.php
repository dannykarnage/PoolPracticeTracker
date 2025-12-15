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
        <title>Logout - Pool Practice Tracker</title>
        <link rel="stylesheet" href="/styles/general.css">
        <link rel="stylesheet" href="/styles/header.css">
    </head>
    <body>
        
        <header>
            <?php 
            // FIX: Changed absolute path to relative path
            include('../temps/header.php'); 
            ?>
        </header>

        <main>
            <div style="margin-top: 66px"></div>
            <div class="text-grid-three-by-one">
                <div class="left-section"> </div>
                <div class="middle-section">
                    <div class="left-justified-paragraph">
                        <p>
                            <h4>You have been logged out. Thanks for visiting!</h4>
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
