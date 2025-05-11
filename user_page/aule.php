<?php
// Connessione al database
$host = 'localhost'; // Host del database
$db = 'inventariosdarzo'; // Nome del database
$user = 'root'; // Nome utente del database
$pass = ''; // Password del database

try {
    // Tentativo di connessione al database con PDO
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Recupera tutte le aule dalla tabella 'aula'
    $stmt = $conn->query("SELECT ID_Aula, descrizione, tipologia FROM aula");
    $aule = $stmt->fetchAll(PDO::FETCH_ASSOC); // Restituisce i risultati come array associativo
} catch (PDOException $e) {
    // Gestisce gli errori di connessione o query
    die("Errore nella connessione o nella query: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Elenco Aule</title>
    <style>
        /* Stile di base per la pagina */
        body {
            font-family: sans-serif;
            padding: 20px;
            background-color: #f0f0f0;
        }
        .container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .aula-box {
            background: #fff;
            border: 1px solid #ccc;
            padding: 12px 20px;
            border-radius: 8px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .aula-link {
            font-weight: bold;
            color: #007BFF;
            text-decoration: none;
        }
        .aula-link:hover {
            text-decoration: underline;
        }
        .label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Elenco Aule</h1>
    <div class="container">
        <!-- Cicla ogni aula e stampa i dettagli -->
        <?php foreach ($aule as $aula): ?>
            <div class="aula-box">
                <!-- Crea un link per ogni aula che porta alla pagina degli inventari con ID dell'aula -->
                <a class="aula-link" href="inventari.php?id=<?= urlencode($aula['ID_Aula']) ?>">
                    <?= htmlspecialchars($aula['descrizione']) ?>
                </a>
                <!-- Mostra la tipologia e ID dell'aula -->
                <div><span class="label">Tipologia:</span> <?= htmlspecialchars($aula['tipologia']) ?></div>
                <div><span class="label">ID:</span> <?= htmlspecialchars($aula['ID_Aula']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
