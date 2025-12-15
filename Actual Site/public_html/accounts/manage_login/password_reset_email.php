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
                    <h2>Your password reset request.</h2>
                </p>
                <p>
                    You are receiving this email because you requested to reset your password 
                    at Pool Practice Tracker. Click the link below to reset your password.
                </p>
                <p>
                    <a href="http://poolpracticetracker.com/accounts/manage_login/change_password.php?pkey='.$pkey.'">
                        Click Here to change your password.
                    </a>
                </p>
                <p>
                    If you did not make this request, kindly disregard this email.
                </p>
                <div style="width: 1fr;"></div>
        </div>
    </body>
</html>
';

?>
