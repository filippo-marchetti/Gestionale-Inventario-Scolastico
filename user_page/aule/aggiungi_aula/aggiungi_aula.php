<?php
session_start();

// Info database
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;

$errors = [];

if (!is_null($username)) {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    // Recupera tipologie per il select
    $stmt = $conn->prepare("SELECT DISTINCT tipologia FROM aula");
    $stmt->execute();
    $elencoTipologie = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (isset($_POST['salva'])) {
        // Recupera dati dal form
        $id_aula = $_POST['codice'] ?? '';
        $tipologia = $_POST['nome'] ?? '';
        $descrizione = $_POST['descrizione'] ?? '';

        // Controlli
        $stmt = $conn->prepare("SELECT COUNT(*) FROM aula WHERE ID_aula = :id_aula");
        $stmt->bindParam(':id_aula', $id_aula, PDO::PARAM_STR);
        $stmt->execute();
        $idEsiste = $stmt->fetchColumn();

        if ($idEsiste > 0) {
            $errors["codice"] = "L'ID aula inserito è già presente.";
        }
        if (empty($tipologia)) {
            $errors["nome"] = "La tipologia è obbligatoria.";
        }

        // Inserimento nel database
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO aula (ID_aula, tipologia, descrizione) VALUES (:id_aula, :tipologia, :descrizione)");
            $stmt->bindParam(':id_aula', $id_aula);
            $stmt->bindParam(':tipologia', $tipologia);
            $stmt->bindParam(':descrizione', $descrizione);
            $stmt->execute();

            header("Location: ../aule.php");
            exit;
        }
    } else if (isset($_POST['reset'])) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
} else {
    header("Location: ../../logout/logout.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="..\..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="aggiungi_aula.css">
        <title>Aggiungi Aula</title>
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
                    <?php
                        if($role == 'admin') {
                            echo '<a href="../../admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../../../user_page/aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        if($role == "admin"){
                            echo '<a href="..\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                            echo '<a href="..\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                        };
                    ?>
                    <a href="..\..\lista_dotazione\lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="..\dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="..\..\dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
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

                <h1>Aggiungi Aula</h1>

                <div class="form-container">
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="codice">Codice</label>
                            <input type="text" name="codice" value="<?php if(isset($_POST['codice'])) echo htmlspecialchars($_POST['codice']); ?>">
                            <?php if (isset($errors['codice'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['codice'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="nome">Tipologia</label>
                            <input type="text" name="nome" value="<?php if(isset($_POST['nome'])) echo htmlspecialchars($_POST['nome']); ?>">
                            <?php if (isset($errors['nome'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['nome'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea name="descrizione"><?php if(isset($_POST['descrizione'])) echo htmlspecialchars($_POST['descrizione']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <input class="save" type="submit" name="salva" value="Aggiungi Aula">
                            <input class="reset" type="submit" name="reset" value="Reset">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>