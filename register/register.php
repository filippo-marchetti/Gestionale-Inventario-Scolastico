<?php
//Variabili del PDO
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

//Variabili dei campi form
$username = "";
$password = "";
$nome = "";
$cognome = "";
$email = "";
$confermaPassword = "";

$errors = [];   //array contenente i possibili errori di inserimento

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}

if (isset($_POST["login"]) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    //Acquisisco i dati del form
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $cognome = $_POST['cognome'] ?? '';
    $email = $_POST['email'] ?? '';
    $confermaPassword = $_POST['confermaPassword'] ?? '';

    //Controllo di ogni possibile parametro non inserito
    if (!$username) $errors['username'] = "Inserisci il nome utente.";
    if (!$password) $errors['password'] = "Inserisci la password.";
    if (!$nome) $errors['nome'] = "Inserisci il nome";
    if (!$cognome) $errors['cognome'] = "Inserisci il cognome.";
    if (!$email) $errors['email'] = "Inserisci l'email";
    if (!$confermaPassword) $errors['confermaPassword'] = "Conferma la password";

    if ($username && $password && $nome && $cognome && $email && $confermaPassword) {
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

        // Controllo se un email admin con la stessa email già esiste
        $stmt = $conn->prepare('SELECT * FROM admin WHERE email = ?');
        $stmt->execute([$username]);
        $adminEmailCheck = $stmt->fetch();

        if (!$user && !$emailCheck && !$adminCheck && !$adminEmailCheck) {
            //Controllo che le parti siano uguali
            if ($password === $confermaPassword) {
                // Inserimento nel database
                $stmt = $conn->prepare("INSERT INTO utente (username, nome, cognome, email, password, stato) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $nome, $cognome, $email, $password, 'attesa']);
            }else{ // Se la password e la conferma sono diverse genera un errore
                $errors['confermaPassword'] = "Le password sono diverse ";
            }
        }else{
            $errors['username'] = "username o email non validi";
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
    <!-- css Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="form">
    <div class="image">
        <img src="..\assets\images\placeholder.png" width="120px">
    </div>
    <h1>Registrati</h1>
    <form method="POST">
        <!-- input username con aggiunta dell'icona -->
        <i class="fas fa-user"></i>
        <label for="username">Username</label>
        <!-- se si verifica un errore viene scritto di cosa si tratta e input si colora -->
        <input type="text" name="username" id="username" class="<?= isset($errors['username']) ? 'input-error' : '' ?>">
        <!-- i serve a disegnare il cerchio con il punto esclamativo -->
        <?php if (isset($errors['username'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['username'] ?></small>
        <?php endif; ?>

        <!-- input nome -->
        <i class="fas fa-user"></i>
        <label for="nome">Nome</label>
        <input type="text" name="nome" id="nome" class="<?= isset($errors['nome']) ? 'input-error' : '' ?>">
        <?php if (isset($errors['nome'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['nome'] ?></small>
        <?php endif; ?>

        <!-- input cognome -->
        <i class="fas fa-user"></i>
        <label for="cognome">Cognome</label>
        <input type="text" name="cognome" id="cognome" class="<?= isset($errors['cognome']) ? 'input-error' : '' ?>">
        <?php if (isset($errors['cognome'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['cognome'] ?></small>
        <?php endif; ?>

        <!-- input email -->
        <i class="fas fa-envelope"></i>
        <label for="email">E-mail</label><br>
        <input type="email" name="email" id="email" class="<?= isset($errors['email']) ? 'input-error' : '' ?>"><br><br>
        <?php if (isset($errors['email'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['email'] ?></small>
        <?php endif; ?>

        <!-- input password con aggiunta dell'icona -->
        <i class="fas fa-lock"></i>
        <label for="password">Password</label>
        <input type="password" name="password" id="password" class="<?= isset($errors['password']) ? 'input-error' : '' ?>">
        <?php if (isset($errors['password'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['password'] ?></small>
        <?php endif; ?>

        <!-- input password con aggiunta dell'icona -->
        <i class="fas fa-lock"></i>
        <label for="confermaPassword">Conferma password</label>
        <input type="password" name="confermaPassword" id="confermaPassword" class="<?= isset($errors['confermaPassword']) ? 'input-error' : '' ?>">
        <?php if (isset($errors['confermaPassword'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['confermaPassword'] ?></small>
        <?php endif; ?>

        <!-- input submit -->
        <input type="submit" href="\Inventario\Login\login.php" name="login" value="Crea Account">
    </form>

    <p>Hai già un account? <a href="..\Login\login.php">Login</a></p>
</div>

</body>
</html>
