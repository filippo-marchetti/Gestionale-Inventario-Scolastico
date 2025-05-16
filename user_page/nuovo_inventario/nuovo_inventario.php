<?php
session_start();

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
            <title>Conferma creazione inventario</title>
            <link rel="stylesheet" href="..\..\..\assets\css\background.css">
            <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
            <link rel="stylesheet" href="nuovo_inventario.css">
        </head>
        <body>
        <div class="container">
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
        </body>
        </html>
        <?php
        exit;
    }

    // Se confermato, esegui la creazione inventario
    if (empty($errors) && $conferma) {
        // Inserisci inventario
        $stmt = $conn->prepare("
            INSERT INTO inventario (codice_inventario, data_inventario, descrizione, ID_Aula, scuola_appartenenza)
            VALUES (?, NOW(), ?, ?, ?)
        ");
        $stmt->execute([$codiceInventarioPOST, $descrizione, $idAula, $scuolaAppartenenza]);

        // Prepara statement per controllo e inserimento
        $stmtCheck = $conn->prepare("SELECT 1 FROM riga_inventario WHERE codice_dotazione = ? AND codice_inventario = ?");
        $stmtInsert = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");

        foreach ($dotazioniSelezionate as $codiceDotazione) {
            // Controlla duplicato
            $stmtCheck->execute([$codiceDotazione, $codiceInventarioPOST]);
            if (!$stmtCheck->fetchColumn()) {
                try {
                    $stmtInsert->execute([$codiceDotazione, $codiceInventarioPOST]);
                } catch (PDOException $e) {
                    // Codice errore 23000 = violation unique constraint
                    if ($e->getCode() != 23000) {
                        throw $e;
                    }
                    // Ignora duplicato se capita
                }
            }
        }

        $_SESSION['success_message'] = "Inventario creato con successo!";
        header("Location: ..\inventari\inventari.php?id=" . urlencode($idAula));
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
    <link rel="stylesheet" href="..\..\..\assets\css\background.css">
    <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
    <link rel="stylesheet" href="nuovo_inventario.css">
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container">
    <h1>Nuovo Inventario<br><span class="subtitle">Aula <?= htmlspecialchars($idAula) ?></span></h1>

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
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="form-inventario">
        <input type="hidden" name="codice_inventario" value="<?= htmlspecialchars($codiceInventario) ?>">

        <div class="form-group">
            <label for="codice_inventario">Codice Inventario:</label>
            <input type="text" id="codice_inventario" value="<?= htmlspecialchars($codiceInventario) ?>" readonly>
        </div>

        <div class="form-group">
            <label for="descrizione">Descrizione:</label>
            <input type="text" id="descrizione" name="descrizione" required value="<?= isset($_POST['descrizione']) ? htmlspecialchars($_POST['descrizione']) : '' ?>">
        </div>

        <?php if ($dotazioni): ?>
            <h3>Dotazioni presenti nell'ultimo inventario:</h3>
            <div class="dotazioni-list">
                <?php foreach ($dotazioni as $d): ?>
                    <?php $spuntata = in_array($d['codice'], $codiciDaSpuntare); ?>
                    <div class="dotazione <?= $spuntata ? 'spuntata' : '' ?>">
                        <label>
                            <input type="checkbox" name="dotazione_presente[]" value="<?= htmlspecialchars($d['codice']) ?>" <?= $spuntata ? 'checked' : '' ?>>
                            <span class="dotazione-nome"><?= htmlspecialchars($d['nome']) ?></span>
                            <span class="dotazione-cat">(<?= htmlspecialchars($d['categoria']) ?>)</span>
                        </label>
                        <div class="dotazione-info">
                            <span><b>Codice:</b> <?= htmlspecialchars($d['codice']) ?></span>
                            <span><b>Stato:</b> <?= htmlspecialchars($d['stato']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-dotazioni">Nessuna dotazione nell'ultimo inventario.</p>
        <?php endif; ?>

        <button type="submit" class="btn-save"><i class="fa fa-save"></i> Crea Inventario</button>
    </form>
</div>
</body>
</html>