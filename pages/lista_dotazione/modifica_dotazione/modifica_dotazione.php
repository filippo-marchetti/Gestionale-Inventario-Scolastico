<?php
session_start();  // Avvia la sessione per accedere alle variabili di sessione

// Informazioni di connessione al database
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

// Recupera username e ruolo dalla sessione, se settati
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;

// Prende il parametro 'codice' dalla query string (GET)
$codice = $_GET['codice'] ?? null;

$errors = []; // Array per memorizzare errori di validazione

// Controlla se l'utente è loggato e se il ruolo è admin oppure user
if (!is_null($username) && $role === "admin" || $role == "user") {
    try {
        // Connessione al database usando PDO
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Abilita eccezioni per errori SQL
    } catch (PDOException $e) {
        // Se la connessione fallisce, termina lo script mostrando errore
        die("Connessione fallita: " . $e->getMessage());
    }

    // Se è stato passato un codice dotazione
    if ($codice !== null) {
        // Recupera i dati della dotazione dal database tramite codice
        $stmt = $conn->prepare("SELECT * FROM dotazione WHERE codice = :codice");
        $stmt->bindParam(':codice', $codice, PDO::PARAM_STR);
        $stmt->execute();
        $dotazione = $stmt->fetch(PDO::FETCH_ASSOC);

        // Recupera elenco ID categorie per il dropdown del form
        $stmt = $conn->prepare("SELECT ID_categoria FROM categoria");
        $stmt->execute();
        $elencoCategorie = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Recupera elenco ID aule per il dropdown del form
        $stmt = $conn->prepare("SELECT ID_aula FROM aula");
        $stmt->execute();
        $elencoAule = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Se il form è stato inviato con il bottone 'salva'
        if (isset($_POST['salva'])) {
            // Prende i valori inviati dal form
            $nome = $_POST['nome'];
            $descrizione = $_POST['descrizione'];
            $codiceCorrente = $_POST['codice'];
            $categoria = $_POST['categoria'];
            $aula = $_POST['aula'];
            $prezzoInput = $_POST['prezzo'];

            // Pulizia del prezzo: rimuove tutto tranne numeri, virgole e punti, poi converte la virgola in punto
            $prezzoPulito = preg_replace('/[^0-9,.]/', '', $prezzoInput);
            $prezzoPulito = str_replace(',', '.', $prezzoPulito);

            // Validazione: controlla che il nome non esista già in un'altra dotazione (escludendo questa)
            $stmt = $conn->prepare("SELECT COUNT(*) FROM dotazione WHERE nome = :nome AND codice != :codice");
            $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindParam(':codice', $codiceCorrente, PDO::PARAM_STR);
            $stmt->execute();
            $nomeEsiste = $stmt->fetchColumn();

            if ($nomeEsiste > 0) {
                $errors["nome"] = "Il nome inserito è già presente in un'altra dotazione";
            }
            // Controlla che il prezzo sia un numero positivo
            if (!is_numeric($prezzoPulito) || floatval($prezzoPulito) < 0) {
                $errors["prezzo"] = "Il prezzo deve essere un numero positivo";
            }
            // Controlla che la categoria sia valida (presente nell'elenco categorie)
            if (!in_array($categoria, $elencoCategorie)) {
                $errors["categoria"] = "La categoria selezionata non è valida";
            }
            // Controlla che l'aula sia valida oppure nulla
            if (!in_array($aula, $elencoAule) && !isset($aula)) {
                $errors["aula"] = "L'aula selezionata non è valida";
            }

            // Se non ci sono errori, aggiorna il record nel database
            if (empty($errors)) {
                // Se aula è stringa vuota, la trasforma in NULL per il DB
                $aula = (($aula) === "") ? null : $aula;

                // Prepara la query di update
                $stmt = $conn->prepare("UPDATE dotazione SET nome = :nome, descrizione = :descrizione, prezzo_stimato = :prezzo_stimato, ID_aula = :aula, categoria = :categoria, stato = :stato WHERE codice = :codice");
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':descrizione', $descrizione);
                $stmt->bindParam(':prezzo_stimato', $prezzoPulito);
                $stmt->bindParam(':aula', $aula);
                $stmt->bindParam(':categoria', $categoria);
                $stmt->bindParam(':codice', $codiceCorrente);

                // Imposta lo stato in base alla presenza o meno di aula associata
                if($aula) $stato = "presente";
                else $stato = "archiviato";
                $stmt->bindParam(':stato', $stato);

                $stmt->execute();

                // Redirect alla pagina appropriata dopo il salvataggio in base a parametro GET "start"
                if($_GET["start"] == 'attiva') header("Location: ../lista_dotazione.php");
                if($_GET["start"] == 'archivio') header("Location: ../../dotazione_archiviata/dotazione_archiviata.php");
            }
        } else if (isset($_POST['reset'])) {
            // Se si preme reset, ricarica la pagina per annullare modifiche
            header("Location: " . $_SERVER['REQUEST_URI']);
        }
    } else {
        // Se non è stato passato codice, mostra errore e termina
        die("Codice non fornito.");
    }
} else {
    // Se non autenticato o ruolo non valido, effettua logout e reindirizzamento
    header("Location: ../../../logout/logout.php");
    exit;
}
?>

<!-- INIZIO HTML -->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <!-- Fogli di stile -->
        <link rel="stylesheet" href="..\..\..\assets\css\background.css" />
        <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css" />
        <link rel="stylesheet" href="..\..\..\assets\css\shared_admin_subpages.css" />
        <link rel="stylesheet" href="modifica_dotazione.css" />
        <title>Modifica - Dotazione</title>
        <!-- Font Awesome per icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
        <script src="lista_dotazione.js"></script>
    </head>
    <body>
        <div class="container">
            <!-- Sidebar di navigazione -->
            <div class="sidebar">
                <div class="image"><img src="..\..\..\assets\images\placeholder.png" width="120px" /></div>
                <div class="section-container">
                    <br />
                    <?php
                        // Link "Home" differente a seconda del ruolo
                        if($role == 'admin') {
                            echo '<a href="../../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        // Link extra per admin
                        if($role == "admin"){
                            echo '<a href="..\..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                            echo '<a href="..\..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                            echo '<a href="..\..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                        };
                    ?>
                    <a href="..\..\lista_dotazione\lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="..\..\dotazione_archiviata\dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="..\..\dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="../../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                    <a href="..\..\impostazioni\impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>
            </div>
            <!-- Content principale -->
            <div class="content">
                <!-- Barra logout + bottone indietro -->
                <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <!-- Bottone "indietro" -->
                    <a class="back-btn" href="../lista_dotazione.php" style="display:inline-block;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <!-- Bottone logout -->
                    <a class="logout-btn" href="../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <h1>Modifica Dotazione</h1>

                <div class="form-container">
                    <form action="" method="post">
                        <!-- Codice, campo readonly -->
                        <div class="form-group">
                            <label for="codice">Codice</label>
                            <input type="text" name="codice" readonly value="<?php echo $dotazione['codice']?>">
                        </div>
                        <!-- Nome dotazione -->
                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" name="nome" value="<?php echo $dotazione['nome'] ?>">
                            <!-- Messaggio errore nome -->
                            <?php if (isset($errors['nome'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['nome'] ?></small>
                            <?php endif; ?>
                        </div>
                        <!-- Categoria -->
                        <div class="form-group">
                            <label for="categoria">Categoria</label>
                            <select name="categoria">
                                <?php foreach ($elencoCategorie as $categoria): ?>
                                    <option value="<?php echo $categoria ?>" <?php if ($dotazione['categoria'] == $categoria) echo 'selected' ?>>
                                        <?php echo $categoria ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Messaggio errore categoria -->
                            <?php if (isset($errors['categoria'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['categoria'] ?></small>
                            <?php endif; ?>
                        </div>
                        <!-- Descrizione -->
                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea name="descrizione"><?php echo $dotazione['descrizione'] ?></textarea>
                        </div>
                        <!-- Prezzo stimato -->
                        <div class="form-group">
                            <label for="prezzo">Prezzo Stimato</label>
                            <div class="input-with-euro">
                                <input type="number" name="prezzo" step="0.1" min="0" value="<?php echo floatval($dotazione['prezzo_stimato']) ?>">
                            </div>
                            <!-- Messaggio errore prezzo -->
                            <?php if (isset($errors['prezzo'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['prezzo'] ?></small>
                            <?php endif; ?>
                        </div>
                        <!-- Aula -->
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
                            <!-- Messaggio errore aula -->
                            <?php if (isset($errors['aula'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['aula'] ?></small>
                            <?php endif; ?>
                        </div>
                        <!-- Pulsanti salva/reset -->
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
