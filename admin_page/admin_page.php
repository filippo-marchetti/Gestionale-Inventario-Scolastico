<?php
    session_start();

    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    if(!is_null($username) && $role == "admin"){

    }else{
        header("Location: ..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="..\assets\css\background.css">
        <link rel="stylesheet" href="..\assets\css\shared_style_user_admin.css">
        <title>Document</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        

        <div class="container">
            
            <!-- sidebar -->
            <div class="sidebar">
                <div class="image"><img src="..\assets\images\logo_darzo.png" width="120px"></div>
                <!-- questa div conterrà i link delle schede -->
                <div class="section-container">
                    <br>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                </div>  
            </div>
            <!-- content contiene tutto ciò che è al di fuori della sidebar -->
            <div class="content">
                <!-- user-logout contiene il nome utente dell'utente loggato e il collegamento per il logout -->
                <div class="user-logout">
                    <span class="username"><?php echo $username; ?></span>
                    <a class="logout-btn" href="..\logout\logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>