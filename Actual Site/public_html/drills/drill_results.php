<?php

    session_start();

    //include('/home/u449903691/domains/poolpracticetracker.com/public_html/functions.php');
    // FIX: Changed absolute path to relative path
    include('../db_files/connection.php');

    //if the user isn't logged in, redirect to the home page
    if(!isset($_SESSION['username']))
    {
        header('Location: /');
        die();
    }

    //if the drill number (drill_id) is not set, redirect back to the accounts page
    if(!isset($_GET['drill_num']) || !is_numeric($_GET['drill_num']))
    {
        header('Location: /accounts');
        die();
    }

    $drill_id = $_GET['drill_num'];
    $username = $_SESSION['username'];
    $user_id = null;
    $drill_results = null;

    // 1. Fetch User ID (Secure)
    $stmt_user = $conn->prepare("SELECT `id` FROM `users` WHERE `username` = ?");
    $stmt_user->bind_param("s", $username);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    if($result_user->num_rows == 1)
    {
        $user_id = $result_user->fetch_assoc()['id'];
    }
    else
    {
        //means the username has no user_id. Log the user out
        header('Location: /accounts/logout.php');
        die();
    }
    $stmt_user->close();

    // 2. Fetch Drill Results (Secure)
    $stmt_results = $conn->prepare("SELECT `pass`, `score`, `timestamp` FROM `drill_results` WHERE `user_id` = ? AND `drill_id` = ? ORDER BY `timestamp` ASC");
    $stmt_results->bind_param("ii", $user_id, $drill_id);
    $stmt_results->execute();
    $drill_results = $stmt_results->get_result();

    if(!$drill_results || $drill_results->num_rows === 0)
    {
        //means that the query failed or no results found. Redirect back to the accounts page.
        header('Location: /accounts');
        die();
    }
    $rows = $drill_results->fetch_all(MYSQLI_ASSOC);
    $stmt_results->close();


    // 3. Fetch Drill Details (Secure)
    $stmt_details = $conn->prepare("SELECT * FROM `drills` WHERE `drill_id` = ?");
    $stmt_details->bind_param("i", $drill_id);
    $stmt_details->execute();
    $drill_detail_results = $stmt_details->get_result();
    
    if(!$drill_detail_results || $drill_detail_results->num_rows != 1)
    {
        header('Location: /accounts');
        die();
    }
    $drill_details = $drill_detail_results->fetch_assoc();
    $stmt_details->close();

    //drill types
    // 1 = pass/fail only
    // 2 = score only
    // 3 = score out of number without pass/fail
    // 4 = score out of number with pass/fail
    $drill_type = 0;
    $data = array(
        "timestamp" => array()
    );
    
    // Deterimine the drill type and populate $data array
    if ($drill_details['pass_fail'] && !$drill_details['out_of'])
    {
        $drill_type = 1;
        $data["pass"] = array();
        foreach ($rows as $row) {
            $data["timestamp"][] = $row['timestamp'];
            $data["pass"][] = $row['pass'] ? "Pass" : "Fail";
        }
    }
    elseif ($drill_details['score'] && !$drill_details['out_of'])
    {
        $drill_type = 2;
        $data["score"] = array();
        // Initialize max/min score with the first data point, then iterate
        $first_score = $rows[0]['score'];
        $max_score = $first_score;
        $min_score = $first_score;
        
        foreach ($rows as $row) {
            $data["timestamp"][] = $row['timestamp'];
            $data["score"][] = $row['score'];
            
            if($row['score'] > $max_score)
            {
                $max_score = $row['score'];
            }
            if($row['score'] < $min_score)
            {
                $min_score = $row['score'];
            }
        }
    }
    elseif ($drill_details['score'] && $drill_details['out_of'] && !$drill_details['pass_fail'])
    {
        $drill_type = 3;
        $data["score"] = array();
        foreach ($rows as $row) {
            $data["timestamp"][] = $row['timestamp'];
            $data["score"][] = $row['score'];
        }
    }
    elseif ($drill_details['score'] && $drill_details['out_of'] && $drill_details['pass_fail'])
    {
        $drill_type = 4;
        $data["score"] = array();
        $data["pass"] = array();
        foreach ($rows as $row) {
            $data["timestamp"][] = $row['timestamp'];
            $data["score"][] = $row['score'];
            $data["pass"][] = $row['pass'] ? "Pass" : "Fail";
        }
    }
    else
    {
        // Fallback for drill type issue
        header('Location: /accounts');
        die();
    }
?>
<!DOCTYPE html>

<html>
    <head>
        <title>
            History for Drill #<?php echo htmlspecialchars($drill_id); ?> - Pool Practice Tracker
        </title>
        <link rel="stylesheet" type="text/css" href="/styles/general.css">
        <link rel="stylesheet" type="text/css" href="/styles/header.css">
        <link rel="stylesheet" type="text/css" href="/styles/history.css">
    </head>
    <body>

        <?php 
        // FIX: Changed absolute path to relative path
        include('../temps/header.php'); 
        ?>

        <main class="main-section">
            <div class="history-header">
                <h4>History of Drill #<?php echo htmlspecialchars($drill_id); ?> for <?php echo htmlspecialchars($username); ?></h4>
            </div>

            <?php 
            // FIX: Changed absolute path to relative path
            include('type' . $drill_type . '_drill_html.php'); 
            ?>
        </main>

        <?php 
        // FIX: Changed absolute path to relative path
        include('../temps/footer.php'); 
        ?>

    </body>
</html>
