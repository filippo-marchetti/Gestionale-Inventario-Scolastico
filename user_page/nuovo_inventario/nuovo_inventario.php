<?php
    session_start();

    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? null;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    $idAula = $_GET['id'] ?? null;
    $codiceInventario = $_GET['codice_inventario'] ?? null;
    $codiciDaSpuntareGET = $_GET['spuntato'] ?? [];
    if (!is_array($codiciDaSpuntareGET)) {
        $codiciDaSpuntareGET = [$codiciDaSpuntareGET];
    }

    if (!$idAula) {
        die("ID aula non specificato.");
    }

    // Recupera ultimo inventario e scuola appartenenza
    $stmt = $conn->prepare("
        SELECT codice_inventario, descrizione, data_inventario, scuola_appartenenza
        FROM inventario
        WHERE ID_Aula = ?
        ORDER BY data_inventario DESC
        LIMIT 1
    ");
    $stmt->execute([$idAula]);
    $lastInventario = $stmt->fetch(PDO::FETCH_ASSOC);

    $dotazioni = [];
    if ($lastInventario) {
        $stmt = $conn->prepare("
            SELECT d.codice, d.nome, d.categoria, d.descrizione, d.stato
            FROM riga_inventario ri
            JOIN dotazione d ON ri.codice_dotazione = d.codice
            WHERE ri.codice_inventario = ?
        ");
        $stmt->execute([$lastInventario['codice_inventario']]);
        $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $scuolaAppartenenza = $lastInventario['scuola_appartenenza'];
    } else {
        $scuolaAppartenenza = null;
    }

    $errors = [];
    $dotazioniSpuntatePOST = $_POST['dotazione_presente'] ?? [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['descrizione'])) {
        $descrizione = trim($_POST['descrizione']);
        $codiceInventarioPOST = $_POST['codice_inventario'];
        $dotazioniSelezionate = $_POST['dotazione_presente'] ?? [];
        $conferma = $_POST['conferma'] ?? null;

        if (!$descrizione) {
            $errors[] = "La descrizione è obbligatoria.";
        }
        if (!$idAula) {
            $errors[] = "ID aula mancante.";
        }
        if (!$codiceInventarioPOST) {
            $errors[] = "Codice inventario mancante.";
        }

        // Pagina di conferma lato server (senza JS)
        if (empty($errors) && !$conferma) {
?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Nuovo Inventario - Aula <?= htmlspecialchars($idAula) ?></title>
            <link rel="stylesheet" href="..\..\assets\css\background.css">
            <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
            <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
            <link rel="stylesheet" href="inventari.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        </head>
        <body>
        <div class="container">
            <div class="sidebar">
                <div class="image"><img src="../../assets/images/logo_darzo.png" width="120px"></div>
                <div class="section-container">
                    <br>
                    <?php
                        if($role == 'admin') {
                            echo '<a href="../../admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="../dotazioni/dotazioni.php?codice=<?= htmlspecialchars($codiceInventarioPOST) ?>"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="../dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="#"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>
            </div>
            <div class="content">
                <div class="logout">
                    <a class="logout-btn" href="../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <h1>Conferma creazione inventario</h1>
                <p>Sei sicuro di voler creare un nuovo inventario per l'aula <b><?= htmlspecialchars($idAula) ?></b>?</p>
                <form method="post" class="form-inventario">
                    <input type="hidden" name="codice_inventario" value="<?= htmlspecialchars($codiceInventarioPOST) ?>">
                    <input type="hidden" name="descrizione" value="<?= htmlspecialchars($descrizione) ?>">
                    <?php foreach ($dotazioniSelezionate as $codice): ?>
                        <input type="hidden" name="dotazione_presente[]" value="<?= htmlspecialchars($codice) ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="conferma" value="1">
                    <button type="submit" class="btn-save"><i class="fa fa-save"></i> Conferma</button>
                    <a href="nuovo_inventario.php?id=<?= urlencode($idAula) ?>&codice_inventario=<?= urlencode($codiceInventarioPOST) ?>" class="btn-back">Annulla</a>
                </form>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit;
    }

    // Se confermato, esegui la creazione inventario
    if (empty($errors) && $conferma) {
        $stmt = $conn->prepare("
            INSERT INTO inventario (codice_inventario, data_inventario, descrizione, ID_Aula, scuola_appartenenza)
            VALUES (?, NOW(), ?, ?, ?)
        ");
        $stmt->execute([$codiceInventarioPOST, $descrizione, $idAula, $scuolaAppartenenza]);

        $stmtCheck = $conn->prepare("SELECT 1 FROM riga_inventario WHERE codice_dotazione = ? AND codice_inventario = ?");
        $stmtInsert = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");

        foreach ($dotazioniSelezionate as $codiceDotazione) {
            $stmtCheck->execute([$codiceDotazione, $codiceInventarioPOST]);
            if (!$stmtCheck->fetchColumn()) {
                try {
                    $stmtInsert->execute([$codiceDotazione, $codiceInventarioPOST]);
                } catch (PDOException $e) {
                    if ($e->getCode() != 23000) {
                        throw $e;
                    }
                }
            }
        }

        $_SESSION['success_message'] = "Inventario creato con successo!";
        header("Location: ../inventari/inventari.php?id=" . urlencode($idAula));
        exit;
    }
} else {
    if (!$codiceInventario) {
        do {
            $codiceInventario = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("SELECT 1 FROM inventario WHERE codice_inventario = ?");
            $stmt->execute([$codiceInventario]);
        } while ($stmt->fetchColumn());
    }
}

$codiciDaSpuntare = array_unique(array_merge($codiciDaSpuntareGET, $dotazioniSpuntatePOST));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo Inventario - Aula <?= htmlspecialchars($idAula) ?></title>
    <link rel="stylesheet" href="../../assets/css/background.css">
    <link rel="stylesheet" href="../../assets/css/shared_style_lista_admin.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container">
    <!-- sidebar -->
    <div class="sidebar">
        <div class="image"><img src="../../assets/images/logo_darzo.png" width="120px"></div>
        <div class="section-container">
            <br>
            <?php
                if($role == 'admin') {
                    echo '<a href="../../admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                } else {
                    echo '<a href="../user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                }
            ?>
            <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
            <a href="../dotazioni/dotazioni.php?codice=<?= htmlspecialchars($codiceInventario) ?>"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
            <a href="../dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
            <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
            <a href="#"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
        </div>
    </div>
    <!-- content -->
    <div class="content">
        <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <!-- Bottone "indietro" -->
            <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                <i class="fas fa-chevron-left"></i>
            </a>
            <!-- Bottone logout -->
            <a class="logout-btn" href="../logout/logout.php">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
        <h1>Nuovo Inventario<br><span class="subtitle">Aula <?php echo $idAula ?></span></h1>

        <div class="actions-bar">
            <a href="scan.php?<?= http_build_query([
                'id' => $idAula,
                'codice_inventario' => $codiceInventario,
                'spuntato' => $codiciDaSpuntare
            ]) ?>" class="btn-scan"><i class="fa fa-qrcode"></i> Scansiona Dotazione</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo $err?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="form-inventario">
            <input type="hidden" name="codice_inventario" value="<?php echo $codiceInventario ?>">

            <div class="form-group">
                <label for="codice_inventario">Codice Inventario:</label>
                <input type="text" id="codice_inventario" value="<?php echo $codiceInventario ?>" readonly>
            </div>

            <div class="form-group">
                <label for="descrizione">Descrizione:</label>
                <input type="text" id="descrizione" name="descrizione" required value="<?php isset($_POST['descrizione']) ? $_POST['descrizione'] : '' ?>">
            </div>

            <?php if ($dotazioni): ?>
                <h3>Dotazioni presenti nell'ultimo inventario:</h3>
                <div class="dotazioni-list">
                    <?php foreach ($dotazioni as $d): ?>
                        <?php
                            $spuntata = in_array($d['codice'], $codiciDaSpuntare);
                            $idAulaDotazione = null;
                            $nomeAulaDestinazione = null;
                            $stmtAula = $conn->prepare("SELECT ID_Aula FROM dotazione WHERE codice = ?");
                            $stmtAula->execute([$d['codice']]);
                            $idAulaDotazione = $stmtAula->fetchColumn();

                            if ($idAulaDotazione && $idAulaDotazione != $idAula) {
                                $stmtNomeAula = $conn->prepare("SELECT ID_Aula FROM aula WHERE ID_Aula = ?");
                                $stmtNomeAula->execute([$idAulaDotazione]);
                                $nomeAulaDestinazione = $stmtNomeAula->fetchColumn();
                            }
                        ?>
                        <div class="dotazione <?= $spuntata ? 'spuntata' : '' ?>">
                            <label>
                                <input type="checkbox" name="dotazione_presente[]" value="<?php echo$d['codice'] ?>" <?= $spuntata ? 'checked' : '' ?>>
                                <span class="dotazione-nome"><?php echo $d['nome'] ?></span>
                                <span class="dotazione-cat">(<?php echo$d['categoria'] ?>)</span>
                            </label>
                            <div class="dotazione-info">
                                <span><b>Codice:</b> <?php echo$d['codice'] ?></span>
                                <span><b>Stato:</b> <?php echo$d['stato'] ?></span>
                            </div>
                            <?php if ($idAulaDotazione && $idAulaDotazione != $idAula): ?>
                                <div class="alert" style="color:#b23c3c; margin-top:6px;">
                                    ⚠️ Questa dotazione ora si trova nell'aula: <b><?php echo $nomeAulaDestinazione ?: $idAulaDotazione ?></b>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-dotazioni">Nessuna dotazione nell'ultimo inventario.</p>
            <?php endif; ?>

            <button type="submit" class="btn-save"><i class="fa fa-save"></i> Crea Inventario</button>
        </form>
    </div>
</div>
</body>
</html>