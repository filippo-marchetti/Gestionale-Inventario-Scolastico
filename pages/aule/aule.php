<?php
session_start(); // Avvia la sessione per accedere alle variabili di sessione

// Info per la connessione al database
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

// Recupera dati utente dalla sessione (username e ruolo)
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;

$errors = []; // Array per memorizzare eventuali errori di validazione

if (!is_null($username)) { // Se l'utente è loggato
    try {
        // Connessione PDO al database
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        // Imposta modalità errori su eccezioni
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        // In caso di errore di connessione termina lo script mostrando l'errore
        die("Connessione fallita: " . $e->getMessage());
    }

    // Recupera la lista delle tipologie diverse esistenti nella tabella 'aula' per il select (non usato nel form qui però)
    $stmt = $conn->prepare("SELECT DISTINCT tipologia FROM aula");
    $stmt->execute();
    $elencoTipologie = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (isset($_POST['salva'])) { // Se è stato premuto il pulsante "salva"
        // Prendi i dati inviati dal form
        $id_aula = $_POST['codice'] ?? '';
        $tipologia = $_POST['nome'] ?? '';
        $descrizione = $_POST['descrizione'] ?? '';

        // Controlla se l'ID aula esiste già nel database
        $stmt = $conn->prepare("SELECT COUNT(*) FROM aula WHERE ID_aula = :id_aula");
        $stmt->bindParam(':id_aula', $id_aula, PDO::PARAM_STR);
        $stmt->execute();
        $idEsiste = $stmt->fetchColumn();

        // Controlla se quell'ID aula esiste ma è nello stato 'eliminata'
        $stmt = $conn->prepare("SELECT COUNT(*) FROM aula WHERE ID_aula = :id_aula AND stato = 'eliminata'");
        $stmt->bindParam(':id_aula', $id_aula, PDO::PARAM_STR);
        $stmt->execute();
        $statoEliminato = $stmt->fetchColumn();

        if ($idEsiste > 0 && $statoEliminato > 0) {
            // Se l'aula esiste ed è stata eliminata, la "riattiva" aggiornando tipologia e descrizione
            $stmt = $conn->prepare("UPDATE aula SET stato = 'attiva', tipologia = :tipologia, descrizione = :descrizione WHERE ID_aula = :id_aula AND stato = 'eliminata'");
            $stmt->bindParam(':id_aula', $id_aula);
            $stmt->bindParam(':tipologia', $tipologia);
            $stmt->bindParam(':descrizione', $descrizione);
            $stmt->execute();

            // Dopo l'aggiornamento reindirizza a aule.php e termina l'esecuzione
            header("Location: ../aule.php");
            exit;
        } elseif ($idEsiste > 0 && $statoEliminato == 0) {
            // Se l'ID aula esiste ed è attiva, aggiungi errore: ID già presente
            $errors["codice"] = "L'ID aula inserito è già presente.";
        }
        // Validazione: se tipologia è vuota, aggiungi errore
        if (empty($tipologia)) {
            $errors["nome"] = "La tipologia è obbligatoria.";
        }

        // Se non ci sono errori e non è stato fatto un update di riattivazione, inserisci una nuova aula
        if (empty($errors) && !($idEsiste > 0 && $statoEliminato > 0)) {
            $stmt = $conn->prepare("INSERT INTO aula (ID_aula, tipologia, descrizione) VALUES (:id_aula, :tipologia, :descrizione)");
            $stmt->bindParam(':id_aula', $id_aula);
            $stmt->bindParam(':tipologia', $tipologia);
            $stmt->bindParam(':descrizione', $descrizione);
            $stmt->execute();

            // Dopo l'inserimento reindirizza a aule.php
            header("Location: ../aule.php");
            exit;
        }
    } else if (isset($_POST['reset'])) { 
        // Se premuto reset, ricarica la pagina cancellando i dati inseriti
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
} else {
    // Se l'utente non è loggato, reindirizza alla pagina di logout (quindi a login)
    header("Location: ../../../logout/logout.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Link ai CSS per sfondo e stile condiviso -->
    <link rel="stylesheet" href="..\..\..\assets\css\background.css">
    <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
    <link rel="stylesheet" href="..\..\..\assets\css\shared_admin_subpages.css">
    <!-- CSS specifico per questa pagina -->
    <link rel="stylesheet" href="aggiungi_aula.css">
    <title>Aggiungi Aula</title>
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="lista_dotazione.js"></script> <!-- Non usato nel codice, forse da rimuovere -->
</head>
<body>
    <div class="container">
        <!-- Sidebar con menu di navigazione -->
        <div class="sidebar">
            <div class="image"><img src="..\..\..\assets\images\placeholder.png" width="120px"></div>
            <div class="section-container">
                <br>
                <?php
                // Link HOME differenziato per ruolo admin o utente normale
                if($role == 'admin') {
                    echo '<a href="../../../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                } else {
                    echo '<a href="../../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                }
                ?>
                <!-- Altri link del menu -->
                <a href="../../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                <?php
                if($role == "admin"){
                    echo '<a href="..\..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                    echo '<a href="..\..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                    echo '<a href="..\..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                };
                ?>
                <a href="..\../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                <a href="..\../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                <a href="..\../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                <a href="../../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                <a href="..\../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
            </div>  
        </div>

        <!-- Area principale contenente form e header -->
        <div class="content">
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <!-- Bottone per tornare indietro -->
                <a class="back-btn" href="../aule.php;" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <!-- Bottone logout -->
                <a class="logout-btn" href="../../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>

            <h1>Aggiungi Aula</h1>

            <div class="form-container">
                <form action="" method="post">
                    <div class="form-group">
                        <label for="codice">Nome</label>
                        <!-- Campo testo per codice aula con valore precedente se presente -->
                        <input type="text" name="codice" value="<?php if(isset($_POST['codice'])) echo htmlspecialchars($_POST['codice']); ?>">
                        <!-- Messaggio errore per codice -->
                        <?php if (isset($errors['codice'])): ?>
                            <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['codice'] ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="nome">Tipologia</label>
                        <!-- Campo testo per tipologia con valore precedente se presente -->
                        <input type="text" name="nome" value="<?php if(isset($_POST['nome'])) echo htmlspecialchars($_POST['nome']); ?>">
                        <!-- Messaggio errore per tipologia -->
                        <?php if (isset($errors['nome'])): ?>
                            <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['nome'] ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="descrizione">Descrizione</label>
                        <!-- Textarea per descrizione con valore precedente -->
                        <textarea name="descrizione"><?php if(isset($_POST['descrizione'])) echo htmlspecialchars($_POST['descrizione']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <!-- Pulsanti salva e reset -->
                        <input class="save" type="submit" name="salva" value="Aggiungi Aula">
                        <input class="reset" type="submit" name="reset" value="Reset">
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
