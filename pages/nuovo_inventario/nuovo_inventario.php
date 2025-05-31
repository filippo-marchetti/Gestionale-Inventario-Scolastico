<?php
    session_start();

    $role = $_SESSION['role'];
    $username = $_SESSION['username'];

    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    $idAula = $_GET['id'] ?? null;
    $codiciSpuntati = $_GET['spuntato'] ?? [];
    if (!is_array($codiciSpuntati)) {
        $codiciSpuntati = [$codiciSpuntati];
    }

    if (!$idAula) {
        die("ID aula non specificato.");
    }

    $iniziale = substr($idAula, 0, 1);
    $finale = substr($idAula, -1, 1);
    $data = date('dmy');
    $baseCodice = strtoupper($iniziale . $finale . $data);

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM inventario 
        WHERE ID_aula = ? 
        AND DATE(data_inventario) = CURDATE()
    ");
    $stmt->execute([$idAula]);
    $count = $stmt->fetchColumn();

    $lettera = chr(65 + $count);
    $codiceInventario = $baseCodice . $lettera;

    $stmt = $conn->prepare("
        SELECT codice_inventario
        FROM inventario
        WHERE ID_aula = ?
        ORDER BY data_inventario DESC
        LIMIT 1
    ");
    $stmt->execute([$idAula]);
    $lastInv = $stmt->fetch(PDO::FETCH_ASSOC);

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

    $codiciDaMostrare = array_unique(array_merge($codiciUltimoInv, $codiciSpuntati));
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $descrizione = trim($_POST['descrizione'] ?? '');
        $spuntati = $_POST['dotazione_presente'] ?? [];

        if (!$descrizione) {
            $errors[] = "Inserire una descrizione.";
        }

        if (empty($errors)) {

            if($_SESSION["role"] == "admin"){
                $stmt = $conn->prepare("
                    SELECT scuola_appartenenza
                    FROM admin 
                    WHERE username = ?
                ");
                $stmt->execute([$username]);
                $scuola = $stmt->fetchColumn();
            }else if($_SESSION["role"] == "user"){
                $stmt = $conn->prepare("
                    SELECT scuola_appartenenza
                    FROM utente 
                    WHERE username = ?
                ");
                $stmt->execute([$username]);
                $scuola = $stmt->fetchColumn();
            }
            $stmt = $conn->prepare("
                INSERT INTO inventario (codice_inventario, data_inventario, descrizione, ID_aula, scuola_appartenenza, ID_tecnico)
                VALUES (?, NOW(), ?, ?, ?, ?)
            ");
            $stmt->execute([$codiceInventario, $descrizione, $idAula,$scuola,$username]);

            $stmtAdd = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");
            if($idAula == "magazzino") $stmtUpdate = $conn->prepare("UPDATE dotazione SET ID_aula = ?, stato = 'archiviato' WHERE codice = ?");
            else $stmtUpdate = $conn->prepare("UPDATE dotazione SET ID_aula = ?, stato = 'presente' WHERE codice = ?");

            foreach ($spuntati as $codice) {
                $stmtAdd->execute([$codice, $codiceInventario]);
                $stmtUpdate->execute([$idAula, $codice]);
            }

            $codiciMancanti = array_diff($codiciUltimoInv, $spuntati);
            $stmtManca = $conn->prepare("UPDATE dotazione SET stato = 'mancante' WHERE codice = ?");
            foreach ($codiciMancanti as $codiceMancante) {
                $stmtManca->execute([$codiceMancante]);
            }

            $_SESSION['success_message'] = "Inventario creato e stato dotazioni aggiornato a 'presente'.";
            header("Location: ..\inventari\inventari.php?id=" . urlencode($idAula));
            exit;
        }
    }
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo Inventario</title>
    <link rel="stylesheet" href="../../assets/css/background.css">
    <link rel="stylesheet" href="../../assets/css/shared_style_user_admin.css">
    <link rel="stylesheet" href="../../assets/css/shared_admin_subpages.css">
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
                <a class="back-btn" href="javascript:history.back();">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a class="logout-btn" href="../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>

            <h1>Nuovo Inventario - Aula <?php echo $idAula ?></h1>

            <form method="post">
                <input type="hidden" name="codice_inventario" value="<?php echo $codiceInventario ?>">

                <label>Codice Inventario:</label>
                <input type="text" value="<?php echo $codiceInventario ?>" readonly>

                <label>Descrizione:</label>
                <input type="text" name="descrizione" required>

                <br><button type="submit" class="btn-scan-save">Salva Inventario</button>

                <?php
                    // Combina codici GET e POST per passarli a scan.php
                    $codiciTotali = array_unique(array_merge(
                        $_GET['spuntato'] ?? [],
                        $_POST['dotazione_presente'] ?? []
                    ));
                    $query = [
                        'id' => $idAula,
                        'codice_inventario' => $codiceInventario
                    ];
                    foreach ($codiciTotali as $valore) {
                        $query['spuntato'][] = $valore;
                    }
                ?>
                <a href="scan.php?<?= http_build_query($query) ?>" class="btn-scan-save">Scansiona Dotazione</a>

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
