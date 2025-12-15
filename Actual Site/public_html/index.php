<?php

    session_start();

?>

<!DOCTYPE html>
<html>
    <head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-0531879237494766" crossorigin="anonymous"></script>
        <title>Pool Practice Tracker - by Cellar Cue Sports</title>
        <link rel="stylesheet" href="/styles/general.css">
        <link rel="stylesheet" href="/styles/header.css">
    </head>
    <body>

        <?php include('./temps/header.php') ?>

        <main class="main-section">
            <!--<div class="page-title">
                Pool Practice Tracker
            </div>
            <div class="page-subtitle">
                Improving your game through laborious repetition!
            </div>-->
            <div class="three-image-grid">
                <div class="right-section">
                    <img src="/images/main-page-table-diagram.png" class="three-image-grid-image">
                </div>
                <div class="middle-section">
                    <img src="/images/website-logo.png" class="main-page-logo">
                </div>
                <div class="left-section">
                    <img src="/images/pool-practice-drill.jpeg" class="three-image-grid-image">                    
                </div>
            </div>
            <div class="centered-youtube-box">
                <iframe
                    width="560" 
                    height="315" src="https://www.youtube.com/embed/O-zxKYgW86E"
                    title="YouTube video player"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>
            <div class="text-grid-three-by-one">
                <div class="left-section"> </div>
                <div class="middle-section">
                    <div class="left-justified-paragraph">
                        <h2>
                            Welcome to the Pool Practice Tracker!
                        </h2>
                        <p>
                            PoolPracticeTracker.com is a resource for improving your skills in the game of Pool. Using
                            a comprehensive (and ever-growing!) set of practice drills, users of this website can track
                            their skills progression over time by logging their scores on each drill during each practice
                            session.
                        </p>
                        <p>
                            As of our launch on December 16th, 2022, we have 16 practice drills available. We will be
                            adding to these constantly, so come back often to view new drills as they become available!
                        </p>
                        <p>
                            The full list of availabe drills can be viewed using the
                            <a style="text-decoration: underline; color: inherit" href="/drills">
                            Drills</a> link above.
                        </p>
                        <p>
                            You'll need to
                            <a style="text-decoration: underline; color: inherit" href="/accounts/login.php">
                            login to your account</a> to be able to log your scores and keep track of your progress. If
                            you don't have an account,
                            <a style="text-decoration: underline; color: inherit" href="/accounts/register.php">
                            create one here</a> and get started right away!
                        </p>
                    </div>
                </div>
                <div class="right-section"> </div>
            </div>
        </main>

        <?php include('./temps/footer.php'); ?>

    </body>
</html>