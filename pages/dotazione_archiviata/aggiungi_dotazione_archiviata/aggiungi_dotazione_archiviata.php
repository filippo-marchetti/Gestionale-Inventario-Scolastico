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

    // Recupera categorie per il select
    $stmt = $conn->prepare("SELECT ID_categoria FROM categoria");
    $stmt->execute();
    $elencoCategorie = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (isset($_POST['salva'])) {
        // Recupera dati dal form
        $codice = $_POST['codice'];
        $nome = $_POST['nome'];
        $descrizione = $_POST['descrizione'];
        $categoria = $_POST['categoria'];
        $prezzoInput = $_POST['prezzo'];
        $prezzoPulito = preg_replace('/[^0-9,.]/', '', $prezzoInput);
        $prezzoPulito = str_replace(',', '.', $prezzoPulito);

        // Controlli
        $stmt = $conn->prepare("SELECT COUNT(*) FROM dotazione WHERE codice = :codice");
        $stmt->bindParam(':codice', $codice, PDO::PARAM_STR);
        $stmt->execute();
        $codiceEsiste = $stmt->fetchColumn();

        if ($codiceEsiste > 0) {
            $errors["codice"] = "Il codice inserito è già presente.";
        }
        if (empty($nome)) {
            $errors["nome"] = "Il nome è obbligatorio.";
        }
        if (!is_numeric($prezzoPulito) || floatval($prezzoPulito) < 0) {
            $errors["prezzo"] = "Il prezzo deve essere un numero positivo";
        }
        if (!in_array($categoria, $elencoCategorie)) {
            $errors["categoria"] = "La categoria selezionata non è valida";
        }

        // Inserimento nel database
        if (empty($errors)) {
            $aula = null; // Aula sempre null per dotazione archiviata
            $stato = "archiviato";
            $stmt = $conn->prepare("INSERT INTO dotazione (codice, nome, descrizione, prezzo_stimato, ID_aula, categoria, stato) VALUES (:codice, :nome, :descrizione, :prezzo_stimato, :aula, :categoria, :stato)");
            $stmt->bindParam(':codice', $codice);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descrizione', $descrizione);
            $stmt->bindParam(':prezzo_stimato', $prezzoPulito);
            $stmt->bindParam(':aula', $aula, PDO::PARAM_NULL);
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':stato', $stato);
            $stmt->execute();

            header("Location: ../../dotazione_archiviata/dotazione_archiviata.php");
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
        <link rel="stylesheet" href="aggiungi_dotazione_archiviata.css">
        <title>Aggiungi Dotazione Archiviata</title>
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
                            echo '<a href="../../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        if($role == "admin"){
                            echo '<a href="..\..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                            echo '<a href="..\..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                            echo '<a href="..\..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                        };
                    ?>
                    <a href="../../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="../../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="../../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="../../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                    <a href="../../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>
            <!-- content contiene tutto ciò che è al di fuori della sidebar -->
            <div class="content">
                <!-- user-logout contiene il nome utente dell'utente loggato e il collegamento per il logout -->
                <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <!-- Bottone "indietro" -->
                    <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <!-- Bottone logout -->
                    <a class="logout-btn" href="../../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <h1>Aggiungi Dotazione Archiviata</h1>

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
                            <label for="nome">Nome</label>
                            <input type="text" name="nome" value="<?php if(isset($_POST['nome'])) echo htmlspecialchars($_POST['nome']); ?>">
                            <?php if (isset($errors['nome'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['nome'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="categoria">Categoria</label>
                            <select name="categoria">
                                <?php foreach ($elencoCategorie as $categoria): ?>
                                    <option value="<?php echo $categoria ?>" <?php if (isset($_POST['categoria']) && $_POST['categoria'] == $categoria) echo 'selected' ?>>
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
                            <textarea name="descrizione"><?php if(isset($_POST['descrizione'])) echo htmlspecialchars($_POST['descrizione']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="prezzo">Prezzo Stimato</label>
                            <div class="input-with-euro">
                                <input type="number" name="prezzo" step="0.1" min="0" value="<?php if(isset($_POST['prezzo'])) echo floatval($_POST['prezzo']); ?>">
                            </div>
                            <?php if (isset($errors['prezzo'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['prezzo'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <input class="save" type="submit" name="salva" value="Aggiungi Dotazione">
                            <input class="reset" type="submit" name="reset" value="Reset">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>