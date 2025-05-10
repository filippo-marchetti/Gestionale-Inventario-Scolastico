<?php
    $nomeUtente = "admin";
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
            
                <div class="section-container">
                    <br>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                </div>  
            </div>
            <div class="content">
                <div class="user-details">
                    <!-- <div class="profile-pic">
                        <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png" alt="Profilo">
                    </div> -->
                    <div class="profile-pic">
                        <img src="..\assets\images\placeholder.png" width="120px">
                    </div>
                    <span class="username"><?php echo $nomeUtente; ?></span>
                    <a href="logout.php" class="logout-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>