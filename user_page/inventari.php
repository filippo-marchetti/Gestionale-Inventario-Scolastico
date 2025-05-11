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

// Recupera gli inventari ordinati dal più recente al meno recente, con nome scuola
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
        }
        .inventario {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: background 0.2s ease;
            cursor: pointer;
        }
        .inventario:hover {
            background: #f1f1f1;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 160px;
        }
        .no-results {
            font-style: italic;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Inventari dell'Aula <?= htmlspecialchars($idAula) ?></h1>

        <?php if (count($inventari) === 0): ?>
            <p class="no-results">Nessun inventario trovato per quest'aula.</p>
        <?php else: ?>
            <?php foreach ($inventari as $inv): ?>
                <div class="inventario" onclick="location.href='dotazioni.php?codice=<?= urlencode($inv['codice_inventario']) ?>'">
                    <div><span class="label">Codice inventario:</span> <?= htmlspecialchars($inv['codice_inventario']) ?></div>
                    <div><span class="label">Data inventario:</span> <?= htmlspecialchars($inv['data_inventario']) ?></div>
                    <div><span class="label">Descrizione:</span> <?= htmlspecialchars($inv['descrizione']) ?></div>
                    <div><span class="label">Scuola di appartenenza:</span> <?= htmlspecialchars($inv['nome_scuola'] ?? 'Non specificata') ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
