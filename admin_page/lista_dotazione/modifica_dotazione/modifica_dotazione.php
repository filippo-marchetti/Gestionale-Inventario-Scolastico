<?php
    session_start();

    //info database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    $codice = $_GET['codice'];

    if(!is_null($username) && $role == "admin"){
        try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connessione fallita: " . $e->getMessage());
        }
        if (isset($_GET['codice'])) {
            
            $stmt = $conn->prepare("SELECT * FROM dotazione WHERE codice = :codice");
            $stmt->bindParam(':codice', $codice, PDO::PARAM_STR);
            $stmt->execute();
            $dotazione = $stmt->fetch(PDO::FETCH_ASSOC);

            // Recupera tutte le categorie da inserire nella select
            $stmtCategorie = $conn->prepare("SELECT ID_categoria FROM categoria");
            $stmtCategorie->execute();
            $elencoCategorie = $stmtCategorie->fetchAll(PDO::FETCH_COLUMN);

            // Recupera tutte le aule da inserire nella select
            $stmtAule = $conn->prepare("SELECT ID_aula FROM aula");
            $stmtAule->execute();
            $elencoAule = $stmtAule->fetchAll(PDO::FETCH_COLUMN);

            if(isset($_POST['save'])){
                $stmt = $conn->prepare("UPDATE dotazione SET nome = :nome, descrizione = :descrizione, prezzo_stimato = :prezzo_stimato, aula = :aula WHERE codice = :codice");

                $stmt->bindParam(':nome', $_POST["nome"], PDO::PARAM_STR);
                $stmt->bindParam(':descrizione', $_POST["descrizione"], PDO::PARAM_STR);
                $stmt->bindParam(':prezzo_stimato', $_POST["prezzo"], PDO::PARAM_STR); // o PARAM_INT se è numerico intero
                $stmt->bindParam(':aula', $_POST["aula"], PDO::PARAM_STR);
                $stmt->bindParam(':codice', $_POST["codice"], PDO::PARAM_STR);

                $stmt->execute();

            }
            if(isset($_POST['reset'])){
                
            }
        } else {
            // Se il codice non è passato, reindirizza o mostra un errore
            die("Codice non fornito.");
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
        <link rel="stylesheet" href="..\..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="modifica_dotazione.css">
        <title>Modifica - Dotazione</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="lista_dotazione.js"></script>
    </head>
    <body>
        <div class="container">
            <!-- sidebar -->
            <div class="sidebar">
                <div class="image"><img src="..\..\..\assets\images\logo_darzo.png" width="120px"></div>
                <!-- questa div conterrà i link delle schede -->
                <div class="section-container">
                    <br>
                    <a href="..\..\admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="..\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>
                    <a href="..\..\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>
                    <a href="..\lista_dotazione.php"><div class="section selected"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
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

                <h1>Modifica Dotazione</h1>

                <div class="form-container">
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="codice">Codice</label>
                            <input type="text" name="codice" value="<?php echo $dotazione['codice'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" name="nome" value="<?php echo $dotazione['nome'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="categoria">Categoria</label>
                            <select name="categoria">
                                <?php foreach ($elencoCategorie as $categoria): ?>
                                    <option value="<?php echo $categoria ?>" <?php if ($dotazione['categoria'] == $categoria) echo 'selected' ?>>
                                        <?php echo $categoria ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea name="descrizione"><?php echo $dotazione['descrizione'] ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="prezzo">Prezzo Stimato</label>
                            <input type="text" name="prezzo" value="<?php echo $dotazione['prezzo_stimato'] ?>€">
                        </div>
                        <div class="form-group">
                            <label for="ID_aula">Aula</label>
                            <select name="aula">
                                <?php foreach ($elencoAule as $aula): ?>
                                    <option value="<?php echo $aula ?>" <?php if ($dotazione['ID_aula'] == $aula) echo 'selected' ?>>
                                        <?php echo $aula ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <input class="save" type="submit" name="salva" value="Salva Modifiche">
                            <input class="reset" type="submit" name="reset" value="Reset Modifiche">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>