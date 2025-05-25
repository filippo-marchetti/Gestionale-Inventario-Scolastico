<?php
    session_start();

    // Informazioni per la connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupera username e ruolo dalla sessione (se settati)
    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? null;

    // Inizializza array errori e messaggio di successo
    $errors = [];
    $success = '';

    // Se l'utente è loggato (username presente)
    if (!is_null($username)) {
        try {
            // Connessione al database con PDO
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // In caso di errore nella connessione, termina lo script con messaggio
            die("Connessione fallita: " . $e->getMessage());
        }

        // Determina la tabella da usare in base al ruolo dell'utente
        if ($role === 'admin') {
            $table = 'admin';
            $pk = 'username';
        } else {
            $table = 'utente';
            $pk = 'username';
        }

        // Recupera i dati dell'utente/admin dal database
        $stmt = $conn->prepare("SELECT * FROM $table WHERE $pk = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se l'utente non esiste più nel DB, forza il logout
        if (!$userData) {
            header("Location: ../logout/logout.php");
            exit;
        }

        // Se il form è stato inviato per salvare le modifiche
        if (isset($_POST['salva'])) {
            // Recupera i dati inviati dal form
            $nuovoUsername = $_POST['username'] ?? '';
            $nuovaPassword = $_POST['password'] ?? '';
            $confermaPassword = $_POST['conferma_password'] ?? '';
            $vecchioUsername = $userData['username'];

            // Validazione username: non vuoto e non già usato da altri utenti
            if (empty($nuovoUsername)) {
                $errors['username'] = "Lo username non può essere vuoto.";
            } else {
                // Controlla se esiste un altro utente con lo stesso username
                $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE username = :username AND username != :oldusername");
                $stmt->bindParam(':username', $nuovoUsername, PDO::PARAM_STR);
                $stmt->bindParam(':oldusername', $vecchioUsername, PDO::PARAM_STR);
                $stmt->execute();
                if ($stmt->fetchColumn() > 0) {
                    $errors['username'] = "Username già in uso.";
                }
            }

            // Validazione password: non vuota e corrispondenza con la conferma
            if (empty($nuovaPassword)) {
                $errors['password'] = "La password non può essere vuota.";
            }
            if (empty($confermaPassword)) {
                $errors['conferma_password'] = "Conferma la password.";
            } elseif ($nuovaPassword !== $confermaPassword) {
                $errors['conferma_password'] = "Le password non coincidono.";
            }

            // Se non ci sono errori, aggiorna i dati nel database
            if (empty($errors)) {
                $stmt = $conn->prepare("UPDATE $table SET username = :newusername, password = :newpassword WHERE $pk = :oldusername");
                $stmt->bindParam(':newusername', $nuovoUsername);
                $stmt->bindParam(':newpassword', $nuovaPassword);
                $stmt->bindParam(':oldusername', $vecchioUsername);
                $stmt->execute();

                // Aggiorna la sessione con il nuovo username se modificato
                $_SESSION['username'] = $nuovoUsername;
                $success = "Modifiche salvate con successo.";

                // Ricarica i dati aggiornati da mostrare nel form
                $stmt = $conn->prepare("SELECT * FROM $table WHERE $pk = :username");
                $stmt->bindParam(':username', $nuovoUsername, PDO::PARAM_STR);
                $stmt->execute();
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    } else {
        // Se l'utente non è loggato, reindirizza al logout (per sicurezza)
        header("Location: ../../logout/logout.php");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- CSS di base e stili condivisi -->
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="impostazioni.css">
        <title>Impostazioni</title>
        <!-- Font Awesome per icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script>
        // Funzione per mostrare/nascondere la password cliccando sull'icona
        function togglePassword() {
            var pwd = document.getElementById('password');
            var eye = document.getElementById('toggleEye');
            if (pwd.type === "password") {
                pwd.type = "text";
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                pwd.type = "password";
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }
        </script>
    </head>
    <body>
        <div class="container">
            <!-- Sidebar con menu di navigazione -->
            <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\placeholder.png" width="120px"></div>
                <div class="section-container">
                    <br>
                <?php
                    // Link a homepage diversa per admin o utente
                    if($role == 'admin') {
                        echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    } else {
                        echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    }
                ?>
                <!-- Link standard per tutti -->
                <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                <?php
                    // Link aggiuntivi riservati ad admin
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

            <!-- Contenuto principale -->
            <div class="content">
                <!-- Barra superiore con pulsante indietro e logout -->
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

                <h1>Impostazioni Account</h1>

                <div class="form-container">
                    <!-- Form di modifica dati account -->
                    <form action="" method="post">
                        <label for="username">Username:</label><br>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($userData['username']) ?>"><br>
                        <!-- Mostra errore username se presente -->
                        <?php if (isset($errors['username'])): ?>
                            <div class="error"><?= htmlspecialchars($errors['username']) ?></div>
                        <?php endif; ?>

                        <label for="password">Password:</label><br>
                        <div class="password-container" style="position:relative;">
                            <input type="password" id="password" name="password" value=""><!-- Non mostrare la password attuale per sicurezza -->
                            <i class="fas fa-eye" id="toggleEye" onclick="togglePassword()" style="position:absolute; right:10px; top:50%; transform: translateY(-50%); cursor:pointer;"></i>
                        </div>
                        <!-- Mostra errore password se presente -->
                        <?php if (isset($errors['password'])): ?>
                            <div class="error"><?= htmlspecialchars($errors['password']) ?></div>
                        <?php endif; ?>

                        <label for="conferma_password">Conferma Password:</label><br>
                        <input type="password" id="conferma_password" name="conferma_password" value=""><br>
                        <!-- Mostra errore conferma password se presente -->
                        <?php if (isset($errors['conferma_password'])): ?>
                            <div class="error"><?= htmlspecialchars($errors['conferma_password']) ?></div>
                        <?php endif; ?>

                        <button type="submit" name="salva">Salva</button>
                    </form>

                    <!-- Messaggio di successo -->
                    <?php if ($success): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
</html>
