<?php
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
}

if (!isset($_GET['id'])) {
    die("ID aula non specificato.");
}

$idAula = $_GET['id'];
$messaggio = "";

// Funzione per generare codice inventario unico
function generaCodiceInventario($conn) {
    do {
        $codice = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT 1 FROM inventario WHERE codice_inventario = ?");
        $stmt->execute([$codice]);
        $exists = $stmt->fetchColumn();
    } while ($exists);
    return $codice;
}

// Recupera il codice dell'ultimo inventario per quell'aula
$stmt = $conn->prepare("
    SELECT codice_inventario, descrizione, data_inventario
    FROM inventario
    WHERE ID_Aula = ?
    ORDER BY data_inventario DESC
    LIMIT 1
");
$stmt->execute([$idAula]);
$lastInventario = $stmt->fetch(PDO::FETCH_ASSOC);

$dotazioni = [];
if ($lastInventario) {
    $codiceInventario = $lastInventario['codice_inventario'];
    $descrizioneInventario = $lastInventario['descrizione'];
    $dataInventario = $lastInventario['data_inventario'];

    // Recupera le dotazioni relative a quell'inventario
    $stmt = $conn->prepare("
        SELECT d.codice, d.nome, d.categoria, d.descrizione, d.stato, d.prezzo_stimato
        FROM riga_inventario ri
        JOIN dotazione d ON ri.codice_dotazione = d.codice
        WHERE ri.codice_inventario = ?
    ");
    $stmt->execute([$codiceInventario]);
    $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Genera codice nuovo inventario solo se non è POST (così non cambia ad ogni submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codiceNuovoInventario = $_POST['codice_inventario'];
} else {
    $codiceNuovoInventario = generaCodiceInventario($conn);
}

// --- Qui va la gestione POST per salvataggio inventario e dotazioni ---

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo Inventario</title>
    <link rel="stylesheet" href="..\..\assets\css\shared_style_login_register.css">
    <link rel="stylesheet" href="..\..\assets\css\background.css">
    <link rel="stylesheet" href="nuovo_inventario.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>Crea un nuovo inventario</h1>
            <div class="subtitle">
                <?php if ($lastInventario): ?>
                    Ultimo inventario per questa aula: <b><?= htmlspecialchars($codiceInventario) ?></b> (<?= htmlspecialchars($dataInventario) ?>)<br>
                    <span style="font-size:0.95em;">Descrizione: <?= htmlspecialchars($descrizioneInventario) ?></span>
                <?php else: ?>
                    Nessun inventario precedente per questa aula.
                <?php endif; ?>
            </div>
        </div>
        <form method="post" action="">
            <div>
                <label for="codice_inventario" class="label">Codice nuovo inventario:</label>
                <input type="text" id="codice_inventario" name="codice_inventario" value="<?= htmlspecialchars($codiceNuovoInventario) ?>" readonly required>
            </div>
            <div>
                <label for="descrizione" class="label">Descrizione:</label>
                <input type="text" id="descrizione" name="descrizione" required>
            </div>
            <?php if ($dotazioni): ?>
                <div style="margin: 30px 0 10px 0; font-weight:600;">Dotazioni presenti nell'ultimo inventario:</div>
                <?php foreach ($dotazioni as $d): ?>
                    <div class="dotazione">
                        <label>
                            <input type="checkbox" name="dotazione_presente[]" value="<?= htmlspecialchars($d['codice']) ?>">
                            <span class="label"><?= htmlspecialchars($d['nome']) ?></span>
                            <span style="color:#888;">(<?= htmlspecialchars($d['categoria']) ?>)</span>
                        </label>
                        <div style="margin-left:28px;">
                            <span class="label">Codice:</span> <?= htmlspecialchars($d['codice']) ?> |
                            <span class="label">Descrizione:</span> <?= htmlspecialchars($d['descrizione']) ?> |
                            <span class="label">Stato:</span> <?= htmlspecialchars($d['stato']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div style="margin: 30px 0 10px 0; font-weight:600;">Aggiungi dotazione non presente:</div>
            <div>
                <input type="text" name="nuova_dotazione" placeholder="Codice dotazione">
                <button type="submit" name="aggiungi_dotazione">Aggiungi dotazione</button>
            </div>
            <div style="margin-top:24px;">
                <button type="submit">Crea inventario</button>
            </div>
        </form>
        <?php if (isset($messaggio)): ?>
            <p class="no-results"><?= htmlspecialchars($messaggio) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>