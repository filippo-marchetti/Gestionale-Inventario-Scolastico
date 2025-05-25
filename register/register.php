<?php
// Variabili del PDO
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

// Variabili dei campi form
$username = "";
$password = "";
$nome = "";
$cognome = "";
$email = "";
$confermaPassword = "";
$scuola_appartenenza = "";

$errors = [];   // Array contenente i possibili errori di inserimento

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}

// Recupero delle scuole di appartenenza distinte
$scuole;
try {
    $stmt = $conn->query("SELECT DISTINCT s.nome FROM utente as  u INNER JOIN scuola as s ON u.scuola_appartenenza = s.codice_meccanografico WHERE scuola_appartenenza IS NOT NULL AND scuola_appartenenza <>''");
    $scuole = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // In caso di errore, l'array resta vuoto
}

if (isset($_POST["login"]) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Acquisisco i dati del form
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $cognome = $_POST['cognome'] ?? '';
    $email = $_POST['email'] ?? '';
    $confermaPassword = $_POST['confermaPassword'] ?? '';
    $scuola_appartenenza = $_POST['scuola_appartenenza'] ?? '';

    // Controlli
    if (!$username) $errors['username'] = "Inserisci il nome utente.";
    if (!$password) $errors['password'] = "Inserisci la password.";
    if (!$nome) $errors['nome'] = "Inserisci il nome";
    if (!$cognome) $errors['cognome'] = "Inserisci il cognome.";
    if (!$email) $errors['email'] = "Inserisci l'email";
    if (!$confermaPassword) $errors['confermaPassword'] = "Conferma la password";
    if (!$scuola_appartenenza) $errors['scuola_appartenenza'] = "Seleziona la scuola di appartenenza.";

    if (empty($errors)) {
        // Controllo se username già esiste
        $stmt = $conn->prepare('SELECT * FROM utente WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Controllo se email già esiste
        $stmt = $conn->prepare('SELECT * FROM utente WHERE email = ?');
        $stmt->execute([$email]);
        $emailCheck = $stmt->fetch();

        // Controllo se un account admin con lo stesso username già esiste
        $stmt = $conn->prepare('SELECT * FROM admin WHERE username = ?');
        $stmt->execute([$username]);
        $adminCheck = $stmt->fetch();

        // Controllo se un'email admin con la stessa email già esiste
        $stmt = $conn->prepare('SELECT * FROM admin WHERE email = ?');
        $stmt->execute([$email]);
        $adminEmailCheck = $stmt->fetch();

        if (!$user && !$emailCheck && !$adminCheck && !$adminEmailCheck) {
            if ($password === $confermaPassword) {
                //reperisco il codice meccanografico da inserire nel database dal nome
                $stmt = $conn->prepare('SELECT DISTINCT codice_meccanografico FROM scuola WHERE nome LIKE ?');
                $stmt->execute([$scuola_appartenenza]);
                $cod_scuola = $stmt->fetch();

                // Inserimento nel database
                $stmt = $conn->prepare("INSERT INTO utente (username, nome, cognome, email, password, stato, scuola_appartenenza) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $nome, $cognome, $email, $password, 'attesa', $cod_scuola['codice_meccanografico']]);
            } else {
                $errors['confermaPassword'] = "Le password sono diverse";
            }
        } else {
            $errors['username'] = "Username o email già in uso";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Register - D'Arzo</title>
    <link rel="stylesheet" href="..\assets\css\shared_style_login_register.css">
    <link rel="stylesheet" href="..\assets\css\background.css">
    <link rel="stylesheet" href="style_register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="form">
        <div class="image">
            <img src="..\assets\images\placeholder.png" width="120px">
        </div>
        <h1>Registrati</h1>
        <form method="POST">
            <!-- Username -->
            <i class="fas fa-user"></i>
            <label for="username">Username</label>
            <input type="text" name="username" id="username" class="<?= isset($errors['username']) ? 'input-error' : '' ?>" value="<?= htmlspecialchars($username) ?>">
            <?php if (isset($errors['username'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['username'] ?></small>
            <?php endif; ?>

            <!-- Nome -->
            <i class="fas fa-user"></i>
            <label for="nome">Nome</label>
            <input type="text" name="nome" id="nome" class="<?= isset($errors['nome']) ? 'input-error' : '' ?>" value="<?= htmlspecialchars($nome) ?>">
            <?php if (isset($errors['nome'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['nome'] ?></small>
            <?php endif; ?>

            <!-- Cognome -->
            <i class="fas fa-user"></i>
            <label for="cognome">Cognome</label>
            <input type="text" name="cognome" id="cognome" class="<?= isset($errors['cognome']) ? 'input-error' : '' ?>" value="<?= htmlspecialchars($cognome) ?>">
            <?php if (isset($errors['cognome'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['cognome'] ?></small>
            <?php endif; ?>

            <!-- Email -->
            <i class="fas fa-envelope"></i>
            <label for="email">E-mail</label><br>
            <input type="email" name="email" id="email" class="<?= isset($errors['email']) ? 'input-error' : '' ?>" value="<?= htmlspecialchars($email) ?>">
            <?php if (isset($errors['email'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['email'] ?></small>
            <?php endif; ?>

            <!-- Scuola di appartenenza -->
            <i class="fas fa-school"></i>
            <label for="scuola_appartenenza">Scuola di appartenenza</label>
            <select name="scuola_appartenenza" id="scuola_appartenenza" class="<?= isset($errors['scuola_appartenenza']) ? 'input-error' : '' ?>">
                <option value="">-- Seleziona la scuola --</option>
                <?php foreach ($scuole as $scuola): ?>
                    <option value="<?= htmlspecialchars($scuola) ?>" <?= $scuola_appartenenza === $scuola ? 'selected' : '' ?>>
                        <?= htmlspecialchars($scuola) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['scuola_appartenenza'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['scuola_appartenenza'] ?></small>
            <?php endif; ?>
            
            <br><br> <!--spazi tra passworde gli altri campi -->

            <!-- Password -->
            <i class="fas fa-lock"></i>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="<?= isset($errors['password']) ? 'input-error' : '' ?>">
            <?php if (isset($errors['password'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['password'] ?></small>
            <?php endif; ?>

            <!-- Conferma Password -->
            <i class="fas fa-lock"></i>
            <label for="confermaPassword">Conferma password</label>
            <input type="password" name="confermaPassword" id="confermaPassword" class="<?= isset($errors['confermaPassword']) ? 'input-error' : '' ?>">
            <?php if (isset($errors['confermaPassword'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['confermaPassword'] ?></small>
            <?php endif; ?>

            <!-- Submit -->
            <input type="submit" href="\Inventario\Login\login.php" name="login" value="Crea Account">
        </form>

        <p>Hai già un account? <a href="..\Login\login.php">Login</a></p>
    </div>
</body>
</html>
