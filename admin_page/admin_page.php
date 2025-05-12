<?php
    session_start();

    //info database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    if(!is_null($username) && $role == "admin"){
        try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connessione fallita: " . $e->getMessage());
        }
        // Query per recuperare il numero degli inventari
        $stmt = $conn->query("SELECT count(*) FROM inventario");
        $num_inventari = $stmt->fetchColumn(); 
        // Query per recuperare il numero dei tecnici
        $stmt = $conn->query("SELECT count(*) FROM utente");
        $num_tecnici = $stmt->fetchColumn(); 
        // Query per recuperare il numero degli account da verificare
        $stmt = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attesa'");
        $num_account_da_verificare = $stmt->fetchColumn();
        // Query per recuperare il materiale non assegnato ad alcuna classe
        $stmt = $conn->query("SELECT count(*) FROM dotazione WHERE ID_aula IS NULL");
        $num_dotazioni_non_assegnate = $stmt->fetchColumn();
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
                    <a href="user_accept/user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                </div>  
            </div>
            <!-- content contiene tutto ciò che è al di fuori della sidebar -->
            <div class="content">
                <!-- user-logout contiene il nome utente dell'utente loggato e il collegamento per il logout -->
                <div class="logout">
                    <a class="logout-btn" href="..\logout\logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <!-- Benvenuto utente -->
                <h1>Benvenuto <?php echo $username?></h1>

                <!-- cards colorate con dati aggiuntivi -->
                <div class="dashboard-cards">
                    <a href="bop.php">
                        <div class="card card-blue">
                            <div class="card-content">
                                <i class="fas fa-clipboard-list"></i>
                                <h3>Totale<br>Inventari</h3>
                            </div>
                            <span class="card-number"><?php echo $num_inventari?> inventari</span>
                        </div>
                    </a>
                    <a href="bop.php">
                        <div class="card card-green">
                            <div class="card-content">
                                <i class="fas fa-user-check"></i>
                                <h3>Tecnici<br>Attivi</h3>
                            </div>
                            <span class="card-number"><?php echo $num_tecnici?> tecnici</span>
                        </div>
                    </a>
                    <a href="bop.php">
                        <div class="card card-orange">
                        <div class="card-content">
                            <i class="fas fa-user-clock"></i>
                            <h3>Attiva<br>Account</h3>
                        </div>
                        <span class="card-number"><?php echo $num_account_da_verificare?> account</span>
                        </div>
                    </a>
                    <a href="bop.php">
                        <div class="card card-red">
                        <div class="card-content">
                            <i class="fas fa-boxes"></i>
                            <h3>Dotazioni<br>Assegnabili</h3>
                        </div>
                        <span class="card-number"><?php echo $num_dotazioni_non_assegnate?> dotazioni</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>