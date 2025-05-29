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
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connessione fallita: " . $e->getMessage());
        }

        // Statistiche varie
        $num_aule = $conn->query("SELECT count(*) FROM aula")->fetchColumn();
        $num_tecnici = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attivo'")->fetchColumn();
        $num_account_da_verificare = $conn->query("SELECT count(*) FROM utente WHERE stato LIKE 'attesa'")->fetchColumn();
        $num_dotazioni_mancanti = $conn->query("SELECT count(*) FROM dotazione WHERE stato = 'mancante'")->fetchColumn();

        // Attività recenti
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

        // Imposta la localizzazione in italiano
        $formatter = new IntlDateFormatter(
            'it_IT',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            'Europe/Rome',
            IntlDateFormatter::GREGORIAN,
            'LLLL yyyy' // formato lungo del mese e anno
        );

        foreach ($inventari_per_mese as $riga) {
            if (in_array($riga['mese'], $mesi_da_mostrare)) {
                $date = DateTime::createFromFormat('Y-m', $riga['mese']);
                $mesi[] = $formatter->format($date);  // nome mese in italiano
                $totali[] = $riga['totale'];
            }
        }
    } else {
        header("Location: ..\..\..\logout\logout.php");
        exit;
    }
?>
<!DOCTYPE html>
    <html lang="it">
    <head>  
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin - Page</title>
        <link rel="stylesheet" href="..\..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="admin_page.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <div class="container">
            <div class="sidebar">
                <div class="image"><img src="..\..\..\assets\images\logo_darzo.png" width="120px"></div>
                <div class="section-container"><br>
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

            <div class="content">
                <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <a class="logout-btn" href="../../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <h1>Benvenuto <?php echo htmlspecialchars($username); ?></h1>

                <div class="dashboard-cards">
                    <a href="../../aule/aule.php">
                        <div class="card card-blue">
                            <div class="card-content">
                                <i class="fas fa-home"></i>
                                <h3>Totale<br>Aule</h3>
                            </div>
                            <span class="card-number"><?php echo $num_aule ?> aule</span>
                        </div>
                    </a>
                    <a href="../mostra_user_attivi/mostra_user_attivi.php">
                        <div class="card card-green">
                            <div class="card-content">
                                <i class="fas fa-user-check"></i>
                                <h3>Tecnici<br>Attivi</h3>
                            </div>
                            <span class="card-number"><?php echo $num_tecnici ?> tecnici</span>
                        </div>
                    </a>
                    <a href="../user_accept/user_accept.php">
                        <div class="card card-orange">
                            <div class="card-content">
                                <i class="fas fa-user-clock"></i>
                                <h3>Attiva<br>Account</h3>
                            </div>
                            <span class="card-number"><?php echo $num_account_da_verificare ?> account</span>
                        </div>
                    </a>
                    <a href="../../lista_dotazione/lista_dotazione.php">
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

                <div class="log-container">
                    <div class="log-title"><span>Attività recenti</span></div>
                    <div class="log-content">
                        <table>
                            <?php 
                                $i = 0;
                                foreach ($inventari_piu_tecnici as $inventario_piu_tecnico) {
                                    echo "<tr><td>" . htmlspecialchars($inventario_piu_tecnico["username"]) . 
                                        " ha aggiornato l'inventario di " . htmlspecialchars($inventario_piu_tecnico["ID_aula"]) . 
                                        " in data " . htmlspecialchars($inventario_piu_tecnico["data_inventario"]) . 
                                        "</td></tr>";
                                    $i++;
                                    if($i == 3) break;
                                }
                            ?>
                        </table>
                    </div>
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
