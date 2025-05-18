<?php
session_start();

// Connessione al database
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

$role = $_SESSION['role'];

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}

if (!isset($_GET['id'])) {
    die("ID aula non specificato.");
}

$idAula = $_GET['id'];

// Recupera descrizione aula
$stmtAula = $conn->prepare("SELECT descrizione FROM aula WHERE ID_Aula = ?");
$stmtAula->execute([$idAula]);
$descrizioneAula = $stmtAula->fetchColumn();

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
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="inventari.css">
        <title>Document</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="inventari.js"></script>
</head>
<body>
    <div class="container">
        <!-- sidebar -->
        <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\logo_darzo.png" width="120px"></div>
                <!-- questa div conterrà i link delle schede -->
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
            <a class="logout-btn" href="../logout/logout.php">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>

        <h1>Inventari</h1>

        <div class="actions">
            <input type="text" id="filterInput" placeholder="Cerca per codice o descrizione" class="filter-input">
            <form method="post" action="../nuovo_inventario/nuovo_inventario.php?id=<?php echo $idAula ?>">
                <button class="btn-add"><i class="fas fa-plus"></i>Nuovo Inventario</button>
            </form>
        </div>
        <div class="lista-dotazioni">
            <table>
                    <thead>
                        <td>Codice Inventario</td>
                        <td>Data Inventario</td>
                        <td>Descrizione</td>
                        <td>Scuola di appartenenza</td>
                        <td>Numero Dotazione</td>
                        <td style="text-align: center;">Azioni</td>
                    </thead>
                <tbody>
                    <?php if (count($inventari) === 0): ?>
                        <tr>
                            <td colspan="5" class="no-results">Nessun inventario trovato per quest'aula.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventari as $inv): 
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM riga_inventario WHERE codice_inventario = ?");
                                    $stmt->execute([$inv['codice_inventario']]);
                                    $numDot = $stmt->fetchColumn();
                                    echo "<tr>";
                                        echo "<td>".$inv['codice_inventario']."</td>";
                                        echo "<td>".$inv['data_inventario']."</td>";
                                        echo "<td>".$inv['descrizione']."</td>";
                                        echo "<td>".$inv['nome_scuola']."</td>";
                                        echo "<td>".$numDot."</td>";
                                        ?>
                                    <td>
                                        <div class="div-action-btn">
                                            <a href="../dotazioni/dotazioni.php?codice=<?php echo $inv['codice_inventario'] ?>" title="Visualizza dotazioni">
                                                <button class="btn-action btn-green"><i class="fas fa-eye"></i></button>
                                            </a>
                                        </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>