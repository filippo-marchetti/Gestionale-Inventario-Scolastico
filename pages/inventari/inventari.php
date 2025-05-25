<?php
    session_start(); // Avvia la sessione per accedere ai dati utente

    // Configurazione connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Prende dalla sessione lo username e il ruolo dell'utente
    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? null;

    if(isset($username)){ // Se l'utente è autenticato
        try {
            // Crea una nuova connessione PDO al database
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Imposta modalità errori per PDO
        } catch (PDOException $e) {
            // Se la connessione fallisce, termina lo script con un messaggio d'errore
            die("Connessione fallita: " . $e->getMessage());
        }

        if (!isset($_GET['id'])) {
            // Se non è stato passato l'ID dell'aula come parametro GET, termina lo script
            die("ID aula non specificato.");
        }

        $idAula = $_GET['id']; // Salva l'ID aula dalla query string

        // Prepara e esegue query per recuperare la descrizione dell'aula con quell'ID
        $stmtAula = $conn->prepare("SELECT descrizione FROM aula WHERE ID_Aula = ?");
        $stmtAula->execute([$idAula]);
        $descrizioneAula = $stmtAula->fetchColumn(); // Prende solo il valore della prima colonna (descrizione)

        try {
            // Query per recuperare gli inventari associati all'aula, con join sulla tabella scuola per il nome scuola
            $stmt = $conn->prepare("
                SELECT i.codice_inventario, i.data_inventario, i.descrizione, s.nome AS nome_scuola
                FROM inventario i
                LEFT JOIN scuola s ON i.scuola_appartenenza = s.codice_meccanografico
                WHERE i.ID_Aula = ?
                ORDER BY i.data_inventario DESC
            ");
            $stmt->execute([$idAula]);
            $inventari = $stmt->fetchAll(PDO::FETCH_ASSOC); // Prende tutti gli inventari come array associativo
        } catch (PDOException $e) {
            die("Errore nel recupero degli inventari: " . $e->getMessage());
        }
    }else{
        // Se l'utente non è autenticato, reindirizza al logout (probabilmente pagina di login)
        header("Location: ..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta tag e collegamenti a CSS -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSS condivisi e specifici -->
    <link rel="stylesheet" href="..\..\assets\css\background.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
    <link rel="stylesheet" href="inventari.css">
    <title>Inventari</title>
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="inventari.js"></script> <!-- JS per funzionalità pagina (es. filtro) -->
</head>
<body>
    <div class="container">
        <!-- Sidebar di navigazione -->
        <div class="sidebar">
            <div class="image"><img src="..\..\assets\images\placeholder.png" width="120px"></div>
            <div class="section-container">
                <br>
                <?php
                    // Link HOME differenziati in base al ruolo (admin o utente normale)
                    if($role == 'admin') {
                        echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    } else {
                        echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    }
                ?>
                <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                <?php
                    // Solo per admin: link a pagine per gestione tecnici, conferma utenti, creazione admin
                    if($role == "admin"){
                        echo '<a href="..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                        echo '<a href="..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                        echo '<a href="..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                    };
                ?>
                <!-- Link comuni a tutte le utenze -->
                <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                <a href="../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                <a href="../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
            </div>  
        </div>

        <!-- Contenuto principale -->
        <div class="content">
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <!-- Pulsante "indietro" che usa JS history -->
                <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <!-- Pulsante logout che rimanda alla pagina di logout -->
                <a class="logout-btn" href="../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>

            <h1>Inventari</h1>

            <div class="actions">
                <!-- Campo input per filtrare la tabella degli inventari -->
                <input type="text" id="filterInput" placeholder="Cerca per codice o descrizione" class="filter-input">
                <!-- Form con bottone per creare nuovo inventario, passando l'id aula come GET -->
                <form method="post" action="../nuovo_inventario/nuovo_inventario.php?id=<?php echo $idAula ?>">
                    <button class="btn-add"><i class="fas fa-plus"></i>Nuovo Inventario</button>
                </form>
            </div>

            <div class="lista-dotazioni">
                <table>
                    <thead>
                        <td>Codice Inventario</td>
                        <td>Data Inventario</td>
                        <td>Descrizione</td>
                        <td>Scuola di appartenenza</td>
                        <td>Numero Dotazione</td>
                        <td style="text-align: center;">Azioni</td>
                    </thead>
                    <tbody>
                        <?php if (count($inventari) === 0): ?>
                            <!-- Messaggio se non ci sono inventari -->
                            <tr>
                                <td colspan="5" class="no-results">Nessun inventario trovato per quest'aula.</td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventari as $inv): 
                                // Per ogni inventario conta il numero di dotazioni associate
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM riga_inventario WHERE codice_inventario = ?");
                                $stmt->execute([$inv['codice_inventario']]);
                                $numDot = $stmt->fetchColumn();

                                echo "<tr>";
                                    echo "<td>".$inv['codice_inventario']."</td>";
                                    echo "<td>".$inv['data_inventario']."</td>";
                                    echo "<td>".$inv['descrizione']."</td>";
                                    echo "<td>".$inv['nome_scuola']."</td>";
                                    echo "<td>".$numDot."</td>";
                            ?>
                                <td>
                                    <div class="div-action-btn">
                                        <!-- Bottone che porta alla pagina delle dotazioni relative a questo inventario -->
                                        <a href="../dotazioni/dotazioni.php?codice=<?php echo $inv['codice_inventario'] ?>" title="Visualizza dotazioni">
                                            <button class="btn-action btn-green"><i class="fas fa-eye"></i></button>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
