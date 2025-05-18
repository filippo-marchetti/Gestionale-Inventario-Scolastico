<?php
    session_start();

    //info database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    if(!is_null($username) && $role == "admin"){
        try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connessione fallita: " . $e->getMessage());
        }
        // Dopo aver premuto il tasto di archiviazione alla dotazione viene tolta l'aula e lo stato diventa scartato
        if(isset($_POST["scarta"])){
            $stmt = $conn->prepare("UPDATE dotazione SET ID_aula = NULL, stato = 'scartato' WHERE codice = :codice");
            $stmt->bindParam(':codice', $_POST['scarta']);
            $stmt->execute();
        }
    }else{
        header("Location: ..\logout\logout.php");
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
        <link rel="stylesheet" href="..\..\assets\css\shared_lista_dotazione.css">
        <link rel="stylesheet" href="lista_dotazione.css">
        <title>Document</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="..\lista_dotazione\lista_dotazione.js"></script>
    </head>
    <body>
        <div class="container">
            <!-- sidebar -->
            <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\logo_darzo.png" width="120px"></div>
                <!-- questa div conterrà i link delle schede -->
                <div class="section-container">
                    <br>
                    <a href="..\admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>
                    <a href="..\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>
                    <a href="..\lista_dotazione\lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="..\dotazione_archiviata\dotazione_archiviata.php"><div class="section selected"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>
            <!-- content contiene tutto ciò che è al di fuori della sidebar -->
            <div class="content">
                <!-- user-logout contiene il nome utente dell'utente loggato e il collegamento per il logout -->
                <div class="logout">
                    <a class="logout-btn" href="..\..\logout\logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <h1>Dotazioni</h1>
                <div class="actions">
                    <input type="text" id="filterInput" placeholder="Cerca per nome o codice" class="filter-input">
                    <form method="post">
                        <button class="btn-add"><i class="fas fa-plus"></i>Aggiungi</button>
                    </form>
                </div>

                <div class="lista-dotazioni">
                    <table>
                        <thead>
                            <td>Codice</td>
                            <td>Nome</td>
                            <td>Categoria</td>
                            <td>Descrizione</td>
                            <td>Prezzo Stimato</td>
                            <td>Aula</td>
                            <td style="text-align: center;">Azioni</td>
                        </thead>
                        <tbody>
                            <?php
                                // Query per recuperare gli account in richiesta
                                $stmt = $conn->query("SELECT * FROM dotazione WHERE ID_aula IS NULL AND stato LIKE 'archiviato'");
                                $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($dotazioni as $dotazione) {  
                                    echo "<tr>";
                                        echo "<td>".$dotazione['codice']."</td>";
                                        echo "<td>".$dotazione['nome']."</td>";
                                        echo "<td>".$dotazione['categoria']."</td>";
                                        echo "<td>".$dotazione['descrizione']."</td>";
                                        echo "<td>".$dotazione['prezzo_stimato']."€</td>";
                                        echo "<td>".$dotazione['ID_aula']."</td>";
                                        ?>
                                            <td>
                                                <div class="div-action-btn">
                                                    <!-- reindirizza alla pagina di modifica -->
                                                    <a href="..\lista_dotazione\modifica_dotazione\modifica_dotazione.php?codice=<?php echo $dotazione['codice']?>&start=archivio">
                                                        <button name="modifica" class="btn-action btn-green" value="">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                    </a>
                                                    <!-- reindirizza alla pagina del qrcode -->
                                                    <a href="modifica_dotazione/modifica_dotazione.php?id=123">
                                                        <button name="qrcode" class="btn-action btn-blu">
                                                            <i class="fas fa-qrcode"></i>
                                                        </button>
                                                    </a>
                                                    <form method="POST">
                                                        <button name="scarta" value="<?php echo $dotazione['codice']?>" class="btn-action btn-red">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        <?php
                                    echo "</tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>