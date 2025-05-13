<?php
session_start();

$username = $_SESSION['username'];
$role = $_SESSION['role'];

if (!is_null($username) && $role == "user") {
    // Connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Recupera tutte le aule dalla tabella 'aula'
        $stmt = $conn->query("SELECT ID_aula, descrizione, tipologia FROM aula");
        $aule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Errore nella connessione o nella query: " . $e->getMessage());
    }
} else {
    header("Location: ..\logout\logout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Elenco Aule</title>
    <link rel="stylesheet" href="..\assets\css\shared_style_login_register.css">
    <link rel="stylesheet" href="..\assets\css\background.css">

</head>
<body>
    <div class="container">
        <h1>Elenco Aule</h1>

        <!-- Cicla ogni aula e rende cliccabile tutto il box -->
        <?php foreach ($aule as $aula): ?>
            <a class="aula-box" href="inventari.php?id=<?php echo($aula['ID_aula']) ?>">
                <div><span class="label">Descrizione:</span> <?= htmlspecialchars($aula['descrizione']) ?></div>
                <div><span class="label">Tipologia:</span> <?= htmlspecialchars($aula['tipologia']) ?></div>
                <div><span class="label">ID:</span> <?= htmlspecialchars($aula['ID_aula']) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
</body>
</html>

