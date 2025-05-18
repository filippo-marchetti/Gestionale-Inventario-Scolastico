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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../../assets/css/background.css">
        <link rel="stylesheet" href="../../assets/css/shared_style_user_admin.css">
        <link rel="stylesheet" href="aule.css">
        <title>Elenco aule</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
<body>
     <div class="container">
            <!-- sidebar -->
            <div class="sidebar">
                <div class="image"><img src="../../assets/images/logo_darzo.png" width="120px"></div>
                <!-- questa div conterrà i link delle schede -->
                <div class="section-container">
                    <br>
                    <a href="../admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>
            <!-- content contiene tutto ciò che è al di fuori della sidebar -->
            <div class="content">
                <!-- user-logout contiene il nome utente dell'utente loggato e il collegamento per il logout -->
                <div class="logout">
                    <a class="logout-btn" href="../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <h1 style="margin-bottom: 24px;">Elenco aule</h1>
                <div class="aule-wrapper">
                    <?php foreach ($aule as $aula): ?>
                        <a class="aula-card" href="../inventari/inventari.php?id=<?= urlencode($aula['ID_aula']) ?>">
                             <div class="aula-info">
                            <span class="label">ID Aula:</span> <?= htmlspecialchars($aula['ID_aula']) ?>
                            </div>
                            <div class="aula-info">
                                <span class="label">Tipologia:</span> <?= htmlspecialchars($aula['tipologia']) ?>
                            </div>
                            <div class="aula-header">
                            <?= htmlspecialchars($aula['descrizione']) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
</body>
</html>
