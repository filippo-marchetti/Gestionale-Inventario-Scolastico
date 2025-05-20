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
$codiciDaSpuntare = $_GET['spuntato'] ?? [];
if (!is_array($codiciDaSpuntare)) {
    $codiciDaSpuntare = [$codiciDaSpuntare];
}

if (!$idAula) {
    die("ID aula non specificato.");
}

// Generazione codice inventario (max 9 caratteri)
$iniziale = substr($idAula, 0, 1);
$finale = substr($idAula, -1, 1);
$data = date('dmY'); // es: 20052025
$codiceInventario = strtoupper($iniziale . $finale . $data);
$codiceInventario = substr($codiceInventario, 0, 9);

// Assicura codice univoco
$stmt = $conn->prepare("SELECT 1 FROM inventario WHERE codice_inventario = ?");
while (true) {
    $stmt->execute([$codiceInventario]);
    if (!$stmt->fetchColumn()) break;
    $codiceInventario = strtoupper($iniziale . $finale . str_pad(strval(random_int(0, 999999)), 7, '0', STR_PAD_LEFT));
    $codiceInventario = substr($codiceInventario, 0, 9);
}

// Recupera ultimo inventario in ordine cronologico reale (con DATETIME)
$stmt = $conn->prepare("
    SELECT codice_inventario
    FROM inventario
    WHERE ID_aula = ?
    ORDER BY data_inventario DESC
    LIMIT 1
");
$stmt->execute([$idAula]);
$lastInv = $stmt->fetch(PDO::FETCH_ASSOC);

// Dotazioni: da ultimo inventario + da scan.php
$dotazioni = [];
$codiciPresenti = [];

if ($lastInv) {
    $stmt = $conn->prepare("
        SELECT d.codice, d.nome, d.categoria, d.stato, d.ID_aula AS aula_corrente
        FROM riga_inventario ri
        JOIN dotazione d ON ri.codice_dotazione = d.codice
        WHERE ri.codice_inventario = ?
    ");
    $stmt->execute([$lastInv['codice_inventario']]);
    $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $codiciPresenti = array_column($dotazioni, 'codice');
}

// Aggiungi dotazioni da scan.php se non già presenti
$nuoviCodici = array_diff($codiciDaSpuntare, $codiciPresenti);
if (!empty($nuoviCodici)) {
    $placeholders = implode(',', array_fill(0, count($nuoviCodici), '?'));
    $stmt = $conn->prepare("
        SELECT codice, nome, categoria, stato, ID_aula AS aula_corrente
        FROM dotazione
        WHERE codice IN ($placeholders)
    ");
    $stmt->execute($nuoviCodici);
    $nuoveDotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dotazioni = array_merge($dotazioni, $nuoveDotazioni);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descrizione = trim($_POST['descrizione'] ?? '');
    $spuntati = $_POST['dotazione_presente'] ?? [];

    if (!$descrizione) {
        $errors[] = "Inserire una descrizione.";
    }

    if (empty($errors)) {
        // Inserisci inventario
        $stmt = $conn->prepare("
            INSERT INTO inventario (codice_inventario, data_inventario, descrizione, ID_aula, scuola_appartenenza, ID_tecnico)
            VALUES (?, NOW(), ?, ?, NULL, '')
        ");
        $stmt->execute([$codiceInventario, $descrizione, $idAula]);

        // Inserisci righe + aggiorna dotazioni
        $stmtAdd = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");
        $stmtUpdate = $conn->prepare("UPDATE dotazione SET ID_aula = ?, stato = 'presente' WHERE codice = ?");

        foreach ($spuntati as $codice) {
            $stmtAdd->execute([$codice, $codiceInventario]);
            $stmtUpdate->execute([$idAula, $codice]);
        }

        $_SESSION['success_message'] = "Inventario creato e stato dotazioni aggiornato a 'presente'.";
        header("Location: inventari.php?id=" . urlencode($idAula));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo Inventario</title>
    <style>
        .container { max-width: 900px; margin: 30px auto; font-family: Arial, sans-serif; }
        .dotazione { padding: 10px; border-bottom: 1px solid #ccc; }
        .warning { color: red; font-size: 0.9em; }
        .success-scan { color: green; font-weight: bold; font-size: 0.9em; }
        .btn { padding: 10px 20px; margin-top: 20px; background: #444; color: white; border: none; cursor: pointer; }
        .btn-scan { text-decoration: none; background: #0077cc; color: white; padding: 10px 15px; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
        .error { color: red; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Nuovo Inventario - Aula <?= htmlspecialchars($idAula) ?></h1>

    <a href="scan.php?<?= http_build_query([
        'id' => $idAula,
        'codice_inventario' => $codiceInventario,
        'spuntato' => $codiciDaSpuntare
    ]) ?>" class="btn-scan">📷 Scansiona Dotazione</a>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="codice_inventario" value="<?= htmlspecialchars($codiceInventario) ?>">

        <label>Codice Inventario:</label>
        <input type="text" value="<?= htmlspecialchars($codiceInventario) ?>" readonly><br><br>

        <label>Descrizione:</label>
        <input type="text" name="descrizione" required style="width:100%;"><br><br>

        <h3>Dotazioni incluse:</h3>
        <?php foreach ($dotazioni as $d): ?>
            <?php
                $codice = $d['codice'];
                $altAula = ($d['aula_corrente'] && $d['aula_corrente'] !== $idAula);
                $aggiuntaDaScan = in_array($codice, $codiciDaSpuntare);
            ?>
            <div class="dotazione">
                <label>
                    <input type="checkbox" name="dotazione_presente[]" value="<?= htmlspecialchars($codice) ?>"
                        <?= in_array($codice, $codiciDaSpuntare) ? 'checked' : '' ?>>
                    <strong><?= htmlspecialchars($d['nome']) ?></strong> (<?= htmlspecialchars($d['categoria']) ?>)
                </label><br>
                Codice: <?= htmlspecialchars($codice) ?> | Stato: <?= htmlspecialchars($d['stato']) ?>
                <?php if ($altAula): ?>
                    <div class="warning">⚠ Attualmente si trova in aula <?= htmlspecialchars($d['aula_corrente']) ?></div>
                <?php endif; ?>
                <?php if ($aggiuntaDaScan): ?>
                    <div class="success-scan">✅ Aggiunta tramite scansione</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <br><button type="submit" class="btn">💾 Salva Inventario</button>
    </form>
</div>
</body>
</html>
