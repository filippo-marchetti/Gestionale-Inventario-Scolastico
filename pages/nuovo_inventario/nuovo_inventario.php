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

if (!$idAula) die("ID aula non specificato.");

$dateStr = date('dmY');
$iniziale = substr($idAula, 0, 1);
$finale = substr($idAula, -1, 1);
$codiceInventario = strtoupper($iniziale . $finale . $dateStr);
$codiceInventario = substr($codiceInventario, 0, 9); // massimo 9 caratteri

// Verifica unicità
$stmt = $conn->prepare("SELECT 1 FROM inventario WHERE codice_inventario = ?");
while (true) {
    $stmt->execute([$codiceInventario]);
    if (!$stmt->fetchColumn()) break;
    $codiceInventario = strtoupper($iniziale . $finale . random_int(100000, 999999));
    $codiceInventario = substr($codiceInventario, 0, 9);
}

// Leggi ultimo inventario
$stmt = $conn->prepare("
    SELECT codice_inventario
    FROM inventario
    WHERE ID_aula = ?
    ORDER BY data_inventario DESC
    LIMIT 1
");
$stmt->execute([$idAula]);
$lastInv = $stmt->fetch(PDO::FETCH_ASSOC);

$dotazioni = [];
if ($lastInv) {
    $stmt = $conn->prepare("
        SELECT d.codice, d.nome, d.categoria, d.stato, d.ID_aula AS aula_corrente
        FROM riga_inventario ri
        JOIN dotazione d ON ri.codice_dotazione = d.codice
        WHERE ri.codice_inventario = ?
    ");
    $stmt->execute([$lastInv['codice_inventario']]);
    $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descrizione = trim($_POST['descrizione'] ?? '');
    $spuntati = $_POST['dotazione_presente'] ?? [];

    if (!$descrizione) $errors[] = "Inserire una descrizione.";

    if (empty($errors)) {
        // Salva inventario
        $stmt = $conn->prepare("
            INSERT INTO inventario (codice_inventario, data_inventario, descrizione, ID_aula, scuola_appartenenza, ID_tecnico)
            VALUES (?, NOW(), ?, ?, NULL, '')
        ");
        $stmt->execute([$codiceInventario, $descrizione, $idAula]);

        // Aggiungi dotazioni spuntate
        $stmtAdd = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");
        foreach ($spuntati as $codice) {
            $stmtAdd->execute([$codice, $codiceInventario]);

            // Aggiorna l’aula della dotazione
            $stmtUpdate = $conn->prepare("UPDATE dotazione SET ID_aula = ? WHERE codice = ?");
            $stmtUpdate->execute([$idAula, $codice]);
        }

        $_SESSION['success_message'] = "Inventario salvato.";
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
        .container { max-width: 800px; margin: 30px auto; font-family: sans-serif; }
        .dotazione { padding: 10px; border-bottom: 1px solid #ccc; }
        .warning { color: red; font-size: 0.9em; }
        .btn { padding: 10px 20px; margin-top: 20px; background: #444; color: white; border: none; cursor: pointer; }
        .btn-scan { text-decoration: none; background: #0077cc; color: white; padding: 10px 15px; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Nuovo Inventario per aula <?= htmlspecialchars($idAula) ?></h1>

    <a class="btn-scan" href="scan.php?<?= http_build_query([
        'id' => $idAula,
        'codice_inventario' => $codiceInventario,
        'spuntato' => $codiciDaSpuntare
    ]) ?>">📷 Scansiona dotazione</a>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="codice_inventario" value="<?= htmlspecialchars($codiceInventario) ?>">
        <label>Codice Inventario: <strong><?= htmlspecialchars($codiceInventario) ?></strong></label><br><br>

        <label>Descrizione:</label><br>
        <input type="text" name="descrizione" style="width:100%;" required><br><br>

        <h3>Dotazioni precedenti:</h3>
        <?php foreach ($dotazioni as $d): ?>
            <?php $altAula = ($d['aula_corrente'] && $d['aula_corrente'] !== $idAula); ?>
            <div class="dotazione">
                <label>
                    <input type="checkbox" name="dotazione_presente[]" value="<?= htmlspecialchars($d['codice']) ?>"
                        <?= in_array($d['codice'], $codiciDaSpuntare) ? 'checked' : '' ?>>
                    <strong><?= htmlspecialchars($d['nome']) ?></strong> (<?= htmlspecialchars($d['categoria']) ?>)
                </label><br>
                Codice: <?= htmlspecialchars($d['codice']) ?> | Stato: <?= htmlspecialchars($d['stato']) ?>
                <?php if ($altAula): ?>
                    <div class="warning">⚠ Attenzione: attualmente in aula <?= htmlspecialchars($d['aula_corrente']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn">💾 Salva Inventario</button>
    </form>
</div>
</body>
</html>