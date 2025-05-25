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

            // Controlla se esiste l'aula magazzino, altrimenti la crea
            $stmt = $conn->prepare("SELECT COUNT(*) FROM aula WHERE ID_aula = 'magazzino'");
            $stmt->execute();
            $magazzinoExists = $stmt->fetchColumn();
            if ($magazzinoExists == 0) {
                $stmt = $conn->prepare("INSERT INTO aula (ID_aula, tipologia, descrizione, stato) VALUES ('magazzino', 'magazzino', 'Aula magazzino di sistema', 'attiva')");
                $stmt->execute();
            }

            // Recupera tutte le aule dalla tabella 'aula'
            $stmt = $conn->query("SELECT ID_aula, descrizione, tipologia FROM aula WHERE stato = 'attiva'");
            $aule = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Errore nella connessione o nella query: " . $e->getMessage());
        }

        if(isset($_POST["scarta"])){
            // Impedisci l'eliminazione dell'aula magazzino
            if ($_POST['scarta'] !== 'magazzino') {
                $stmt = $conn->prepare("UPDATE aula SET stato = 'eliminata' WHERE ID_aula = :ID_aula");
                $stmt->bindParam(':ID_aula', $_POST['scarta']);
                $stmt->execute();

                $stmt = $conn->prepare("UPDATE dotazione SET stato = 'archiviato' WHERE ID_aula = :ID_aula");
                $stmt->bindParam(':ID_aula', $_POST['scarta']);
                $stmt->execute();
            }
        }
    } else {
        header("Location: ..\..\logout\logout.php");
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
        <title>Aule</title>
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
        <h1>Aule</h1>
        <div class="actions">
            <input type="text" id="filterInput" placeholder="Cerca per codice o descrizione" class="filter-input">
            <form method="post" action="aggiungi_aula/aggiungi_aula.php">
                <button class="btn-add"><i class="fas fa-plus"></i>Nuova Aula</button>
            </form>
        </div>
        <div class="lista-dotazioni">
            <table>
                    <thead>
                        <td>Aula</td>
                        <td>Tipologia</td>
                        <td>Descrizione</td>
                        <td>Numero Inventari</td>
                        <td>Numero Dotazioni</td>
                        <td style="text-align: center;">Azioni</td>
                    </thead>
                <tbody>
                        <?php foreach ($aule as $aula): 
                            $stmt = $conn->prepare("SELECT ID_aula FROM inventario");
                            $controlloMagazzino = $stmt->fetchColumn();
                            if($controlloMagazzino != "magazzino"){
                                //Numero dotazioni
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM dotazione WHERE ID_aula = ?");
                                $stmt->execute([$aula['ID_aula']]);
                                $numDot = $stmt->fetchColumn();
                            }else{
                                //Numero dotazioni
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM dotazione WHERE stato = 'archiviato'");
                                $stmt->execute([$aula['ID_aula']]);
                                $numDot = $stmt->fetchColumn();
                            }
                            //Numero inventari
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM inventario WHERE ID_aula = ? ");
                            $stmt->execute([$aula['ID_aula']]);
                            $numInv = $stmt->fetchColumn();
                            echo "<tr>";
                            echo "<td>".$aula['ID_aula']."</td>";
                            echo "<td>".$aula['tipologia']."</td>";
                            echo "<td>".$aula['descrizione']."</td>";
                            echo "<td>".$numInv."</td>";
                            echo "<td>".$numDot."</td>";
                        ?>
                        <td>
                            <div class="div-action-btn">
                                <a href="../inventari/inventari.php?id=<?php echo $aula['ID_aula'] ?>" >
                                    <button class="btn-action btn-green"><i class="fas fa-eye"></i></button>
                                </a>
                                <a href="..\generazione_QR\genera_pdf_aule.php?ID_aula=<?php echo $aula['ID_aula']; ?>" target="_BLANK" <?php if($numDot == 0) echo "onclick='return false;'"?>>
                                    <button name="qrcode" class="btn-action btn-blu">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </a>
                                <?php if($aula['ID_aula'] !== 'magazzino'): ?>
                                    <form method="POST" style="display:inline;">
                                        <button name="scarta" value="<?php echo $aula['ID_aula']?>" class="btn-action btn-red">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
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