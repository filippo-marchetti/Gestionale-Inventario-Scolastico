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

// Verifica codice inventario
if (!isset($_GET['codice'])) {
    die("Codice inventario non specificato.");
}

$codiceInventario = $_GET['codice'];

// Recupera le dotazioni relative a quell'inventario
try {
    $stmt = $conn->prepare("
        SELECT d.codice, d.nome, d.categoria, d.descrizione, d.stato, d.prezzo_stimato, d.ID_aula
        FROM dotazione d
        INNER JOIN riga_inventario ri ON d.codice = ri.codice_dotazione
        INNER JOIN inventario i ON i.codice_inventario = ri.codice_inventario
        WHERE i.codice_inventario = ?
    ");
    $stmt->execute([$codiceInventario]);
    $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Errore nella lettura delle dotazioni: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dotazioni dell'inventario <?= htmlspecialchars($codiceInventario) ?></title>
    <link rel="stylesheet" href="..\assets\css\shared_style_login_register.css">
    <link rel="stylesheet" href="..\assets\css\background.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 900px;
            margin: 50px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.15);
        }
        h1 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .dotazione {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .label {
            font-weight: bold;
        }
        .no-results {
            color: #777;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dotazioni per l'inventario: <?= htmlspecialchars($codiceInventario) ?></h1>

        <?php if (count($dotazioni) === 0): ?>
            <p class="no-results">Nessuna dotazione trovata per questo inventario.</p>
        <?php else: ?>
            <?php foreach ($dotazioni as $d): ?>
                <div class="dotazione">
                    <div><span class="label">Codice:</span> <?= htmlspecialchars($d['codice']) ?></div>
                    <div><span class="label">Nome:</span> <?= htmlspecialchars($d['nome']) ?></div>
                    <div><span class="label">Categoria:</span> <?= htmlspecialchars($d['categoria']) ?></div>
                    <div><span class="label">Descrizione:</span> <?= htmlspecialchars($d['descrizione']) ?></div>
                    <div><span class="label">Stato:</span> <?= htmlspecialchars($d['stato']) ?></div>
                    <div><span class="label">Prezzo stimato:</span> €<?= htmlspecialchars($d['prezzo_stimato']) ?></div>
                    <div><span class="label">ID Aula:</span> <?= htmlspecialchars($d['ID_Aula']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
