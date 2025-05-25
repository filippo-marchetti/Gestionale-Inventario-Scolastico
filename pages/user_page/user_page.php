<?php
    session_start(); // Avvia la sessione per poter accedere alle variabili di sessione

    // Informazioni per la connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupera le variabili di sessione per username e ruolo utente
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Controlla che l'utente sia autenticato e che abbia ruolo "user"
    if(!is_null($username) && $role == "user"){
        try {
            // Connessione al database con PDO
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Se la connessione fallisce, termina lo script con un messaggio d'errore
            die("Connessione fallita: " . $e->getMessage());
        }

        // Query per contare il numero totale delle aule presenti nel database
        $stmt = $conn->query("SELECT count(*) FROM aula");
        $num_aule = $stmt->fetchColumn(); 

        // Query per contare il numero dei tecnici (utenti con stato "attivo")
        $stmt = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attivo'");
        $num_tecnici = $stmt->fetchColumn(); 

        // Query per contare quanti account sono in stato "attesa" (da verificare)
        $stmt = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attesa'");
        $num_account_da_verificare = $stmt->fetchColumn();

        // Query per contare il numero di dotazioni mancanti
        $stmt = $conn->query("SELECT count(*) FROM dotazione WHERE ID_aula IS NULL AND stato = 'mancante'");
        $num_dotazioni_mancanti = $stmt->fetchColumn();

        // Query per recuperare dati degli inventari con il relativo tecnico che li ha effettuati,
        // ordinati per data inventario decrescente (ultimi inventari prima)
        $stmt = $conn->prepare("
            SELECT i.ID_aula, u.username, i.data_inventario
            FROM inventario i
            LEFT JOIN utente u ON i.ID_tecnico = u.username
            ORDER BY i.data_inventario DESC
        ");
        $stmt->execute();
        $inventari_piu_tecnici = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Inventari per mese
        $stmt = $conn->query("
            SELECT DATE_FORMAT(data_inventario, '%Y-%m') AS mese, COUNT(*) AS totale
            FROM inventario
            GROUP BY mese
            ORDER BY mese ASC
        ");
        $inventari_per_mese = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcolo degli ultimi 6 mesi (incluso quello attuale)
        $oggi = new DateTime(); // Ottiene la data e l'ora attuali
        $mesi_da_mostrare = []; // Inizializza un array per contenere i mesi da visualizzare

        // Ciclo per ottenere il mese attuale e i 5 mesi precedenti
        for ($i = 5; $i >= 0; $i--) {
            $data = (clone $oggi)->modify("-$i months"); // Clona la data odierna e sottrae $i mesi
            $mesi_da_mostrare[] = $data->format('Y-m');  // Aggiunge il mese all'elenco
        }

        $mesi = [];   // Array che conterrà i nomi dei mesi da visualizzare nel grafico
        $totali = []; // Array che conterrà i totali degli inventari per ciascun mese

        // Scorre tutti i risultati ottenuti dalla query SQL
        foreach ($inventari_per_mese as $riga) {
            // Verifica se il mese corrente è tra quelli da mostrare
            if (in_array($riga['mese'], $mesi_da_mostrare)) {
                $date = DateTime::createFromFormat('Y-m', $riga['mese']); // Converte 'YYYY-MM' in oggetto DateTime
                $mesi[] = strftime('%B %Y', $date->getTimestamp());  // Aggiunge il nome del mese in italiano 
                $totali[] = $riga['totale']; // Aggiunge il numero di inventari effettuati in quel mese
            }
        }
    } else {
        // Se l'utente non è autenticato o non ha ruolo "user" viene reindirizzato al logout
        header("Location: ..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Collegamenti ai file CSS per lo stile della pagina -->
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="user_page.css">
        <title>User - Page</title>
        <!-- Font Awesome per le icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <div class="container">
            <!-- Sidebar di navigazione -->
            <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\logo_darzo.png" width="120px"></div>
                <div class="section-container">
                    <br>
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
                    <a href="../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                    <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>

            <!-- Contenuto principale della pagina -->
            <div class="content">
                <!-- Barra in alto con pulsanti indietro e logout -->
                <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <!-- Bottone "indietro" per tornare alla pagina precedente -->
                    <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <!-- Bottone logout -->
                    <a class="logout-btn" href="../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <!-- Messaggio di benvenuto con il nome utente -->
                <h1>Benvenuto <?php echo $username?></h1>

                <!-- Dashboard con card contenenti statistiche e link -->
                <div class="dashboard-cards">
                    <!-- Card Aule -->
                    <a href="../aule/aule.php">
                        <div class="card card-blue">
                            <div class="card-content">
                                <i class="fas fa-home"></i>
                                <h3>Totale<br>Aule</h3>
                            </div>
                            <span class="card-number"><?php echo $num_aule ?> aule</span>
                        </div>
                    </a>

                    <!-- Card Tecnici attivi -->
                    <a href="bop.php">
                        <div class="card card-green">
                            <div class="card-content">
                                <i class="fas fa-user-check"></i>
                                <h3>Tecnici<br>Attivi</h3>
                            </div>
                            <span class="card-number"><?php echo $num_tecnici?> tecnici</span>
                        </div>
                    </a>

                    <!-- Card Account da attivare -->
                    <a href="user_accept/user_accept.php">
                        <div class="card card-orange">
                            <div class="card-content">
                                <i class="fas fa-user-clock"></i>
                                <h3>Attiva<br>Account</h3>
                            </div>
                            <span class="card-number"><?php echo $num_account_da_verificare?> account</span>
                        </div>
                    </a>

                    <!-- Card Dotazioni mancanti -->
                    <a href="bop.php">
                        <div class="card card-red">
                            <div class="card-content">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>Dotazioni<br>Mancanti</h3>
                            </div>
                            <span class="card-number"><?php echo $num_dotazioni_mancanti ?> dotazioni</span>
                        </div>
                    </a>
                </div>

                <!-- Grafico -->
                <div class="grafico">
                    <canvas id="graficoInventari"></canvas>
                </div>

            </div>
        </div>
        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const mesi = <?php echo json_encode($mesi); ?>;
            const totali = <?php echo json_encode($totali); ?>;

            new Chart(document.getElementById('graficoInventari'), {
                type: 'bar',
                data: {
                    labels: mesi, // Etichette dell'asse X (mesi)
                    datasets: [{
                        label: 'Inventari effettuati', // Etichetta visibile nella legenda
                        data: totali, // Valori associati ad ogni mese
                        backgroundColor: 'rgba(235, 208, 54, 0.6)', // Colore delle barre
                        borderColor: 'rgb(235, 187, 54)', // Colore del bordo delle barre
                        borderWidth: 1 // Spessore del bordo
                    }]
                },
                options: {
                    // Configurazione delle scale degli assi (X e Y)
                    scales: {
                        y: {
                            beginAtZero: true, // L'asse Y parte da 0
                            ticks: {
                                precision: 0,   // Nessuna cifra decimale nei numeri dell'asse y
                                color: 'black'  // Colore dei numeri dell'asse y
                            }
                        },
                        x: {
                            ticks: {
                                color: 'black' // Colore dei nomi dei mesi
                            }
                        }
                    },
                    plugins: {
                        // Configurazione della legenda del grafico
                        legend: {
                            labels: {
                                color: 'black' // Colore del testo nella legenda
                            }
                        },
                        title: {
                            display: false, // Nasconde il titolo del grafico
                        }
                    }
                }
            });
        </script>
    </body>
</html>
