<<<<<<< HEAD
<?php
session_start();

$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

// Recupero dati sessione
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
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

// Recupera ultimo inventario e scuola di appartenenza
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
$scuolaAppartenenza = null;

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
}

$errors = [];
$dotazioniSpuntatePOST = $_POST['dotazione_presente'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['descrizione'])) {
    $descrizione = trim($_POST['descrizione']);
    $codiceInventarioPOST = $_POST['codice_inventario'] ?? null;
    $dotazioniSelezionate = $_POST['dotazione_presente'] ?? [];
    $conferma = $_POST['conferma'] ?? null;

    // Validazioni base
    if (!$descrizione) {
        $errors[] = "La descrizione è obbligatoria.";
    }
    if (!$idAula) {
        $errors[] = "ID aula mancante.";
    }
    if (!$codiceInventarioPOST) {
        $errors[] = "Codice inventario mancante.";
    }

    // Pagina di conferma lato server senza JS
    if (empty($errors) && !$conferma) {
?>
        <!DOCTYPE html>
        <html lang="it">

        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>Conferma Creazione Inventario - Aula <?= htmlspecialchars($idAula) ?></title>
            <link rel="stylesheet" href="../../assets/css/background.css" />
            <link rel="stylesheet" href="../../assets/css/shared_style_user_admin.css" />
            <link rel="stylesheet" href="../../assets/css/shared_admin_subpages.css" />
            <link rel="stylesheet" href="inventari.css" />
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
        </head>

        <body>
            <div class="container">
                <div class="sidebar">
                    <div class="image"><img src="../../assets/images/logo_darzo.png" width="120px" alt="Logo Darzo"></div>
                    <div class="section-container">
                        <br />
                        <?php if ($role === 'admin'): ?>
                            <a href="../../admin_page/admin_page.php">
                                <div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div>
                            </a>
                        <?php else: ?>
                            <a href="../user_page.php">
                                <div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div>
                            </a>
                        <?php endif; ?>
                        <a href="../aule/aule.php">
                            <div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div>
                        </a>
                        <a href="../dotazioni/dotazioni.php?codice=<?= urlencode($codiceInventarioPOST) ?>">
                            <div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i> DOTAZIONE</span></div>
                        </a>
                        <a href="../dotazione_archiviata.php">
                            <div class="section"><span class="section-text"><i class="fas fa-warehouse"></i> MAGAZZINO</span></div>
                        </a>
                        <a href="../dotazione_eliminata/dotazione_eliminata.php">
                            <div class="section"><span class="section-text"><i class="fas fa-trash"></i> STORICO SCARTI</span></div>
                        </a>
                        <a href="#">
                            <div class="section"><span class="section-text"><i class="fas fa-cogs"></i> IMPOSTAZIONI</span></div>
                        </a>
                    </div>
                </div>

                <div class="content">
                    <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <a class="logout-btn" href="../../logout/logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>

                    <h1>Conferma creazione inventario</h1>
                    <p>Sei sicuro di voler creare un nuovo inventario per l'aula <b><?= htmlspecialchars($idAula) ?></b>?</p>

                    <form method="post" class="form-inventario">
                        <input type="hidden" name="codice_inventario" value="<?= htmlspecialchars($codiceInventarioPOST) ?>" />
                        <input type="hidden" name="descrizione" value="<?= htmlspecialchars($descrizione) ?>" />
                        <?php foreach ($dotazioniSelezionate as $codice): ?>
                            <input type="hidden" name="dotazione_presente[]" value="<?= htmlspecialchars($codice) ?>" />
                        <?php endforeach; ?>
                        <input type="hidden" name="conferma" value="1" />
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
        // Inserisci nuovo inventario
        $stmt = $conn->prepare("
            INSERT INTO inventario (codice_inventario, data_inventario, descrizione, ID_Aula, scuola_appartenenza)
            VALUES (?, NOW(), ?, ?, ?)
        ");
        $stmt->execute([$codiceInventarioPOST, $descrizione, $idAula, $scuolaAppartenenza]);

        // Inserisci righe inventario associate
        $stmtCheck = $conn->prepare("SELECT 1 FROM riga_inventario WHERE codice_dotazione = ? AND codice_inventario = ?");
        $stmtInsert = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");

        foreach ($dotazioniSelezionate as $codiceDotazione) {
            $stmtCheck->execute([$codiceDotazione, $codiceInventarioPOST]);
            if (!$stmtCheck->fetchColumn()) {
                try {
                    $stmtInsert->execute([$codiceDotazione, $codiceInventarioPOST]);
                } catch (PDOException $e) {
                    if ($e->getCode() != 23000) { // ignora errore duplicati
                        throw $e;
                    }
                }
            }
        }

        // Aggiorna lo stato delle dotazioni in base alle selezioni:
        // se selezionata -> stato = "presente"
        // se NON selezionata -> stato = "mancante"
        $stmtUpdateStato = $conn->prepare("UPDATE dotazione SET stato = ? WHERE codice = ?");

        // Ottieni tutti i codici delle dotazioni dell'ultimo inventario (quelle visualizzate)
        $codiciTutteDotazioni = array_column($dotazioni, 'codice');

        foreach ($codiciTutteDotazioni as $codiceDot) {
            if (in_array($codiceDot, $dotazioniSelezionate)) {
                // Spuntata: stato = presente
                $stmtUpdateStato->execute(['presente', $codiceDot]);
            } else {
                // Non spuntata: stato = mancante
                $stmtUpdateStato->execute(['mancante', $codiceDot]);
            }
        }

        $_SESSION['success_message'] = "Inventario creato con successo!";
        header("Location: ../inventari/inventari.php?id=" . urlencode($idAula));
        exit;
    }
}

// Genera un codice inventario univoco se non presente
if (!$codiceInventario) {
    do {
        $codiceInventario = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT 1 FROM inventario WHERE codice_inventario = ?");
        $stmt->execute([$codiceInventario]);
    } while ($stmt->fetchColumn());
}

// Unisci codici spuntati da GET e POST (per mantenere le spunte quando si torna da scan)
$codiciDaSpuntare = array_unique(array_merge($codiciDaSpuntareGET, $dotazioniSpuntatePOST));
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nuovo Inventario - Aula <?= htmlspecialchars($idAula) ?></title>
    <link rel="stylesheet" href="../../assets/css/background.css" />
    <link rel="stylesheet" href="../../assets/css/shared_style_user_admin.css" />
    <link rel="stylesheet" href="../../assets/css/shared_admin_subpages.css" />
    <link rel="stylesheet" href="nuovo_inventario.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="image"><img src="../../assets/images/logo_darzo.png" width="120px" alt="Logo Darzo" /></div>
            <div class="section-container">
                <br />
                <?php if ($role === 'admin'): ?>
                    <a href="../../admin_page/admin_page.php">
                        <div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div>
                    </a>
                <?php else: ?>
                    <a href="../user_page.php">
                        <div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div>
                    </a>
                <?php endif; ?>
                <a href="../aule/aule.php">
                    <div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div>
                </a>
                <a href="../dotazioni/dotazioni.php?codice=<?= urlencode($codiceInventario) ?>">
                    <div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i> DOTAZIONE</span></div>
                </a>
                <a href="../dotazione_archiviata.php">
                    <div class="section"><span class="section-text"><i class="fas fa-warehouse"></i> MAGAZZINO</span></div>
                </a>
                <a href="../dotazione_eliminata/dotazione_eliminata.php">
                    <div class="section"><span class="section-text"><i class="fas fa-trash"></i> STORICO SCARTI</span></div>
                </a>
                <a href="#">
                    <div class="section"><span class="section-text"><i class="fas fa-cogs"></i> IMPOSTAZIONI</span></div>
                </a>
            </div>
        </div>

        <div class="content">
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a class="logout-btn" href="../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>

            <h1>Nuovo Inventario</h1>

            <?php if (!empty($errors)): ?>
                <div class="error-messages" style="color: red; margin-bottom: 1em;">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="form-inventario">
                <label for="descrizione">Descrizione inventario:</label><br />
                <input type="text" id="descrizione" name="descrizione" required value="<?= htmlspecialchars($_POST['descrizione'] ?? '') ?>" />

                <input type="hidden" name="codice_inventario" value="<?= htmlspecialchars($codiceInventario) ?>" />

                <h2>Seleziona dotazioni presenti:</h2>
                <ul class="lista-dotazioni">
                    <?php if (empty($dotazioni)): ?>
                        <li>Nessuna dotazione trovata per l'ultimo inventario.</li>
                    <?php else: ?>
                        <?php foreach ($dotazioni as $dotazione): ?>
                            <?php
                            $checked = in_array($dotazione['codice'], $codiciDaSpuntare) ? 'checked' : '';
                            ?>
                            <li>
                                <label>
                                    <input type="checkbox" name="dotazione_presente[]" value="<?= htmlspecialchars($dotazione['codice']) ?>" <?= $checked ?> />
                                    <?= htmlspecialchars($dotazione['nome']) ?> - <?= htmlspecialchars($dotazione['categoria']) ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>

                <button type="submit" class="btn-save"><i class="fa fa-save"></i> Salva Inventario</button>
            </form>
        </div>
    </div>
</body>

</html>