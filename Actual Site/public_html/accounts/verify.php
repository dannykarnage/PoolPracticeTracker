<?php

    if(isset($_GET['vkey']))
    {
        $outcome = "";
        //include('/home/u449903691/domains/poolpracticetracker.com/public_html/functions.php');

        $vkey = $_GET['vkey'];

        // FIX: Changed absolute path to relative path
        include('../db_files/connection.php');

        // Check for unverified account with vkey (using prepared statements)
        $stmt_unverified = $conn->prepare("SELECT verified, vkey FROM users WHERE verified = 0 AND vkey = ? LIMIT 1");
        $stmt_unverified->bind_param("s", $vkey);
        $stmt_unverified->execute();
        $result_unverified = $stmt_unverified->get_result();

        if($result_unverified->num_rows != 1)
        {
            // Check for already verified account with vkey (using prepared statements)
            $stmt_verified = $conn->prepare("SELECT verified, vkey FROM users WHERE verified = 1 AND vkey = ? LIMIT 1");
            $stmt_verified->bind_param("s", $vkey);
            $stmt_verified->execute();
            $result_verified = $stmt_verified->get_result();
            
            if($result_verified->num_rows == 1)
            {
                $outcome = "This account has already been verified.";
            }
            else
            {
                $outcome = "Could not locate that account. Please try again.";
            }
            $stmt_verified->close();
        }
        else
        {
            // Validate and update the email (using prepared statements)
            $stmt_update = $conn->prepare("UPDATE users SET verified = 1 WHERE vkey = ? LIMIT 1"); 
            $stmt_update->bind_param("s", $vkey);
            $result = $stmt_update->execute();
            $stmt_update->close();

            if($result)
            {
                $outcome = "Your account has been verified. Click the \"Login\" button above to log in to your account!";
            }
            else
            {
                $outcome = "DB error: " . $conn->error;
            }
        }
        $stmt_unverified->close();
    }
    else
    {
        $outcome = "Something went wrong.";
        die();
    }

?>

<!DOCTYPE html>
<html>
    <head>
    <head>
        <title>Account Verification - by Cellar Cue Sports</title>
        <link rel="stylesheet" href="/styles/general.css">
        <link rel="stylesheet" href="/styles/header.css">
    </head>
    </head>
    <body>

        <?php 
        // FIX: Changed absolute path to relative path
        include('../temps/header.php'); 
        ?>

        <main class="main-section">
        <div class="text-grid-three-by-one">
                <div class="left-section"> </div>
                <div class="middle-section">
                    <div class="left-justified-paragraph">
                        <p>
                            <?php echo htmlspecialchars($outcome); ?>
                        </p>
                    </div>
                </div>
                <div class="right-section"> </div>
            </div>
        </main>

        </main>

        <?php 
        // FIX: Changed absolute path to relative path
        include('../temps/footer.php'); 
        ?>
    </body>
</html>
