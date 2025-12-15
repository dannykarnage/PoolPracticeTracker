<?php

    session_start();
    include('../db_files/connection.php');
    $error_message = "";
    $post_message = "";
    $row = null; // Drill details row

    if($_SERVER['REQUEST_METHOD'] == "GET")
    {
        // --- GET Request: Fetch Drill Details ---
        if(!isset($_GET['drill_id']))
        {
            $error_message = "Error - no drill selected. Please try again.";
        }
        elseif(!is_numeric($_GET['drill_id']))
        {
            $error_message = "Invalid drill ID. Please try again.";
        }
        else
        {
            $drill_id = $_GET['drill_id'];
            
            // Fetch drill details (Secure)
            $stmt = $conn->prepare("SELECT * FROM drills WHERE drill_id = ? AND published = 1 LIMIT 1");
            $stmt->bind_param("i", $drill_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result && $result->num_rows == 1)
            {
                $row = $result->fetch_assoc();
            }
            else
            {
                $error_message = "That drill could not be found.";
            }
            $stmt->close();
        }
    }
    elseif($_SERVER['REQUEST_METHOD'] == "POST")
    {
        // --- POST Request: Record Drill Result ---
        if(empty($_SESSION['username']))
        {
            $error_message = "No user logged in. Please log in to record results.";
        }
        else
        {
            $drill_id = $_POST['drill_id'];
            $username = $_SESSION['username'];
            $user_id = null;

            // 1. Fetch User ID (Secure)
            $stmt_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_user->bind_param("s", $username);
            $stmt_user->execute();
            $user_result = $stmt_user->get_result();
            if ($user_result->num_rows == 1) {
                $user_id = $user_result->fetch_assoc()['id'];
            }
            $stmt_user->close();
            
            if (!$user_id) {
                 $error_message = "User session invalid. Please log in again.";
            }
            
            // 2. Fetch Drill Details (Secure)
            if (empty($error_message)) {
                $stmt_drill = $conn->prepare("SELECT * FROM drills WHERE drill_id = ? AND published = 1 LIMIT 1");
                $stmt_drill->bind_param("i", $drill_id);
                $stmt_drill->execute();
                $result = $stmt_drill->get_result();
                
                if($result && $result->num_rows == 1)
                {
                    $row = $result->fetch_assoc();
                }
                else
                {
                    $error_message = "That drill could not be found.";
                }
                $stmt_drill->close();
            }


            // 3. Process and Insert Result (Secure)
            if (empty($error_message)) 
            {
                $pass = null;
                $score = null;
                $success_message = "Result recorded.";
                
                // Determine values based on drill type
                if($row['out_of'] && $row['pass_fail'])
                {
                    $score = intval($_POST['score']);
                    $pass = ($score >= intval($row['out_of_pass'])) ? 1 : 0;
                    $success_message = $pass ? "Result recorded, and you passed!" : "Result recorded. This was not a passing score, but keep trying!";
                }
                elseif($row['score'])
                {
                    $score = intval($_POST['score']);
                    $success_message = "Result recorded. Good job!";
                }
                elseif($row['pass_fail'])
                {
                    $pass = ($_POST['pass'] == "yes") ? 1 : 0;
                    $success_message = $pass ? "Result recorded. You passed!" : "Result recorded. Keep practicing!";
                }
                
                // Prepare INSERT statement based on what data is present
                $stmt_insert_sql = "INSERT INTO `drill_results` (`user_id`, `drill_id`, `pass`, `score`, `timestamp`) VALUES (?, ?, ?, ?, current_timestamp())";
                $stmt_insert = $conn->prepare($stmt_insert_sql);

                // Dynamically bind parameters based on which fields are used
                if ($pass !== null && $score !== null) {
                    $stmt_insert->bind_param("iiis", $user_id, $drill_id, $pass, $score);
                } elseif ($pass !== null) {
                    $score_null = null; // MySQLi requires a variable for null
                    $stmt_insert->bind_param("iiis", $user_id, $drill_id, $pass, $score_null);
                } elseif ($score !== null) {
                    $pass_null = null; // MySQLi requires a variable for null
                    $stmt_insert->bind_param("iiis", $user_id, $drill_id, $pass_null, $score);
                } else {
                    $error_message = "Invalid drill type or missing score/pass data.";
                }
                
                // Execute insertion
                if (empty($error_message)) {
                    $insert_result = $stmt_insert->execute();
                    if($insert_result)
                    {
                        $post_message = $success_message;
                    }
                    else
                    {
                        $error_message = "An error occurred while trying to record your result. Please try again. Error: " . $conn->error;
                    }
                }
                $stmt_insert->close();
            }
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <?php if($row): ?>
            <title>Drill #<?php echo htmlspecialchars($row['drill_id']) ?> - Pool Practice Tracker</title>
        <?php else: ?>
            <title>Pool Practice Tracker</title>
        <?php endif; ?>
        <link rel="stylesheet" href="/styles/general.css">
        <link rel="stylesheet" href="/styles/header.css">
        <link rel="stylesheet" type="text/css" href="/styles/user-handling.css">
        <link rel="stylesheet" type="text/css" href="/styles/drills.css">
    </head>
    <body style="padding-bottom: 40px;">

        <?php include('/home/u449903691/domains/poolpracticetracker.com/public_html/temps/header.php'); ?>

        <?php if(!empty($error_message)): ?>
            <div class="ueser-form-error" style="width: 600px; margin-top: 100px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php die(); ?>
        <?php endif; ?>

        <main class="main-section">
            <div class="page-subtitle">
                Drill #<?php echo htmlspecialchars($row['drill_id']); ?> - <?php echo htmlspecialchars($row['name']); ?>
            </div>
            <div class="text-grid-three-by-one">
                <div class="left-section"> </div>
                <div class="middle-section">
                    <div class="left-justified-paragraph">
                        <?php echo $row['description']; ?>
                    </div>
                </div>
                <div class="right-section"></div>
            </div>

            <?php if(!empty($_SESSION['username'])): ?>
                <div class="user-heading">
                    <h2>Record your practice result!</h2>
                </div>
                <form action="drill_details.php" class="user-form" method="post">
                    <?php if(!empty($post_message)): ?>
                        <div style="width: 92%; margin: 0px auto; padding: 10px; border: 1px solid rgb(117, 199, 199); color: rgb(117, 199, 199); background: rgb(225, 255, 255); border-radius: 5px; text-align: left;">
                            <p><?php echo htmlspecialchars($post_message); ?></p>
                        </div>
                        <?php endif; ?>
                    <div class="input-group">
                        <label for="drill_id">Drill #</label>
                        <select name="drill_id" id="drill_id" readonly="true">
                            <option value="<?php echo htmlspecialchars($row['drill_id']); ?>"><?php echo htmlspecialchars($row['drill_id']); ?></option>
                        </select>
                    </div>
                    <!--an "out of" means that the drill has a score out of number, such as scoring 7 out of 10 -->
                    <?php if($row['out_of']): ?>
                        <div class="input-group">
                            <label for="score">Score:</label>
                            <select name="score" id="score" style="font-size: 16px">
                                <?php for($i = 0; $i <= $row['out_of_num']; $i++): ?>
                                    <option value="<?php echo htmlspecialchars($i); ?>"><?php echo htmlspecialchars($i); ?></option>
                                <?php endfor; ?>
                            </select>
                            out of <?php echo htmlspecialchars($row['out_of_num']); ?>
                        </div>
                    <?php elseif($row['score']): ?>
                        <div class="input-group">
                            <label for="score">Score:</label>
                            <input type="number" id="score" name="score" min="0" style="font-size: 16px;">
                        </div>
                    <?php elseif($row['pass_fail']): ?>
                        <div class="input-group">
                            <label for="pass">Did you pass the drill?</label>
                            <select name="pass" id="pass" style="font-size: 16px;">
                                <option value="no">No</option>
                                <option value="yes">Yes</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="input-group">
                        <button id="button" type="submit" value="submit" name="submit" class="user-form-btn" style="cursor: pointer;">Submit Result</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <?php if($row['has_table_diagram']): ?>
                <div>
                    <a href="/drills/diagrams/<?php echo htmlspecialchars($row['drill_id']); ?>.png">
                        <img src="/drills/diagrams/<?php echo htmlspecialchars($row['drill_id']); ?>.png" class="centered_table_diagram">
                    </a>
                </div>
                <div class="text-grid-three-by-one">
                    <div class="left-section"></div>
                    <div class="middle-section" style="margin-top: 0px;">
                        Click the image to enlarge.
                    </div>
                    <div class="right-section"></div>
                </div>
            <?php endif; ?>

            <?php if($row['has_video']): ?>
                <div class="centered-youtube-box">
                    <iframe
                        width="560" 
                        height="315" src="https://www.youtube.com/embed/<?php echo htmlspecialchars($row['youtube_video_code']); ?>"
                        title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="text-grid-three-by-one">
                    <div class="left-section"></div>
                    <div class="middle-section" style="margin-top: 0px;">
                        Demonstration of drill #<?php echo htmlspecialchars($row['drill_id']); ?> by Cellar Cue Sports
                    </div>
                    <div class="right-section"></div>
                </div>
            <?php endif; ?>

            <?php if($row['has_creator_video']): ?>
                <div class="centered-youtube-box">
                    <iframe
                        width="560" 
                        height="315" src="https://www.youtube.com/embed/<?php echo htmlspecialchars($row['creator_video_youtube_video_code']); ?>"
                        title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="text-grid-three-by-one">
                    <div class="left-section"></div>
                    <div class="middle-section" style="margin-top: 0px;">
                        <?php echo htmlspecialchars($row['creator_video_description']); ?>
                    </div>
                    <div class="right-section"></div>
                </div>
            <?php endif; ?>
        </main>

        <?php include('../temps/footer.php'); ?>

    </body>
</html>
