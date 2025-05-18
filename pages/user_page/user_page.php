<?php
    session_start();

    //info database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    if(!is_null($username) && $role == "user"){
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
        $stmt = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attivo'");
        $num_tecnici = $stmt->fetchColumn(); 

        // Query per recuperare il numero degli account da verificare
        $stmt = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attesa'");
        $num_account_da_verificare = $stmt->fetchColumn();

        // Query per recuperare il materiale non assegnato ad alcuna classe
        $stmt = $conn->query("SELECT count(*) FROM dotazione WHERE ID_aula IS NULL");
        $num_dotazioni_non_assegnate = $stmt->fetchColumn();

        //Query per recuperare i dati degli ultimi inventari e i relativi tecnici
        $stmt = $conn->prepare("
        SELECT i.ID_aula, u.username, i.data_inventario
        FROM inventario i
        LEFT JOIN utente u ON i.ID_tecnico = u.username
        ORDER BY i.data_inventario DESC
        ");
        $stmt->execute();
        $inventari_piu_tecnici = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <link rel="stylesheet" href="user_page.css">
        <title>Admin - Page</title>
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
                    <?php
                        if($role == 'admin') {
                            echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        if($role == "admin"){
                            echo '<a href="..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                            echo '<a href="..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                            echo '<a href="..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                        };
                    ?>
                    <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>
            <!-- content contiene tutto ciò che è al di fuori della sidebar -->
            <div class="content">
                <!-- user-logout contiene il nome utente dell'utente loggato e il collegamento per il logout -->
                <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <!-- Bottone "indietro" -->
                    <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <!-- Bottone logout -->
                    <a class="logout-btn" href="../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <!-- Benvenuto utente -->
                <h1>Benvenuto <?php echo $username?></h1>

                <!-- cards colorate con dati aggiuntivi -->
                <div class="dashboard-cards">
                    <a href=".\aule\aule.php">
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
                    <a href="user_accept/user_accept.php">
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

                <!-- da aggiungere -->
                 <div class="placeholder"></div>

            </div>
        </div>
    </body>
</html>