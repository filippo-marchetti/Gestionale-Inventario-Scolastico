<?php
session_start();
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}

$idAula = $_GET['id'] ?? null;
if (!$idAula) die("ID aula non specificato.");

$nomeAula = $idAula; // ID_Aula già contiene il codice aula (es. LAB01)

// Recupera ultimo inventario
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
    $codiceInventarioPrecedente = $lastInventario['codice_inventario'];
    $scuolaAppartenenza = $lastInventario['scuola_appartenenza'];

    $stmt = $conn->prepare("
        SELECT d.codice, d.nome, d.categoria, d.descrizione, d.stato
        FROM riga_inventario ri
        JOIN dotazione d ON ri.codice_dotazione = d.codice
        WHERE ri.codice_inventario = ?
    ");
    $stmt->execute([$codiceInventarioPrecedente]);
    $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Genera codice inventario nel formato: prima+ultima lettera ID aula + giorno + mese + anno
$oggi = date('ymd'); // formato YYMMDD
$idPulito = preg_replace('/\s+/', '', strtoupper($idAula));
$iniziale = substr($idPulito, 0, 1);
$finale = substr($idPulito, -1);
$baseCodice = $iniziale . $finale . $oggi;

// Verifica univocità e aggiunge lettera finale se necessario
$codiceInventario = $baseCodice;
$stmtCheckCodice = $conn->prepare("SELECT 1 FROM inventario WHERE codice_inventario = ?");
$suffix = 'A';

while (true) {
    $stmtCheckCodice->execute([$codiceInventario]);
    if (!$stmtCheckCodice->fetchColumn()) break;
    $codiceInventario = $baseCodice . $suffix;
    $suffix++;
}

// Recupera dotazioni spuntate da GET (ad esempio da scan.php)
$dotazioniSpuntate = $_GET['spuntato'] ?? [];
if (!is_array($dotazioniSpuntate)) {
    $dotazioniSpuntate = [$dotazioniSpuntate];
}

// Se POST: salva nuovo inventario
$errors = [];
$descrizioneVal = $_POST['descrizione'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descrizione = trim($_POST['descrizione'] ?? '');
    $dotazioniSelezionate = $_POST['dotazione_presente'] ?? [];

    if ($descrizione === '') {
        $errors['descrizione'] = "La descrizione è obbligatoria.";
    }

    if (empty($errors)) {
        try {
            // Inserisce inventario
            $stmt = $conn->prepare("
                INSERT INTO inventario (codice_inventario, data_inventario, descrizione, ID_Aula, scuola_appartenenza)
                VALUES (?, NOW(), ?, ?, ?)
            ");
            $stmt->execute([$codiceInventario, $descrizione, $idAula, $scuolaAppartenenza]);

            // Inserisce righe e aggiorna stato dotazioni
            $stmtInsert = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");
            $stmtUpdateStato = $conn->prepare("UPDATE dotazione SET stato = ? WHERE codice = ?");

            foreach ($dotazioni as $dotazione) {
                $codice = $dotazione['codice'];
                if (in_array($codice, $dotazioniSelezionate)) {
                    $stmtInsert->execute([$codice, $codiceInventario]);
                    $stmtUpdateStato->execute(['presente', $codice]);
                } else {
                    $stmtUpdateStato->execute(['mancante', $codice]);
                }
            }

            // Redirect per evitare doppio POST
            header("Location: nuovo_inventario.php?id=" . urlencode($idAula));
            exit;
        } catch (PDOException $e) {
            $errors['db'] = "Errore nel salvataggio: " . $e->getMessage();
        }
    }
}
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
            <a href="../../dotazione_mancante/dotazione_mancante.php">
                <div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div>
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

        <form method="post" class="form-inventario" novalidate>
            <label>Codice Inventario:</label>
            <input type="text" id="codice_inventario" name="codice_inventario" value="<?= htmlspecialchars($codiceInventario) ?>" readonly />

            <label for="descrizione">Descrizione inventario:</label>
            <input type="text" id="descrizione" name="descrizione" value="<?= htmlspecialchars($_POST['descrizione'] ?? '') ?>" />
            <?php if (!empty($errors['descrizione'])): ?>
                <div class="error"><?= htmlspecialchars($errors['descrizione']) ?></div>
            <?php endif; ?>

            <h2>Seleziona dotazioni presenti:</h2>
            <ul class="lista-dotazioni">
                <?php if (empty($dotazioni)): ?>
                    <li>Nessuna dotazione trovata per l'ultimo inventario.</li>
                <?php else: ?>
                    <?php foreach ($dotazioni as $dotazione): 
                        // Se il codice è presente in GET['spuntato'], spuntalo di default
                        $checked = in_array($dotazione['codice'], $_POST['dotazione_presente'] ?? $dotazioniSpuntate) ? 'checked' : '';
                    ?>
                        <li>
                            <input type="checkbox" id="dotazione_<?= htmlspecialchars($dotazione['codice']) ?>" name="dotazione_presente[]" value="<?= htmlspecialchars($dotazione['codice']) ?>" <?= $checked ?> />
                            <label for="dotazione_<?= htmlspecialchars($dotazione['codice']) ?>">
                                <?= htmlspecialchars($dotazione['nome']) ?> - <small><?= htmlspecialchars($dotazione['categoria']) ?></small>
                            </label>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <button type="submit">Salva</button>
            <a href="scan.php?id=<?= urlencode($idAula) ?>" class="scan-btn">SCAN</a>

            <?php if (!empty($errors['db'])): ?>
                <div class="error"><?= htmlspecialchars($errors['db']) ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>
</body>
</html>
