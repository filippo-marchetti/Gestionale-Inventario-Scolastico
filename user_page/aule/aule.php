<?php
session_start();

$username = $_SESSION['username'];
$role = $_SESSION['role'];

if (!is_null($username)) {
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
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="aule.css">
        <title>Document</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <script src="aule.js"></script>
    <div class="container">
        <!-- sidebar -->
        <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\logo_darzo.png" width="120px"></div>
                <!-- questa div conterrà i link delle schede -->
                <div class="section-container">
                    <br>
                    <a href="..\admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="..\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>
                    <a href="lista_dotazione.php"><div class="section selected"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>
        <!-- content -->
    <div class="content">
        <div class="logout">
            <a class="logout-btn" href="../../logout/logout.php">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>

        <h1>Aule</h1>

        <div class="actions">
            <input type="text" id="filterInput" placeholder="Cerca per codice o descrizione" class="filter-input">
            <form method="post" action="../aggiungi_aule/aggiungi_aule.php?id=<?php echo $idAula ?>">
                <button class="btn-add"><i class="fas fa-plus"></i> Nuova Aula</button>
            </form>
        </div>
        <div class="lista-dotazioni">
            <table>
                    <thead>
                        <td>Aula</td>
                        <td>Tipologia</td>
                        <td>Descrizione</td>
                        <td>Numero Inventari</td>
                        <td style="text-align: center;">Azioni</td>
                    </thead>
                <tbody>
                        <?php foreach ($aule as $aula): 
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM inventario WHERE ID_aula = ?");
                            $stmt->execute([$aula['ID_aula']]);
                            $numInv = $stmt->fetchColumn();
                            echo "<tr>";
                            echo "<td>".$aula['ID_aula']."</td>";
                            echo "<td>".$aula['tipologia']."</td>";
                            echo "<td>".$aula['descrizione']."</td>";
                            echo "<td>".$numInv."</td>";
                        ?>
                        <td>
                            <div class="div-action-btn">
                                <a href="../inventari/inventari.php?id=<?php echo $aula['ID_aula'] ?>" >
                                    <button class="btn-action btn-green"><i class="fas fa-eye"></i></button>
                                </a>
                                <a href="generazione_QR_aula/generazione_QR_aula.php?id=<?php echo $aula['ID_aula']; ?>">
                                    <button name="qrcode" class="btn-action btn-blu">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>