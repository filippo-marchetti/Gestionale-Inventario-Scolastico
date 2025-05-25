<?php
    session_start(); // Avvia la sessione

    $role = $_SESSION['role']; // Recupera il ruolo dell'utente (admin o tecnico)

    // Dati per la connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Connessione al database con gestione errori
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    // Recupera l'ID dell'aula dalla query string
    $idAula = $_GET['id'] ?? null;

    // Recupera i codici delle dotazioni spuntate da scan.php (può essere una stringa o array)
    $codiciSpuntati = $_GET['spuntato'] ?? [];
    if (!is_array($codiciSpuntati)) {
        $codiciSpuntati = [$codiciSpuntati]; // Converte in array se necessario
    }

    if (!$idAula) {
        die("ID aula non specificato."); // Blocca se manca ID aula
    }

    // Genera il codice inventario in base all’ID aula e data
    $iniziale = substr($idAula, 0, 1);
    $finale = substr($idAula, -1, 1);
    $data = date('dmy');
    $baseCodice = strtoupper($iniziale . $finale . $data);

    // Conta quanti inventari esistono oggi per quell’aula
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM inventario 
        WHERE ID_aula = ? 
        AND DATE(data_inventario) = CURDATE()
    ");
    $stmt->execute([$idAula]);
    $count = $stmt->fetchColumn();

    // Converti il numero in lettera (0 → A, 1 → B, ...)
    $lettera = chr(65 + $count);

    // Codice finale
    $codiceInventario = $baseCodice . $lettera;

    // Recupera l'ultimo inventario effettuato per l'aula selezionata
    $stmt = $conn->prepare("
        SELECT codice_inventario
        FROM inventario
        WHERE ID_aula = ?
        ORDER BY data_inventario DESC
        LIMIT 1
    ");
    $stmt->execute([$idAula]);
    $lastInv = $stmt->fetch(PDO::FETCH_ASSOC);

    // Recupera le dotazioni dell'ultimo inventario
    $dotazioniUltimoInv = [];
    $codiciUltimoInv = [];
    if ($lastInv) {
        $stmt = $conn->prepare("
            SELECT d.codice, d.nome, d.categoria, d.stato, d.ID_aula AS aula_corrente
            FROM riga_inventario ri
            JOIN dotazione d ON ri.codice_dotazione = d.codice
            WHERE ri.codice_inventario = ?
        ");
        $stmt->execute([$lastInv['codice_inventario']]);
        $dotazioniUltimoInv = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $codiciUltimoInv = array_column($dotazioniUltimoInv, 'codice');
    }

    // Unisci codici: prima quelli dell'ultimo inventario, poi quelli aggiunti tramite scan (che non erano già presenti)
    $codiciDaMostrare = array_unique(array_merge($codiciUltimoInv, $codiciSpuntati));

    // Ricostruisci l'elenco delle dotazioni da mostrare
    $dotazioniDaMostrare = [];
    foreach ($dotazioniUltimoInv as $d) {
        $dotazioniDaMostrare[$d['codice']] = $d;
    }
    $nuoviCodici = array_diff($codiciSpuntati, $codiciUltimoInv);
    if (!empty($nuoviCodici)) {
        $placeholders = implode(',', array_fill(0, count($nuoviCodici), '?'));
        $stmt = $conn->prepare("
            SELECT codice, nome, categoria, stato, ID_aula AS aula_corrente
            FROM dotazione
            WHERE codice IN ($placeholders)
        ");
        $stmt->execute($nuoviCodici);
        $nuoveDotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($nuoveDotazioni as $d) {
            $dotazioniDaMostrare[$d['codice']] = $d;
        }
    }

    $errors = [];

    // Se il form è stato inviato
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $descrizione = trim($_POST['descrizione'] ?? '');
        $spuntati = $_POST['dotazione_presente'] ?? [];

        if (!$descrizione) {
            $errors[] = "Inserire una descrizione."; // Messaggio errore se descrizione mancante
        }

        if (empty($errors)) {
            // Inserisce un nuovo inventario
            $stmt = $conn->prepare("
                INSERT INTO inventario (codice_inventario, data_inventario, descrizione, ID_aula, scuola_appartenenza, ID_tecnico)
                VALUES (?, NOW(), ?, ?, NULL, '')
            ");
            $stmt->execute([$codiceInventario, $descrizione, $idAula]);

            // Inserisce le righe di inventario e aggiorna la dotazione
            $stmtAdd = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");
            $stmtUpdate = $conn->prepare("UPDATE dotazione SET ID_aula = ?, stato = 'presente' WHERE codice = ?");

            foreach ($spuntati as $codice) {
                $stmtAdd->execute([$codice, $codiceInventario]);
                $stmtUpdate->execute([$idAula, $codice]);
            }

            // Imposta "mancante" le dotazioni del vecchio inventario non spuntate
            $codiciMancanti = array_diff($codiciUltimoInv, $spuntati);
            $stmtManca = $conn->prepare("UPDATE dotazione SET stato = 'mancante' WHERE codice = ?");
            foreach ($codiciMancanti as $codiceMancante) {
                $stmtManca->execute([$codiceMancante]);
            }

            // Messaggio di successo e reindirizzamento
            $_SESSION['success_message'] = "Inventario creato e stato dotazioni aggiornato a 'presente'.";
            header("Location: ..\inventari\inventari.php?id=" . urlencode($idAula));
            exit;
        }
    }
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Nuovo Inventario</title>
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="nuovo_inventario.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <div class="container">
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
                    <a href="../../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                    <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>      
                </div>  
            </div>
            <div class="content">

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
                
                <h1>Nuovo Inventario - Aula <?php echo $idAula ?></h1>

                <!-- <?php if (!empty($errors)): ?> -->
                    <!-- <div class="error"> -->
                        <!-- <ul> -->
                            <!-- <?php foreach ($errors as $err): ?> -->
                                <!-- <li><?php echo $err ?></li> -->
                            <!-- <?php endforeach; ?> -->
                        <!-- </ul> -->
                    <!-- </div> -->
                <!-- <?php endif; ?> -->

                <form method="post">
                    <input type="hidden" name="codice_inventario" value="<?php echo $codiceInventario ?>">

                    <label>Codice Inventario:</label>
                    <input type="text" value="<?php echo $codiceInventario ?>" readonly>

                    <label>Descrizione:</label>
                    <input type="text" name="descrizione" required>

                    <br><button type="submit" class="btn-scan-save">Salva Inventario</button>

                    <a href="scan.php?<?= http_build_query([
                        'id' => $idAula,
                        'codice_inventario' => $codiceInventario,
                        'spuntato' => $codiciSpuntati
                    ]) ?>" class="btn-scan-save">Scansiona Dotazione</a>
                
                    <h3>Dotazioni incluse:</h3>
                    <?php foreach ($dotazioniDaMostrare as $d): ?>
                        <?php
                            $codice = $d['codice'];
                            $altAula = ($d['aula_corrente'] && $d['aula_corrente'] !== $idAula);
                            $aggiuntaDaScan = in_array($codice, $nuoviCodici);
                        ?>
                        <div class="dotazione">
                            <label>
                                <input type="checkbox" name="dotazione_presente[]" value="<?php echo $codice ?>"
                                    <?= in_array($codice, $codiciSpuntati) ? 'checked' : '' ?>>
                                <strong><?php echo $d['nome'] ?></strong> (<?php echo $d['categoria']?>)
                            </label><br>
                            Codice: <?php echo $codice ?> | Stato: <?php echo $d['stato'] ?>
                            <?php if ($altAula): ?>
                                <div class="warning">⚠ Attualmente si trova in aula <?php echo $d['aula_corrente'] ?></div>
                            <?php endif; ?>
                            <?php if ($aggiuntaDaScan): ?>
                                <div class="success-scan">Aggiunta tramite scansione</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
        </div>
    </body>
</html>