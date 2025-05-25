<?php
    session_start(); // Avvia la sessione per mantenere i dati dell'utente loggato

    // Configurazione dei parametri per la connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupero delle variabili di sessione: username e ruolo dell'utente
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Controllo che l'utente sia loggato e che abbia il ruolo di admin
    if(!is_null($username) && $role == "admin"){
        try {
            // Connessione al database tramite PDO
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            // Configura PDO per lanciare eccezioni in caso di errore
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // In caso di errore di connessione, termina lo script mostrando il messaggio di errore
            die("Connessione fallita: " . $e->getMessage());
        }

        // Esecuzione di una query per contare il numero totale di aule nel database
        $stmt = $conn->query("SELECT count(*) FROM aula");
        $num_aule = $stmt->fetchColumn();

        // Esecuzione di una query per contare il numero totale di tecnici con stato 'attivo'
        $stmt = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attivo'");
        $num_tecnici = $stmt->fetchColumn();

        // Esecuzione di una query per contare gli account in attesa di verifica (stato 'attesa')
        $stmt = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attesa'");
        $num_account_da_verificare = $stmt->fetchColumn();

        // Esecuzione di una query per contare il materiale contrassegnato come 'mancante'
        $stmt = $conn->query("SELECT count(*) FROM dotazione WHERE stato = 'mancante'");
        $num_dotazioni_mancanti = $stmt->fetchColumn();

        // Preparazione di una query per recuperare gli ultimi inventari con i relativi tecnici associati
        $stmt = $conn->prepare("
            SELECT i.ID_aula, u.username, i.data_inventario
            FROM inventario i
            LEFT JOIN utente u ON i.ID_tecnico = u.username
            ORDER BY i.data_inventario DESC
        ");
        $stmt->execute();
        $inventari_piu_tecnici = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query per ottenere il numero totale di dotazioni presenti nel sistema
        $stmt = $conn->query("SELECT COUNT(*) FROM dotazione");
        $tot_dotazioni = $stmt->fetchColumn();

        // Query per contare le dotazioni con stato 'presente'
        $stmt = $conn->query("SELECT COUNT(*) FROM dotazione WHERE stato = 'presente'");
        $dotazioni_assegnate = $stmt->fetchColumn();

        // Query per contare le dotazioni con stato 'archiviato'
        $stmt = $conn->query("SELECT COUNT(*) FROM dotazione WHERE stato = 'archiviato'");
        $dotazioni_archiviate = $stmt->fetchColumn();

        // Query per contare le dotazioni con stato 'eliminato'
        $stmt = $conn->query("SELECT COUNT(*) FROM dotazione WHERE stato = 'eliminato'");
        $dotazioni_eliminate = $stmt->fetchColumn();

        // Calcolo delle percentuali di dotazioni assegnate, archiviate ed eliminate,
        // con controllo per evitare divisione per zero
        $perc_assegnate = $tot_dotazioni > 0 ? round(($dotazioni_assegnate / $tot_dotazioni) * 100) : 0;
        $perc_archiviate = $tot_dotazioni > 0 ? round(($dotazioni_archiviate / $tot_dotazioni) * 100) : 0;
        $perc_eliminate = $tot_dotazioni > 0 ? round(($dotazioni_eliminate / $tot_dotazioni) * 100) : 0;
    } else {
        // Se l'utente non è autenticato o non ha il ruolo admin, viene reindirizzato al logout
        header("Location: ..\..\..\logout\logout.php");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Inclusione dei file CSS per lo stile della pagina -->
    <link rel="stylesheet" href="..\..\..\assets\css\background.css">
    <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
    <link rel="stylesheet" href="admin_page.css">
    <title>Admin - Page</title>
    <!-- Inclusione Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <div class="container">

        <!-- Sidebar di navigazione laterale -->
        <div class="sidebar">
            <!-- Immagine profilo o logo -->
            <div class="image"><img src="..\..\..\assets\images\placeholder.png" width="120px"></div>
            <!-- Contenitore dei link di navigazione -->
            <div class="section-container">
                <br>
                <!-- Link alla pagina principale admin con evidenziazione -->
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

        <!-- Contenuto principale della pagina, a destra della sidebar -->
        <div class="content">
            <!-- Barra superiore con nome utente e pulsanti logout e indietro -->
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <!-- Pulsante per tornare indietro alla pagina precedente -->
                <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <!-- Pulsante per effettuare il logout -->
                <a class="logout-btn" href="../../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>

            <!-- Messaggio di benvenuto personalizzato con il nome utente -->
            <h1>Benvenuto <?php echo $username?></h1>

            <!-- Sezione di cards colorate che mostrano statistiche e dati sintetici -->
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

            <!-- Sezione riepilogativa delle percentuali di dotazioni per stato -->
            <div class="placeholder" style="display:flex; flex-direction:column; align-items:center; justify-content:center; color:white; font-size:1.3rem;">
                <div><b>Dotazioni assegnate:</b> <?php echo $perc_assegnate; ?>%</div>
                <div><b>Dotazioni archiviate:</b> <?php echo $perc_archiviate; ?>%</div>
                <div><b>Dotazioni eliminate:</b> <?php echo $perc_eliminate; ?>%</div>
            </div>

            <!-- Tabella che mostra le ultime 3 attività degli utenti tecnici sugli inventari -->
            <div class="log-container">
                <div class="log-title"><span>Attività recenti</span></div>
                <div class="log-content">
                    <table>
                        <?php 
                            $i = 0; // Contatore per limitare la visualizzazione a massimo 3 righe
                            foreach ($inventari_piu_tecnici as $inventario_piu_tecnico) {
                                // Stampa di una riga della tabella con nome tecnico, aula e data aggiornamento inventario
                                echo "<tr><td>" . htmlspecialchars($inventario_piu_tecnico["username"]) . 
                                     " ha aggiornato l'inventario di " . htmlspecialchars($inventario_piu_tecnico["ID_aula"]) . 
                                     " in data " . htmlspecialchars($inventario_piu_tecnico["data_inventario"]) . 
                                     "</td></tr>";
                                $i++;
                                if($i == 3) break; // Uscita dal ciclo dopo 3 righe
                            }
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
