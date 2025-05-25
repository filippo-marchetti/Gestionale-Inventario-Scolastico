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

    // 🔹 Parametri da GET
    $idAula = $_GET['id'] ?? null;
    $codiceInventario = $_GET['codice_inventario'] ?? null;
    $spuntati = $_GET['spuntato'] ?? [];

    if (!is_array($spuntati)) {
        $spuntati = [$spuntati];
    }

    if (!$idAula || !$codiceInventario) {
        die("Parametri mancanti.");
    }

    $messaggio = '';
    $errore = '';

    // 🔹 Quando viene inviato un nuovo codice dotazione
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codice_dotazione'])) {
        $codiceLetto = trim($_POST['codice_dotazione']);

        // ✅ Controlla se esiste nel database
        $stmt = $conn->prepare("SELECT * FROM dotazione WHERE codice = ?");
        $stmt->execute([$codiceLetto]);
        $dotazione = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dotazione) {
            // ✅ Evita duplicati
            if (!in_array($codiceLetto, $spuntati)) {
                $spuntati[] = $codiceLetto;
                $messaggio = "Dotazione $codiceLetto aggiunta.";
            } else {
                $messaggio = "Dotazione $codiceLetto già presente.";
            }
        } else {
            $errore = "Codice dotazione non trovato nel database.";
        }

        // 🔁 Reindirizza di nuovo a nuovo_inventario.php
        $query = http_build_query([
            'id' => $idAula,
            'codice_inventario' => $codiceInventario,
            'spuntato' => $spuntati
        ]);
        header("Location: nuovo_inventario.php?$query");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Scanner Dotazione</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 600px; margin: auto; }
        .success { background-color: #d4edda; padding: 10px; margin-bottom: 20px; color: #155724; }
        .error { background-color: #f8d7da; padding: 10px; margin-bottom: 20px; color: #721c24; }
        input[type=text] { padding: 10px; width: 100%; margin-bottom: 10px; }
        button { padding: 10px 20px; background-color: #0077cc; color: white; border: none; cursor: pointer; }
        a { text-decoration: none; color: #0077cc; }
    </style>
</head>
<body>
<div class="container">
    <h1>Scanner Dotazione</h1>

    <?php if ($messaggio): ?>
        <div class="success"><?= htmlspecialchars($messaggio) ?></div>
    <?php endif; ?>

    <?php if ($errore): ?>
        <div class="error"><?= htmlspecialchars($errore) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="codice_dotazione">Inserisci o scansiona un codice:</label>
        <input type="text" name="codice_dotazione" id="codice_dotazione" required autofocus>
        <button type="submit">✅ Aggiungi</button>
    </form>

    <br>
    <a href="nuovo_inventario.php?<?= http_build_query([
        'id' => $idAula,
        'codice_inventario' => $codiceInventario,
        'spuntato' => $spuntati
    ]) ?>">🔙 Torna al nuovo inventario</a>
</div>
</body>
</html>
