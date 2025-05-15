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

$idAula = $_GET['id'] ?? null;
$codiceInventario = $_GET['codice_inventario'] ?? null;
$spuntati = $_GET['spuntato'] ?? [];
if (!is_array($spuntati)) {
    $spuntati = [$spuntati];
}

$messaggio = "";

if (!$idAula || !$codiceInventario) {
    die("Dati mancanti.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codiceDotazione = $_POST['codice_dotazione'] ?? '';

    if ($codiceDotazione) {
        $stmt = $conn->prepare("SELECT 1 FROM riga_inventario WHERE codice_inventario = ? AND codice_dotazione = ?");
        $stmt->execute([$codiceInventario, $codiceDotazione]);
        $giaPresente = $stmt->fetchColumn();

        if ($giaPresente) {
            // già presente → torna con lista spuntati aggiornata
            $spuntati[] = $codiceDotazione;
        } else {
            $stmt = $conn->prepare("SELECT * FROM dotazione WHERE codice = ?");
            $stmt->execute([$codiceDotazione]);
            $dotazione = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dotazione) {
                // Aggiungi riga_inventario
                $stmt = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");
                $stmt->execute([$codiceDotazione, $codiceInventario]);

                // Aggiorna aula
                $stmt = $conn->prepare("UPDATE dotazione SET ID_Aula = ? WHERE codice = ?");
                $stmt->execute([$idAula, $codiceDotazione]);

                $spuntati[] = $codiceDotazione;
            } else {
                $messaggio = "❌ Codice dotazione non trovato.";
            }
        }

        if (empty($messaggio)) {
            $query = http_build_query([
                'id' => $idAula,
                'codice_inventario' => $codiceInventario,
                'spuntato' => $spuntati
            ]);
            header("Location: nuovo_inventario.php?$query");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="scan.css">
        <title>Admin - Page</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container">
    <h1>Scansione Dotazione</h1>
    <form method="post">
        <input type="text" name="codice_dotazione" placeholder="Inserisci codice dotazione..." required>
        <br>
        <button type="submit">Verifica / Aggiungi</button>
    </form>
    <?php if ($messaggio): ?>
        <div class="message"><?= htmlspecialchars($messaggio) ?></div>
    <?php endif; ?>
    <br>
    <a href="nuovo_inventario.php?<?= http_build_query([
        'id' => $idAula,
        'codice_inventario' => $codiceInventario,
        'spuntato' => $spuntati
    ]) ?>">⬅ Torna all'inventario</a>
</div>
</body>
</html>
