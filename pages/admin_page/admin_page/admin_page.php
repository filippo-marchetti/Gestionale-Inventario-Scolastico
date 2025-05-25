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
        $stmt = $conn->query("SELECT count(*) FROM aula");
        $num_aule = $stmt->fetchColumn(); 

        // Query per recuperare il numero dei tecnici
        $stmt = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attivo'");
        $num_tecnici = $stmt->fetchColumn(); 

        // Query per recuperare il numero degli account da verificare
        $stmt = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attesa'");
        $num_account_da_verificare = $stmt->fetchColumn();

        // Query per recuperare il materiale non assegnato ad alcuna classe
        $stmt = $conn->query("SELECT count(*) FROM dotazione WHERE stato = 'mancante'");
        $num_dotazioni_mancanti = $stmt->fetchColumn();

        //Query per recuperare i dati degli ultimi inventari e i relativi tecnici
        $stmt = $conn->prepare("
        SELECT i.ID_aula, u.username, i.data_inventario
        FROM inventario i
        LEFT JOIN utente u ON i.ID_tecnico = u.username
        ORDER BY i.data_inventario DESC
        ");
        $stmt->execute();
        $inventari_piu_tecnici = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->query("SELECT COUNT(*) FROM dotazione");

        $tot_dotazioni = $stmt->fetchColumn();

        $stmt = $conn->query("SELECT COUNT(*) FROM dotazione WHERE stato = 'presente'");
        $dotazioni_assegnate = $stmt->fetchColumn();

        $stmt = $conn->query("SELECT COUNT(*) FROM dotazione WHERE stato = 'archiviato'");
        $dotazioni_archiviate = $stmt->fetchColumn();

        $stmt = $conn->query("SELECT COUNT(*) FROM dotazione WHERE stato = 'eliminato'");
        $dotazioni_eliminate = $stmt->fetchColumn();

        // Calcolo percentuali (evita divisione per zero)
        $perc_assegnate = $tot_dotazioni > 0 ? round(($dotazioni_assegnate / $tot_dotazioni) * 100) : 0;
        $perc_archiviate = $tot_dotazioni > 0 ? round(($dotazioni_archiviate / $tot_dotazioni) * 100) : 0;
        $perc_eliminate = $tot_dotazioni > 0 ? round(($dotazioni_eliminate / $tot_dotazioni) * 100) : 0;
    }else{
        header("Location: ..\..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="..\..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="admin_page.css">
        <title>Admin - Page</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        

        <div class="container">
            
            <!-- sidebar -->
            <div class="sidebar">
                <div class="image"><img src="..\..\..\assets\images\placeholder.png" width="120px"></div>
                <!-- questa div conterrà i link delle schede -->
                <div class="section-container">
                    <br>
                    <a href="admin_page.php"><div class="section selected"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>
                    <a href="../../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="../mostra_user_attivi/mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>
                    <a href="../user_accept/user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>
                    <a href="../nuovo_admin/nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>
                    <a href="../../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="../../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="../../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="../../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                    <a href="../../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
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
                    <a class="logout-btn" href="../../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <!-- Benvenuto utente -->
                <h1>Benvenuto <?php echo $username?></h1>

                <!-- cards colorate con dati aggiuntivi -->
                <div class="dashboard-cards">
                    <a href="../../aule/aule.php">
                        <div class="card card-blue">
                            <div class="card-content">
                                <i class="fas fa-home"></i>
                                <h3>Totale<br>Aule</h3>
                            </div>
                            <span class="card-number"><?php echo $num_aule?> aule</span>
                        </div>
                    </a>
                    <a href="../mostra_user_attivi/mostra_user_attivi.php">
                        <div class="card card-green">
                            <div class="card-content">
                                <i class="fas fa-user-check"></i>
                                <h3>Tecnici<br>Attivi</h3>
                            </div>
                            <span class="card-number"><?php echo $num_tecnici?> tecnici</span>
                        </div>
                    </a>
                    <a href="../user_accept/user_accept.php">
                        <div class="card card-orange">
                        <div class="card-content">
                            <i class="fas fa-user-clock"></i>
                            <h3>Attiva<br>Account</h3>
                        </div>
                        <span class="card-number"><?php echo $num_account_da_verificare?> account</span>
                        </div>
                    </a>
                    <a href="../../lista_dotazione/lista_dotazione.php">
                        <div class="card card-red">
                        <div class="card-content">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Dotazioni<br>Mancanti</h3>
                        </div>
                        <span class="card-number"><?php echo $num_dotazioni_mancanti?> dotazioni</span>
                        </div>
                    </a>
                </div>

                <!-- da aggiungere -->
                 <div class="placeholder" style="display:flex; flex-direction:column; align-items:center; justify-content:center; color:white; font-size:1.3rem;">
                    <div><b>Dotazioni assegnate:</b> <?php echo $perc_assegnate; ?>%</div>
                    <div><b>Dotazioni archiviate:</b> <?php echo $perc_archiviate; ?>%</div>
                    <div><b>Dotazioni eliminate:</b> <?php echo $perc_eliminate; ?>%</div>
                </div>

                <!-- task recenti -->
                <div class="log-container">
                    <div class="log-title"><span>Attività recenti</span></div>
                    <div class="log-content">
                        <table>
                            <?php 
                                $i = 0; //serve a prendere solo le ultime 3 azioni
                                foreach ($inventari_piu_tecnici as $inventario_piu_tecnico) {
                                    echo "<tr><td>".$inventario_piu_tecnico["username"]." ha aggiornato l'inventario di ".$inventario_piu_tecnico["ID_aula"]." in data ".$inventario_piu_tecnico["data_inventario"]."</tr></td>";
                                    $i++;
                                    if($i == 3) exit;
                                }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>