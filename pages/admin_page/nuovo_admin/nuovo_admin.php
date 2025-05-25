<?php
session_start();  // Avvia la sessione per accedere alle variabili di sessione

// Info per la connessione al database
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

// Prende username e ruolo dalla sessione, se settati
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;

$errors = [];  // Array per memorizzare eventuali errori di validazione

if (!is_null($username)) {  // Se l'utente è loggato
    try {
        // Connessione al database con PDO
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // Setta modalità errori PDO
    } catch (PDOException $e) {
        // Se la connessione fallisce, termina lo script mostrando l'errore
        die("Connessione fallita: " . $e->getMessage());
    }

    // Recupera la scuola di appartenenza dell'admin loggato (necessaria per l'inserimento)
    $scuolaAppartenenza = null;
    $stmt = $conn->prepare("SELECT scuola_appartenenza FROM admin WHERE username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $scuolaAppartenenza = $stmt->fetchColumn();

    if (isset($_POST['salva'])) {  // Se il form è stato inviato col pulsante 'salva'
        // Recupera i dati inviati dal form, con fallback a stringa vuota
        $nome = $_POST['nome'] ?? '';
        $cognome = $_POST['cognome'] ?? '';
        $usernameNew = $_POST['username_new'] ?? '';
        $password = $_POST['password'] ?? '';
        $email = $_POST['email'] ?? '';

        // Controlli di validazione sui campi obbligatori
        if (empty($nome)) {
            $errors["nome"] = "Il nome è obbligatorio.";
        }
        if (empty($cognome)) {
            $errors["cognome"] = "Il cognome è obbligatorio.";
        }
        if (empty($usernameNew)) {
            $errors["username_new"] = "Lo username è obbligatorio.";
        }
        if (empty($password)) {
            $errors["password"] = "La password è obbligatoria.";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Email non valida.";
        }

        // Controlla che lo username non sia già presente nel database
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin WHERE username = :username");
        $stmt->bindParam(':username', $usernameNew, PDO::PARAM_STR);
        $stmt->execute();
        $usernameEsiste = $stmt->fetchColumn();

        if ($usernameEsiste > 0) {
            $errors["username_new"] = "Lo username inserito è già presente.";
        }

        // Se non ci sono errori, inserisce il nuovo admin nel database
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO admin (username, nome, cognome, email, password, scuola_appartenenza) VALUES (:username, :nome, :cognome, :email, :password, :scuola)");
            $stmt->bindParam(':username', $usernameNew);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':cognome', $cognome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password); // NOTA: la password NON è cifrata, va migliorato!
            $stmt->bindParam(':scuola', $scuolaAppartenenza);
            $stmt->execute();

            // Reindirizza alla pagina admin principale dopo l'inserimento
            header("Location: ../admin_page.php");
            exit;
        }
    } else if (isset($_POST['reset'])) {  // Se è stato premuto il pulsante reset
        // Ricarica la pagina per resettare i campi del form
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
} else {  // Se non c'è un utente loggato, reindirizza al logout (fuoriuscita)
    header("Location: ../../../logout/logout.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Link ai file CSS per lo stile della pagina -->
    <link rel="stylesheet" href="..\..\..\assets\css\background.css">
    <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
    <link rel="stylesheet" href="..\..\..\assets\css\shared_admin_subpages.css">
    <link rel="stylesheet" href="nuovo_admin.css">
    <title>Aggiungi Admin</title>
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar con menu di navigazione -->
        <div class="sidebar">
            <div class="image"><img src="..\..\..\assets\images\logo_darzo.png" width="120px"></div>
            <div class="section-container">
                <br>
                <?php
                    // Link HOME diversa a seconda del ruolo
                    if($role == 'admin') {
                        echo '<a href="../admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    } else {
                        echo '<a href="../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    }
                ?>
                <a href="../../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                <?php
                    // Link extra solo per admin
                    if($role == "admin"){
                        echo '<a href="..\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                        echo '<a href="..\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                        echo '<a href="nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
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
            <!-- Barra superiore con pulsanti indietro e logout -->
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <!-- Bottone "indietro" che richiama la pagina precedente -->
                <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <!-- Bottone logout -->
                <a class="logout-btn" href="../../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>

            <h1>Aggiungi Admin</h1>

            <div class="form-container">
                <form action="" method="post">
                    <!-- Campo nome -->
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" name="nome" value="<?php if(isset($_POST['nome'])) echo $_POST['nome']; ?>">
                        <?php if (isset($errors['nome'])): ?>
                            <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['nome'] ?></small>
                        <?php endif; ?>
                    </div>
                    <!-- Campo cognome -->
                    <div class="form-group">
                        <label for="cognome">Cognome</label>
                        <input type="text" name="cognome" value="<?php if(isset($_POST['cognome'])) echo $_POST['cognome']; ?>">
                        <?php if (isset($errors['cognome'])): ?>
                            <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['cognome'] ?></small>
                        <?php endif; ?>
                    </div>
                    <!-- Campo username -->
                    <div class="form-group">
                        <label for="username_new">Username</label>
                        <input type="text" name="username_new" value="<?php if(isset($_POST['username_new'])) echo $_POST['username_new']; ?>">
                        <?php if (isset($errors['username_new'])): ?>
                            <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['username_new'] ?></small>
                        <?php endif; ?>
                    </div>
                    <!-- Campo email -->
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" name="email" value="<?php if(isset($_POST['email'])) echo $_POST['email']; ?>">
                        <?php if (isset($errors['email'])): ?>
                            <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['email'] ?></small>
                        <?php endif; ?>
                    </div>
                    <!-- Campo password -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="text" name="password" value="<?php if(isset($_POST['password'])) echo $_POST['password']; ?>">
                        <?php if (isset($errors['password'])): ?>
                            <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['password'] ?></small>
                        <?php endif; ?>
                    </div>
                    <!-- Pulsanti per inviare il form o resettarlo -->
                    <div class="form-group">
                        <input class="save" type="submit" name="salva" value="Aggiungi Admin">
                        <input class="reset" type="submit" name="reset" value="Reset">
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
