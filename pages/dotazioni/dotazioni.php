<?php
    session_start();

    // Connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? null;

    if(isset($role)){
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
                WHERE ri.codice_inventario = ?
            ");
            $stmt->execute([$codiceInventario]);
            $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Errore nella lettura delle dotazioni: " . $e->getMessage());
        }
    }else{
        header("Location: ..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dotazioni dell'inventario <?= htmlspecialchars($codiceInventario) ?></title>
    <link rel="stylesheet" href="..\..\assets\css\background.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_lista_dotazione.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="..\..\admin_page\lista_dotazione\lista_dotazione.js"></script>
</head>
<body>
    <div class="container">
        <!-- sidebar -->
        <div class="sidebar">
            <div class="image"><img src="..\..\assets\images\placeholder.png" width="120px"></div>
            <div class="section-container">
                <br>
                <?php
                    if($role == 'admin') {
                        echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    } else {
                        echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    }
                ?>
                <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                <?php
                    if($role == "admin"){
                        echo '<a href="..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                        echo '<a href="..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                        echo '<a href="..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                    };
                ?>
                <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                <a href="../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                <a href="../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
            </div>
        </div>
        <!-- content -->
        <div class="content">
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <!-- Bottone "indietro" -->
                <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <!-- Bottone logout -->
                <a class="logout-btn" href="../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <h1>Dotazioni dell'inventario <?= htmlspecialchars($codiceInventario) ?></h1>
            <div class="actions">
                    <input type="text" id="filterInput" placeholder="Cerca per nome o codice" class="filter-input">
            </div>
            <div class="lista-dotazioni">
                <?php if (count($dotazioni) === 0): ?>
                    <p class="no-results">Nessuna dotazione trovata per questo inventario.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <td>Codice</td>
                                <td>Nome</td>
                                <td>Categoria</td>
                                <td>Descrizione</td>
                                <td>Stato</td>
                                <td>Prezzo Stimato</td>
                                <td>Aula</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dotazioni as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['codice']) ?></td>
                                    <td><?= htmlspecialchars($d['nome']) ?></td>
                                    <td><?= htmlspecialchars($d['categoria']) ?></td>
                                    <td><?= htmlspecialchars($d['descrizione']) ?></td>
                                    <td><?= htmlspecialchars($d['stato']) ?></td>
                                    <td><?= htmlspecialchars($d['prezzo_stimato']) ?>€</td>
                                    <td><?= htmlspecialchars($d['ID_aula']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>