<?php
    session_start(); // Avvia la sessione per poter usare $_SESSION

    // Dati di connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupera username e ruolo dalla sessione, oppure null se non impostati
    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? null;

    // Se esiste un ruolo (utente autenticato)
    if(isset($role)){
        try {
            // Connessione PDO al database MySQL
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Setta modalità di errore per eccezioni
        } catch (PDOException $e) {
            // In caso di errore di connessione termina lo script con messaggio
            die("Connessione fallita: " . $e->getMessage());
        }

        // Controlla che il parametro 'codice' sia passato via GET, altrimenti termina con messaggio
        if (!isset($_GET['codice'])) {
            die("Codice inventario non specificato.");
        }

        // Memorizza il codice dell'inventario passato via GET
        $codiceInventario = $_GET['codice'];

        // Esegue la query per ottenere le dotazioni legate a questo inventario
        try {
            $stmt = $conn->prepare("
                SELECT d.codice, d.nome, d.categoria, d.descrizione, d.stato, d.prezzo_stimato, d.ID_aula
                FROM dotazione d
                INNER JOIN riga_inventario ri ON d.codice = ri.codice_dotazione
                WHERE ri.codice_inventario = ?
            ");
            $stmt->execute([$codiceInventario]); // Passa il codice inventario come parametro per la query
            $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC); // Ottiene tutte le righe risultanti come array associativo
        } catch (PDOException $e) {
            die("Errore nella lettura delle dotazioni: " . $e->getMessage());
        }
    }else{
        // Se non è autenticato, reindirizza al logout (che probabilmente porta alla pagina di login)
        header("Location: ..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dotazioni dell'inventario <?= htmlspecialchars($codiceInventario) ?></title> <!-- Titolo dinamico con codice inventario -->
    <!-- Collegamento ai fogli di stile CSS -->
    <link rel="stylesheet" href="..\..\assets\css\background.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_lista_dotazione.css">

    <!-- FontAwesome per le icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="..\..\admin_page\lista_dotazione\lista_dotazione.js"></script> <!-- Script JS personalizzato -->
</head>
<body>
    <div class="container">
        <!-- Sidebar laterale con link di navigazione -->
        <div class="sidebar">
            <div class="image"><img src="..\..\assets\images\placeholder.png" width="120px"></div>
            <div class="section-container">
                <br>
                <?php
                    // Mostra il link HOME differente in base al ruolo
                    if($role == 'admin') {
                        echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    } else {
                        echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    }
                ?>
                <!-- Link comuni a tutti -->
                <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                <?php
                    // Link visibili solo ad admin
                    if($role == "admin"){
                        echo '<a href="..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                        echo '<a href="..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                        echo '<a href="..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                    };
                ?>
                <!-- Altri link -->
                <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                <a href="../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                <a href="../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
            </div>
        </div>

        <!-- Contenuto principale della pagina -->
        <div class="content">
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <!-- Bottone "indietro" che torna alla pagina precedente -->
                <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <!-- Bottone logout -->
                <a class="logout-btn" href="../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>

            <!-- Titolo della pagina -->
            <h1>Dotazioni dell'inventario <?= htmlspecialchars($codiceInventario) ?></h1>

            <!-- Campo di ricerca per filtrare la tabella (funziona tramite JS esterno) -->
            <div class="actions">
                <input type="text" id="filterInput" placeholder="Cerca per nome o codice" class="filter-input">
            </div>

            <!-- Lista dotazioni -->
            <div class="lista-dotazioni">
                <?php if (count($dotazioni) === 0): ?>
                    <!-- Messaggio se non ci sono dotazioni -->
                    <p class="no-results">Nessuna dotazione trovata per questo inventario.</p>
                <?php else: ?>
                    <!-- Tabella con i dati delle dotazioni -->
                    <table>
                        <thead>
                            <tr>
                                <td>Codice</td>
                                <td>Nome</td>
                                <td>Categoria</td>
                                <td>Descrizione</td>
                                <td>Stato</td>
                                <td>Prezzo Stimato</td>
                                <td>Aula</td>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Ciclo per mostrare ogni dotazione -->
                            <?php foreach ($dotazioni as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['codice']) ?></td>
                                    <td><?= htmlspecialchars($d['nome']) ?></td>
                                    <td><?= htmlspecialchars($d['categoria']) ?></td>
                                    <td><?= htmlspecialchars($d['descrizione']) ?></td>
                                    <td><?= htmlspecialchars($d['stato']) ?></td>
                                    <td><?= htmlspecialchars($d['prezzo_stimato']) ?>€</td>
                                    <td><?= htmlspecialchars($d['ID_aula']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
