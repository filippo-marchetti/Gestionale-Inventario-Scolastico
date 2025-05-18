<?php
session_start();

// Info database
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;

$codice = $_GET['codice'] ?? null;

$errors = [];

if (!is_null($username) && $role === "admin" || $role == "user") {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    if ($codice !== null) {
        $stmt = $conn->prepare("SELECT * FROM dotazione WHERE codice = :codice");
        $stmt->bindParam(':codice', $codice, PDO::PARAM_STR);
        $stmt->execute();
        $dotazione = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT ID_categoria FROM categoria");
        $stmt->execute();
        $elencoCategorie = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $conn->prepare("SELECT ID_aula FROM aula");
        $stmt->execute();
        $elencoAule = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (isset($_POST['salva'])) {
            // Recupera dati dal form
            $nome = $_POST['nome'];
            $descrizione = $_POST['descrizione'];
            $codiceCorrente = $_POST['codice'];
            $categoria = $_POST['categoria'];
            $aula = $_POST['aula'];
            $prezzoInput = $_POST['prezzo'];
            $prezzoPulito = preg_replace('/[^0-9,.]/', '', $prezzoInput);
            $prezzoPulito = str_replace(',', '.', $prezzoPulito);

            // Controlli
            $stmt = $conn->prepare("SELECT COUNT(*) FROM dotazione WHERE nome = :nome AND codice != :codice");
            $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindParam(':codice', $codiceCorrente, PDO::PARAM_STR);
            $stmt->execute();
            $nomeEsiste = $stmt->fetchColumn();

            if ($nomeEsiste > 0) {
                $errors["nome"] = "Il nome inserito è già presente in un'altra dotazione";
            }
            if (!is_numeric($prezzoPulito) || floatval($prezzoPulito) < 0) {
                $errors["prezzo"] = "Il prezzo deve essere un numero positivo";
            }
            if (!in_array($categoria, $elencoCategorie)) {
                $errors["categoria"] = "La categoria selezionata non è valida";
            }
            if (!in_array($aula, $elencoAule) && !isset($aula)) {
                $errors["aula"] = "L'aula selezionata non è valida";
            }

            // Aggiunamento dei parametri
            if (empty($errors)) {
                $aula = (($aula) === "") ? null : $aula;

                $stmt = $conn->prepare("UPDATE dotazione SET nome = :nome, descrizione = :descrizione, prezzo_stimato = :prezzo_stimato, ID_aula = :aula, categoria = :categoria, stato = :stato WHERE codice = :codice");
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':descrizione', $descrizione);
                $stmt->bindParam(':prezzo_stimato', $prezzoPulito);
                $stmt->bindParam(':aula', $aula);
                $stmt->bindParam(':categoria', $categoria);
                $stmt->bindParam(':codice', $codiceCorrente);
                if($aula) $stato = "presente";
                else $stato = "archiviato";
                $stmt->bindParam(':stato', $stato);
                $stmt->execute();

                if($_GET["start"] == 'attiva') header("Location: ../lista_dotazione.php");
                if($_GET["start"] == 'archivio') header("Location: ../../dotazione_archiviata/dotazione_archiviata.php");
            }
        }else if (isset($_POST['reset'])) {
            header("Location: " . $_SERVER['REQUEST_URI']);
        }
    } else {
        die("Codice non fornito.");
    }
} else {
    header("Location: ../../logout/logout.php");
    exit;
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
                    <a href="..\..\dotazione_archiviata\dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
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

                <h1>Modifica Dotazione</h1>

                <div class="form-container">
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="codice">Codice</label>
                            <input type="text" name="codice" readonly value="<?php echo $dotazione['codice']?>">
                        </div>
                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" name="nome" value="<?php echo $dotazione['nome'] ?>">
                            <?php if (isset($errors['nome'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['nome'] ?></small>
                            <?php endif; ?>
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
                            <?php if (isset($errors['categoria'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['categoria'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea name="descrizione"><?php echo $dotazione['descrizione'] ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="prezzo">Prezzo Stimato</label>
                            <div class="input-with-euro">
                                <input type="number" name="prezzo" step="0.1" min="0" value="<?php echo floatval($dotazione['prezzo_stimato']) ?>">
                            </div>
                            <?php if (isset($errors['prezzo'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['prezzo'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="ID_aula">Aula</label>
                            <select name="aula">
                                <option value="">-- Nessuna aula --</option>
                                <?php foreach ($elencoAule as $aula): ?>
                                    <option value="<?php echo $aula ?>" <?php if ($dotazione['ID_aula'] == $aula) echo 'selected' ?>>
                                        <?php echo $aula ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['aula'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['aula'] ?></small>
                            <?php endif; ?>
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