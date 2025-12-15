<?php

    session_start();

    // FIX: Changed absolute path to relative path
    include('../db_files/connection.php');
    // FIX: Changed absolute path to relative path
    include('../functions.php');

    //if the user isn't logged in, redirect to the home page
    if(!isset($_SESSION['username']))
    {
        header('Location: /');
        die();
    }
    
    $username = $_SESSION['username'];
    $user_id = null;
    $user_has_results = false;
    $latest_drill = null;
    $completed_drills = array();

    // 1. Fetch User ID (Secure)
    $stmt = $conn->prepare("SELECT `id` FROM `users` WHERE `username` = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user_id = $result->fetch_assoc()['id'];
    }
    $stmt->close();
    
    if ($user_id)
    {
        // 2. Fetch all drill results for this user (Secure)
        $stmt_drills = $conn->prepare("SELECT `drill_id`, `timestamp` FROM `drill_results` WHERE `user_id` = ? ORDER BY `timestamp` DESC");
        $stmt_drills->bind_param("i", $user_id);
        $stmt_drills->execute();
        $drill_results = $stmt_drills->get_result();
        
        if ($drill_results->num_rows > 0)
        {
            $user_has_results = true;
            $rows = $drill_results->fetch_all(MYSQLI_ASSOC);
            
            // Set latest drill (first row)
            $latest_drill = $rows[0];
            
            // Get all unique completed drill IDs
            foreach ($rows as $row) {
                $drill_id_int = intval($row['drill_id']);
                if (!in_array($drill_id_int, $completed_drills))
                {
                    array_push($completed_drills, $drill_id_int);
                }
            }
            
            // Fetch latest drill name (Secure)
            $latest_drill_id = $latest_drill['drill_id'];
            $stmt_name = $conn->prepare("SELECT `name` FROM `drills` WHERE `drill_id` = ?");
            $stmt_name->bind_param("i", $latest_drill_id);
            $stmt_name->execute();
            $latest_drill['name'] = $stmt_name->get_result()->fetch_assoc()['name'];
            $stmt_name->close();

            $latest_drill['date_string'] = get_timestamp_in_english($latest_drill['timestamp']);        
        }
        $stmt_drills->close();
    }
?>

<!DOCTYPE html>

<html>
    <head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-0531879237494766" crossorigin="anonymous"></script>
        <title>
            My Account - Pool Practice Tracker
        </title>
        <link rel="stylesheet" type="text/css" href="/styles/general.css">
        <link rel="stylesheet" type="text/css" href="/styles/header.css">
        <link rel="stylesheet" type="text/css" href="/styles/user-handling.css">
        <link rel="stylesheet" type="text/css" href="/styles/accounts_page.css">
    </head>
    <body>
        
        <?php 
        // FIX: Changed absolute path to relative path
        include('../temps/header.php'); 
        ?>

        <main class="main-section">
            <div class="accounts-form-header">
                <h2>My Account</h2>
            </div>
            <form class="accounts-form" action="/index.php " method="post">
                <div class="input-group">
                    <h3>Hello, <?php echo htmlspecialchars($username); ?>!</h3>
                    <?php if($user_has_results): ?>
                        <p>
                            The last drill you completed was <b>Drill #<?php echo htmlspecialchars($latest_drill['drill_id']) . " - " . htmlspecialchars($latest_drill['name']); ?></b> on <?php echo htmlspecialchars($latest_drill['date_string']); ?>.
                        </p>
                        <p>
                            <b>Your completed drills:</b>
                            <?php 
                                // Fetch names for all completed drills efficiently
                                $drill_names = [];
                                if (!empty($completed_drills)) {
                                    $in_clause = implode(',', array_fill(0, count($completed_drills), '?'));
                                    $stmt_completed_names = $conn->prepare("SELECT `drill_id`, `name` FROM `drills` WHERE `drill_id` IN ({$in_clause})");
                                    $types = str_repeat('i', count($completed_drills));
                                    $stmt_completed_names->bind_param($types, ...$completed_drills);
                                    $stmt_completed_names->execute();
                                    $result_completed_names = $stmt_completed_names->get_result();
                                    while ($row = $result_completed_names->fetch_assoc()) {
                                        $drill_names[$row['drill_id']] = $row['name'];
                                    }
                                    $stmt_completed_names->close();
                                }
                            ?>

                            <?php foreach ($completed_drills as $drill_id): ?>
                                
                                <?php $drill_name = $drill_names[$drill_id] ?? 'Unknown Drill'; ?>

                                <p><a href="/drills/drill_results.php?drill_num=<?php echo htmlspecialchars($drill_id); ?>" class="drill_results_link">
                                    <?php echo "Drill #" . htmlspecialchars($drill_id) . " - " . htmlspecialchars($drill_name); ?>
                                </a></p>
                            <?php endforeach; ?>
                        </p>
                    <?php else: ?>
                        You have not completed any drills yet.
                    <?php endif; ?>
                </div>
            </form>
        </main>

        <?php 
        // FIX: Changed absolute path to relative path
        include('../temps/footer.php'); 
        ?>

    </body>
</html>
