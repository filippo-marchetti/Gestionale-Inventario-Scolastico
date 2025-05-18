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
$success = '';

if (!is_null($username)) {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    // Determina tabella e chiave primaria in base al ruolo
    if ($role === 'admin') {
        $table = 'admin';
        $pk = 'username';
    } else {
        $table = 'utente';
        $pk = 'username';
    }

    // Recupera dati utente/admin
    $stmt = $conn->prepare("SELECT * FROM $table WHERE $pk = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        header("Location: ../logout/logout.php");
        exit;
    }

    if (isset($_POST['salva'])) {
        $nuovoUsername = $_POST['username'] ?? '';
        $nuovaPassword = $_POST['password'] ?? '';
        $vecchioUsername = $userData['username'];

        if (empty($nuovoUsername)) {
            $errors['username'] = "Lo username non può essere vuoto.";
        } else {
            // Controlla se username già esistente (diverso dal proprio)
            $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE username = :username AND username != :oldusername");
            $stmt->bindParam(':username', $nuovoUsername, PDO::PARAM_STR);
            $stmt->bindParam(':oldusername', $vecchioUsername, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $errors['username'] = "Username già in uso.";
            }
        }

        if (empty($nuovaPassword)) {
            $errors['password'] = "La password non può essere vuota.";
        }

        if (empty($errors)) {
            // Aggiorna username e password
            $stmt = $conn->prepare("UPDATE $table SET username = :newusername, password = :newpassword WHERE $pk = :oldusername");
            $stmt->bindParam(':newusername', $nuovoUsername);
            $stmt->bindParam(':newpassword', $nuovaPassword);
            $stmt->bindParam(':oldusername', $vecchioUsername);
            $stmt->execute();

            // Aggiorna la sessione se lo username è cambiato
            $_SESSION['username'] = $nuovoUsername;
            $success = "Modifiche salvate con successo.";

            // Aggiorna i dati visualizzati
            $stmt = $conn->prepare("SELECT * FROM $table WHERE $pk = :username");
            $stmt->bindParam(':username', $nuovoUsername, PDO::PARAM_STR);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
} else {
    header("Location: ../logout/logout.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="impostazioni.css">
        <title>Impostazioni</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script>
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
            <!-- sidebar -->
            <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\logo_darzo.png" width="120px"></div>
                <div class="section-container">
                    <br>
                    <?php
                        if($role == 'admin') {
                            echo '<a href="../admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="impostazioni.php"><div class="section selected"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>
            <!-- content -->
            <div class="content">
                <<div class="logout" style="display: flex; gap: 18px; align-items: center;">
                    <!-- Bottone "indietro" -->
                    <a class="back-btn" href="javascript:history.back();" title="Torna indietro" style="display:inline-block;">
                        <i class="fas fa-circle-chevron-left" style="font-size: 2.2em; color: #007bff;"></i>
                    </a>
                    <!-- Bottone logout -->
                    <a class="logout-btn" href="../logout/logout.php" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <h1>Impostazioni Account</h1>

                <div class="form-container">
                    <?php if ($success): ?>
                        <div class="success" style="color:green; margin-bottom:10px;">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" name="nome" value="<?= htmlspecialchars($userData['nome']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="cognome">Cognome</label>
                            <input type="text" name="cognome" value="<?= htmlspecialchars($userData['cognome']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="text" name="email" value="<?= htmlspecialchars($userData['email']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : htmlspecialchars($userData['username']) ?>" class="<?= isset($errors['username']) ? 'input-error' : '' ?>">
                            <?php if (isset($errors['username'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['username'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group" style="position:relative;">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" value="<?= isset($_POST['password']) ? htmlspecialchars($_POST['password']) : htmlspecialchars($userData['password']) ?>" class="<?= isset($errors['password']) ? 'input-error' : '' ?>">
                            <span style="position:absolute; right:18px; top:38px; cursor:pointer;" onclick="togglePassword()">
                                <i id="toggleEye" class="fas fa-eye"></i>
                            </span>
                            <?php if (isset($errors['password'])): ?>
                                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['password'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <input class="save" type="submit" name="salva" value="Salva Modifiche">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>