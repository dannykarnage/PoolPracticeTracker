<?php 
$email_message = '
<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <div style="background-color: gray;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
        border-radius: 30px;
        ">
            <div style="width: 1fr;"></div>
            <div style="font-family: Arial, Helvetica, sans-serif;
            font-size: 20px;
            color: rgb(175, 250, 250);
            display: flex;
            flex-direction: column;
            background-color: rgba(42, 71, 71, 0.6);
            padding-left: 20px;
            padding-right: 20px;
            border-radius: 30px;
            align-items: center;
            width: 450px;
            ">
                <p>
                    <h2>Thank you for registering for Pool Practice Tracker!</h2>
                </p>
                <p>
                    Please follow the link below to verify your email and complete 
                    your registration. You will then be able to log in and begin tracking 
                    your practice routines!
                </p>
                <p>
                    <a href="http://poolpracticetracker.com/accounts/verify.php?vkey='.$vkey.'">
                        Click Here to verify your account!
                    </a>
                </p>
            <div style="width: 1fr;"></div>
        </div>
    </body>
</html>
';

?>