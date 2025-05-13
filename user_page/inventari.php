<?php
// Connessione al database
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

// Recupera l'ID dell'aula passato tramite GET
if (!isset($_GET['id'])) {
    die("ID aula non specificato.");
}

$idAula = $_GET['id'];

// Recupera gli inventari ordinati dal più recente, con nome scuola
try {
    $stmt = $conn->prepare("
        SELECT i.codice_inventario, i.data_inventario, i.descrizione, s.nome AS nome_scuola
        FROM inventario i
        LEFT JOIN scuola s ON i.scuola_appartenenza = s.codice_meccanografico
        WHERE i.ID_Aula = ?
        ORDER BY i.data_inventario DESC
    ");
    $stmt->execute([$idAula]);
    $inventari = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Errore nel recupero degli inventari: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inventari Aula <?= htmlspecialchars($idAula) ?></title>
    <link rel="stylesheet" href="..\assets\css\shared_style_login_register.css">
    <link rel="stylesheet" href="..\assets\css\background.css">
</head>
<body>
    <div class="container">
        <h1>Inventari dell'Aula <?= htmlspecialchars($idAula) ?></h1>
        <button></button>
        <a href="nuovo_inventario.php?id=<?= urlencode($idAula) ?>" class="btn-nuovo">➕ Nuovo Inventario</a>

        <?php if (count($inventari) === 0): ?>
            <p class="no-results">Nessun inventario trovato per quest'aula.</p>
        <?php else: ?>
            <?php foreach ($inventari as $inv): ?>
                <a class="inventario" href="dotazioni.php?codice=<?= urlencode($inv['codice_inventario']) ?>">
                    <div><span class="label">Codice inventario:</span> <?= htmlspecialchars($inv['codice_inventario']) ?></div>
                    <div><span class="label">Data inventario:</span> <?= htmlspecialchars($inv['data_inventario']) ?></div>
                    <div><span class="label">Descrizione:</span> <?= htmlspecialchars($inv['descrizione']) ?></div>
                    <div><span class="label">Scuola di appartenenza:</span> <?= htmlspecialchars($inv['nome_scuola'] ?? 'Non specificata') ?></div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
