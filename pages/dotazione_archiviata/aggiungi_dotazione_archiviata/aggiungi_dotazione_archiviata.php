<?php
session_start(); 
// Avvia la sessione per poter accedere alle variabili di sessione (es. username, ruolo)

// Configurazione dati database
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

$username = $_SESSION['username'] ?? null; // Prende lo username dalla sessione o null se non esiste
$role = $_SESSION['role'] ?? null;         // Prende il ruolo dalla sessione o null se non esiste

$errors = []; // Array per memorizzare eventuali errori di validazione

// Verifica se l'utente è loggato (ha username in sessione)
if (!is_null($username)) {
    try {
        // Connessione PDO al database MySQL
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Imposta gestione errori tramite eccezioni
    } catch (PDOException $e) {
        // Se la connessione fallisce, stampa l'errore e termina lo script
        die("Connessione fallita: " . $e->getMessage());
    }

    // Recupera tutti gli ID_categoria dalla tabella categoria per popolare il select
    $stmt = $conn->prepare("SELECT ID_categoria FROM categoria");
    $stmt->execute();
    $elencoCategorie = $stmt->fetchAll(PDO::FETCH_COLUMN); // Prende solo la colonna ID_categoria

    // Se è stato inviato il form per salvare
    if (isset($_POST['salva'])) {
        // Recupera i dati inviati dal form
        $codice = $_POST['codice'];
        $nome = $_POST['nome'];
        $descrizione = $_POST['descrizione'];
        $categoria = $_POST['categoria'];
        $prezzoInput = $_POST['prezzo'];

        // Pulizia del prezzo: rimuove tutto tranne numeri, virgole e punti
        $prezzoPulito = preg_replace('/[^0-9,.]/', '', $prezzoInput);
        // Sostituisce la virgola con il punto per avere un formato numerico valido
        $prezzoPulito = str_replace(',', '.', $prezzoPulito);

        // Controlla se il codice esiste già nel database
        $stmt = $conn->prepare("SELECT COUNT(*) FROM dotazione WHERE codice = :codice");
        $stmt->bindParam(':codice', $codice, PDO::PARAM_STR);
        $stmt->execute();
        $codiceEsiste = $stmt->fetchColumn();

        if ($codiceEsiste > 0) {
            $errors["codice"] = "Il codice inserito è già presente."; // Errore se codice duplicato
        }
        if (empty($nome)) {
            $errors["nome"] = "Il nome è obbligatorio."; // Errore se nome vuoto
        }
        if (!is_numeric($prezzoPulito) || floatval($prezzoPulito) < 0) {
            $errors["prezzo"] = "Il prezzo deve essere un numero positivo"; // Errore prezzo negativo o non numerico
        }
        if (!in_array($categoria, $elencoCategorie)) {
            $errors["categoria"] = "La categoria selezionata non è valida"; // Errore se categoria non valida
        }

        // Se non ci sono errori
        if (empty($errors)) {
            $aula = null; // Aula è null perché la dotazione è archiviata (non assegnata ad aula)
            $stato = "archiviato"; // Stato fisso per dotazione archiviata

            // Query di inserimento nella tabella dotazione
            $stmt = $conn->prepare("INSERT INTO dotazione (codice, nome, descrizione, prezzo_stimato, ID_aula, categoria, stato) VALUES (:codice, :nome, :descrizione, :prezzo_stimato, :aula, :categoria, :stato)");
            $stmt->bindParam(':codice', $codice);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descrizione', $descrizione);
            $stmt->bindParam(':prezzo_stimato', $prezzoPulito);
            $stmt->bindParam(':aula', $aula, PDO::PARAM_NULL); // Valore NULL per ID_aula
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':stato', $stato);
            $stmt->execute(); // Esegue l'inserimento

            // Dopo inserimento, reindirizza alla pagina principale delle dotazioni archiviate
            header("Location: ../../dotazione_archiviata/dotazione_archiviata.php");
            exit;
        }
    } 
    // Se è stato premuto il pulsante reset
    else if (isset($_POST['reset'])) {
        // Ricarica la pagina per resettare il form
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
} else {
    // Se non c'è username in sessione, reindirizza al logout (forse perché sessione scaduta o non autenticato)
    header("Location: ../../../logout/logout.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Link ai CSS -->
        <link rel="stylesheet" href="..\..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="aggiungi_dotazione_archiviata.css">
        <title>Aggiungi Dotazione Archiviata</title>
        <!-- Font Awesome per icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <!-- Script JS (non presente nel codice condiviso) -->
        <script src="lista_dotazione.js"></script>
    </head>
    <body>
        <div class="container">
            <!-- Sidebar di navigazione -->
            <div class="sidebar">
                <div class="image"><img src="..\..\..\assets\images\logo_darzo.png" width="120px"></div>
                <div class="section-container">
                    <br>
                    <?php
                        // Se l'utente è admin, mostra link a pagina admin, altrimenti user page
                        if($role == 'admin') {
                            echo '<a href="../../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        // Se admin, mostra link extra per gestione utenti e admin
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

            <!-- Contenuto principale -->
            <div class="content">
                <!-- Barra logout e pulsante indietro -->
                <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <!-- Pulsante indietro -->
                    <a class="back-btn" href="../dotazione_archiviata.php" style="display:inline-block;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <!-- Pulsante logout -->
                    <a class="logout-btn" href="../../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <h1>Aggiungi Dotazione Archiviata</h1>

                <!-- Form per inserire una nuova dotazione archiviata -->
                <div class="form-container">
                    <form action="" method="post">
                        <!-- Input codice -->
                        <div class="form-group">
                            <label for="codice">Codice</label>
                            <input type="text" name="codice" value="<?php if(isset($_POST['codice'])) echo htmlspecialchars($_POST['codice']); ?>">
                            <?php if (isset($errors['codice'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['codice'] ?></small>
                            <?php endif; ?>
                        </div>
                        <!-- Input nome -->
                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" name="nome" value="<?php if(isset($_POST['nome'])) echo htmlspecialchars($_POST['nome']); ?>">
                            <?php if (isset($errors['nome'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['nome'] ?></small>
                            <?php endif; ?>
                        </div>
                        <!-- Select categoria -->
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
                        <!-- Textarea descrizione -->
                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea name="descrizione"><?php if(isset($_POST['descrizione'])) echo htmlspecialchars($_POST['descrizione']); ?></textarea>
                        </div>
                        <!-- Input prezzo stimato -->
                        <div class="form-group">
                            <label for="prezzo">Prezzo Stimato</label>
                            <div class="input-with-euro">
                                <input type="number" name="prezzo" step="0.1" min="0" value="<?php if(isset($_POST['prezzo'])) echo floatval($_POST['prezzo']); ?>">
                            </div>
                            <?php if (isset($errors['prezzo'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['prezzo'] ?></small>
                            <?php endif; ?>
                        </div>
                        <!-- Pulsanti di submit e reset -->
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
